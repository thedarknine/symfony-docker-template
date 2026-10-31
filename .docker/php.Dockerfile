ARG PHP_VERSION=8.4

FROM php:${PHP_VERSION}-fpm-alpine AS base

SHELL ["/bin/ash", "-o", "pipefail", "-c"]

RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    libxslt-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    libzip-dev \
    zip \
    unzip \
    jq \
    nodejs \
    npm \
    bash \
    make \
    shellcheck \
    shfmt \
    yamllint \
    chromium \
    chromium-chromedriver

# Install php extensions using mlocati/php-extension-installer
# This is the recommended tool to manage dependencies and extensions
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
    pcov \
    pdo \
    pdo_mysql \
    gd \
    opcache \
    intl \
    dom \
    mbstring \
    xsl \
    exif \
    pcntl \
    bcmath \
    zip

# Get Composer (official image frozen on a major version for stability)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Symfony CLI
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.alpine.sh' | bash \
    && apk add --no-cache symfony-cli

# Custom PHP configuration
COPY php/php.ini "$PHP_INI_DIR/conf.d/zz-app.ini"

# Set working directory
WORKDIR /var/www/html

# Security: Never run container as root in production
USER www-data
