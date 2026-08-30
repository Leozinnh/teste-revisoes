# Documentação da API

Todas as rotas começam com `/api` e retornam **JSON**.

## Formato das respostas

**Sucesso:**

```json
{
  "success": true,
  "data": { }
}
```

**Erro (validação, registro não encontrado, etc.):**

```json
{
  "success": false,
  "message": "Existem dados inválidos no formulário.",
  "errors": { "campo": ["mensagem"] }
}
```

O campo `errors` só aparece quando há erros de validação (HTTP 422).

## Códigos de erro usados

| Código | Situação |
|---|---|
| 422 | Dados inválidos (validação do Form Request) |
| 404 | Registro não encontrado |
| 409 | Exclusão bloqueada (registro possui itens relacionados) |
| 500 | Erro inesperado (a mensagem é amigável; o detalhe vai para o log) |

---

## Pessoas

### `GET /api/pessoas`

Lista as pessoas em ordem alfabética, cada uma com a quantidade de veículos.

**Resposta:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nome": "Ana Oliveira",
      "cpf": "123.456.789-00",
      "sexo": "F",
      "data_nascimento": "1995-07-15",
      "telefone": "(31) 97777-6666",
      "email": "ana@exemplo.com",
      "veiculos_count": 2
    }
  ]
}
```

### `POST /api/pessoas`

Cadastra uma pessoa.

| Campo | Tipo | Obrigatório | Regra |
|---|---|---|---|
| `nome` | texto | sim | mínimo 3 caracteres |
| `cpf` | texto | sim | único, formato `000.000.000-00` |
| `sexo` | texto | sim | apenas `M` ou `F` |
| `data_nascimento` | data | sim | `AAAA-MM-DD`, não pode ser no futuro |
| `telefone` | texto | não | |
| `email` | texto | não | formato de e-mail, único |

**Resposta (201):**

```json
{
  "success": true,
  "data": { "id": 5, "nome": "Maria da Silva", "cpf": "111.222.333-44", "sexo": "F", "data_nascimento": "1990-05-10", "telefone": "(11) 99999-1234", "email": "maria@exemplo.com", "veiculos_count": 0 }
}
```

### `GET /api/pessoas/{id}`

Retorna uma pessoa específica.

### `PUT /api/pessoas/{id}`

Atualiza a pessoa. Aceita os mesmos campos do cadastro; o `cpf` e o `email`
podem ser mantidos iguais (a regra de unicidade ignora o próprio registro).

### `DELETE /api/pessoas/{id}`

Exclui a pessoa. Se ela tiver veículos, responde **409**:

```json
{
  "success": false,
  "message": "Não é possível excluir esta pessoa, pois ela possui veículos cadastrados."
}
```

---

## Veículos

### `GET /api/veiculos`

Lista os veículos com o nome do proprietário. Aceita o filtro opcional
`?pessoa_id=3` para listar só os veículos daquela pessoa.

**Resposta:**

```json
{
  "success": true,
  "data": [
    {
      "id": 12,
      "pessoa_id": 3,
      "marca": "Toyota",
      "modelo": "Corolla",
      "ano": 2020,
      "placa": "XYZ-9999",
      "pessoa": { "id": 3, "nome": "João Santos" }
    }
  ]
}
```

### `POST /api/veiculos`

Cadastra um veículo **obrigatoriamente vinculado a uma pessoa**.

| Campo | Tipo | Obrigatório | Regra |
|---|---|---|---|
| `pessoa_id` | inteiro | sim | deve existir em `pessoas` |
| `marca` | texto | sim | mínimo 2 caracteres |
| `modelo` | texto | sim | mínimo 2 caracteres |
| `ano` | inteiro | sim | entre 1900 e o ano atual |
| `placa` | texto | sim | único, formato `ABC-1234` |

### `GET /api/veiculos/{id}`

Retorna um veículo específico.

### `PUT /api/veiculos/{id}`

Atualiza o veículo (aceita os mesmos campos do cadastro).

### `DELETE /api/veiculos/{id}`

Exclui o veículo. Se ele tiver revisões, responde **409** com mensagem amigável.

---

## Revisões

### `GET /api/revisoes`

Lista as revisões (mais recentes primeiro) com veículo e proprietário. Aceita
o filtro opcional `?veiculo_id=8`.

**Resposta:**

```json
{
  "success": true,
  "data": [
    {
      "id": 45,
      "veiculo_id": 8,
      "data_revisao": "2026-08-15",
      "quilometragem": 45000,
      "descricao": "Troca de óleo e filtros",
      "valor": 350.00,
      "observacoes": null,
      "veiculo": { "id": 8, "marca": "Honda", "modelo": "Civic", "placa": "HON-2020" }
    }
  ]
}
```

### `POST /api/revisoes`

Cadastra uma revisão **obrigatoriamente vinculada a um veículo**.

| Campo | Tipo | Obrigatório | Regra |
|---|---|---|---|
| `veiculo_id` | inteiro | sim | deve existir em `veiculos` |
| `data_revisao` | data | sim | `AAAA-MM-DD`, não pode ser no futuro |
| `quilometragem` | inteiro | sim | deve ser ≥ 0 |
| `descricao` | texto | sim | mínimo 3 caracteres |
| `valor` | decimal | sim | deve ser ≥ 0 |
| `observacoes` | texto | não | |

**Exemplo de erro de validação (422):**

```json
{
  "success": false,
  "message": "Existem dados inválidos no formulário.",
  "errors": {
    "data_revisao": ["O campo data revisao é obrigatório."],
    "valor": ["O campo valor deve ser maior ou igual a 0."]
  }
}
```

### `GET /api/revisoes/{id}`

Retorna uma revisão específica.

### `PUT /api/revisoes/{id}`

Atualiza a revisão (aceita os mesmos campos do cadastro).

### `DELETE /api/revisoes/{id}`

Exclui a revisão.

---

## Dashboard

### `GET /api/dashboard`

Indicadores e dados para os gráficos da tela inicial.

**Resposta:**

```json
{
  "success": true,
  "data": {
    "total_pessoas": 20,
    "total_veiculos": 30,
    "total_revisoes": 64,
    "valor_total_gasto": 12458.75,
    "veiculos_por_marca": [
      { "marca": "Fiat", "total": 5 }
    ],
    "revisoes_por_mes": [
      { "mes": "2025-09", "total": 3 }
    ],
    "pessoas_por_sexo": [
      { "sexo": "Masculino", "total": 10 },
      { "sexo": "Feminino", "total": 10 }
    ]
  }
}
```

---

## Relatórios

Todos retornam a mesma estrutura:

```json
{
  "success": true,
  "data": {
    "titulo": "Todos os veículos",
    "colunas": [ { "chave": "placa", "rotulo": "Placa" } ],
    "linhas": [ { "placa": "ABC-1234", "marca": "Fiat" } ],
    "grafico": { "tipo": "bar", "rotulos": ["Fiat"], "valores": [5], "titulo": "Veículos por marca" }
  }
}
```

O front-end monta a tabela e o gráfico a partir de `colunas`, `linhas` e
`grafico` — não precisa conhecer cada relatório individualmente.

| # | Rota | Filtros |
|---|---|---|
| 1 | `GET /api/relatorios/veiculos` | — |
| 2 | `GET /api/relatorios/veiculos-por-pessoa` | — |
| 3 | `GET /api/relatorios/sexo-com-mais-veiculos` | — |
| 4 | `GET /api/relatorios/marcas-por-quantidade` | — |
| 5 | `GET /api/relatorios/marcas-por-sexo` | — |
| 6 | `GET /api/relatorios/pessoas` | — |
| 7 | `GET /api/relatorios/pessoas-por-sexo` | — |
| 8 | `GET /api/relatorios/revisoes-por-periodo` | `?data_inicio=AAAA-MM-DD&data_fim=AAAA-MM-DD` |
| 9 | `GET /api/relatorios/marcas-com-mais-revisoes` | — |
| 10 | `GET /api/relatorios/pessoas-com-mais-revisoes` | — |
| 11 | `GET /api/relatorios/media-tempo-entre-revisoes` | — |
| 12 | `GET /api/relatorios/proximas-revisoes` | — |

O SQL de cada relatório está em `database/reports/` com os mesmos nomes,
numerados de `01` a `12`.
