# CORREÇÕES.MD

Documento de correções do AutoCare, gerado ao fim da rodada de revisão em que
um analista apontou lacunas no projeto (validações, limites, regras de negócio,
frontend, escala e testes). Cada item abaixo registra **o que estava errado**,
**a causa**, **a correção** (com arquivos) e **como validar**.

Códigos de referência usados na auditoria:

| Código | Categoria |
|---|---|
| B1 | bug de comportamento real |
| V1–V5 | validações de formato no backend |
| N1–N4 | limites numéricos/de data (o "número máximo" do analista) |
| R1–R3 | regras de negócio e consistência |
| F1–F4 | frontend (UX e validação espelhada) |
| E1–E3 | escala, testes e segurança |

**Decisões do usuário durante a revisão:**

1. CPF e telefone são **normalizados para somente dígitos** no banco (a API
   aceita a máscara na entrada).
2. **Paginação somente nas 3 listagens CRUD** (pessoas, veículos, revisões).
   Dashboard e relatórios continuam integrais.
3. **Autenticação fica de fora** desta rodada → registrada como pendência (E3).

---

## 1. Bug real

### B1 — Filtro `?veiculo_id=` ignorado na listagem de revisões

**O que estava errado:** o frontend envia `?veiculo_id=` ao abrir a tela de
revisões de um veículo (`Revisoes.vue`), mas `RevisaoController::index()` não
lia `Request` nenhum — retornava **todas as revisões sempre**. O equivalente em
veículos (`?pessoa_id=`) funcionava. Nenhum teste cobria o filtro.

**Correção:** `index()` passou a receber `Request $request` e aplicar
`->when($request->query('veiculo_id'), ...)` no mesmo padrão do
`VeiculoController` — `app/Http/Controllers/Api/RevisaoController.php`.

**Como validar:** `GET /api/revisoes?veiculo_id=X` retorna só as revisões do
veículo X; teste `test_lista_revisoes_paginada_e_filtrada_por_veiculo` em
`tests/Feature/RevisaoApiTest.php`.

---

## 2. Validações de formato que faltavam no backend

### V1 — CPF sem validação real

**O que estava errado:** regra era só `string|max:14` + `unique` —
`"abc"`, `"123"` e `"111.111.111-11"` passavam; pior, com e sem máscara o mesmo
CPF era gravado duas vezes (o `unique` comparava a grafia, não o número).

**Correção** (`app/Http/Requests/StorePessoaRequest.php`):
- `prepareForValidation()` normaliza o CPF **antes** das regras
  (`preg_replace('/\D/', '')`) — o `unique` passa a comparar o valor gravado;
- regra `digits:11` + closure que valida os **dígitos verificadores** (módulo
  11, rejeitando os 11 dígitos iguais), com mensagem pt-BR.

**Como validar:** POST com CPF mascarado válido → 201 e banco com só dígitos;
CPF de DV inválido (`52998224726`) → 422; o mesmo CPF com e sem máscara →
duplicata bloqueada. Testes em `tests/Feature/PessoaApiTest.php`.

### V2 — Telefone sem formato

**O que estava errado:** regra `string|max:20` aceitava qualquer texto.

**Correção** (`StorePessoaRequest.php`): normalização para só dígitos +
`regex:/^\d{10,11}$/` (DDD + 8/9 dígitos). A tela aceita a máscara
`(11) 99999-9999` e o banco guarda `11999999999`.

**Como validar:** POST com telefone mascarado → 201 normalizado; com 9 dígitos
→ 422.

### V3 — Placa sem formato e `unique` sensível a maiúsculas

**O que estava errado:** regra `string|max:8` aceitava qualquer coisa e o
`unique` do PostgreSQL diferencia maiúsculas — `abc-1234` e `ABC-1234`
coexistiam como "placas diferentes".

**Correção** (`app/Http/Requests/StoreVeiculoRequest.php`): normalização
(`strtoupper` + remove hífen/espaços) antes das regras; regex ancorada que
aceita **somente** o padrão antigo (`ABC1234`) ou o Mercosul (`ABC1D23`), e o
`unique` compara o valor normalizado.

**Como validar:** POST `abc-1234` → 201 e banco `ABC1234`; `abc1d23` →
`ABC1D23`; `ABC12`, `AB12345`, `ABC1D234` → 422. Testes em
`tests/Feature/VeiculoApiTest.php`.

### V4 — E-mail sem normalização

**O que estava errado:** o `unique` do e-mail diferencia maiúsculas no banco —
`Joao@x.com` e `joao@x.com` podiam duplicar.

**Correção** (`StorePessoaRequest.php`): `prepareForValidation()` converte o
e-mail para minúsculas (`strtolower` + `trim`) antes das regras e da gravação.

### V5 — Espaços nas bordas

**O que estava errado:** `required` passava com `"  nome  "` e o valor era
gravado com espaços; `" ABC-1234 "` idem.

**Correção:** `trim()` em `prepareForValidation()` de todos os Form Requests
(`nome`, `email`, `marca`, `modelo`, `descricao`, `observacoes` — observações
vazias viram `null`). Os demais campos já eram normalizados mais fundo
(CPF/telefone/placa/e-mail), conforme V1–V4.

---

## 3. Limites numéricos e de data que faltavam

### N1 — `valor` sem teto (estourava o banco com erro 500)

**O que estava errado:** a coluna é `decimal(10,2)` (máx. 99.999.999,99); um
`valor` acima disso gerava **500 genérico do PostgreSQL** em vez de 422.

**Correção** (`app/Http/Requests/StoreRevisaoRequest.php`): regras
`min:0`, `max:99999999.99` e `decimal:0,2` (até 2 casas decimais), com
mensagens pt-BR. Reforço no banco incluído em R3.

**Como validar:** POST `valor: 100000000` → 422 com mensagem clara;
`valor: 1.999` (3 casas) → 422. Testes em `tests/Feature/RevisaoApiTest.php`.

### N2 — `quilometragem` sem teto (mesmo risco)

**O que estava errado:** coluna `integer` do PostgreSQL vai até ~2,1 bilhões;
não havia `max` coerente.

**Correção** (`StoreRevisaoRequest.php`): `integer`, `min:0`, `max:2000000`
(2.000.000 km — teto que nunca será atingido por um veículo real, mas impede o
erro 500). Mensagem: *"A quilometragem não pode ultrapassar 2.000.000 km."*

### N3 — `data_nascimento` sem limite inferior

**O que estava errado:** só `before:today` — uma pessoa nascida em `0001-01-01`
(2000 anos) era aceita.

**Correção** (`StorePessoaRequest.php`): adicionado `after:1900-01-01` com
mensagem própria. O front espelha com `min="1900-01-01"` no input de data (F4).

### N4 — `data_revisao` sem limites

**O que estava errado:** só `required|date` — revisão no futuro (2099) e em
passado remoto eram aceitas.

**Correção** (`StoreRevisaoRequest.php`): `after:1900-01-01` +
`before_or_equal:today`. O front espelha com `min="1900-01-01"` e `max` = hoje.

**Como validar:** datas `1899-12-31` e amanhã → 422. Testes em
`tests/Feature/RevisaoApiTest.php`.

---

## 4. Regras de negócio e consistência

### R1 — Quilometragem "voltando para trás"

**O que estava errado:** nada impedia cadastrar uma revisão com km **menor que
a já registrada do mesmo veículo** (hodômetro não volta).

**Correção** (`StoreRevisaoRequest.php`): validação **com dados do banco** via
`withValidator()->after()` — a km informada não pode ser menor que a **maior km
já registrada** do veículo. Na **edição**, a própria revisão é ignorada na
comparação, então um erro de digitação pode ser corrigido para baixo desde que
não fique abaixo de outro registro. A regra só roda quando `veiculo_id` e
`quilometragem` passaram na validação básica (evita mensagens duplicadas e
query com dado inválido). Mensagem traz a maior km registrada formatada
(ex.: *"...menor que a última registrada para este veículo (50.000 km)"*).

Detalhe descoberto e corrigido no caminho: o parâmetro de rota do Laravel era
`{reviso}` (inflector em inglês), então `$this->route('revisao')` sempre
retornava `null` e a edição **nunca** ignorava a própria revisão — ver seção 7.

**Como validar:** veículo com revisão de 50.000 km → POST de 30.000 → 422;
POST de 50.000 (igual) → 201. Com revisões 20.000/50.000, editar a de 50.000
para 30.000 → 200; para 15.000 → 422. Testes
`test_nao_cadastra_revisao_com_km_regredindo` e
`test_edicao_de_km_ignora_a_propria_revisao` em `RevisaoApiTest.php`.

### R2 — Mensagens de erro em inglês vazando

**O que estava errado:** as `messages()` cobriam só parte das regras; as
demais devolviam o texto padrão **em inglês** do Laravel (ex.:
`quilometragem.integer`, `valor.numeric`, datas inválidas).

**Correção:** `messages()` completas nos 3 Form Requests
(`StorePessoaRequest.php`, `StoreVeiculoRequest.php`, `StoreRevisaoRequest.php`)
— toda regra com chance de falhar tem mensagem pt-BR amigável.

### R3 — Sem reforço no banco (padrão inconsistente)

**O que estava errado:** o projeto bloqueia exclusão no banco com `RESTRICT`
"por garantia", mas `sexo`, `valor` e `quilometragem` ficavam sem `CHECK`
(a própria migration comentava "o padrão é validado no Form Request").

**Correção:** adicionados CHECKs nas migrations originais (como o `RESTRICT`,
o banco é a última linha de defesa), executados **somente no PostgreSQL**
(SQLite dos testes não suporta `ALTER TABLE ADD CONSTRAINT`):

- `pessoas_sexo_check CHECK (sexo IN ('M', 'F'))` —
  `database/migrations/2026_08_29_100001_create_pessoas_table.php`
- `revisoes_quilometragem_check CHECK (quilometragem >= 0)` e
  `revisoes_valor_check CHECK (valor >= 0)` —
  `2026_08_29_100003_create_revisoes_table.php`

**Como validar:** `migrate:fresh` no PostgreSQL sem erros; insert manual de
`sexo = 'X'` ou `valor = -1` no banco é recusado pelo próprio PostgreSQL.

---

## 5. Frontend

### F1 — Erros 422 não apareciam em vários campos

**O que estava errado:** `Pessoas.vue` mostrava erro só em nome/cpf/email —
**sexo, data de nascimento e telefone falhavam em silêncio** (só mensagem
genérica); `Veiculos.vue` idem para marca/modelo/ano; `Revisoes.vue` idem para
data/km/valor/descrição/observações.

**Correção:** os três componentes ganharam `<p>` de erro sob cada campo e
borda vermelha no input inválido (`erros.campo` vindo do 422 do backend ou da
validação local): `resources/js/components/Pessoas.vue`, `Veiculos.vue` e
`Revisoes.vue`.

### F2 — Quilometragem não validada no front

**O que estava errado:** `validaFrontend()` do `Revisoes.vue` não checava
quilometragem (o único número sem checagem) — km vazia ou negativa só era
barrada pelo backend.

**Correção:** validação local agora exige quilometragem e limita a
0–2.000.000 km, com mensagem por campo (F1), antes de qualquer request.

### F3 — Valor com vírgula pt-BR quebrava na API

**O que estava errado:** campo `type="text"` — usuário digitava `1.234,56` e o
Laravel `numeric` (que só aceita ponto decimal) devolvia 422.

**Correção:** o `Revisoes.vue` converte o valor **antes do envio**
(`1.234,56` → `1234.56`), mantendo `type="text" inputmode="decimal"` e
mostrando o valor em pt-BR ao editar (o banco devolve `1234.56`, o input exibe
`1.234,56`). A validação local aceita o formato brasileiro e o backend
permanece exigindo ponto — a conversão é fronteira do front.

### F4 — Sem `maxlength` / `min` / `max` nos inputs

**O que estava errado:** nada limitava a digitação; o usuário só descobria o
limite no erro genérico do backend.

**Correção (espelha as regras do backend em todos os inputs):**

| Tela | Limites |
|---|---|
| Pessoas | nome/email `maxlength=255`; cpf `maxlength=14`; telefone `maxlength=20`; datas com `min="1900-01-01"` e `max` = hoje |
| Veículos | marca/modelo `maxlength=100`; placa `maxlength=8` + caixa alta automática; ano `min="1900"` `max="2100"` |
| Revisões | descrição `maxlength=255`; km `min=0` `max=2000000`; valor `maxlength=15` (99.999.999,99 em pt-BR tem 13 chars); data com `min="1900-01-01"` e `max` = hoje |

---

## 6. Escala, testes e segurança

### E1 — Paginação nas listagens (decisão: só os 3 CRUDs)

**O que estava errado:** listagens devolviam tudo; com o
`DadosEmVolumeSeeder` (1.000+ revisões, 200 pessoas) as telas e os payloads
crescem sem limite.

**Correção (conforme decisão do usuário — somente as 3 listagens CRUD;**
dashboard e relatórios continuam integrais):

- Backend: helper `respostaPaginada()` no `Controller` base mantém o shape
  `{ success, data: [...], meta: { current_page, total, per_page, last_page } }`
  nos 3 `index()` (`PessoaController`, `VeiculoController`,
  `RevisaoController`). `?per_page=` é aceito com teto 500 — o teto existe
  para os dropdowns internos do front (seleção de pessoa/veículo no
  formulário) que pedem a lista inteira com `per_page: 500`.
- Frontend: rodapé de paginação nas 3 telas (Anterior/Próxima com `disabled`
  nos extremos + "Página X de Y (N no total)"), estado `pagina` resetado ao
  trocar de filtro, e recuo automático de página ao excluir o último item da
  última página.

**Como validar:** `GET /api/pessoas?per_page=1` → `meta.last_page` ≥ 2 e `data`
com 1 item; navegação Anterior/Próxima nas telas com o `DadosEmVolumeSeeder`.

### E2 — Dashboard sem teste

**O que estava errado:** nenhum teste cobria `/api/dashboard` (foi o que
deixou o bug B1 passar — funcionalidade sem teste quebra sem ninguém ver).
Bônus descoberto no caminho: o dashboard usava `to_char` do PostgreSQL — SQL
exclusivo do PG num projeto cujos testes rodam em SQLite (ver seção 7).

**Correção:** novo `tests/Feature/DashboardApiTest.php` cobrindo indicadores,
totais e os 3 gráficos, com fixture real (pessoa → veículo → revisões em
meses diferentes) que valida também os rótulos de mês e a contagem por sexo.
`DashboardController` reescrito com agrupamento em PHP (portável entre os 2
bancos), resposta pública inalterada.

**Como validar:** `php artisan test --filter=DashboardApiTest` — verde.

### E3 — API sem autenticação

**Decisão:** não incluída nesta rodada (fora do escopo do teste prático).
**Pendência registrada:** qualquer rota do `/api/*` hoje é pública; se o
analista cobrar, o próximo passo é `laravel/sanctum` com login por e-mail +
token (ou `auth:sanctum` nas rotas), telas de login no SPA e proteção do
`.env` de produção.

---

## 7. Bônus: bugs e inconsistências descobertos durante a correção

Além dos itens da auditoria, a implementação e a suíte de testes revelaram
problemas reais que foram corrigidos nesta rodada:

### Parâmetro de rota `{reviso}` (inflector em inglês)

A rota `Route::apiResource('revisoes', ...)` gera o parâmetro como
`{reviso}` — o inglês pluraliza "revisões" como se o singular fosse "reviso".
Consequência real: `StoreRevisaoRequest` usava `$this->route('revisao')` para
ignorar a própria revisão na edição, o valor **sempre vinha `null`** e a regra
R1 no update comparava contra a própria revisão — um veículo com uma única
revisão de 50.000 km não deixava **corrigi-la** para 45.000. Correção em
`routes/api.php`:
`->parameters(['revisoes' => 'revisao'])`. A suíte pegou o bug
(`test_edicao_de_km_ignora_a_propria_revisao`).

### Suíte de testes não rodava sem filtro

O `phpunit.xml` declarava a suite `Unit` apontando para `tests/Unit`, que não
existe no projeto — `php artisan test` (sem filtro) falhava com
"Test directory tests/Unit not found". Correção: suite `Unit` removida do
`phpunit.xml` (o projeto só tem testes de Feature), com comentário explicando.

### Seeders geravam dados que violariam as regras novas

- **Placas inválidas:** `lexify('???-####')` do Faker **não substitui `#`** —
  gerava placas `ABC####` (400 das 400 verificadas). Trocado por
  `bothify('???-####')` + `strtoupper` (sem hífen) nos 2 seeders.
- **Quilometragem invertida no tempo:** os loops geravam as revisões da mais
  recente para a mais antiga somando km a cada passo — no banco, a revisão
  mais antiga ficava com a **maior** km (o hodômetro andava para trás), o que
  violaria a regra R1 e faria o `DadosEmVolumeSeeder` inteiro ser rejeitado em
  cadastros futuros. Reescritos para gerar da mais antiga para a mais recente,
  com km crescente no tempo e os comentários corrigidos (que antes
  descreviam o contrário do que o código fazia).
- **CPF e telefone mascarados:** gerados com máscara pelo Faker pt_BR; agora
  normalizados para só dígitos (coerente com V1/V2, já que o seeder grava
  direto no banco, sem passar pelos Form Requests).

Ambos os seeders foram validados com um script de conferência (placas
`ABC1234`/Mercosul, CPF 11 dígitos, telefone 10–11, km crescente por veículo,
sem duplicatas) e re-executados do zero: `database/seeders/DatabaseSeeder.php`
e `database/seeders/DadosEmVolumeSeeder.php`.

### Dashboard dependia de SQL exclusivo do PostgreSQL

`DashboardController` usava `selectRaw("to_char(data_revisao, 'YYYY-MM')")` —
quebrava em SQLite. A correção de E2 exigia um teste do dashboard, e o teste
roda em SQLite; a query foi reescrita com agrupamento em PHP (`pluck` +
`substr` + `countBy` + `sortKeys`), sem mudar a resposta pública. Comentário
do próprio phpunit.xml prometia: "Nenhum teste usa SQL exclusivo do PG" — agora
é verdade de novo.

### Migration `placa` com tamanho errado

A coluna foi criada como `string(8)`, mas as placas aceitas têm **7
caracteres** (`ABC1234` ou `ABC1D23`). Ajustado para `string(7)` com comentário
explicando os dois padrões: `2026_08_29_100002_create_veiculos_table.php`.

---

## 8. Documentação

### `docs/API.md` reescrito com a verdade do código

A documentação estava **desatualizada em relação ao código**, o que é pior que
não ter documentação (quem consumisse a API seguiria regras que não existem):

- campos marcados como "não obrigatórios" que o código **exige** (`telefone`,
  `email`);
- `nome` documentado com "mínimo 3 caracteres" — regra que nunca existiu;
- seção do **Dashboard com chaves inexistentes** (`total_pessoas`,
  `veiculos_por_marca`, `revisoes_por_mes`...) — as chaves reais são
  `pessoas`, `veiculos`, `revisoes`, `total_gasto` e `grafico_marcas|meses|sexo`;
- sem menção à **paginação** (respostas agora têm `meta`);
- exemplos com dados mascarados, mas a API devolve CPF/telefone/placa
  normalizados.

Agora documenta: regras reais de cada POST (com os limites novos de V/N/R),
resposta paginada dos 3 GETs (`?page`, `?per_page` 1–500, padrão 25; relatórios
e dashboard não paginam), filtro `?veiculo_id=` em `/revisoes`, seção de
**normalização de dados** (o que a API aceita × o que grava), regra da km
regressiva, separador decimal com ponto, e as chaves reais do dashboard.

### `README.md` atualizado

Linha do `GET /api/revisoes` ganhou `?veiculo_id=` (simetria com veículos),
nota sobre paginação das listagens e referência às regras em `docs/API.md`.

---

## Como validar tudo

```bash
# 1. Suíte de testes completa (SQLite em memória) — agora roda sem filtro
php artisan test

# 2. Banco recriado com as migrations corrigidas (CHECKs e placa 7)
php artisan migrate:fresh --seed
php artisan db:seed --class=DadosEmVolumeSeeder

# 3. Frontend compilado sem erros
npm run build
```

Testes da suíte: 5 arquivos em `tests/Feature/` cobrindo os 3 CRUDs, os
relatórios e o dashboard — incluindo os casos de N1/N2 (tetos), V1 (DV do CPF
e normalização), R1 (km regressiva no cadastro e na edição) e E1 (paginação e
filtros).

Fluxo manual sugerido nas telas: cadastrar pessoa com CPF mascarado → ver CPF
sem máscara na lista; cadastrar veículo com `abc-1234` → placa `ABC1234`;
cadastrar revisão com valor `1.234,56` e km menor que a anterior (mensagens em
cada campo); "Ver revisões" de um veículo (filtro + banner corretos); "Ver
todas"; paginar com o banco em volume; tentar excluir pessoa/veículo com
itens (409).

---

## Pendências registradas (fora desta rodada)

| Item | Situação |
|---|---|
| **E3 — Autenticação na API** | não incluída por decisão; todo `/api/*` é público hoje |
| Paginação em outros endpoints | decisão: só os 3 CRUDs; relatórios e dashboard ficam integrais por enquanto (se o volume crescer, revisitar) |
| Validação de unicidade de placa/CPF case-insensitive no banco | resolvida por normalização na entrada (V1/V3); um índice `citext`/`lower()` no PG seria reforço opcional |
| `migrate:fresh` apaga dados | rodar em produção/banco real exige migration nova, não edição das originais (aqui o banco é recriado de propósito) |
