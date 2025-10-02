# ------------------------
# Stage 1: Build dddparser
# ------------------------
FROM golang:1.21-bullseye AS builder

WORKDIR /build

# Install Python & dependencies for PKCS scripts
RUN apt-get update && apt-get install -y python3 python3-pip git unzip && \
    pip3 install --user lxml requests && rm -rf /var/lib/apt/lists/*

# Clone tachoparser repo
RUN git clone https://github.com/traconiq/tachoparser.git tachoparser

WORKDIR /build/tachoparser/scripts

# Create certificate folders
RUN mkdir -p ../internal/pkg/certificates/pks1 ../internal/pkg/certificates/pks2

# Download PKS1 + PKS2 certificates
RUN cd pks1 && python3 dl_all_pks1.py && cd .. && \
    cd pks2 && python3 dl_all_pks2.py && cd ..

# Build dddparser binary
WORKDIR /build/tachoparser/cmd/dddparser
RUN go build -o dddparser

# ------------------------
# Stage 2: PHP + Apache + MySQL + phpMyAdmin
# ------------------------
FROM php:8.2-apache

# Enable PHP mysqli
RUN docker-php-ext-install mysqli

# Install MySQL server, supervisord, wget, unzip
RUN apt-get update && apt-get install -y \
        mariadb-server \
        supervisor \
        wget \
        unzip \
    && rm -rf /var/lib/apt/lists/*

# Copy dddparser binary from builder
COPY --from=builder /build/tachoparser/cmd/dddparser/dddparser /usr/local/bin/dddparser

# Create uploads folder
RUN mkdir -p /var/www/html/uploads && chown www-data:www-data /var/www/html/uploads

# Copy PHP webapp
COPY src/ /var/www/html/

# Install phpMyAdmin (for dev convenience)
RUN wget https://www.phpmyadmin.net/downloads/phpMyAdmin-latest-all-languages.zip -O /tmp/pma.zip \
    && unzip /tmp/pma.zip -d /var/www/html/ \
    && mv /var/www/html/phpMyAdmin-*-all-languages /var/www/html/phpmyadmin \
    && rm /tmp/pma.zip

# Supervisord config
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expose ports
EXPOSE 80 3306

# Start supervisord (runs Apache + MySQL)
CMD ["/usr/bin/supervisord"]

