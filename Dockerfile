ARG PHP_VERSION=7.3
FROM php:${PHP_VERSION}-cli

RUN apt-get update

# 1. development packages
RUN apt-get install -y \
    git \
    libicu-dev \
    libpq-dev \
    libgmp-dev \
    libmcrypt-dev \
    zlib1g-dev \
    libzip-dev \
    vim \
    unzip \
    bind9utils \
    && rm -r /var/lib/apt/lists/* \
    && docker-php-ext-install \
        intl \
        mbstring \
        pcntl \
        zip \
        opcache \
        gmp \
        bcmath

# 3. composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

# 5. we need a user with the same UID/GID with host user
RUN useradd -u 1000 -d /home/devuser devuser
RUN mkdir -p /home/devuser/.composer && \
    chown -R devuser:devuser /home/devuser
USER devuser
WORKDIR /home/devuser/package
