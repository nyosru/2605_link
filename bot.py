import os
import json
from flask import Flask, request, jsonify
import vk_api
from opencode_ai import Opencode

app = Flask(__name__)

GROUP_TOKEN = os.getenv('GROUP_TOKEN')
CONFIRM_TOKEN = os.getenv('CONFIRM_TOKEN')
OPENCODE_API_KEY = os.getenv('OPENCODE_API_KEY')

if not GROUP_TOKEN or not CONFIRM_TOKEN:
    raise ValueError("GROUP_TOKEN and CONFIRM_TOKEN must be set in environment variables")

vk_session = vk_api.VkApi(token=GROUP_TOKEN)
vk = vk_session.get_api()

# Initialize Opencode client with API key
opencode_client = Opencode(api_key=OPENCODE_API_KEY)

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

        # Call Opencode API
        try:
            response = opencode_client.chat.completions.create(
                messages=[{"role": "user", "content": prompt}],
                model="openai/gpt-4o-mini"  # или другая доступная модель
            )
            answer = response.choices[0].message.content.strip()
        except Exception as e:
            app.logger.error(f"Opencode API error: {e}")
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