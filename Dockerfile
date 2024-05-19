FROM nginx:latest AS base
WORKDIR /usr/share/nginx/html/PA
RUN apt-get update && apt-get upgrade -y && apt-get install -y php-xml php-cli php-mysqli php-zip unzip curl git && apt-get clean
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash
RUN apt-get install symfony-cli
# test



FROM base AS api 
COPY CaretakerServicesApi ./CaretakerServicesApi
WORKDIR /usr/share/nginx/html/PA/CaretakerServicesApi
RUN composer install --no-scripts --no-autoloader


FROM base AS web
COPY CaretakerServicesWeb ./CaretakerServicesWeb
WORKDIR /usr/share/nginx/html/PA/PA/CaretakerServicesWeb
RUN composer install --no-scripts --no-autoloader
