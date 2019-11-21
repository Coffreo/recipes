ENV ?= $(shell cenv -t 2>/dev/null || ( [ -z ${COFFREO_ENV} ] && echo dev ) || echo ${COFFREO_ENV} )
.DEFAULT_GOAL := help
SHELL := /bin/bash -O extglob -c
COMPOSER_CMD=composer install --optimize-autoloader --prefer-dist
BUILDING=false
# initialize custom variables

.PHONY: help install build it env health test unused insights phpstan qa

## Run application tests
test:
	echo "Not implemented yet"

## (re)Build a project
build:
	composer validate
ifeq (${ENV}, dev)
	[[ ${BUILDING} == false ]] && SYMFONY_ENV=${ENV} ${COMPOSER_CMD} || SYMFONY_ENV=${ENV} ${COMPOSER_CMD} --no-scripts
else
	[[ ${BUILDING} == false ]] && SYMFONY_ENV=${ENV} ${COMPOSER_CMD} || SYMFONY_ENV=${ENV} ${COMPOSER_CMD} --no-scripts --no-dev
endif

## Initialize a project from scratch
install: build

## Perform precommit task (build translation, cs, test, ...)
it: build test

## Generate environment variables
env:
	cenv --output .env.local -d .cenv-modules
	echo "APP_ENV=${ENV}" >> .env.local
	cat .env >> .env.local

## Monitor project health
health:
	echo "Not implemented yet"

## Show composer unused dependencies
unused:
	@printf "\n\n\n\n"
	@read -p "Show composer unused dependencies:"
	composer unused

## Show phpinsights review
insights:
	@printf "\n\n\n\n"
	@read -p "Show phpinsights review:"
	./vendor/bin/phpinsights --no-interaction

## Show phpstan review
phpstan:
	@printf "\n\n\n\n"
	@read -p "Show phpstan review:"
	./vendor/bin/phpstan analyse src tests --level max

## Launch all qa reviews
qa:	unused insights phpstan


APPLICATION := $(shell (cat package.json 2>/dev/null || cat composer.json) | grep "\"name\"" | head -1 | cut -d\" -f 4 )

# COLORS
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
WHITE  := $(shell tput -Txterm setaf 7)
RESET  := $(shell tput -Txterm sgr0)

TARGET_MAX_CHAR_NUM=20

## Show this help
help:
	@echo '# ${YELLOW}${APPLICATION}${RESET} / ${GREEN}${ENV}${RESET}'
	@echo ''
	@echo 'Usage:'
	@echo '  ${YELLOW}make${RESET} ${GREEN}<target>${RESET}'
	@echo ''
	@echo 'Targets:'
	@awk '/^[a-zA-Z\-\_0-9]+:/ { \
        helpMessage = match(lastLine, /^## (.*)/); \
        if (helpMessage) { \
            helpCommand = substr($$1, 0, index($$1, ":")); \
            gsub(":", " ", helpCommand); \
            helpMessage = substr(lastLine, RSTART + 3, RLENGTH); \
            printf "  ${YELLOW}%-$(TARGET_MAX_CHAR_NUM)s${RESET} ${GREEN}%s${RESET}\n", helpCommand, helpMessage; \
        } \
    } \
    { lastLine = $$0 }' $(MAKEFILE_LIST) | sort
