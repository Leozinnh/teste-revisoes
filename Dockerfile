# Imagem do PHP com o necessário para o projeto.
# php-cli já traz o essencial; o "artisan serve" atua como servidor web.
# (8.4+ porque as versões atuais das dependências exigem; o requisito é PHP 8.1+)
FROM php:8.4-cli

# libpq-dev: biblioteca para o PHP se comunicar com o PostgreSQL
# pdo_pgsql: extensão PDO que o Laravel usa na conexão com o banco
RUN apt-get update \
    && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo_pgsql

# Composer: gerenciador de dependências PHP
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Servidor embutido do PHP, simples de subir; em produção o padrão
# seria nginx + php-fpm.
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]
