ARG PHP_VERSION=7.2
FROM php:${PHP_VERSION}-fpm

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

WORKDIR /app/src/ext

ENV NO_INTERACTION=1

# C call stack capture should be supported on non-Alpine by default
ENV ELASTIC_APM_ASSUME_CAN_CAPTURE_C_STACK_TRACE=true

# Disable agent for auxiliary PHP processes to reduce noise in logs
ENV ELASTIC_APM_ENABLED=false

CMD phpize \
    && CFLAGS="-std=gnu99" ./configure --enable-elastic_apm \
    && make clean \
    && make && \
    cp -r ./* /output
