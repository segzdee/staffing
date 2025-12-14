# [BASE STAGE]
FROM php:7.4-fpm-alpine as base

# Install the php extension installer from its image
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Install dependencies
RUN apk add --no-cache  \
    openssl \
    ca-certificates \
    libxml2-dev \
    oniguruma-dev

# Install php extensions
RUN install-php-extensions \
    bcmath \
    ctype \
    dom \
    fileinfo \
    mbstring \
    pdo pdo_mysql \
    tokenizer \
    pcntl \
    gd \
    zip \
    redis-stable

# Install the composer packages using www-data user
WORKDIR /app
RUN chown www-data:www-data /app
COPY --chown=www-data:www-data . .
COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer
USER www-data
#RUN composer artisan optimize:clear  && composer dumpautoload && artisan optimize
RUN composer install --prefer-dist
# [END BASE STAGE]

# [FRONTEND STAGE]
FROM node:20-alpine as frontend
WORKDIR /app
COPY --from=base /app /app
COPY . .
RUN apk add --no-progress --quiet --no-cache git \
    && npm install \
    && npm install -D @tailwindcss/forms \
    && npm run prod
# [END FRONTEND STAGE]

# [APP STAGE]
FROM base as app

# Prepare the frontend files & caching
COPY --from=frontend --chown=www-data:www-data /app/public /app/public
RUN php artisan view:cache
RUN php artisan optimize:clear
#    && php artisan migrate --no-interaction \
#   && php artisan passport:keys

# Setup nginx & supervisor as root user
USER root
RUN apk add --no-progress --quiet --no-cache nginx supervisor
COPY .docker/nginx-default.conf /etc/nginx/http.d/default.conf
COPY .docker/supervisord.conf /etc/supervisord.conf

# Apply the required changes to run nginx as www-data user
RUN chown -R www-data:www-data /run/nginx /var/lib/nginx /var/log/nginx && \
    sed -i '/user nginx;/d' /etc/nginx/nginx.conf

# Switch to www-user
USER www-data
EXPOSE 8000
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
# [END APP STAGE]

# DEFAULT STAGE
#FROM base
