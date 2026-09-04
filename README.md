# AutoCare
@Leonardo Alves
Sistema de controle de revisões de veículos, feito como teste prático para vaga de Desenvolvedor Web Júnior.

O projeto é uma aplicação completa: uma API REST em Laravel (com PostgreSQL) e um frontend em Vue.js que consome essa API. O sistema cadastra pessoas (proprietárias), veículos e revisões, mostra um painel com indicadores e gráficos, e tem 12 relatórios prontos para consulta.

## O que o sistema faz

- CRUD completo de pessoas, veículos e revisões (o veículo pertence a uma pessoa; a revisão pertence a um veículo)
- Dashboard com indicadores (total de pessoas, veículos, revisões e gastos) e três gráficos
- 12 relatórios: veículos, pessoas, revisões por período, marcas, próximas revisões estimadas, entre outros
- Validação dos dados no backend (Form Requests) e no frontend, com mensagens amigáveis
- Erros registrados em log para facilitar o diagnóstico

## Tecnologias

| O quê | Como |
|---|---|
| Backend | PHP 8.4, Laravel 11 |
| Banco de dados | PostgreSQL 17 |
| Frontend | Vue 2.7 (Options API), Vue Router 3, Axios |
| Estilo e gráficos | Tailwind CSS 4, Chart.js 4 |
| Build | Vite 5 (com plugin Vue 2) |
| Testes | PHPUnit 10 (SQLite em memória) |
| Dados de exemplo | Faker (pt_BR) |
| Infra | Docker Compose (container `app` + PostgreSQL) |

## Requisitos

- Docker Desktop (o projeto roda inteiro em containers)
- Node.js 18+ (só para compilar os assets do frontend, com Vite)

Não é preciso instalar PHP nem PostgreSQL na máquina: o Docker cuida disso. O diretório do projeto é montado dentro do container, então qualquer alteração no código já vale no próximo acesso.

## Como rodar

```bash
# 1. Subir os containers (a primeira vez baixa as imagens e demora mais)
docker compose up -d

# 2. Instalar as dependências do PHP (dentro do container)
docker compose exec app composer install

# 3. Configurar o ambiente
cp .env.example .env

# 4. Criar as tabelas e inserir os dados de exemplo
docker compose exec app php artisan migrate:fresh --seed

# 5. Compilar o frontend (na máquina, não no container)
npm install
npm run build

# Pronto: http://localhost:8000
```

Detalhes do `.env` que vale conhecer:

- `DB_SCHEMA=leonardo` — as tabelas são criadas dentro do schema `leonardo` no PostgreSQL, como o teste pede
- `SESSION_DRIVER=file` e `CACHE_STORE=file` — sem Redis, para a instalação ser simples

## Banco de dados

Estrutura criada pelas migrations em `database/migrations/`:

- **pessoas**: nome, cpf (único), sexo, data_nascimento, telefone, email (único)
- **veiculos**: pessoa_id (chave estrangeira), marca, modelo, ano, placa (única)
- **revisoes**: veiculo_id (chave estrangeira), data_revisao, quilometragem, descricao, valor, observacoes

Regras de exclusão: pessoa com veículos não pode ser excluída, e veículo com revisões também não. Além do bloqueio na API (com mensagem amigável), o banco reforça a regra com `RESTRICT`.

### Dados de exemplo

O seeder (`database/seeders/DatabaseSeeder.php`) cria:

- 20 pessoas: metade homens, metade mulheres, idades variadas
- Veículos de 10 marcas (Fiat, Volkswagen, Chevrolet, Toyota...); as 16 primeiras pessoas recebem de 1 a 3 veículos e as 4 últimas ficam sem nenhum — de propósito, para os relatórios de contagem terem variação
- 60+ revisões, de 1 a 6 por veículo, com quilometragem crescente e datas até 2 anos atrás

### Dados em volume

Para os relatórios ficarem bem preenchidos, o seeder `DadosEmVolumeSeeder` gera um volume grande de dados: 200 pessoas, em torno de 360 veículos e mais de 1.000 revisões, com datas espalhadas pelos últimos 3 anos.

```bash
docker compose exec app php artisan db:seed --class=DadosEmVolumeSeeder
```

Ele pode rodar depois do seeder padrão (os dados se somam) ou sozinho, num banco recém-criado.

## API

Base: `http://localhost:8000/api`. Todas as respostas seguem o mesmo formato:

```json
{ "success": true, "data": { ... } }
```

Em caso de erro:

```json
{ "success": false, "message": "Motivo do erro." }
```

Endpoints:

| Método | Rota | Descrição |
|---|---|---|
| GET | `/api/pessoas` | Lista as pessoas, paginada (com a contagem de veículos de cada uma) |
| POST | `/api/pessoas` | Cadastra uma pessoa |
| GET | `/api/pessoas/{id}` | Mostra uma pessoa |
| PUT | `/api/pessoas/{id}` | Edita uma pessoa |
| DELETE | `/api/pessoas/{id}` | Exclui uma pessoa (bloqueado se tiver veículos) |
| GET | `/api/veiculos?pessoa_id=5&busca=toyota` | Lista os veículos, paginada (opcional: só os de uma pessoa e/ou busca por placa, marca, modelo ou proprietário) |
| POST | `/api/veiculos` | Cadastra um veículo |
| GET | `/api/veiculos/{id}` | Mostra um veículo |
| PUT | `/api/veiculos/{id}` | Edita um veículo |
| DELETE | `/api/veiculos/{id}` | Exclui um veículo (bloqueado se tiver revisões) |
| GET | `/api/revisoes?veiculo_id=8` | Lista as revisões, paginada (opcional: só as de um veículo) |
| POST | `/api/revisoes` | Cadastra uma revisão |
| GET | `/api/revisoes/{id}` | Mostra uma revisão |
| PUT | `/api/revisoes/{id}` | Edita uma revisão |
| DELETE | `/api/revisoes/{id}` | Exclui uma revisão |
| GET | `/api/dashboard` | Indicadores e dados dos gráficos da página inicial |
| GET | `/api/relatorios/{nome}` | Executa um dos 12 relatórios |
| GET | `/api/manutencao` | Diz se o painel de manutenção está liberado |
| POST | `/api/manutencao/limpar` | Apaga o banco e popula de novo (exige o `MANUTENCAO_TOKEN` do `.env` no corpo; opcional `com_volume=true`) |

As três listagens (pessoas, veículos e revisões) são paginadas: aceitam `?page=` e `?per_page=` (padrão 25, teto 500) e respondem com os itens em `data` e a paginação em `meta`. O dashboard e os relatórios não paginam.

A documentação completa dos endpoints (campos, regras de validação e exemplos de resposta) está em `docs/API.md`.

## Relatórios

A tela de Relatórios reúne os 12 relatórios do teste:

1. Todos os veículos (com o proprietário)
2. Veículos por pessoa
3. Quem possui mais veículos: homens ou mulheres
4. Marcas pela quantidade de veículos
5. Marcas distintas por sexo do proprietário
6. Todas as pessoas
7. Pessoas por sexo com idade média
8. Revisões por período (filtros opcionais de data)
9. Marcas com maior número de revisões
10. Pessoas com maior número de revisões
11. Média de tempo entre revisões (por pessoa)
12. Próximas revisões estimadas

Cada relatório tem a consulta explícita no método correspondente de `app/Http/Controllers/Api/RelatorioController.php`, usando Eloquent. Para os mais complexos (11 e 12, que calculam o intervalo entre revisões), as versões em SQL puro estão em `database/reports/`, do `01-veiculos.sql` ao `12-proximas-revisoes.sql`, prontas para rodar no pgAdmin ou no psql.

Uma regra de negócio que vale destacar: a próxima revisão estimada é a última revisão + a média de dias entre as revisões anteriores do veículo. Veículo com apenas uma revisão não tem média, então usa um prazo padrão de 180 dias, definido na constante `DIAS_PADRAO_ENTRE_REVISOES`.

## Frontend

SPA em Vue 2 (Options API), organizada assim:

- `resources/js/layouts/AppLayout.vue` — estrutura com sidebar e cabeçalho
- `resources/js/components/` — as cinco telas: Dashboard, Pessoas, Veiculos, Revisoes, Relatorios
- `resources/js/router.js` — rotas do Vue Router (troca de página sem recarregar o navegador)
- `resources/js/bootstrap.js` — configuração do axios (base `/api` e token CSRF)
- `resources/js/chart.js` — helper dos gráficos Chart.js

Cada tela segue o mesmo padrão: buscar os dados da API no `mounted`, formulário para cadastro/edição e tabela para listar.

## Testes

Os testes de API ficam em `tests/Feature/` e cobrem os três CRUDs e os relatórios. Rodam em SQLite em memória — são rápidos e não dependem do PostgreSQL estar de pé:

```bash
docker compose exec app php artisan test
```

## Estrutura do projeto

```
app/Http/Controllers/Api/   # controllers da API (CRUDs, dashboard, relatórios)
app/Http/Requests/          # validações (Form Requests)
app/Models/                 # Pessoa, Veiculo, Revisao
database/migrations/        # criação das tabelas
database/seeders/           # dados de exemplo
database/reports/           # os 12 relatórios em SQL puro
resources/js/               # frontend Vue
routes/api.php              # rotas da API
docs/API.md                 # documentação da API
tests/Feature/              # testes automatizados
```
