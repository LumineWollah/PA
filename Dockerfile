FROM nginx:latest AS base
WORKDIR /usr/share/nginx/html/PA
RUN apt-get update && apt-get upgrade -y && apt-get install -y php-xml php-cli php-mysqli php-zip unzip certbot python3-certbot-nginx curl git && apt-get clean
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash
RUN apt-get install symfony-cli

FROM base AS app-api 
WORKDIR /usr/share/nginx/html/CaretakerServicesApi
RUN certbot --nginx -d api.caretakerservices.fr

FROM base AS app-web
WORKDIR /usr/share/nginx/html/CaretakerServicesWeb
