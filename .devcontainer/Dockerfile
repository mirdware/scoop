FROM node:20-alpine AS node
FROM webdevops/php-dev:8.2-alpine

COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=node /usr/local/bin/node /usr/local/bin/node
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

COPY ./etc/  /opt/docker/etc/
WORKDIR /app
EXPOSE 8001 8000
