ARG ALPINE_VERSION=3.10
ARG PHP_VERSION=7
ARG PHP_BUILD_DEPS="php${PHP_VERSION} \
    php${PHP_VERSION}-ctype \
    php${PHP_VERSION}-iconv \
    php${PHP_VERSION}-json \
    php${PHP_VERSION}-session \
    php${PHP_VERSION}-simplexml \
    php${PHP_VERSION}-tokenizer \
    php${PHP_VERSION}-phar \
    php${PHP_VERSION}-openssl \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-xmlwriter \
    php${PHP_VERSION}-dom \
    php${PHP_VERSION}-mbstring \
    "

#######------- multi-staging builder -------#######
FROM alpine:${ALPINE_VERSION} AS builder
ARG COFFREO_ENV
ARG BUILDING=true
ARG PHP_BUILD_DEPS
ARG ID_RSA
ARG PHP_VERSION
ARG UID
ARG APP_HOME
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_NO_INTERACTION=1
ENV COMPOSER_MEMORY_LIMIT=-1
ENV COMPOSER_HOME=/home/coffreo/cache/.composer
ENV COFFREO_LOCALE=fr
ENV COFFREO_SF_LOG=php://stdout

RUN set -xe; \
    apk add --no-cache \
    git \
    openssh-client \
    bash make ncurses \
    curl \
    ${PHP_BUILD_DEPS} \
    php${PHP_VERSION}-xdebug \
# TEMPORARY FIX needed for phpinsight caused by wrong iconv charset https://github.com/nunomaduro/phpinsights/issues/43
    && apk add --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/edge/community/ --allow-untrusted gnu-libiconv \
    && apk add php7-pear php7-dev gcc musl-dev\
    && pecl install mongodb \
    && echo "extension=mongodb.so" >> /etc/php${PHP_VERSION}/php.ini \
    && echo "zend_extension=xdebug.so" > /etc/php${PHP_VERSION}/conf.d/xdebug.ini  \
    && adduser -D -u ${UID} coffreo \
    && curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer \
    && composer global require hirak/prestissimo --no-plugins --no-scripts \
    && mkdir -p ${APP_HOME} \
    && chown -R coffreo: ${COMPOSER_HOME} ${APP_HOME}

# same reason #TEMPORARY FIX needed for phpinsight caused by wrong iconv charset https://github.com/nunomaduro/phpinsights/issues/43
ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php

WORKDIR ${APP_HOME}
USER coffreo
RUN mkdir ~/.ssh \
    && echo "${ID_RSA}" > ~/.ssh/id_rsa \
    && set -xe; \
    chmod 600 ~/.ssh/id_rsa \
    && ssh-keyscan -p9922 -t ecdsa git.coffreo.com >> ~/.ssh/known_hosts \
    && mkdir src

#######------- Install Php dependencies -------#######
COPY --chown=coffreo composer.json composer.lock Makefile ./
RUN make install
COPY --chown=coffreo . Makefile ./
RUN composer run auto-scripts

#######------- Final image -------#######
FROM docker-registry.coffreo.com/coffreo/coffreo-container/onbuild-nginx-fpm7-alpine-310:latest
ARG PHP_BUILD_DEPS
USER root

# install runtimes dependencies
RUN set -xe; \
    apk add --no-cache \
    ${PHP_BUILD_DEPS}

USER coffreo
COPY --chown=coffreo --from=builder ${APP_HOME} .
