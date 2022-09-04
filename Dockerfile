FROM php:8.1-fpm

RUN apt-get update && apt-get install -y libzip-dev git
RUN docker-php-ext-install zip

# Setup composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
RUN alias composer='php /usr/bin/composer'

# Install, configure and enable xdebug
RUN mkdir /var/log/xdebug/ && \
    pecl install xdebug && \
    echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.start_with_request=trigger" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.mode=develop,debug,profile,trace" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.log=/var/log/xdebug/xdebug.log" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    echo "xdebug.output_dir=/var/log/xdebug/" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
    docker-php-ext-enable xdebug
