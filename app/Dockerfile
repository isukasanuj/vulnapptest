FROM php:8.3-apache

# PDO MySQL driver
RUN docker-php-ext-install pdo pdo_mysql

# Apache: mod_rewrite + custom docroot/vhost
RUN a2enmod rewrite
COPY vhost.conf /etc/apache2/sites-available/000-default.conf

# Hardening that intentionally REMOVES the "PHP version disclosure" finding
# (expose_php=Off => no X-Powered-By header). Other settings are deliberately
# left at insecure-but-realistic values for the practice scenario.
COPY php.ini /usr/local/etc/php/conf.d/zz-cloudinsure.ini

COPY src/ /var/www/html/

RUN mkdir -p /var/www/html/storage/uploads \
    && chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 775 /var/www/html/storage

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80
ENTRYPOINT ["docker-entrypoint.sh"]
