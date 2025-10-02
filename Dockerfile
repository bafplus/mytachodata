# ------------------------
# Stage 1: Build dddparser
# ------------------------
FROM golang:1.21-bullseye AS builder

WORKDIR /build

# Install Python & dependencies for PKS scripts
RUN apt-get update && apt-get install -y python3 python3-pip git unzip wget && \
    pip3 install lxml requests && rm -rf /var/lib/apt/lists/*

# Clone tachoparser repo
RUN git clone https://github.com/traconiq/tachoparser.git tachoparser

WORKDIR /build/tachoparser/scripts

# Download PKS1 + PKS2 certificates
RUN mkdir -p pks1 pks2 \
    && cd pks1 && python3 dl_all_pks1.py && cd .. \
    && cd pks2 && python3 dl_all_pks2.py && cd ..

# Build dddparser
WORKDIR /build/tachoparser/cmd/dddparser
RUN go build -o dddparser

# ------------------------
# Stage 2: PHP + Apache + MariaDB + phpMyAdmin
# ------------------------
FROM php:8.2-apache

# Install necessary packages
RUN apt-get update && apt-get install -y \
        mariadb-server \
        supervisor \
        unzip \
        wget \
        curl \
        less \
        vim \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy dddparser binary
COPY --from=builder /build/tachoparser/cmd/dddparser/dddparser /usr/local/bin/dddparser

# Create folders
RUN mkdir -p /var/www/html/uploads /var/www/html/node /var/www/html/phpmyadmin /var/log/supervisor && \
    chown -R www-data:www-data /var/www/html/uploads

# Copy PHP webapp
COPY src/ /var/www/html/

# Download phpMyAdmin (latest stable)
RUN wget https://www.phpmyadmin.net/downloads/phpMyAdmin-latest-all-languages.zip -O /tmp/pma.zip && \
    unzip /tmp/pma.zip -d /var/www/html/phpmyadmin --strip-components=1 && \
    rm /tmp/pma.zip

# Copy supervisord configuration
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expose Apache
EXPOSE 80

# Start supervisord
CMD ["/usr/bin/supervisord", "-n"]
