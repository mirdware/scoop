FROM node:22-alpine3.20 AS node
FROM webdevops/php-dev:8.4-alpine

COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=node /usr/local/bin/node /usr/local/bin/node
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

COPY ./docker/  /opt/docker/
WORKDIR /app
EXPOSE 8000
