# Contract: sugestões no bloco e na página

Estende [o contrato da página](../../003-teacher-experience/contracts/overview-page.md) e [o do
bloco](../../003-teacher-experience/contracts/block-ui.md) da Feature 003. Continuam valendo: acesso,
capability, direção visual aprovada, WCAG 2.2 AA, nenhuma consulta a `files`, nenhum texto fixo em
templates.

## Página: seção "Sugestões para a sua sala"

Posição: depois da abertura (`overview_summary`) e antes da grade de distribuição e grandes.
`<section id="block_coursecare-suggestions">`.

| Situação | Conteúdo |
| --- | --- |
| Sem resultado (A, B, C, F sem resultado) | Seção ausente |
| Com resultado e S1 | `h3` `suggestions:heading`; `suggestions:basedon` com a data (FR-007); cartão de S1 |
| Com resultado e sem S1 (inclui G e E/F sem arquivos) | `h3` `suggestions:heading`; `suggestions:none` (sem data: a abertura já a mostra) |

**Cartão de S1** (cartão discreto, sem cor ou ícone de alerta; refinado em 2026-10-18: a `dl` agrupa
os fatos num painel tingido e o motivo e as possibilidades noutro bloco, lado a lado a partir de 768 px
e empilhados abaixo disso; quantidade e volume em `strong`; cada lugar com o seu tamanho; o exemplo da
primeira possibilidade, `suggestion:large:option1_example`, numa linha menor abaixo dela):

1. `h4` `suggestion:large:title`.
2. `dl` com três pares:
   - `dt` `suggestion:observed` → `dd`: `suggestion:large:observed` (count, threshold, size); `ul` dos
     lugares (atividade: nome com link; nível da sala: `overview:courselevel` com link para a sala);
     `suggestion:large:more` ({$a} = more) quando `more > 0`; `overview:large_truncated` quando
     `truncated`. Sem lugar com nome: `suggestion:large:observed_unnamed` (ou `_one`), sem `ul` e sem
     `more`.
   - `dt` `suggestion:why` → `dd`: `suggestion:large:why`.
   - `dt` `suggestion:todo` → `dd`: `suggestion:large:todo`; `ul` com `suggestion:large:option1`,
     `option2`, `option3`.
3. Os nomes de atividade são impressos já formatados (como na 003, sem escape duplo). Os links usam o
   texto completo do nome; nenhum "clique aqui".

## Bloco

| Situação | Conteúdo |
| --- | --- |
| Sem sugestão | Igual à 003 |
| Com sugestão (resultado com arquivos e ligação a material grande) | 003 + `block:suggestion` antes do link; o link (`overview:link`) aponta para `overview.php?id=<instância>#block_coursecare-suggestions` |

Nenhum número, selo ou ícone novo no bloco.

## Strings (propostas; texto final na revisão de tom, FR-050)

| Chave | en | pt_br |
| --- | --- | --- |
| `block:suggestion` | There is a suggestion about the files of your course. | Há uma sugestão sobre os arquivos da sua sala. |
| `suggestions:heading` | Suggestions for your course | Sugestões para a sua sala |
| `suggestions:basedon` | These suggestions use the analysis of {$a}. | Estas sugestões usam a análise de {$a}. |
| `suggestions:none` | There are no suggestions for this course at the moment. | Não há sugestões para esta sala no momento. |
| `suggestion:observed` | What we noticed | O que observamos |
| `suggestion:why` | Why it may deserve attention | Por que pode merecer atenção |
| `suggestion:todo` | What you can do, if you wish | O que você pode fazer, se quiser |
| `suggestion:large:title` | Large materials | Materiais de grande porte |
| `suggestion:large:observed` | {$a->count} course materials of {$a->threshold} or more, {$a->size} in total, in: | {$a->count} materiais da sala de {$a->threshold} ou mais, somando {$a->size}, em: |
| `suggestion:large:more` | and {$a} more places, listed under Large files. | e mais {$a} lugares, listados em Arquivos de grande porte. |
| `suggestion:large:why` | Large files can take longer to open, especially on slow connections or on a phone. Size alone says nothing about the quality or the importance of a material. | Arquivos grandes podem demorar mais para abrir, sobretudo em conexões lentas ou no celular. O tamanho, sozinho, não diz nada sobre a qualidade ou a importância do material. |
| `suggestion:large:todo` | When you review the activity, consider whether any of these options suits your material. Not all of them apply to every case: | Ao revisar a atividade, avalie se alguma destas opções faz sentido para o seu material. Nem todas se aplicam a todos os casos: |
| `suggestion:large:option1` | an optimised version of the file that stays readable and accessible; | uma versão otimizada do arquivo, que continue legível e acessível; |
| `suggestion:large:option1_example` | for example, a PDF with images at a resolution suited to the screen, or a video at the resolution it needs. | por exemplo, um PDF com imagens na resolução adequada para a tela ou um vídeo na resolução necessária. |
| `suggestion:large:option2` | splitting long content into shorter parts; | dividir um conteúdo longo em partes menores; |
| `suggestion:large:option3` | a link to where the content is already published. | um link para onde o conteúdo já está publicado. |

Singular: com quantidade 1, as frases no plural ficariam erradas ("1 materials", "1 more places").
Há duas variantes completas, escolhidas pela quantidade, não por concatenação:

| Chave | en | pt_br |
| --- | --- | --- |
| `suggestion:large:observed_one` | 1 course material of {$a->threshold} or more, {$a->size}, in: | 1 material da sala de {$a->threshold} ou mais, com {$a->size}, em: |
| `suggestion:large:more_one` | and 1 more place, listed under Large files. | e mais 1 lugar, listado em Arquivos de grande porte. |

Sem lugar com nome (todos os materiais grandes estão em atividades que o professor não pode ver ou que
foram removidas): uma frase completa que remete à lista da seção de grandes, sem a lista de lugares e
sem "e mais N lugares" (correção da revisão de código de 2026-10-18):

| Chave | en | pt_br |
| --- | --- | --- |
| `suggestion:large:observed_unnamed` | {$a->count} course materials of {$a->threshold} or more, {$a->size} in total, in activities listed under Large files. | {$a->count} materiais da sala de {$a->threshold} ou mais, somando {$a->size}, em atividades listadas em Arquivos de grande porte. |
| `suggestion:large:observed_unnamed_one` | 1 course material of {$a->threshold} or more, {$a->size}, in an activity listed under Large files. | 1 material da sala de {$a->threshold} ou mais, com {$a->size}, numa atividade listada em Arquivos de grande porte. |

Reaproveitadas: `overview:courselevel`, `overview:large_truncated`, tamanhos por
`diagnostic_line::size()`, data por `userdate()` com `strftimedatetimeshort`.

## Acessibilidade

- Títulos em ordem: `h2` (página) → `h3` (seção) → `h4` (sugestão).
- `dl`/`dt`/`dd` para as três partes; listas `ul` para lugares e possibilidades.
- Links com o nome completo, foco visível do tema; alcançáveis pelo teclado em ordem de leitura.
- A 320 px, uma coluna, sem rolagem horizontal.
