# Contract: matriz técnica de cobertura (regras de classificação)

Fonte única da classificação (FR-021). Em PHP ela é uma tabela de regras na camada de análise; a
coleta gera dela a expressão SQL `CASE` (R12). Um teste garante que as duas formas classificam
igual. Base: Moodle 4.5.15. As linhas M* remetem à matriz da spec.

## Ordem de avaliação

1. Diretório (`filename = '.'`) → **ignorado** (M1). Filtrado na consulta.
2. Referência (`referencefileid` não nulo) → **referência** (M16, M17). Fora de A, B, categorias,
   atividades e do agregado; tipo pela regra de R4.
3. Primeira regra da tabela que casar (componente, área, nível de contexto) → resultado da regra.
4. Nenhuma regra → **Não classificado** (M18), com `partial = 1`.

Áreas: casamento exato, exceto onde indicado "prefixo". Contextos: CRS = curso, MOD = módulo,
BLK = bloco; "qualquer" = qualquer contexto do escopo da sala.

## Regras

| # | Componente | Área | Contexto | Resultado | Evidência (código 4.5) | Matriz |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | `mod_folder` | `temp` | MOD | excluído | `mod/folder/lib.php:376-383` | M4 |
| 2 | `mod_book` | `importhtmltemp` | MOD | excluído | `mod/book/tool/importhtml/locallib.php:45-46,152` | M4 |
| 3 | `assignfeedback_file` | `feedback_files_batch` | MOD | excluído | `mod/assign/feedback/file/locallib.php:33,501-543` | M4 |
| 4 | `assignfeedback_file` | `feedback_files_import` | MOD | excluído | `locallib.php:34`; `importziplib.php:180-188` | M4 |
| 5 | `assignfeedback_editpdf` | `tmp_jpg_to_pdf` | MOD | excluído | `document_services.php:60,1077` | M4 |
| 6 | `assignfeedback_editpdf` | `tmp_rotated_jpg` | MOD | excluído | `document_services.php:62,1098` | M4 |
| 7 | `assignfeedback_editpdf` | `importhtml` | MOD | excluído | `document_services.php:50,223-227` | M4 |
| 8 | `backup` | `course`, `section`, `automated` | CRS | agregado: backup | `backup/util/helper/backup_helper.class.php:262-334` | M12 |
| 9 | `backup` | `activity` | MOD | agregado: backup | idem | M12 |
| 10 | `tool_recyclebin` | `recyclebin_course` | CRS | agregado: lixeira | `admin/tool/recyclebin/classes/course_bin.php:29,176-189` | M13 |
| 11 | `course` | `summary`, `overviewfiles`, `section` | CRS | Materiais | `course/lib.php:1927,1933`; `course/editsection_form.php:129` | M5 |
| 12 | `mod_resource` | `content` | MOD | Materiais | `mod/resource/locallib.php:549` | M6 |
| 13 | `mod_folder` | `content` | MOD | Materiais | `mod/folder/lib.php:120,149` | M6 |
| 14 | `mod_page` | `content` | MOD | Materiais | `mod/page/lib.php:124,167` | M6 |
| 15 | `mod_book` | `chapter` | MOD | Materiais | `mod/book/edit.php:92,119` | M6 |
| 16 | `mod_assign` | `introattachment`, `activityattachment` | MOD | Materiais | `mod/assign/locallib.php:81,84`; `mod_form.php:324` | M7 |
| 17 | `mod_*` (prefixo de componente) | `intro` | MOD | Materiais | `course/modlib.php:174,657,805` | M6, M7 |
| 18 | `block_html` | `content` | BLK | Materiais | `blocks/html/block_html.php:137` | M15 |
| 19 | `assignsubmission_file` | `submission_files` | MOD | Envios em atividades | `mod/assign/submission/file/locallib.php:31,221-227` | M8 |
| 20 | `assignsubmission_onlinetext` | `submissions_onlinetext` | MOD | Envios em atividades | `mod/assign/submission/onlinetext/locallib.php:32,218-224` | M8 |
| 21 | `question` | `response_` (prefixo) | MOD | Envios em atividades | `question/engine/questionattemptstep.php:274-276`; `mod/quiz/locallib.php:2050` | M8 (ampliação, R5) |
| 22 | `assignfeedback_file` | `feedback_files` | MOD | Outros | `feedback/file/locallib.php:32,291-297` | M9 |
| 23 | `assignfeedback_comments` | `feedback` | MOD | Outros | `feedback/comments/locallib.php:34,397-405` | M9 |
| 24 | `assignfeedback_editpdf` | `download`, `readonlypages`, `stamps` | MOD | Outros | `document_services.php:44,54,786,832`; `editpdf/locallib.php:86,113` | M9, M10 |
| 25 | `assignfeedback_editpdf` | `combined`, `partial`, `pages` | MOD | Outros | `document_services.php:46-52,293-294,432` | M10 |
| 26 | `grade` | `feedback`, `historyfeedback` | MOD, CRS | Outros | `lib/grade/grade_grade.php:1094-1099`; `grade_item.php:2597-2616` | M9 (ampliação, R5) |
| 27 | `mod_forum` | `attachment`, `post` | MOD | Outros | `mod/forum/lib.php:2671-2676,2874,2926` | M11 |
| 28 | `question` | qualquer outra área | CRS, MOD | Outros | `backup/moodle2/backup_stepslib.php:2509-2524`; `lib/questionlib.php:1256-1259` | M14 |
| 29 | `qtype_` (prefixo de componente) | qualquer | CRS, MOD | Outros | `question/type/*/questiontype.php` (áreas `bgimage`, `dragimage`, `graderinfo`, `subquestion`…) | M14 |
| 30 | `contentbank` | `public` | CRS | Outros | `contentbank/classes/contentbank.php:33`; `content.php:312` | M14 |

Regras 1–7 vêm antes das demais, e a regra 21 vem antes da 28, para que a primeira regra que casar
seja a mais específica.

## Fora do escopo por construção (nunca no contexto da sala)

| Situação | Onde | Evidência | Matriz |
| --- | --- | --- | --- |
| Rascunhos | `user/draft`, contexto do usuário | `lib/filelib.php` (área de rascunho por usuário) | M2 |
| Arquivos privados, backups do usuário, `tohub` | `user/private`, `user/backup`, `user/tohub` | `backup_helper.class.php:321-326` | M3, M12a |
| Lixeira de categoria | `tool_recyclebin/recyclebin_coursecat`, contexto da categoria | `category_bin.php:29,161-174` | M13a |
| Conteúdo H5P implantado, carimbos do site, conversões | contexto do sistema | `h5p/classes/file_storage.php:73-74`; `editpdf/locallib.php:81,93`; `files/classes/conversion.php:380-382` | — |
| Backups automáticos em diretório | sistema de arquivos | `backup_helper.class.php:291-317` (`backup_auto_storage = 1`) | M19 |
| Prévia de questões | contexto do usuário | `question/bank/previewquestion/preview.php:118-119` | — |

## Não classificado conhecido (contam em A e B; `partial = 1`)

`course/legacy`, `group/*`, `grouping/*`, `calendar/event_description`, `badges/badgeimage`,
`customfield_textarea/value`, `mod_feedback/*` (exceto `intro`), `mod_lesson/*`, `mod_workshop/*`,
`workshopform_*`, `mod_glossary/*`, `mod_data/*`, `mod_wiki/*`, `mod_scorm/*`, `mod_h5pactivity/*`,
`mod_imscp/*`, `mod_quiz/feedback`, `quizaccess_seb/*`, `grading/description`,
`mod_bigbluebuttonbn/presentation` e qualquer componente de terceiros. Motivo: autoria mista
(estudantes e professores na mesma área) ou contrato não incluído no conjunto inicial (FR-025).
Classificá-los exige uma nova linha nesta tabela com evidência e teste.

## Limitações registradas

- Fórum mistura material e participação (M11).
- `grade/feedback` pode estar no contexto do curso quando o módulo não existe mais (fallback do core).
- `backup/course` também recebe, por instantes, backups de cópia de curso, apagados ao fim da cópia
  (`lib/classes/task/asynchronous_copy_task.php:146,206`).
- A limpeza de órfãos do `assignfeedback_editpdf` pode deixar `importhtml` para trás (comparação por
  id de nota com um id de envio, `remove_orphaned_editpdf_files.php:53-58`): ele é excluído do
  escopo (regra 7), então não distorce o volume.

## Testes obrigatórios

Uma linha de teste por regra (casa e não casa), mais: precedência (regra 21 antes da 28; 1–7 antes
de 13, 15, 22 e 24), componente desconhecido, área desconhecida de componente conhecido e igualdade
entre a classificação em PHP e a expressão SQL para todas as linhas.
