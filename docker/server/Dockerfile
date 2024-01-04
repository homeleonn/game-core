FROM ubuntu:20.04

RUN apt update && \
    apt install -y software-properties-common && \
    add-apt-repository ppa:ondrej/php

RUN apt update && apt install -y php8.0-fpm php8.0-mysql php8.0-redis
