# ------------------------
# Stage 1: Build dddparser
# ------------------------
FROM golang:1.21-bullseye AS builder

WORKDIR /build

RUN apt-get update && apt-get install -y python3 python3-pip git unzip && \
    pip3 install --user lxml requests && \
    rm -rf /var/lib/apt/lists/*

RUN git clone https://github.com/traconiq/tachoparser.git tachoparser

WORKDIR /build/tachoparser/scripts
RUN cd pks1 && python3 dl_all_pks1.py && cd .. && \
    cd pks2 && python3 dl_all_pks2.py && cd ..

WORKDIR /build/tachoparser/cmd/dddparser
RUN go build -o dddparser

# ------------------------
# Stage 2: PHP + Apache + MariaDB + AdminLTE
# ------------------------
FROM php:8.2-apache

# Set database environment values
ENV DB_HOST=127.0.0.1 \
    DB_NAME=mytacho \
    DB_USER=mytacho_user \
    DB_PASS=mytacho_pass

# PHP extensions + MariaDB
RUN apt-get update && \
    apt-get install -y mariadb-server unzip wget && \
    docker-php-ext-install mysqli pdo_mysql && \
    rm -rf /var/lib/apt/lists/*

# Copy dddparser
COPY --from=builder /build/tachoparser/cmd/dddparser/dddparser /usr/local/bin/dddparser

# Web app
COPY src/ /var/www/html/
RUN mkdir -p /var/www/html/uploads && chown www-data:www-data /var/www/html/uploads

RUN wget https://github.com/ColorlibHQ/AdminLTE/archive/refs/tags/v4.0.0-rc4.zip -O /tmp/adminlte.zip && \
    unzip /tmp/adminlte.zip -d /var/www/html/ && \
    mv /var/www/html/AdminLTE-4.0.0-rc4 /var/www/html/adminlte && \
    rm /tmp/adminlte.zip && \
    chown -R www-data:www-data /var/www/html/adminlte


# phpMyAdmin
RUN wget https://www.phpmyadmin.net/downloads/phpMyAdmin-latest-all-languages.zip -O /tmp/pma.zip && \
    unzip /tmp/pma.zip -d /var/www/html/phpmyadmin && \
    rm /tmp/pma.zip
RUN chown -R www-data:www-data /var/www/html/phpmyadmin

# Configure Apache for phpMyAdmin using a proper heredoc
RUN cat << 'EOF' > /etc/apache2/conf-available/phpmyadmin.conf
<Directory "/var/www/html/phpmyadmin">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
EOF

RUN a2enconf phpmyadmin


# Entrypoint
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Ports
EXPOSE 80 3306
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
