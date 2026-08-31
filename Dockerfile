# ============================================================
# Imagem para deploy (Render / qualquer host Docker).
#
# Multi-stage:
#   1. node      -> compila os assets do Vue/Vite (public/build)
#   2. composer  -> instala as dependências do PHP (vendor/)
#   3. php       -> imagem final com a aplicação pronta
#
# O docker-compose.yml local NÃO usa esta imagem: ele monta o código
# como volume (estilo dev). Esta imagem é autossuficiente (produção).
# ============================================================

# ---- Estágio 1: assets do frontend (Vue + Vite) ----
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---- Estágio 2: dependências PHP ----
# --no-scripts: o artisan ainda não existe nesta etapa; o
# package:discover roda no start.sh, antes de subir o servidor.
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# ---- Estágio 3: runtime final ----
FROM php:8.4-cli

# libpq-dev: biblioteca para o PHP se comunicar com o PostgreSQL
# pdo_pgsql: extensão PDO que o Laravel usa na conexão com o banco
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Código da aplicação (o .dockerignore exclui vendor, node_modules, .env...)
COPY . .
# Dependências PHP e assets compilados vindos dos estágios anteriores
COPY --from=vendor /app/vendor vendor
COPY --from=assets /app/public/build public/build

# Porta usada pelo Render ($PORT = 10000)
EXPOSE 10000

# start.sh: package:discover, migrations, seed (uma vez) e servidor
CMD ["sh", "start.sh"]
