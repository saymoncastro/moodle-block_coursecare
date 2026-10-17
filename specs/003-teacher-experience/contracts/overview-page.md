# Contract: página "Sua sala em números" (níveis 2 e 3)

Arquivo: `overview.php` na raiz do bloco (dentro do pacote). Dados: [overview-data.md](overview-data.md).

## Endereço e acesso

- URL: `/blocks/coursecare/overview.php?id=<id da instância do bloco>`; `id` com
  `required_param('id', PARAM_INT)`. Nenhum outro parâmetro.
- Ordem das verificações, em `classes/local/overview_access.php` (chamada antes de qualquer leitura do
  diagnóstico; a instância é lida por `eligibility::get_course_instance()`):
  1. a instância existe e tem `blockname = 'coursecare'`;
  2. o contexto pai é um contexto de curso e o curso não é o do site;
  3. `require_login($course, false)`;
  4. `require_capability('block/coursecare:view', context_block::instance($id))`.
- Falha em qualquer etapa: exceção padrão do Moodle (registro inexistente, login ou permissão), sem
  nenhum dado do diagnóstico na resposta.
- A sala exibida é sempre a do contexto pai da instância (FR-010).
- Página: `set_url` com o `id`, contexto do curso (pelo `require_login`), `set_pagelayout('incourse')`,
  título e cabeçalho `overview:title`, trilha "sala › `overview:title`".
- Sem ações que alterem estado (não há `sesskey`): a página só lê.

## Estrutura (FR-011)

Hierarquia de títulos: `h2` para o título da página (o cabeçalho do curso vem do tema), `h3` por
seção. Cada seção pode ser um cartão discreto (`card` do core) conforme as diretrizes visuais.

| # | Seção | Conteúdo | Recolhida |
| --- | --- | --- | --- |
| 1 | Resumo | Total "como registrado pelo Moodle", data da análise, aviso do estado (E, F), cobertura (H), link "Voltar à sala" | Não |
| 2 | Onde estão os arquivos | Quatro categorias da sala: nome, frase explicativa, volume, proporção em texto e barra decorativa | Não |
| 3 | Materiais por atividade | Até 10 atividades por volume de materiais (nome com link, volume, proporção); nível da sala; "outras atividades"; "removidas após a última análise"; `<details>` "Mostrar todas" com o restante | Restante sim |
| 4 | Arquivos de grande porte | Quantidade e volume da sala, limite usado e frase de triagem; materiais grandes por atividade; aviso de limite de 1.000 quando aplicável | Não |
| 5 | Detalhes técnicos | `<details>` recolhido: Conteúdo único, referências, cópias de segurança e lixeira, cobertura, limite usado, o que os números não significam | Sim |

## Estados (FR-020)

| Estado | Seções |
| --- | --- |
| A, F sem resultado | Só o resumo com `diagnostic:notavailable` e o link de volta |
| B, C sem resultado | Só o resumo com `diagnostic:preparing` e o link de volta |
| D | 1 a 5 |
| E | 1 a 5; resumo com `diagnostic:mayoutdated` |
| F com resultado | 1 a 5 do resultado anterior; resumo com `diagnostic:updatefailed` |
| G | Resumo com `diagnostic:nofiles` e data; 5 disponível; 2 a 4 omitidas |
| H | Como D ou G; resumo com `overview:partial` (registros não classificados e proporção) |

Com resultado e nova análise pendente: mostra o resultado vigente (FR-021). Nenhum zero sem resultado
(FR-022).

## Strings (propostas; texto final na revisão de tom, SC-008)

| Chave | en | pt_br |
| --- | --- | --- |
| `overview:title` | Your course in numbers | Sua sala em números |
| `overview:intro` | Get to know the files of your course: where they are and when they were measured. | Conheça os arquivos da sua sala: onde estão e quando foram medidos. |
| `overview:back` | Back to the course | Voltar à sala |
| `overview:total` | Files in this course, as recorded by Moodle: {$a} | Arquivos desta sala, conforme registrados no Moodle: {$a} |
| `overview:partial` | Part of the files ({$a}) could not be classified; they are included in the total. | Parte dos arquivos ({$a}) não pôde ser classificada; ela está incluída no total. |
| `overview:categories` | Where the files are | Onde estão os arquivos |
| `overview:category:material` | Course materials | Materiais da sala |
| `overview:category:material_help` | Files you and other editors published: resources, folders, pages, books and descriptions. | Arquivos publicados por quem edita a sala: recursos, pastas, páginas, livros e descrições. |
| `overview:category:submission` | Files submitted in activities | Arquivos enviados em atividades |
| `overview:category:submission_help` | Files sent by participants in assignments and quizzes, shown only for the whole course. | Arquivos enviados por participantes em tarefas e questionários, mostrados só para a sala inteira. |
| `overview:category:other` | Other associated files | Outros arquivos associados |
| `overview:category:other_help` | Feedback, forum attachments, question and content banks. | Comentários de avaliação, anexos de fóruns, bancos de questões e de conteúdo. |
| `overview:category:unclassified` | Not classified | Não classificados |
| `overview:category:unclassified_help` | Files from areas the overview does not recognise yet; they count in the total. | Arquivos de áreas que o panorama ainda não reconhece; eles contam no total. |
| `overview:share` | {$a}% | {$a}% |
| `overview:shareless` | less than 1% | menos de 1% |
| `overview:activities` | Materials by activity | Materiais por atividade |
| `overview:activities_help` | Only the materials of each activity are shown here. | Aqui aparecem só os materiais de cada atividade. |
| `overview:courselevel` | Course page, sections and blocks | Página da sala, seções e blocos |
| `overview:otheractivities` | Other activities | Outras atividades |
| `overview:removed` | Activities removed after the last analysis | Atividades removidas após a última análise |
| `overview:showall` | Show all activities ({$a}) | Mostrar todas as atividades ({$a}) |
| `overview:large` | Large files | Arquivos de grande porte |
| `overview:large_summary` | Files of {$a->threshold} or more: {$a->count} ({$a->size}). | Arquivos de {$a->threshold} ou mais: {$a->count} ({$a->size}). |
| `overview:large_help` | The threshold is a screening value set by the site, not a teaching rule. | O limite é um valor de triagem definido pelo site, não uma regra pedagógica. |
| `overview:large_materials` | Large materials by activity | Materiais de grande porte por atividade |
| `overview:large_truncated` | This list considers the 1,000 largest materials. | Esta lista considera os 1.000 maiores materiais. |
| `overview:technical` | Technical details | Detalhes técnicos |
| `overview:unique` | Unique content: {$a} | Conteúdo único: {$a} |
| `overview:unique_help` | The same file counted once, even when used in several places. The same content may also exist in other courses, so this is not space used only by this course. | O mesmo arquivo contado uma vez, mesmo usado em vários lugares. O mesmo conteúdo pode existir em outras salas, então isto não é espaço ocupado só por esta sala. |
| `overview:references` | Links to files kept elsewhere | Ligações a arquivos guardados em outro lugar |
| `overview:references_help` | Counted apart and not added to the total. External links may point to files outside Moodle. | Contadas à parte e não somadas ao total. Ligações externas podem apontar para arquivos fora do Moodle. |
| `overview:references_internal` | Inside Moodle: {$a->count} ({$a->size}) | No Moodle: {$a->count} ({$a->size}) |
| `overview:references_external` | External: {$a->count} ({$a->size}) | Externas: {$a->count} ({$a->size}) |
| `overview:references_undetermined` | Size not known: {$a} | Tamanho desconhecido: {$a} |
| `overview:copies` | Backups and recycle bin recorded in Moodle for this course | Cópias de segurança e lixeira registradas no Moodle para esta sala |
| `overview:copies_help` | Measured apart, not added to the total; backups kept outside Moodle are not included. | Medidas à parte, não somadas ao total; cópias guardadas fora do Moodle não entram. |
| `overview:copies_backup` | Backups: {$a->count} ({$a->size}) | Cópias de segurança: {$a->count} ({$a->size}) |
| `overview:copies_recyclebin` | Recycle bin: {$a->count} ({$a->size}) | Lixeira: {$a->count} ({$a->size}) |
| `overview:threshold` | Large-file threshold used: {$a} | Limite de grande porte usado: {$a} |
| `overview:notmeaning` | These numbers show the files Moodle records for this course. They are not the disk space used only by this course and do not show how much space deleting files would free. | Estes números mostram os arquivos que o Moodle registra para esta sala. Eles não são o espaço em disco ocupado só por ela e não indicam quanto espaço seria liberado ao apagar arquivos. |

Reaproveitadas da Feature 002: `diagnostic:notavailable`, `preparing`, `nofiles`, `calculatedon`,
`mayoutdated`, `updatefailed`. Tamanhos com `display_size()`, datas com `userdate()`.

## Acessibilidade e responsividade (FR-050)

- Títulos em ordem (`h2` → `h3`); listas como `<ul>`/`<ol>`; cada proporção em texto; barras com
  `aria-hidden="true"`; nenhuma informação só por cor.
- `<details>`/`<summary>` para seções recolhíveis e "Mostrar todas": teclado nativo, estado
  anunciado; o texto do `summary` diz o que abre.
- Foco visível do tema em todos os links e `summary`; nenhum `tabindex` positivo.
- Contraste: 4,5:1 para texto, 3:1 para barras e bordas necessárias.
- A 320 px: uma coluna, sem rolagem horizontal, nomes longos quebram linha.
- Verificação automática (axe) em Behat nos estados D, G e sem resultado, em desktop e celular.

## Desempenho (SC-003, FR-040)

- Leituras: resumo, categorias, atividades e grandes agrupados (tabelas próprias, por índice), mais o
  cache de módulos do core. Nenhuma consulta a `files`, nenhuma escrita.
- Teste: `$DB->set_debug(true)` em volta da renderização da página, sem o nome `{prefix}files`.
- Medição: `tools/dev/bench/render.php --page` numa sala com 200 atividades com materiais.
