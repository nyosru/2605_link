.PHONY: build up down logs restart shell help run-longpoll

help:
	@echo "Available commands:"
	@echo "  make build       - Build Docker image"
	@echo "  make up          - Start containers in detached mode"
	@echo "  make down        - Stop and remove containers"
	@echo "  make logs        - Follow container logs"
	@echo "  make restart     - Restart containers"
	@echo "  make shell       - Open shell in the bot container"
	@echo "  make run-longpoll- Run longpoll bot locally (no Docker)"
	@echo "  make help        - Show this help"

build:
	docker-compose build

up:
	docker-compose up -d

down:
	docker-compose down

logs:
	docker-compose logs -f

restart:
	docker-compose restart

shell:
	docker-compose exec bot /bin/sh

l:
	docker exec vk_bot pip3 install --break-system-packages -r requirements.txt
	docker exec vk_bot python3 -u longpoll_bot.py

go:
	make up
	make l