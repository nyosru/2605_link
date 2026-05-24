import os
import sys
import time
import json
import requests
from dotenv import load_dotenv
from event_store import append_event

load_dotenv()

HUB_URL = os.getenv('HUB_URL', 'https://api.php-cat.com/api/vk/incoming')
CHANNEL = os.getenv('CHANNEL')

print(f"[HUB] HUB_URL={HUB_URL}", flush=True)
print(f"[HUB] CHANNEL={'set' if CHANNEL else 'NOT SET'}", flush=True)

if not CHANNEL:
    raise ValueError("CHANNEL must be set in environment variables")

def fetch_messages():
    params = {'channel': CHANNEL}
    try:
        resp = requests.get(HUB_URL, params=params, timeout=10)
    except requests.exceptions.Timeout:
        print("[HUB] Request timed out", flush=True)
        append_event('error', {'title': 'Таймаут запроса хаба'}, source='hub')
        return
    except requests.exceptions.ConnectionError as e:
        print(f"[HUB] Connection error: {e}", flush=True)
        append_event('error', {'title': 'Ошибка подключения к хабу', 'error': str(e)}, source='hub')
        return

    if resp.status_code != 200:
        print(f"[HUB] HTTP {resp.status_code}: {resp.text[:200]}", flush=True)
        return

    try:
        data = resp.json()
    except json.JSONDecodeError as e:
        print(f"[HUB] Invalid JSON: {e}", flush=True)
        return

    messages = data if isinstance(data, list) else [data]
    for msg in messages:
        print(f"[HUB] Received: {json.dumps(msg, ensure_ascii=False)}", flush=True)
        append_event('hub_message', {
            'title': 'Получено из хаба',
            'message': msg,
        }, source='hub')

def main():
    print(f"[HUB] Starting hub poller for channel(s): {CHANNEL}", flush=True)
    print(f"[HUB] Polling every 10 seconds...", flush=True)
    append_event('info', {'title': 'Hub poller запущен', 'channel': CHANNEL}, source='hub')

    while True:
        fetch_messages()
        time.sleep(10)

if __name__ == '__main__':
    main()
