FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && rm -rf /var/lib/apt/lists/*

ENV PORT=8080
EXPOSE 8080

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT} -t /var/www/html /var/www/html/server.php"]
