const http = require('http');
const { execSync } = require('child_process');

const PORT = 8080;
const MODEL = process.env.OPENCODE_MODEL || 'opencode/deepseek-v4-flash-free';
const TIMEOUT = 120_000;

const server = http.createServer((req, res) => {
  const cors = {
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Methods': 'POST, OPTIONS',
    'Access-Control-Allow-Headers': 'Content-Type',
  };

  if (req.method === 'OPTIONS') {
    res.writeHead(204, cors);
    return res.end();
  }

  if (req.method !== 'POST' || req.url !== '/run') {
    res.writeHead(404, { ...cors, 'Content-Type': 'application/json' });
    return res.end(JSON.stringify({ error: 'not found' }));
  }

  let body = '';
  req.on('data', chunk => body += chunk);
  req.on('end', () => {
    let prompt, model, instructions;
    try {
      ({ prompt, model, instructions } = JSON.parse(body));
    } catch {
      res.writeHead(400, { ...cors, 'Content-Type': 'application/json' });
      return res.end(JSON.stringify({ error: 'invalid json' }));
    }

    if (!prompt) {
      res.writeHead(400, { ...cors, 'Content-Type': 'application/json' });
      return res.end(JSON.stringify({ error: 'prompt is required' }));
    }

    let cmd = `opencode run ${JSON.stringify(prompt)} -m ${model || MODEL}`;
    if (instructions) {
      cmd += ` --file /agents.md`;
    }

    const start = Date.now();

    try {
      const stdout = execSync(cmd, { timeout: TIMEOUT, encoding: 'utf-8', maxBuffer: 10 * 1024 * 1024 });
      const elapsed = Date.now() - start;
      res.writeHead(200, { ...cors, 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ response: stdout.trim(), elapsed_ms: elapsed }));
    } catch (e) {
      res.writeHead(500, { ...cors, 'Content-Type': 'application/json' });
      res.end(JSON.stringify({
        error: e.stderr?.trim() || e.message || 'unknown error',
      }));
    }
  });
});

server.listen(PORT, '0.0.0.0', () => {
  console.log(`opencode-server listening on port ${PORT}, model: ${MODEL}`);
});
