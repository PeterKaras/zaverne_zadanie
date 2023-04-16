FROM php:8.1-apache

ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf
RUN a2enmod rewrite

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN apt-get update && apt-get install -y nano apt-utils zip unzip

ARG extensions="intl opcache pdo_mysql"

RUN install-php-extensions ${extensions}

COPY composer.json /var/www/html
COPY composer.lock /var/www/html
COPY bin/console /var/www/html
RUN composer install --no-dev --no-interaction --no-plugins --no-progress --no-scripts --optimize-autoloader

COPY . /var/www/html

RUN chown -R www-data /var/www/html

USER www-data