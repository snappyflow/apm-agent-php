ARG PHP_VERSION=7.2
FROM php:${PHP_VERSION}-fpm-buster

RUN apt-get -qq update \
 && apt-get -qq install -y \
    autoconf \
    build-essential \
    curl \
    libcmocka-dev \
    libcurl4 \
    libcurl4-openssl-dev \
    procps \
    rsyslog \
    unzip \
    wget \
    --no-install-recommends \
 && rm -rf /var/lib/apt/lists/*

COPY . /app

WORKDIR /app/agent/native/ext

ENV NO_INTERACTION=1

# Disable agent for auxiliary PHP processes to reduce noise in logs
ENV ELASTIC_APM_ENABLED=false

# Create a link to extensions directory to make it easier accessible (paths are different between php releases)
RUN ln -s `find /usr/local/lib/php/extensions/ -name opcache.so | head -n1 | xargs dirname` /tmp/extensions
