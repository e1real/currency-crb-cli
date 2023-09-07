.DEFAULT_GOAL=help
PLATFORM := $(shell uname)

DC=docker-compose
APP=$(DC) exec php-fpm
APP_TTY=$(DC) exec -T php-fpm
CURRENT_DIR=$(shell pwd)

help:  ## Display this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' Makefile \
	  | sort \
	  | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[0;32m%-30s\033[0m %s\n", $$1, $$2}'

up: 
	$(DC) up -d

down: 
	$(DC) down

php-fpm-bash:
	docker compose run --rm --remove-orphans php-fpm bash

check-currency:
	docker compose run --rm --remove-orphans php-fpm php index.php