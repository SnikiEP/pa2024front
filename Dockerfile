FROM php:7.4-apache

RUN docker-php-ext-install pdo pdo_mysql

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html/languages \
    && chmod -R 775 /var/www/html/languages

    RUN echo "ServerName ddns.callidos-mtf.fr" >> /etc/apache2/apache2.conf

EXPOSE 80
