import os
import json
import time
from flask import Flask, render_template, jsonify, Response, request
from event_store import get_events, get_stats

app = Flask(__name__)
app.config['TEMPLATES_AUTO_RELOAD'] = True


@app.route('/')
def index():
    return render_template('index.html')


@app.route('/api/events')
def api_events():
    limit = request.args.get('limit', 100, type=int)
    offset = request.args.get('offset', 0, type=int)
    events = get_events(limit=limit, offset=offset)
    return jsonify(events)


@app.route('/api/stats')
def api_stats():
    return jsonify(get_stats())


@app.route('/api/events/stream')
def stream():
    def generate():
        last_events = get_events(limit=50)
        last_id = last_events[0]['id'] if last_events else None
        while True:
            time.sleep(1)
            current = get_events(limit=50)
            if current and current[0]['id'] != last_id:
                new_events = []
                for e in current:
                    if e['id'] == last_id:
                        break
                    new_events.append(e)
                if new_events:
                    last_id = current[0]['id']
                    yield f"data: {json.dumps(new_events, ensure_ascii=False)}\n\n"
            else:
                yield ": heartbeat\n\n"

    return Response(generate(), mimetype='text/event-stream')


if __name__ == '__main__':
    port = int(os.getenv('WEBUI_PORT', '5001'))
    app.run(host='0.0.0.0', port=port, debug=True)
