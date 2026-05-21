.PHONY: build up down logs restart shell help

help:
	@echo "Available commands:"
	@echo "  make build   - Build Docker image"
	@echo "  make up      - Start containers in detached mode"
	@echo "  make down    - Stop and remove containers"
	@echo "  make logs    - Follow container logs"
	@echo "  make restart - Restart containers"
	@echo "  make shell   - Open shell in the bot container"
	@echo "  make help    - Show this help"

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