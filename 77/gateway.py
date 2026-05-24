import os
import sys
import subprocess
import json
from datetime import datetime
from flask import Flask, request, jsonify
from dotenv import load_dotenv

load_dotenv()

app = Flask(__name__)

LOG_FILE = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'gateway.log')
OPENCODE_BIN = os.getenv('OPENCODE_BIN', 'opencode')
DEFAULT_MODEL = os.getenv('MODEL', 'opencode/deepseek-v4-flash-free')
DEFAULT_TIMEOUT = int(os.getenv('OPENCODE_TIMEOUT', '120'))


def log_to_file(entry: dict):
    entry['timestamp'] = datetime.now().isoformat()
    try:
        with open(LOG_FILE, 'a') as f:
            f.write(json.dumps(entry, ensure_ascii=False) + '\n')
    except Exception as e:
        app.logger.error(f"Failed to write log: {e}")


def call_opencode(message: str, session_id: str | None = None, model: str | None = None) -> dict:
    cmd = [OPENCODE_BIN, 'run', message]
    if session_id:
        cmd.extend(['--session', session_id])
    if model:
        cmd.extend(['--model', model])

    app.logger.info(f"Running: {' '.join(cmd)}")

    project_dir = os.path.dirname(os.path.abspath(__file__))
    try:
        result = subprocess.run(
            cmd,
            capture_output=True,
            text=True,
            timeout=DEFAULT_TIMEOUT,
            cwd=project_dir
        )
    except subprocess.TimeoutExpired:
        return {'success': False, 'error': 'Timeout expired'}
    except FileNotFoundError:
        return {'success': False, 'error': f'Opencode binary not found at {OPENCODE_BIN}'}
    except Exception as e:
        return {'success': False, 'error': str(e)}

    if result.returncode != 0:
        return {
            'success': False,
            'error': result.stderr.strip() or result.stdout.strip(),
            'returncode': result.returncode,
        }

    return {'success': True, 'output': result.stdout.strip()}


@app.route('/api/send', methods=['GET'])
def send():
    message = request.args.get('message', '').strip()
    session_id = request.args.get('session', '').strip() or None
    model = request.args.get('model', '').strip() or DEFAULT_MODEL

    if not message:
        return jsonify({'success': False, 'error': 'Missing "message" parameter'}), 400

    log_to_file({
        'type': 'request',
        'message': message,
        'session_id': session_id,
        'model': model,
    })

    result = call_opencode(message, session_id, model)

    log_to_file({
        'type': 'response',
        'message': message,
        'session_id': session_id,
        'model': model,
        'result': result,
    })

    return jsonify(result)


if __name__ == '__main__':
    port = int(os.getenv('GATEWAY_PORT', '5003'))
    app.run(host='0.0.0.0', port=port, debug=True)
