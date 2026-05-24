import os
import json
import uuid
from datetime import datetime, timezone

EVENTS_FILE = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'events.jsonl')
MAX_EVENTS = 2000


def append_event(event_type, data, source='system', conv=None):
    event = {
        'id': str(uuid.uuid4())[:8],
        'timestamp': datetime.now(timezone.utc).isoformat(),
        'type': event_type,
        'source': source,
        'data': data,
    }
    if conv:
        event['conv'] = conv
    try:
        with open(EVENTS_FILE, 'a') as f:
            f.write(json.dumps(event, ensure_ascii=False) + '\n')
    except Exception:
        pass
    return event


def get_events(limit=100, offset=0):
    events = []
    if not os.path.exists(EVENTS_FILE):
        return events
    try:
        with open(EVENTS_FILE, 'r') as f:
            for line in f:
                line = line.strip()
                if line:
                    try:
                        events.append(json.loads(line))
                    except json.JSONDecodeError:
                        pass
    except Exception:
        pass
    if len(events) > MAX_EVENTS:
        events = events[-MAX_EVENTS:]
    events.reverse()
    return events[offset:offset + limit]


def get_stats():
    by_type = {}
    total = 0
    if not os.path.exists(EVENTS_FILE):
        return {'total': 0, 'by_type': {}}
    try:
        with open(EVENTS_FILE, 'r') as f:
            for line in f:
                line = line.strip()
                if line:
                    try:
                        e = json.loads(line)
                        total += 1
                        t = e['type']
                        by_type[t] = by_type.get(t, 0) + 1
                    except json.JSONDecodeError:
                        pass
    except Exception:
        pass
    return {'total': total, 'by_type': by_type}
