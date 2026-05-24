import os
import json
import time
import random
from flask import Flask, request, jsonify
import vk_api
from opencode_ai import Opencode
from dotenv import load_dotenv
from event_store import append_event

load_dotenv()  # Load environment variables from .env file

app = Flask(__name__)

GROUP_TOKEN = os.getenv('GROUP_TOKEN')
CONFIRM_TOKEN = os.getenv('CONFIRM_TOKEN')

if not GROUP_TOKEN or not CONFIRM_TOKEN:
    raise ValueError("GROUP_TOKEN and CONFIRM_TOKEN must be set in environment variables")

vk_session = vk_api.VkApi(token=GROUP_TOKEN)
vk = vk_session.get_api()

# Initialize Opencode client (will use free models by default)
opencode_client = Opencode()

@app.route('/callback', methods=['POST'])
def callback():
    data = request.json
    if not data:
        return jsonify({'error': 'Invalid JSON'}), 400

    # Handle confirmation request
    if data.get('type') == 'confirmation':
        return CONFIRM_TOKEN, 200

    # Handle new message event
    if data.get('type') == 'message_new':
        message = data.get('object', {}).get('message', {})
        user_text = message.get('text', '')
        peer_id = message.get('peer_id')
        from_id = message.get('from_id')

        if not user_text or not peer_id:
            return jsonify({'error': 'Missing required fields'}), 400

        conv_id = f"{from_id}_{int(time.time()*1000)}_{random.randint(100,999)}"

        append_event('message_received', {
            'title': f'Сообщение от @id{from_id}',
            'from': from_id,
            'text': user_text,
            'peer_id': peer_id,
        }, source='webhook', conv=conv_id)

        prompt = f"Пользователь написал: {user_text}. Дай короткий, дружелюбный ответ."

        # Call Opencode API with free model
        model_used = "minimax-m2.7-free"
        append_event('ai_request', {
            'title': 'Запрос в Opencode',
            'model': model_used,
            'prompt': prompt,
        }, source='webhook', conv=conv_id)

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
            }, source='webhook', conv=conv_id)
        except Exception as e:
            app.logger.error(f"Opencode API error: {e}")
            append_event('error', {
                'title': 'Ошибка Opencode API',
                'error': str(e),
                'model': model_used,
            }, source='webhook', conv=conv_id)
            model_used = "glm-5-free"
            append_event('ai_request', {
                'title': 'Запрос в Opencode (запасная модель)',
                'model': model_used,
                'prompt': prompt,
            }, source='webhook', conv=conv_id)
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
                }, source='webhook', conv=conv_id)
            except Exception as e2:
                app.logger.error(f"Opencode API error (fallback): {e2}")
                append_event('error', {
                    'title': 'Ошибка Opencode API (запасная)',
                    'error': str(e2),
                    'model': model_used,
                }, source='webhook', conv=conv_id)
                answer = "Извините, произошла ошибка при генерации ответа."

        # Send response via VK API
        try:
            vk.method('messages.send', {
                'peer_id': peer_id,
                'message': answer,
                'random_id': 0
            })
            append_event('message_sent', {
                'title': 'Ответ отправлен',
                'peer_id': peer_id,
                'text': answer,
            }, source='webhook', conv=conv_id)
        except Exception as e:
            app.logger.error(f"Failed to send message to VK: {e}")
            append_event('error', {
                'title': 'Ошибка отправки в VK',
                'error': str(e),
                'peer_id': peer_id,
            }, source='webhook', conv=conv_id)
            return jsonify({'error': 'Failed to send message'}), 500

        return jsonify({'status': 'ok'}), 200

    # For other event types, just acknowledge
    return jsonify({'status': 'ok'}), 200

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)