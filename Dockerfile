FROM php:8.3-apache
ENV DOCKERIZE_VERSION v0.9.3
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    && apt-get install -y wget \
    && wget -O - https://github.com/jwilder/dockerize/releases/download/$DOCKERIZE_VERSION/dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz | tar xzf - -C /usr/local/bin \
    && apt-get autoremove -yqq --purge wget && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY wallet-app/ ./
RUN composer install --optimize-autoloader
RUN chmod -R 755 /var/www/html && \
    chown -R www-data:www-data /var/www/html
RUN chmod -R 775 /var/www/html/storage && \
    chmod -R 775 /var/www/html/storage/logs && \
    chmod -R 775 /var/www/html/bootstrap/cache && \
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod +x /var/www/html/entrypoint.sh

RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

RUN a2enmod rewrite

EXPOSE 80

ENTRYPOINT ["/var/www/html/entrypoint.sh"]
