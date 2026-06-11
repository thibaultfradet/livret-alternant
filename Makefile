DOCKER_COMP = docker compose
PHP_CONT    = $(DOCKER_COMP) exec php
PHP         = $(PHP_CONT) php
COMPOSER    = $(PHP_CONT) composer
SYMFONY     = $(PHP) bin/console

.DEFAULT_GOAL = help
.PHONY: help build start stop logs sh bash vendor sf cc deploy

help: ## List available commands
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}{printf "%-20s %s\n", $$1, $$2}'

## Docker

build: ## Build Docker images (no cache)
	$(DOCKER_COMP) build --pull --no-cache

start: ## Start containers in background
	$(DOCKER_COMP) up --detach --wait

stop: ## Stop and remove containers
	$(DOCKER_COMP) down --remove-orphans

logs: ## Follow container logs
	$(DOCKER_COMP) logs --tail=0 --follow

sh: ## Open sh shell in php container
	$(PHP_CONT) sh

bash: ## Open bash in php container
	$(PHP_CONT) bash

## Composer

vendor: ## Install composer dependencies (no dev)
	$(COMPOSER) install --prefer-dist --no-dev --no-progress --no-scripts --no-interaction

## Symfony

sf: ## Run a Symfony command: make sf c=about
	@$(eval c ?=)
	$(SYMFONY) $(c)

cc: c=cache:clear ## Clear Symfony cache
cc: sf

## Deployment

deploy: ## Rebuild images and restart containers (migrations run via entrypoint)
	$(DOCKER_COMP) build --pull
	$(DOCKER_COMP) up --detach --wait
	$(SYMFONY) cache:clear
