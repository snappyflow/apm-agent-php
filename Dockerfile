ARG PHP_VERSION=7.0
ARG UBUNTU_VERSION=16.04
FROM ubuntu:$UBUNTU_VERSION

ARG PHP_VERSION
ARG UBUNTU_VERSION

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get -qq update \
 && apt-get -qq install -y \
    autoconf \
    build-essential \
    curl \
    libcmocka-dev \
    procps \
    rsyslog \
    unzip \
    wget \
    software-properties-common

RUN echo  "$PHP_VERSION  $UBUNTU_VERSION"

RUN /bin/bash -c "if [[ \"$PHP_VERSION\" == \"7.0\" && \"$UBUNTU_VERSION\" == \"16.04\" ]] ; \
    then apt-get --qq install -y libcurl3 libcurl3-openssl-dev ; \
    else apt-get --qq install -y libcurl4 libcurl4-openssl-dev ;\
    fi"

RUN /bin/bash -c "if [[ \"$PHP_VERSION\" == \"7.0\" && \"$UBUNTU_VERSION\" == \"16.04\" ]] ; \
    then apt-get install -y language-pack-en-base; \
    fi"

RUN  apt-get -qq install --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

RUN LC_ALL=en_US.UTF-8 add-apt-repository ppa:ondrej/php && \
    apt-get -qq update

RUN apt-get install -y php$PHP_VERSION php$PHP_VERSION-dev

COPY . /app

WORKDIR /app/src/ext

CMD phpize \
    && CFLAGS="-std=gnu99" ./configure --enable-elastic_apm && \
    make clean && make && \
    cp -r ./* /output
