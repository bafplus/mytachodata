# ------------------------
# Stage 1: Build dddparser
# ------------------------
FROM golang:1.21-bullseye AS builder

WORKDIR /build

# Install Python & dependencies for PKCS scripts
RUN apt-get update && apt-get install -y python3 python3-pip git unzip && \
    pip3 install lxml requests && rm -rf /var/lib/apt/lists/*

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
# Stage 2: PHP + Apache
# ------------------------
FROM php:8.2-apache

# Enable PHP mysqli
RUN docker-php-ext-install mysqli

# Copy dddparser binary from builder
COPY --from=builder /build/tachoparser/cmd/dddparser/dddparser /usr/local/bin/dddparser

# Create uploads folder with proper permissions
RUN mkdir -p /var/www/html/uploads && chown www-data:www-data /var/www/html/uploads

# Copy PHP webapp
COPY src/ /var/www/html/

# Expose Apache port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
