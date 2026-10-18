# Data Model: Recomendações (Feature 004)

Nenhuma tabela, campo ou configuração nova. A feature lê os dados que a Feature 003 já lê e monta,
a cada exibição, uma estrutura em memória.

## Fonte: a visão de grandes da Feature 003

`overview_builder::build()` devolve, com resultado e arquivos, `large` com:

| Campo | Conteúdo |
| --- | --- |
| `materials` | Atividades visíveis ao professor com materiais de grande porte: `cmid`, `name` (nome formatado atual), `url` (ou `null`), `count`, `bytes`; ordenadas por `bytes` desc, empate pelo nome |
| `courselevel`, `others`, `removed` | `null` ou `{count, bytes}`: nível da sala, atividades invisíveis ao professor, atividades removidas depois da análise |
| `threshold` | Limite de grande porte registrado no resultado (bytes) |
| `truncated` | `true` quando as ligações foram limitadas aos 1.000 maiores materiais |
| `count`, `bytes` | Totais do resumo da sala (todas as categorias); **não** usados por S1 (research R2) |

A data da análise vem de `timecompleted` da visão.

## Sugestão S1 (em memória)

Saída de `large_materials::evaluate()` quando a condição é verdadeira:

| Campo | Tipo | Regra |
| --- | --- | --- |
| `count` | int ≥ 1 | Soma de `count` de `materials`, `courselevel`, `others` e `removed` |
| `bytes` | int ≥ 1 | Soma de `bytes` dos mesmos |
| `threshold` | int | `large.threshold` |
| `places` | lista de 1 a 3 | Lugares com nome: cada atividade de `materials` (`name`, `url`, `bytes`) e o nível da sala (`courselevel`, sem nome próprio, com `url` da sala definido na apresentação), ordenados por `bytes` desc; empate: atividades pelo nome, o nível da sala depois; os três primeiros |
| `more` | int ≥ 0 | Lugares com nome além dos três, mais 1 para `others` e 1 para `removed` quando existirem |
| `truncated` | bool | `large.truncated` |

**Condição**: `count ≥ 1`, isto é, existe pelo menos uma ligação a material de grande porte. Sem
nenhuma, `evaluate()` devolve `null`.

**Validações**:
- `others` e `removed` nunca aparecem em `places` (sem nome; FR-004, FR-030).
- Nenhum campo vem do resumo da sala (`large.count`, `large.bytes`), que inclui envios.

## Indicador do bloco

`bool` "há sugestão": resultado vigente com arquivos e `diagnostic_store::has_large_materials(diagid)`
verdadeiro. Equivale à condição de S1 (research R3).

## Estados

A tabela de FR-020 da spec define a presença da seção e da frase do bloco. A regra só é chamada com
resultado e arquivos (`large` não nulo); nos demais casos, a seção mostra a frase neutra (com
resultado) ou não aparece (sem resultado).
