FROM nginx:latest AS base
WORKDIR /var/www/html
RUN apt-get update && apt-get upgrade -y && apt-get install -y php-xml php-cli curl git && apt-get clean
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash
RUN apt-get install symfony-cli

FROM base AS api 
COPY CaretakerServicesApi ./CaretakerServicesApi
WORKDIR /var/www/html/CaretakerServicesApi
RUN composer install --no-scripts --no-autoloader

FROM base AS web
COPY CaretakerServicesWeb ./CaretakerServicesWeb
WORKDIR /var/www/html/CaretakerServicesWeb
RUN composer install --no-scripts --no-autoloader


