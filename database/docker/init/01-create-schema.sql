-- Cria o schema do projeto com o nome do desenvolvedor.
-- Este script roda uma vez, quando o container do PostgreSQL é criado
-- (montado em /docker-entrypoint-initdb.d); as migrations do Laravel
-- criam as tabelas dentro deste schema.

CREATE SCHEMA IF NOT EXISTS leonardo;
