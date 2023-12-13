FROM node:20-alpine AS node
FROM webdevops/php-dev:8.1-alpine

COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=node /usr/local/bin/node /usr/local/bin/node
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

COPY ./php.ini /opt/docker/etc/php/php.ini
WORKDIR /app
EXPOSE 8001 8000
