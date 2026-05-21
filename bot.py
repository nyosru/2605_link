import os
import subprocess
import json
from flask import Flask, request, jsonify
import vk_api

app = Flask(__name__)

GROUP_TOKEN = os.getenv('GROUP_TOKEN')
CONFIRM_TOKEN = os.getenv('CONFIRM_TOKEN')

if not GROUP_TOKEN or not CONFIRM_TOKEN:
    raise ValueError("GROUP_TOKEN and CONFIRM_TOKEN must be set in environment variables")

vk_session = vk_api.VkApi(token=GROUP_TOKEN)
vk = vk_session.get_api()

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

        # Prepare prompt for Opencode
        prompt = f"Пользователь написал: {user_text}. Дай короткий, дружелюбный ответ."

        # Call Opencode CLI
        try:
            result = subprocess.run(
                ['opencode', 'run', '--prompt', prompt],
                capture_output=True,
                text=True,
                timeout=30
            )
            if result.returncode != 0:
                app.logger.error(f"Opencode error: {result.stderr}")
                answer = "Извините, произошла ошибка при генерации ответа."
            else:
                answer = result.stdout.strip()
        except subprocess.TimeoutExpired:
            app.logger.error("Opencode timeout")
            answer = "Извините, превышено время ожидания ответа."
        except Exception as e:
            app.logger.error(f"Exception calling Opencode: {e}")
            answer = "Извините, произошла ошибка при генерации ответа."

        # Send response via VK API
        try:
            vk.method('messages.send', {
                'peer_id': peer_id,
                'message': answer,
                'random_id': 0
            })
        except Exception as e:
            app.logger.error(f"Failed to send message to VK: {e}")
            return jsonify({'error': 'Failed to send message'}), 500

        return jsonify({'status': 'ok'}), 200

    # For other event types, just acknowledge
    return jsonify({'status': 'ok'}), 200

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)