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

# Download certificates
RUN cd pks1 && python3 dl_all_pks1.py && cd .. && \
    cd pks2 && python3 dl_all_pks2.py && cd ..

# Build dddparser
WORKDIR /build/tachoparser/cmd/dddparser
RUN go build -o dddparser

# ------------------------
# Stage 2: PHP + Apache + MariaDB + phpMyAdmin
# ------------------------
FROM php:8.2-apache

# Install required packages
RUN apt-get update && \
    apt-get install -y mariadb-server unzip wget python3 python3-pip supervisor && \
    rm -rf /var/lib/apt/lists/*

# Enable PHP mysqli extension
RUN docker-php-ext-install mysqli

# Copy dddparser binary from builder
COPY --from=builder /build/tachoparser/cmd/dddparser/dddparser /usr/local/bin/dddparser

# Create folders with proper permissions
RUN mkdir -p /var/www/html/uploads && chown www-data:www-data /var/www/html/uploads

# Copy PHP webapp
COPY src/ /var/www/html/

# Download and install phpMyAdmin
RUN wget https://www.phpmyadmin.net/downloads/phpMyAdmin-latest-all-languages.zip -O /tmp/pma.zip && \
    unzip /tmp/pma.zip -d /var/www/html/ && \
    rm /tmp/pma.zip && \
    mv /var/www/html/phpMyAdmin-*-all-languages /var/www/html/phpmyadmin

# Copy supervisord config
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy entrypoint script
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose Apache port
EXPOSE 80

# Start container using our entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
