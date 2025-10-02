FROM php:8.2-apache

# ----------------
# System utilities + PHP extensions
# ----------------
RUN apt-get update && apt-get install -y \
        default-mysql-server \
        wget unzip git supervisor python3 python3-pip \
        python3-lxml python3-requests \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# ----------------
# Install Go
# ----------------
RUN wget https://golang.org/dl/go1.21.0.linux-amd64.tar.gz \
    && tar -C /usr/local -xzf go1.21.0.linux-amd64.tar.gz \
    && rm go1.21.0.linux-amd64.tar.gz
ENV PATH="/usr/local/go/bin:${PATH}"

# ----------------
# Clone tachoparser
# ----------------
RUN git clone https://github.com/traconiq/tachoparser.git /tachoparser

# ----------------
# Create certificate folders
# ----------------
RUN mkdir -p /tachoparser/internal/pkg/certificates/pks1 \
    && mkdir -p /tachoparser/internal/pkg/certificates/pks2 \
    && mkdir -p /tachoparser/pks1 \
    && mkdir -p /tachoparser/pks2

# ----------------
# Download PKS1 + PKS2 scripts and run them
# ----------------
# PKS1
RUN cd /tachoparser/pks1 \
    && wget https://github.com/traconiq/tachoparser/raw/main/scripts/pks1/dl_all_pks1.py \
    && python3 dl_all_pks1.py

# PKS2
RUN cd /tachoparser/pks2 \
    && wget https://github.com/traconiq/tachoparser/raw/main/scripts/pks2/dl_all_pks2.py \
    && python3 dl_all_pks2.py

# ----------------
# Build tachoparser binary
# ----------------
RUN cd /tachoparser/cmd/dddparser \
    && go build -o dddparser ./ \
    && mv dddparser /usr/local/bin/dddparser

# ----------------
# Copy PHP source code
# ----------------
COPY src/ /var/www/html/

# ----------------
# Uploads folder
# ----------------
RUN mkdir -p /var/www/html/uploads && chmod 777 /var/www/html/uploads

# ----------------
# Install phpMyAdmin (dev convenience)
# ----------------
RUN wget https://www.phpmyadmin.net/downloads/phpMyAdmin-latest-all-languages.zip \
    && unzip phpMyAdmin-latest-all-languages.zip -d /var/www/html/ \
    && mv /var/www/html/phpMyAdmin-*-all-languages /var/www/html/phpmyadmin \
    && rm phpMyAdmin-latest-all-languages.zip

# ----------------
# Supervisor to run Apache + MySQL
# ----------------
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ----------------
# Expose Apache + MySQL ports
# ----------------
EXPOSE 80 3306

CMD ["/usr/bin/supervisord"]
