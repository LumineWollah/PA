FROM nginx:latest AS base
RUN apt-get update && apt-get upgrade -y && apt-get install -y certbot python3-certbot-nginx curl git && apt-get clean
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash && apt-get install -y symfony-cli

FROM base AS app-api 
WORKDIR /usr/share/nginx/html/api

FROM base AS app-web
WORKDIR /usr/share/nginx/html/web
