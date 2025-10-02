FROM php:8.2-apache

# ----------------
# PHP extensions + utilities
# ----------------
RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && apt-get update && apt-get install -y \
        wget unzip python3 python3-pip git supervisor default-mysql-server curl \
    && rm -rf /var/lib/apt/lists/*

# ----------------
# Install Go
# ----------------
RUN wget https://golang.org/dl/go1.21.0.linux-amd64.tar.gz \
    && tar -C /usr/local -xzf go1.21.0.linux-amd64.tar.gz \
    && rm go1.21.0.linux-amd64.tar.gz

# Add Go to PATH for all future RUN commands
ENV PATH="/usr/local/go/bin:${PATH}"

# ----------------
# Build tachoparser
# ----------------
RUN git clone https://github.com/traconiq/tachoparser.git /tachoparser \
    && cd /tachoparser/cmd/dddparser \
    && go build -o dddparser ./ \
    && mv dddparser /usr/local/bin/dddparser

# ----------------
# Download PKS1 + PKS2 certificates
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

# Expose Apache + MySQL ports
EXPOSE 80 3306

CMD ["/usr/bin/supervisord"]
