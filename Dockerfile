FROM php:8.2-apache

# ----------------
# PHP extensions
# ----------------
RUN docker-php-ext-install mysqli pdo pdo_mysql

# ----------------
# Utilities
# ----------------
RUN apt-get update && apt-get install -y \
    git wget unzip python3 python3-pip supervisor curl \
    && rm -rf /var/lib/apt/lists/*

# ----------------
# Go for tachoparser
# ----------------
RUN wget https://golang.org/dl/go1.21.0.linux-amd64.tar.gz \
    && tar -C /usr/local -xzf go1.21.0.linux-amd64.tar.gz \
    && rm go1.21.0.linux-amd64.tar.gz \
    && export PATH=$PATH:/usr/local/go/bin

# ----------------
# Build tachoparser
# ----------------
RUN git clone https://github.com/traconiq/tachoparser.git /tachoparser \
    && cd /tachoparser/cmd/dddparser \
    && go build -o dddparser ./ \
    && mv dddparser /usr/local/bin/dddparser

# ----------------
# Certificates (PKS1 + PKS2)
# ----------------
RUN mkdir -p /tachoparser/pks1 /tachoparser/pks2 \
    && cd /tachoparser/pks1 \
    && wget https://github.com/traconiq/tachoparser/raw/main/scripts/pks1/dl_all_pks1.py \
    && pip3 install --user lxml requests \
    && python3 dl_all_pks1.py \
    && cd /tachoparser/pks2 \
    && wget https://github.com/traconiq/tachoparser/raw/main/scripts/pks2/dl_all_pks2.py \
    && python3 dl_all_pks2.py

# ----------------
# Copy PHP source
# ----------------
COPY src/ /var/www/html/

# ----------------
# Uploads folder
# ----------------
RUN mkdir -p /var/www/html/uploads && chmod 777 /var/www/html/uploads

# ----------------
# Expose Apache
# ----------------
EXPOSE 80
