# Data Model: Teacher Experience (Feature 003)

A Feature 003 **não cria tabelas nem campos**. Este documento descreve a **visão da sala**: a
estrutura montada a cada exibição pelo `overview_builder` (puro) a partir do resultado da Feature 002
e da estrutura da sala, e entregue ao renderable `course_overview`. Nada aqui é persistido.

## Origem de cada dado

| Dado exibido | Origem (Feature 002 ou core) |
| --- | --- |
| Estado, data da análise, data da tentativa | `block_coursecare_diag` via `state_resolver` (`timecompleted`, `timeattempted`, `lastattemptfailed`, `requeststatus`) |
| Armazenamento lógico, quantidade de registros | `block_coursecare_diag.logicalbytes`, `filecount` |
| Conteúdo único | `block_coursecare_diag.uniquebytes`, `uniquecount` |
| Categorias da sala | `block_coursecare_cat` (`material`, `submission`, `other`, `unclassified`: `filecount`, `logicalbytes`) |
| Cópias de segurança e lixeira | `block_coursecare_cat` (`backup`, `recyclebin`) e `diag.brcount`, `brbytes`, `bruniquebytes` |
| Referências | `diag.refinternalcount/bytes`, `refexternalcount/bytes`, `refundeterminedcount` |
| Cobertura | `diag.partial`, `unclassifiedcount`, `unclassifiedbytes` |
| Grandes da sala | `diag.largecount`, `largebytes`, `largethreshold` |
| Materiais por atividade | `block_coursecare_act.materialbytes` por `cmid` (`0` = nível da sala) |
| Materiais grandes por atividade | `block_coursecare_large` agrupado por `cmid` (quantidade, soma de `filesize`); `diag.largelinkstruncated` |
| Nome, link e visibilidade da atividade | `get_fast_modinfo()` do core: `cm_info::get_formatted_name()`, `url`, `uservisible` |

Campos da Feature 002 **não usados por atividade** (FR-030, research R3): `filecount`,
`logicalbytes`, `submissionbytes`, `otherbytes`, `unclassifiedbytes`, `largecount`, `largebytes`,
`largeuniquecount`, `largeuniquebytes`.

## Visão da sala (`overview`)

| Campo | Tipo | Regra |
| --- | --- | --- |
| `state` | estado A–H da Feature 002 | Do `state_resolver` |
| `hasresult` | bool | D, E, F com resultado, G, H |
| `timecompleted` | data | Presente quando `hasresult` |
| `outdated`, `failed`, `partial` | bool | E; F com resultado; H |
| `total` | bytes | `logicalbytes`; ausente sem resultado |
| `categories` | lista de `category` | Só com resultado e fora de G |
| `activities` | `activity_list` | Só com resultado e fora de G |
| `large` | `large_summary` | Só com resultado e fora de G |
| `technical` | `technical` | Com resultado (inclusive G) |

### `category`

| Campo | Regra |
| --- | --- |
| `key` | `material`, `submission`, `other`, `unclassified`, nesta ordem |
| `bytes` | `logicalbytes` da categoria; `0` se a categoria não tem linha |
| `share` | `share(bytes, total)` |

Regra: `Σ bytes = total` (invariante da Feature 002).

### `activity_list`

| Campo | Regra |
| --- | --- |
| `first` | Até 10 `activity_item`, por `bytes` decrescente, desempate pelo nome |
| `rest` | Os demais, mesma ordem; vazio se ≤ 10 |
| `courselevel` | `activity_item` do `cmid 0` (página inicial, seções, blocos), se `materialbytes > 0` |
| `others` | Grupo das atividades com materiais que o usuário não pode ver (`uservisible = false`): só a soma, sem nomes |
| `removed` | Grupo dos `cmid` sem atividade correspondente na sala (removidas após a análise): só a soma |

### `activity_item`

| Campo | Regra |
| --- | --- |
| `name` | `get_formatted_name()` atual; ausente nos grupos |
| `url` | URL da atividade quando existir (áreas de texto e mídia não têm); ausente nos grupos |
| `bytes` | `materialbytes` (só materiais) |
| `share` | `share(bytes, total de materiais da sala)` |
| `largecount`, `largebytes` | Das ligações de materiais da atividade; ausentes se zero |

Regras: só `materialbytes > 0` entram; `Σ bytes` (itens, nível da sala e grupos) = bytes da categoria
`material`.

### `large_summary`

| Campo | Regra |
| --- | --- |
| `count`, `bytes` | `diag.largecount`, `largebytes` (sala inteira, todas as categorias) |
| `threshold` | `diag.largethreshold` |
| `materials` | `activity_item` com `largecount > 0`, ordenados por `largebytes`; atividades que o usuário não pode ver somadas em `others` e as removidas após a análise em `removed`, sem nomes, como em `activity_list` |
| `truncated` | `diag.largelinkstruncated = 1`: a lista de materiais considera só os 1.000 maiores |

### `technical`

Conteúdo único (`uniquebytes`, `uniquecount`); referências (internas e externas com quantidade e
tamanho registrado, indeterminadas só com quantidade); cópias de segurança e lixeira (quantidade e
volume de cada uma e conteúdo único do conjunto); cobertura (registros e volume não classificados,
`share` no total); limite de grande porte usado.

## Função `share(part, whole)`

| Caso | Resultado |
| --- | --- |
| `whole = 0` ou `part = 0` | `0` |
| `0 < part/whole < 1%` | "menos de 1%" |
| demais (`part/whole ≥ 1%`) | inteiro mais próximo de `100 × part / whole` (no mínimo 1) |

As porcentagens não são normalizadas para somar 100.

## Transições

A visão não tem estado próprio: cada exibição reflete o diagnóstico vigente. As transições de estado
são as da Feature 002 (data-model da Feature 002, "Estados").
