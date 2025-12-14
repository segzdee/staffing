FROM php:7.4-fpm

RUN apt-get update -y

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Install npm
RUN apt-get install -y npm

# Install libzip
RUN apt-get install -y libzip-dev

RUN docker-php-ext-install zip
RUN docker-php-ext-enable zip

RUN docker-php-ext-install bcmath
RUN docker-php-ext-enable bcmath

# Install gd
RUN apt-get install -y \
        zlib1g-dev \
        libpng-dev \
        libjpeg-dev

RUN docker-php-ext-configure gd --with-jpeg
RUN docker-php-ext-install gd
RUN docker-php-ext-enable gd

RUN apt-get autoremove -y

# Set permissions
ARG UID

ENV UID=${UID}

RUN usermod -u ${UID} www-data
