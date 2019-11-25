SHELL := /bin/bash
ENV ?= $(shell cenv -t 2>/dev/null || ( [ -z ${COFFREO_ENV} ] && echo dev ) || echo ${COFFREO_ENV} )
APP_NAME := $(shell (cat package.json 2>/dev/null || cat composer.json) | grep "\"name\"" | head -1 | cut -d\" -f 4 )
.DEFAULT_GOAL := help

#Building arguments
TAG ?= $(shell cenv -e $(ENV) -u COFFREO_APP_VERSION)
REGISTRY ?= docker-registry.coffreo.com
REPO_NS ?= /coffreo/${APP_NAME}
IMAGE ?= $(REGISTRY)$(REPO_NS)/nginxfpm
#Detect coffreo user id, 1000 or current id
BUILD_UID ?= $(shell (docker info 2>&1 | grep -q -s userns) && echo 1000 || [[ $(shell id -u) -gt 0 ]] && id -u || echo 1000)
#get key for image building
ID_RSA ?= "$$(cat ~/.ssh/id_rsa)"
NAMESPACE ?= default
CONTAINER_DIR=.container
SANDBOX_NAME=${APP_NAME}-builder
ENV_FILE=.env.local
CURRENT_TIME := $(shell date +%s%N)


$(shell cenv -f deploy.tpl -o deploy.env 2> /dev/null)

-include deploy.env
-include deploy-${ENV}.env
-include deploy-local.env
export

.PHONY: env image-tag image-push image-test image-build-sandbox image-build image-it tp deploy-config deploy help is-push-allowed image-dev tpxd
## ------

## Generate environment variables
env:
	cenv --output ${ENV_FILE} -d .cenv-modules
	echo "APP_ENV=${ENV}" >> ${ENV_FILE}
	cat .env >> ${ENV_FILE}



is-push-allowed:
	@[[ ! -z "${CI}" ]] && [[ "${REGISTRY}" =~ coffreo.com$$ ]] || [[ "${REGISTRY}" =~ coffreo.ext$$ ]] || (echo "not allowed to push @${REGISTRY}" && exit 1)

## docker tag <current-image>
image-tag:
	docker tag ${IMAGE}:${TAG} ${IMAGE}:${TAG_NEW}

## docker push <current-image>
image-push: is-push-allowed
	docker push ${IMAGE}:${TAG}

## docker run "make test-unit"
image-test:
	docker run ${SANDBOX_NAME}:${TAG} /bin/sh -c "make test"

## docker run "make qa"
image-qa:
	docker run ${SANDBOX_NAME}:${TAG} /bin/sh -c "printf '\n\n\n' | make qa"

## Build a dev environment
image-build-sandbox: env
	docker build --target builder \
	--build-arg COFFREO_ENV=${ENV} \
	--build-arg ID_RSA="$$(echo ${ID_RSA})" \
	--build-arg UID=${BUILD_UID} \
	--build-arg APP_HOME=/var/www/${APP_NAME} \
	-t ${SANDBOX_NAME}:${TAG} -t ${SANDBOX_NAME}:latest -f $(CONTAINER_DIR)/Dockerfile .

## docker build (image will be ready for deployment)
image-build: env
	docker build \
	--build-arg COFFREO_ENV=${ENV} \
	--build-arg ID_RSA="$$(echo ${ID_RSA})" \
	--build-arg UID=${BUILD_UID} \
	--build-arg APP_HOME=/var/www/${APP_NAME} \
	--build-arg NGINX_LOG_NAME=${APP_NAME} \
	--build-arg REWRITE_PHP=index \
	--build-arg PUBLIC_FOLDER_NAME=public \
	-t ${IMAGE}:${TAG} -t ${IMAGE}:latest -f $(CONTAINER_DIR)/Dockerfile .

## Connect into the sandbox container for dev usage
image-it:
	docker run \
	--env-file ${ENV_FILE} \
	-v $$(pwd):/var/www/${APP_NAME} \
	-v coffreo-cache:/home/coffreo/cache \
	-it ${SANDBOX_NAME}:latest

image-dev: image-build image-push

## Use telepresence to mock Kubernetes registry for dev usage
tp:
	telepresence --verbose --swap-deployment ${APP_NAME}-deployment:nginx \
	--docker-run --rm --env XDEBUG=false --env BLACKFIRE=true --env CORS_ALLOW_ORIGIN=* --env-file $$(pwd)/${ENV_FILE} \
	-v $$(pwd):/var/www/${APP_NAME} ${IMAGE}:latest

## make tp but with Xdebug enable
tpxd:
	telepresence --verbose --swap-deployment ${APP_NAME}-deployment:nginx \
	--docker-run --rm --env XDEBUG=true --env BLACKFIRE=false --env CORS_ALLOW_ORIGIN=* --env-file $$(pwd)/${ENV_FILE} \
	-v $$(pwd):/var/www/${APP_NAME} ${IMAGE}:latest

## Configure the deploy on Kubernetes
deploy-config: env
	kubectl delete configmap ${APP_NAME} || exit 0
	kubectl create configmap ${APP_NAME} --from-file=${ENV_FILE} || exit 0

## Deploy on the Kubernetes cluster
deploy:
	kubectl config get-contexts | grep ^*
	kubectl kustomize .container/deploy/${ENV} | envsubst > .container/deploy/${ENV}/deployed.yaml
	cat .container/deploy/${ENV}/deployed.yaml | kubectl apply -f -

dev: image-dev deploy-config deploy


image-name:
	echo "${IMAGE}:${TAG}" > /tmp/image-name

## ------

# COLORS
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
WHITE  := $(shell tput -Txterm setaf 7)
RESET  := $(shell tput -Txterm sgr0)

TARGET_MAX_CHAR_NUM=20

## Show this help
help:
	@echo '# ${YELLOW}${APP_NAME}${RESET} / ${GREEN}${ENV}${RESET}'
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
