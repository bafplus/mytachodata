FROM php:8.2-apache

# PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Install utilities
RUN apt-get update && apt-get install -y \
    git wget unzip supervisor curl \
    && rm -rf /var/lib/apt/lists/*

# Install Go (for tachoparser build)
RUN wget https://golang.org/dl/go1.21.0.linux-amd64.tar.gz \
    && tar -C /usr/local -xzf go1.21.0.linux-amd64.tar.gz \
    && rm go1.21.0.linux-amd64.tar.gz \
    && export PATH=$PATH:/usr/local/go/bin

# Build tachoparser automatically
RUN git clone https://github.com/traconiq/tachoparser.git /tachoparser \
    && cd /tachoparser/cmd/dddparser \
    && go build -o dddparser ./ \
    && mv dddparser /usr/local/bin/dddparser

# Copy PHP source code
COPY src/ /var/www/html/

# Expose Apache port
EXPOSE 80
