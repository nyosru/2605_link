import os
import sys
import time
import json
import requests
import random
import vk_api
from opencode_ai import Opencode
from dotenv import load_dotenv
from event_store import append_event

load_dotenv()

GROUP_TOKEN = os.getenv('GROUP_TOKEN')
GROUP_ID = os.getenv('GROUP_ID')
CHANNEL = os.getenv('CHANNEL', 'my_channel')

print(f"[BOT] GROUP_TOKEN={'set' if GROUP_TOKEN else 'NOT SET'}", flush=True)
print(f"[BOT] GROUP_ID={'set' if GROUP_ID else 'NOT SET'}", flush=True)
print(f"[BOT] CHANNEL={'set' if CHANNEL else 'NOT SET'}", flush=True)

if not all([GROUP_TOKEN, GROUP_ID, CHANNEL]):
    raise ValueError("GROUP_TOKEN, GROUP_ID and CHANNEL must be set in environment variables")

vk_session = vk_api.VkApi(token=GROUP_TOKEN)
vk = vk_session.get_api()
opencode_client = Opencode()

HUB_URL = os.getenv('HUB_URL', 'https://api.php-cat.com/api/vk/incoming')

def process_message(msg):
    try:
        if isinstance(msg, dict):
            if 'object' in msg and isinstance(msg['object'], dict):
                obj = msg['object']
                if 'message' in obj and isinstance(obj['message'], dict):
                    m = obj['message']
                else:
                    m = obj
            elif 'message' in msg and isinstance(msg['message'], dict):
                m = msg['message']
            else:
                m = msg

            peer_id = m.get('peer_id')
            from_id = m.get('from_id')
            text = m.get('text', '')

            if not from_id or not peer_id or not text:
                append_event('warning', {
                    'title': 'Пропущено сообщение без обязательных полей',
                    'msg': msg,
                }, source='bot')
                return
        else:
            append_event('warning', {
                'title': 'Пропущено сообщение неверного формата',
                'msg': msg,
            }, source='bot')
            return

        try:
            user_info = vk.users.get(user_ids=from_id, fields='first_name,last_name')
            author_name = f"{user_info[0]['first_name']} {user_info[0]['last_name']}" if user_info else "Пользователь"
        except Exception as e:
            print(f"[ERROR] Failed to get user info: {e}", flush=True)
            author_name = "Пользователь"

        conv_id = f"{from_id}_{int(time.time()*1000)}_{random.randint(100,999)}"

        print(f"[MESSAGE] from {author_name} (@id{from_id}): {text}", flush=True)
        append_event('message_received', {
            'title': f'От {author_name}',
            'from': from_id,
            'author': author_name,
            'text': text,
            'peer_id': peer_id,
        }, source='bot', conv=conv_id)

        prompt = f"Пользователь написал: {text}. Дай короткий, дружелюбный ответ."

        model_used = "minimax-m2.7-free"
        append_event('ai_request', {
            'title': 'Запрос в Opencode',
            'model': model_used,
            'prompt': prompt,
        }, source='bot', conv=conv_id)

        answer = None
        try:
            t_start = time.time()
            response = opencode_client.chat.completions.create(
                messages=[{"role": "user", "content": prompt}],
                model=model_used
            )
            t_elapsed = round(time.time() - t_start, 2)
            answer = response.choices[0].message.content.strip()
            append_event('ai_response', {
                'title': 'Ответ от Opencode',
                'model': model_used,
                'answer': answer,
                'elapsed': t_elapsed,
            }, source='bot', conv=conv_id)
        except Exception as e:
            print(f"[ERROR] Opencode API error: {e}", flush=True)
            append_event('error', {
                'title': 'Ошибка Opencode API',
                'error': str(e),
                'model': model_used,
            }, source='bot', conv=conv_id)
            try:
                model_used = "glm-5-free"
                append_event('ai_request', {
                    'title': 'Запрос в Opencode (запасная модель)',
                    'model': model_used,
                    'prompt': prompt,
                }, source='bot', conv=conv_id)
                t_start = time.time()
                response = opencode_client.chat.completions.create(
                    messages=[{"role": "user", "content": prompt}],
                    model=model_used
                )
                t_elapsed = round(time.time() - t_start, 2)
                answer = response.choices[0].message.content.strip()
                append_event('ai_response', {
                    'title': 'Ответ от Opencode',
                    'model': model_used,
                    'answer': answer,
                    'elapsed': t_elapsed,
                }, source='bot', conv=conv_id)
            except Exception as e2:
                print(f"[ERROR] Opencode API error (fallback): {e2}", flush=True)
                append_event('error', {
                    'title': 'Ошибка Opencode API (запасная)',
                    'error': str(e2),
                    'model': model_used,
                }, source='bot', conv=conv_id)
                answer = "Извините, произошла ошибка при генерации ответа."

        try:
            vk.method('messages.send', {
                'peer_id': peer_id,
                'message': answer,
                'random_id': int(time.time() * 1000)
            })
            print(f"[SENT] Response to {peer_id}: {answer}", flush=True)
            append_event('message_sent', {
                'title': 'Ответ отправлен',
                'peer_id': peer_id,
                'author': author_name,
                'text': answer,
            }, source='bot', conv=conv_id)
        except Exception as e:
            print(f"[ERROR] Failed to send message: {e}", flush=True)
            append_event('error', {
                'title': 'Ошибка отправки сообщения',
                'error': str(e),
                'peer_id': peer_id,
            }, source='bot', conv=conv_id)

    except Exception as e:
        print(f"[ERROR] process_message: {e}", flush=True)
        append_event('error', {
            'title': 'Ошибка обработки сообщения',
            'error': str(e),
        }, source='bot')

def main():
    print(f"[BOT] Starting VK bot via hub: {HUB_URL}", flush=True)
    append_event('info', {'title': 'Бот запущен', 'hub_url': HUB_URL, 'channel': CHANNEL}, source='bot')

    while True:
        try:
            params = {'channel': CHANNEL}
            resp = requests.get(HUB_URL, params=params, timeout=15)

            if resp.status_code != 200:
                print(f"[BOT] Hub HTTP {resp.status_code}: {resp.text[:200]}", flush=True)
                time.sleep(5)
                continue

            data = resp.json()
            messages = data if isinstance(data, list) else [data] if data else []

            for msg in messages:
                if msg:
                    print(f"[BOT] Received: {json.dumps(msg, ensure_ascii=False)}", flush=True)
                    process_message(msg)

        except requests.exceptions.Timeout:
            print("[BOT] Hub request timed out", flush=True)
            append_event('warning', {'title': 'Таймаут запроса хаба'}, source='bot')
        except requests.exceptions.ConnectionError as e:
            print(f"[BOT] Hub connection error: {e}", flush=True)
            append_event('error', {'title': 'Ошибка подключения к хабу', 'error': str(e)}, source='bot')
            time.sleep(10)
        except json.JSONDecodeError as e:
            print(f"[BOT] Invalid JSON from hub: {e}", flush=True)
            append_event('error', {'title': 'Неверный JSON от хаба', 'error': str(e)}, source='bot')
        except Exception as e:
            print(f"[BOT] Error: {e}", flush=True)
            append_event('error', {'title': 'Ошибка', 'error': str(e)}, source='bot')

        time.sleep(3)

if __name__ == '__main__':
    main()
