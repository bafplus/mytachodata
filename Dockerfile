# ------------------------
# Stage 1: Build dddparser
# ------------------------
FROM golang:1.21-bullseye AS builder

WORKDIR /build

RUN apt-get update && apt-get install -y python3 python3-pip git unzip && \
    pip3 install --user lxml requests && \
    rm -rf /var/lib/apt/lists/*

# RUN git clone https://github.com/traconiq/tachoparser.git tachoparser
RUN git clone https://github.com/bafplus/tachoparser.git tachoparser

WORKDIR /build/tachoparser/scripts
RUN cd pks1 && python3 dl_all_pks1.py && cd .. && \
    cd pks2 && python3 dl_all_pks2.py && cd ..

WORKDIR /build/tachoparser/cmd/dddparser
RUN go build -o dddparser

# ------------------------
# Stage 2: PHP + Apache + MariaDB + AdminLTE
# ------------------------
FROM php:8.2-apache

# Environment variables for database
ENV DB_HOST=127.0.0.1 \
    DB_NAME=mytacho \
    DB_USER=mytacho_user \
    DB_PASS=mytacho_pass

# Install dependencies
RUN apt-get update && \
    apt-get install -y mariadb-server unzip wget git nodejs npm && \
    docker-php-ext-install mysqli pdo_mysql && \
    rm -rf /var/lib/apt/lists/*

# Copy dddparser binary
COPY --from=builder /build/tachoparser/cmd/dddparser/dddparser /usr/local/bin/dddparser

# Copy web app
COPY src/ /var/www/html/
RUN mkdir -p /var/www/html/uploads && chown www-data:www-data /var/www/html/uploads

# Install AdminLTE with plugins included
RUN wget https://github.com/ColorlibHQ/AdminLTE/archive/refs/tags/v3.2.0.zip -O /tmp/adminlte.zip && \
    unzip /tmp/adminlte.zip -d /var/www/html/ && \
    mv /var/www/html/AdminLTE-3.2.0 /var/www/html/adminlte && \
    rm /tmp/adminlte.zip && \
    chown -R www-data:www-data /var/www/html/adminlte

# Copy entrypoint
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose ports
EXPOSE 80 3306

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]