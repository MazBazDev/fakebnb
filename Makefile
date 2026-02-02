INFRA_COMPOSE = infra/compose.yaml
ENV_FILE = infra/.env

.PHONY: init up down rebuild logs ps

init:
	sh infra/init.sh

up:
	docker compose --env-file $(ENV_FILE) -f $(INFRA_COMPOSE) up -d --build

down:
	docker compose --env-file $(ENV_FILE) -f $(INFRA_COMPOSE) down

rebuild:
	docker compose --env-file $(ENV_FILE) -f $(INFRA_COMPOSE) up -d --build --force-recreate

logs:
	docker compose --env-file $(ENV_FILE) -f $(INFRA_COMPOSE) logs -f

ps:
	docker compose --env-file $(ENV_FILE) -f $(INFRA_COMPOSE) ps
