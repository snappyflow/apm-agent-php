ARG PHP_VERSION=7.2
FROM php:${PHP_VERSION}-fpm

RUN apt-get -qq update \
 && apt-get -qq install -y \
    autoconf \
    build-essential \
    curl \
    libcmocka-dev \
    libcurl4-openssl-dev \
    procps \
    rsyslog \
    unzip \
    wget \
    --no-install-recommends \
 && rm -rf /var/lib/apt/lists/*

COPY . /app

WORKDIR /app/src/ext

CMD phpize \
    && CFLAGS="-std=gnu99" ./configure --enable-elastic_apm && \
    make clean && make && \
    cp -r ./* /output
