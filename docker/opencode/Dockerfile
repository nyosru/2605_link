FROM node:20-alpine

RUN apk add --no-cache bash curl && \
    curl -fsSL https://opencode.ai/install | bash && \
    ln -s /root/.opencode/bin/opencode /usr/local/bin/opencode

COPY server.js /server.js

EXPOSE 8080

CMD ["node", "/server.js"]
