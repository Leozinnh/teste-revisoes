# Documentação da API

Todas as rotas começam com `/api` e retornam **JSON**.

## Formato das respostas

**Sucesso (registro único, cadastro, edição):**

```json
{
  "success": true,
  "data": { }
}
```

**Sucesso (listagens — as respostas são paginadas, veja a seção "Paginação"):**

```json
{
  "success": true,
  "data": [ ],
  "meta": {
    "current_page": 1,
    "total": 42,
    "per_page": 25,
    "last_page": 2
  }
}
```

**Erro (validação, registro não encontrado, etc.):**

```json
{
  "success": false,
  "message": "Motivo do erro.",
  "errors": { "campo": ["mensagem"] }
}
```

O campo `errors` só aparece quando há erros de validação (HTTP 422).

## Paginação

As três listagens (`GET /api/pessoas`, `GET /api/veiculos` e `GET /api/revisoes`)
são paginadas. Os itens da página atual ficam em `data` e a meta em `meta`
(veja acima). Parâmetros de query:

| Parâmetro | Padrão | Regra |
|---|---|---|
| `page` | 1 | página a exibir |
| `per_page` | 25 | itens por página, entre 1 e 500 (500 existe para os dropdowns internos do front) |

O dashboard e os relatórios não são paginados: retornam tudo de uma vez.

## Códigos de erro usados

| Código | Situação |
|---|---|
| 422 | Dados inválidos (validação do Form Request) |
| 404 | Registro não encontrado |
| 409 | Exclusão bloqueada (registro possui itens relacionados) |
| 500 | Erro inesperado (a mensagem é amigável; o detalhe vai para o log) |

## Normalização de dados

Alguns campos são normalizados antes de validar e gravar — a API aceita a
grafia "de gente" e guarda sempre o mesmo formato:

- **cpf** e **telefone**: aceitam máscara (`000.000.000-00`, `(11) 99999-9999`)
  e são gravados **somente com dígitos**;
- **email**: gravado em minúsculas, sem espaços nas bordas;
- **placa**: aceita hífen, espaços e minúsculas e é gravada em **maiúsculas,
  sem hífen** (`abc-1234` → `ABC1234`);
- **nome/marca/modelo/descricao/observacoes**: sem espaços nas bordas;
  observações vazias viram `null`.

As respostas sempre devolvem o valor **como gravado** (CPF e telefone sem
máscara, placa sem hífen).

---

## Pessoas

### `GET /api/pessoas`

Lista as pessoas em ordem alfabética, cada uma com a quantidade de veículos.
**Paginada** (`?page=` e `?per_page=`).

**Resposta:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nome": "Ana Oliveira",
      "cpf": "11144477735",
      "sexo": "F",
      "data_nascimento": "1995-07-15",
      "telefone": "31977776666",
      "email": "ana@exemplo.com",
      "veiculos_count": 2
    }
  ],
  "meta": { "current_page": 1, "total": 20, "per_page": 25, "last_page": 1 }
}
```

### `POST /api/pessoas`

Cadastra uma pessoa.

| Campo | Tipo | Obrigatório | Regra |
|---|---|---|---|
| `nome` | texto | sim | até 255 caracteres |
| `cpf` | texto | sim | 11 dígitos (aceita máscara), único, com dígitos verificadores válidos |
| `sexo` | texto | sim | apenas `M` ou `F` |
| `data_nascimento` | data | sim | `AAAA-MM-DD`, posterior a 1900-01-01 e anterior a hoje |
| `telefone` | texto | sim | 10 ou 11 dígitos com DDD (aceita máscara) |
| `email` | texto | sim | formato de e-mail, até 255 caracteres, único |

**Exemplo de corpo:**

```json
{
  "nome": "Maria da Silva",
  "cpf": "529.982.247-25",
  "sexo": "F",
  "data_nascimento": "1990-05-10",
  "telefone": "(11) 99999-1234",
  "email": "maria@exemplo.com"
}
```

**Resposta (201):**

```json
{
  "success": true,
  "data": { "id": 5, "nome": "Maria da Silva", "cpf": "52998224725", "sexo": "F", "data_nascimento": "1990-05-10", "telefone": "11999991234", "email": "maria@exemplo.com", "veiculos_count": 0 }
}
```

### `GET /api/pessoas/{id}`

Retorna uma pessoa específica (mesmos campos do cadastro, com `veiculos_count`).

### `PUT /api/pessoas/{id}`

Atualiza a pessoa. Espera **os mesmos campos obrigatórios** do cadastro; o
`cpf` e o `email` podem ser mantidos iguais (a regra de unicidade ignora o
próprio registro).

### `DELETE /api/pessoas/{id}`

Exclui a pessoa. Se ela tiver veículos, responde **409**:

```json
{
  "success": false,
  "message": "Esta pessoa possui veículos cadastrados. Exclua os veículos primeiro."
}
```

---

## Veículos

### `GET /api/veiculos`

Lista os veículos (em ordem alfabética de marca e modelo) com o nome do
proprietário. Aceita o filtro opcional `?pessoa_id=3` para listar só os
veículos daquela pessoa. **Paginada** (`?page=` e `?per_page=`).

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
      "placa": "XYZ9999",
      "pessoa": { "id": 3, "nome": "João Santos" }
    }
  ],
  "meta": { "current_page": 1, "total": 30, "per_page": 25, "last_page": 2 }
}
```

### `POST /api/veiculos`

Cadastra um veículo **obrigatoriamente vinculado a uma pessoa**.

| Campo | Tipo | Obrigatório | Regra |
|---|---|---|---|
| `pessoa_id` | inteiro | sim | deve existir em `pessoas` |
| `marca` | texto | sim | até 100 caracteres |
| `modelo` | texto | sim | até 100 caracteres |
| `ano` | inteiro | sim | entre 1900 e 2100 |
| `placa` | texto | sim | única; padrão antigo `ABC1234` ou Mercosul `ABC1D23` (aceita hífen e minúsculas) |

### `GET /api/veiculos/{id}`

Retorna um veículo específico, com `pessoa` e a lista de `revisoes`.

### `PUT /api/veiculos/{id}`

Atualiza o veículo (espera os mesmos campos obrigatórios do cadastro).

### `DELETE /api/veiculos/{id}`

Exclui o veículo. Se ele tiver revisões, responde **409** com mensagem amigável.

---

## Revisões

### `GET /api/revisoes`

Lista as revisões (mais recentes primeiro) com veículo e proprietário. Aceita
o filtro opcional `?veiculo_id=8`. **Paginada** (`?page=` e `?per_page=`).

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
      "veiculo": {
        "id": 8,
        "pessoa_id": 3,
        "marca": "Honda",
        "modelo": "Civic",
        "ano": 2018,
        "placa": "HON2020",
        "pessoa": { "id": 3, "nome": "João Santos" }
      }
    }
  ],
  "meta": { "current_page": 1, "total": 64, "per_page": 25, "last_page": 3 }
}
```

### `POST /api/revisoes`

Cadastra uma revisão **obrigatoriamente vinculada a um veículo**.

| Campo | Tipo | Obrigatório | Regra |
|---|---|---|---|
| `veiculo_id` | inteiro | sim | deve existir em `veiculos` |
| `data_revisao` | data | sim | `AAAA-MM-DD`, posterior a 1900-01-01 e não no futuro |
| `quilometragem` | inteiro | sim | entre 0 e 2.000.000; **não pode ser menor que a maior km já registrada do veículo** |
| `descricao` | texto | sim | até 255 caracteres |
| `valor` | decimal | sim | entre 0 e 99.999.999,99, com até 2 casas decimais |
| `observacoes` | texto | não | (vazio é gravado como `null`) |

Regras de negócio e formato:

- **Quilometragem não regride**: no cadastro, a km informada não pode ser
  menor que a maior km já registrada para aquele veículo (ex.: veículo com
  uma revisão de 50.000 km → cadastrar uma de 30.000 responde 422). Na
  edição, a própria revisão é ignorada na comparação — um erro de digitação
  pode ser corrigido para baixo desde que não fique abaixo de outro registro.
- **Separador decimal é o ponto** (`350.00`). A API não aceita vírgula; o
  front converte o formato brasileiro (`1.234,56`) para ponto antes de enviar.

**Exemplo de corpo:**

```json
{
  "veiculo_id": 8,
  "data_revisao": "2026-08-15",
  "quilometragem": 45000,
  "descricao": "Troca de óleo e filtros",
  "valor": 350.00,
  "observacoes": null
}
```

**Exemplo de erro de validação (422):**

```json
{
  "success": false,
  "message": "Existem dados inválidos no formulário.",
  "errors": {
    "data_revisao": ["A data da revisão não pode ser no futuro."],
    "quilometragem": ["A quilometragem não pode ser menor que a última registrada para este veículo (50.000 km)."]
  }
}
```

### `GET /api/revisoes/{id}`

Retorna uma revisão específica, com `veiculo` e a `pessoa` do proprietário.

### `PUT /api/revisoes/{id}`

Atualiza a revisão (espera os mesmos campos obrigatórios do cadastro).

### `DELETE /api/revisoes/{id}`

Exclui a revisão.

---

## Dashboard

### `GET /api/dashboard`

Indicadores e dados para os gráficos da tela inicial. **Não é paginado.**

**Resposta:**

```json
{
  "success": true,
  "data": {
    "pessoas": 20,
    "veiculos": 30,
    "revisoes": 64,
    "total_gasto": 12458.75,
    "grafico_marcas": {
      "rotulos": ["Fiat", "Honda"],
      "valores": [5, 4]
    },
    "grafico_meses": {
      "rotulos": ["2025-09", "2026-08"],
      "valores": [3, 7]
    },
    "grafico_sexo": {
      "rotulos": ["Masculino", "Feminino"],
      "valores": [10, 10]
    }
  }
}
```

| Chave | Conteúdo |
|---|---|
| `pessoas` / `veiculos` / `revisoes` | totais no banco |
| `total_gasto` | soma de `valor` de todas as revisões |
| `grafico_marcas` | veículos agrupados por marca, da mais frequente para a menos |
| `grafico_meses` | revisões por mês (`AAAA-MM`), do mais antigo para o mais recente |
| `grafico_sexo` | pessoas por sexo (`Masculino`/`Feminino`) |

---

## Relatórios

Todos retornam a mesma estrutura (e **não são paginados**):

```json
{
  "success": true,
  "data": {
    "titulo": "Todos os veículos",
    "colunas": [ { "chave": "placa", "rotulo": "Placa" } ],
    "linhas": [ { "placa": "ABC1234", "marca": "Fiat" } ],
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
