# VK Bot with Opencode

This system receives messages from a VK group, processes them with Opencode (locally via Docker), and sends back responses.

## Initial Setup

### 1. VK Group Configuration
- Create a VK group/community.
- Go to **Manage** -> **Messages** -> allow messages from users.
- Enable **Callback API** in the group settings.
- Obtain:
  - **Group Token** (service key) from **Manage** -> **API Work** -> **Create key** (select necessary permissions: messages, offline).
  - **Confirmation Token** from the Callback API settings page.

### 2. Environment Variables
Copy the example `.env` file and fill in your tokens:

```bash
cp .env.example .env   # if you have an example, otherwise create .env manually
```

Edit `.env`:
```
GROUP_TOKEN=your_vk_group_token_here
CONFIRM_TOKEN=your_vk_confirmation_token_here
```

### 3. Install Dependencies (if not using Docker)
If you want to run locally without Docker, install the required packages:

```bash
pip install -r requirements.txt
```

Ensure you have the Opencode CLI installed and available in your PATH.

### 4. Running with Docker (Recommended)

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

### 5. Setting Up Public URL for VK Callback
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

### 6. Testing
- Send a message to your VK group.
- The bot should receive it, call Opencode, and reply with a generated response.
- Check logs (`make logs`) for any errors.

## How It Works
1. VK sends a POST request to `/callback` with event data.
2. The bot verifies the confirmation token or processes `message_new` events.
3. For new messages, it extracts the text and forms a prompt for Opencode.
4. Opencode CLI is invoked with the prompt.
5. The generated answer is sent back to the user via VK API `messages.send`.

## Troubleshooting
- **Bot not responding**: Check logs for errors (Opencode timeout, VK API errors).
- **Opencode not found**: Ensure Opencode is installed in the Docker image (we rely on it being available; if not, you may need to install it via pip or add the binary).
- **VK API errors**: Verify your GROUP_TOKEN has the required permissions and is not expired.

## Notes
- The bot uses Flask and runs on port 5000 inside the container.
- The `.env` file is loaded by docker-compose; ensure it's in the project root.
- For production, consider using a proper reverse proxy, process manager, and secure secret storage.

## Files Overview
- `bot.py`: Main application with Flask endpoint and VK/Opencode logic.
- `Dockerfile`: Defines the Docker image.
- `docker-compose.yml`: Defines the service, ports, and environment.
- `requirements.txt`: Python dependencies.
- `Makefile`: Convenience commands.
- `.env`: Stores VK tokens (not committed to git).
