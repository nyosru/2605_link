# VK Bot with Opencode

This system receives messages from a VK group, processes them with Opencode, and sends back responses.

Two methods are supported:
1. **Webhook (Callback API)** - VK sends HTTP POST requests to your server (requires public URL via ngrok or similar)
2. **Long Poll** - Your server periodically polls VK for new messages (no public URL needed, can run locally)

## Initial Setup

### 1. VK Group Configuration
- Create a VK group/community.
- Go to **Manage** -> **Messages** -> allow messages from users.
- Obtain:
  - **Group Token** (service key) from **Manage** -> **API Work** -> **Create key** (select necessary permissions: groups, messages, offline).
  - **Group ID** - numeric ID of your group (visible in the group URL: `vk.com/public{GROUP_ID}` or can be obtained via `groups.getById` API).

### 2. Environment Variables
Copy the example `.env` file and fill in your values:

```bash
cp .env.example .env   # if you have an example, otherwise create .env manually
```

Edit `.env`:
```
GROUP_TOKEN=your_vk_group_token_here
CONFIRM_TOKEN=your_vk_confirmation_token_here   # Only needed for webhook method
GROUP_ID=your_vk_group_id_here
# Opencode API key is NOT required when using free models.
# If you want to use paid models, uncomment and set:
# OPENCODE_API_KEY=your_opencode_api_key_here
```

### 3. Install Dependencies (if not using Docker)
If you want to run locally without Docker, install the required packages:

```bash
pip install -r requirements.txt
```

Ensure you have Python 3.8+.

## Method 1: Webhook (Callback API) - Requires Public URL

### 3.1. Running with Docker (Recommended for Webhook)

#### Build and Start
```bash
make build   # or docker-compose build
make up      # or docker-compose up -d
```

#### View Logs
```bash
make logs    # docker-compose logs -f
```

#### Stop
```bash
make down    # docker-compose down
```

#### Restart
```bash
make restart
```

#### Open Shell in Container
```bash
make shell   # docker-compose exec bot /bin/sh
```

### 3.2. Setting Up Public URL for VK Callback (Webhook Only)
VK requires a publicly accessible URL for the Callback API.

#### Using ngrok (example)
1. Install ngrok: https://ngrok.com/download
2. Start tunnel for port 5000:
   ```bash
   ngrok http 5000
   ```
3. Copy the forwarding URL (e.g., `https://abc123.ngrok.io`).
4. In VK group settings -> Callback API, set the URL to `https://abc123.ngrok.io/callback`.
5. VK will send a confirmation request; your bot should respond with the confirmation token.

### 3.3. Testing Webhook
- Send a message to your VK group.
- The bot should receive it, call Opencode, and reply with a generated response.
- Check logs (`make logs`) for any errors.

## Method 2: Long Poll (No Public URL Needed)

### 3.1. Running Long Poll Bot Locally
```bash
# Install dependencies if not already done
pip install -r requirements.txt

# Run the longpoll bot
make run-longpoll
```
or directly:
```bash
python longpoll_bot.py
```

### 3.2. Running Long Poll Bot in Docker (Optional)
If you prefer to run the longpoll bot in Docker, you can use the same image but override the command:

```bash
docker-compose run --rm bot python longpoll_bot.py
```
Or add a service to docker-compose.yml for longpoll (not included by default to keep it simple).

### 3.3. Testing Long Poll
- Send a message to your VK group.
- The bot should receive it via long polling, call Opencode, and reply with a generated response.
- Check console output for logs.

## How It Works (Both Methods)
1. VK sends a new message event (via webhook or longpoll).
2. The bot extracts the text and forms a prompt for Opencode.
3. Opencode API is invoked with the prompt.
4. The generated answer is sent back to the user via VK API `messages.send`.

## Using Opencode Without External API Keys

The current bot code is configured to use **Opencode's free models** by default, which do not require any API key or registration. These models are hosted by Opencode but are free to use.

### Available Free Models
You can see the list of free models by running:
```bash
opencode /models
```
Look for models with `-free` suffix or `$0` price.

The bot currently uses:
- Primary: `minimax-m2.7-free` (200K context, strong reasoning)
- Fallback: `glm-5-free` (excellent Chinese performance, reasoning)

These models work immediately after installing the `opencode-ai` Python package - no additional configuration needed.

### Option: Using a Local Model for Fully Offline Use
If you prefer to keep all processing completely local (no data sent to external services), you can set up a local model server and configure Opencode to use it.

#### Step 1: Set Up a Local Model Server
Install and run a local inference server that provides an OpenAI-compatible API. Popular options:

**Ollama (recommended for simplicity):**
```bash
# Install Ollama
curl -fsSL https://ollama.com/install.sh | sh

# Start the server
ollama serve

# Pull a model (example: CodeLlama)
ollama pull codellama:7b
```

**LM Studio:**
1. Download from https://lmstudio.ai/
2. Load a model and start the server (defaults to localhost:1234)

**vLLM or text-generation-inference:**
For more advanced setups with GPU acceleration.

#### Step 2: Configure Opencode to Use the Local Model
Set the base URL for the Opencode provider to point to your local server:

```bash
# For Ollama (default port 11434)
opencode config set provider.base_url http://localhost:11434/v1

# For LM Studio (default port 1234)
opencode config set provider.base_url http://localhost:1234/v1

# Verify the configuration
opencode config show
```

#### Step 3: Modify the Bot to Use the CLI (Alternative Approach)
The current bot code uses the Opencode Python SDK, which is designed for the Opencode cloud API. To use a local model server, you would need to modify the bot to call the Opencode CLI instead.

Here's an example of how to modify `bot.py` to use the CLI with a local model:

```python
import os
import subprocess
from flask import Flask, request, jsonify
import vk_api

app = Flask(__name__)

# ... (VK setup remains the same)

@app.route('/callback', methods=['POST'])
def callback():
    # ... (request handling remains the same)
    
    if data.get('type') == 'message_new':
        # ... (extract message text and peer_id)
        
        # Prepare prompt for Opencode CLI
        prompt = f"Пользователь написал: {user_text}. Дай короткий, дружелюбный ответ."
        
        # Call Opencode CLI (will use locally configured model)
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
        
        # ... (send response via VK API remains the same)
```

You would need to make similar changes to `longpoll_bot.py`.

**Important:** When using the CLI approach, you must first configure Opencode to use your local model server (as shown in Step 2 above) before running the bot.

## Troubleshooting
- **Bot not responding**: Check logs for errors (Opencode API errors, VK API errors).
- **Opencode API errors**: 
  - For free models: Verify you have internet access (the free models are hosted externally).
  - For local models: Verify your local server is running and accessible at the configured URL.
- **VK API errors**: Verify your GROUP_TOKEN has the required permissions (groups, messages) and is not expired.
- **Long Poll reconnection**: The bot automatically attempts to reconnect on errors.

## Files Overview
- `bot.py`: Main application for webhook method with Flask endpoint (uses Opencode free models via Python SDK).
- `longpoll_bot.py`: Application for long poll method (uses Opencode free models via Python SDK).
- `Dockerfile`: Defines the Docker image (based on python:3.11-slim).
- `docker-compose.yml`: Defines the webhook service, ports, and environment.
- `requirements.txt`: Python dependencies (vk_api, Flask, opencode-ai).
- `Makefile`: Convenience commands.
- `.env.example`: Template for environment variables (copy to .env).
- `.gitignore`: Excludes .env but keeps .env.example.

## Choosing a Method
- **Webhook**: Better for real-time processing with lower latency, but requires a public URL (ngrok, cloudflare tunnel, etc.).
- **Long Poll**: Simpler to set up (no public URL needed), but slightly higher latency due to polling interval and may consume more API requests over time.

## Choosing an Opencode Model Option
- **Free Models (Default)**: 
  - Pros: No setup required, no API key needed, good quality for many tasks.
  - Cons: Requires internet access to reach Opencode's hosted free models.
  
- **Local Model**:
  - Pros: Fully offline, complete data privacy, works without internet.
  - Cons: Requires setting up and maintaining a local model server, consumes local resources (RAM/VRAM).

For development/testing, the free models option is easiest. For production with strict privacy requirements, a local model is recommended.
