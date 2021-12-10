FROM node:16-alpine AS node
FROM webdevops/php-dev:8.0-alpine

COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=node /usr/local/bin/node /usr/local/bin/node
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

WORKDIR /app
COPY . .
RUN composer install -d /app && npm install && echo 'display_errors = 1' > /opt/docker/etc/php/php.ini
EXPOSE 8001 8000

COPY --chown=application:application . .
ENTRYPOINT ["npm", "run", "dev"]
