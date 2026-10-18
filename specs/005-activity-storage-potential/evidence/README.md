# Evidências da Feature 005 — Potencial das tarefas

Branch `005-activity-storage-potential` (local, sem push; base: `004-recommendations` `9274654`).
Nenhum item é marcado sem evidência. Validações humanas pendentes ficam como pendentes.

## Cenários e critérios

| Item | Evidência | Situação |
| --- | --- | --- |
| CT-01 tarefa padrão | `storage_potential_test` (900 MB), `course_overview_test::test_potential_section` (exemplo da spec renderizado), conferência à mão (T027) | ✅ |
| CT-02 tamanho zero (padrão) | `storage_potential_test` (padrão do site, empates), T027 (*Relatório parcial*: padrão 0 → limite da sala) | ✅ |
| CT-03 limite da sala menor | `storage_potential_test`, `test_potential_section` (sala de 512 KB), T027 (*Pôster do projeto*) | ✅ |
| CT-04 limite do servidor menor | `storage_potential_test` (servidor 2 MB, só servidor) | ✅ (o servidor de desenvolvimento tem 200 MB; o caso na página só é visível com PHP configurado abaixo dos limites das tarefas) |
| CT-05 tentativas | `storage_potential_test` (none ×1, máximo 1, ×3, ilimitado), `test_potential_section`, T027 | ✅ |
| CT-06 grupos | `storage_potential_test` (6 grupos, agrupamento, sem grupos, envio fora de grupo impedido, dois grupos, agrupamento removido), `assign_settings_test::test_groups_match_count_teams` (igual a `assign::count_teams()` em 6 cenários), T027 | ✅ |
| CT-07 participantes | `assign_settings_test::test_settings_and_participants` (suspenso, professor, sobreposição, papel local; igual a `count_enrolled_users()`) | ✅ |
| CT-08 sem envio de arquivos | `assign_settings_test`, `test_potential_section`, Behat | ✅ |
| CT-09 configuração incompleta | `storage_potential_test`, `assign_settings_test` (registro apagado), `test_potential_states`, Behat | ✅ |
| CT-10 sala sem tarefas com arquivos | `test_potential_states`, Behat | ✅ |
| CT-11 privacidade | `test_potential_privacy_and_tone` (nomes de estudante, grupo e arquivo ausentes) | ✅ |
| CT-12 renderização | `assign_settings_test::test_reads` (nenhuma leitura de `files`, `assign_submission`, `assign_grades`; leituras constantes de 5 para 20 tarefas), medição T028 | ✅ |
| CT-13 textos | `test_potential_privacy_and_tone` (palavras proibidas en e pt_br); revisão de tom T029 | ⏳ aprovação do proprietário do produto |
| CT-14 acessibilidade e visual | Behat `@accessibility` (axe, desktop e celular), T030 (axe, teclado, zoom, 320 px, árvore) | ⏳ aprovação visual |
| SC-001 | CT-01 a CT-10 acima | ✅ |
| SC-002 | revisão de tom | ⏳ |
| SC-003 | T028 | ✅ |
| SC-004 | CT-11 | ✅ |
| SC-005 | Behat `@accessibility`, T030 | ✅ (verificação automática); leitor de tela não exigido pela spec 005, mas pendente para 003/004 |
| SC-006 | capturas T030 | ⏳ aprovação |
| SC-007 | CI | ⏳ push depende de autorização |

## Conferência à mão (T027)

Site de demonstração (Moodle 4.5.15, PHP 8.1, limites do PHP 200 MB), sala *Projeto integrador
(demonstração)* criada por `tools/dev/demo/setup.php` (limite da sala 20 MB, padrão do plugin 0,
3 estudantes; Grupo A com Ana e Bruno, Grupo B com Carla; agrupamento *Projeto* só com o Grupo A).

| Tarefa | Configuração | Cálculo à mão | Página |
| --- | --- | --- | --- |
| Trabalho final | 3 arquivos, 10 MB, 1 tentativa | 3 × 3 × 10 MB = 90 MB; limite da tarefa | 90,0 MB, "definido na tarefa" ✅ |
| Relatório parcial | 3 arquivos, tamanho 0 (padrão 0) | menor > 0: sala 20 MB → 3 × 3 × 20 = 180 MB | 180,0 MB, "configurações da sala" ✅ |
| Pôster do projeto | 1 arquivo, 50 MB | sala 20 MB → 3 × 1 × 20 = 60 MB | 60,0 MB, "configurações da sala" ✅ |
| Exercícios | 3 tentativas, reabertura manual | 3 × 3 × 3 × 10 = 270 MB | 270,0 MB (3 × 3 tentativas × 3 × 10,0 MB) ✅ |
| Diário de bordo | tentativas ilimitadas | sem teto; 90 MB por tentativa | "Sem teto definido … 90,0 MB" ✅ |
| Projeto em grupo | envio em grupo, sem agrupamento | grupos A e B → 2 × 3 × 10 = 60 MB | 2 grupos, 60,0 MB ✅ |
| Projeto em grupo (agrupamento) | agrupamento com o Grupo A | A + grupo padrão (Carla fora do agrupamento) = 2 | 2 grupos, 60,0 MB ✅ |
| Reflexão escrita | só texto online | fora da lista | ausente ✅ |
| Entrega sem arquivos | máximo de arquivos 0 | sem número | "Nenhum arquivo pode ser enviado…" ✅ |
| Rascunho | oculta | potencial da configuração, com a indicação | "Oculta para os estudantes…", 90,0 MB ✅ |

## Desempenho (T028, SC-003)

`tools/dev/bench/potential.php` no site de desenvolvimento (PostgreSQL 17, PHP 8.1): sala com 200
atividades (150 páginas, 50 tarefas com arquivos, 5 em grupo), 500 estudantes em 25 grupos, com 0, 5 e
50 tarefas com sobreposição de `mod/assign:submit`. `render.php --page` (página inteira: diagnóstico,
estrutura, potencial e template), saídas em `logs/t028/`:

| Sala | Sobreposições | Média (20 aberturas) | Leituras | Primeira abertura depois de limpar os caches |
| --- | --- | --- | --- | --- |
| 8 | 0 | 16,6 ms | 15 | 88,7 ms (288 leituras) |
| 11 | 5 | 18,9 ms | 24,8 | — |
| 12 | 50 (pior caso) | 62,3 ms | 114,8 | 120,2 ms (388 leituras) |

- O pior caso fica muito abaixo de 2 s; não há decisão a levar ao proprietário do produto (research R2).
- A estimativa da pesquisa (146 ms por contexto) não se confirmou na página (cerca de 1 ms por
  contexto); a causa exata da diferença não foi isolada (research R2).
- **Bloco**: o código do bloco é idêntico ao da 0.4.0 (`git diff 9274654 -- block_coursecare.php
  classes/output/diagnostic_line.php templates/content.mustache classes/local/collector/diagnostic_store.php db/`
  vazio); `render.php --course=8`: 0,83 ms e 4 leituras por renderização (`logs/t028/block-8.json`).
- Os tempos são do servidor (renderização); a abertura no navegador não foi cronometrada nesta feature.
- As salas 9 e 10 da primeira tentativa ficaram sem sobreposição (`role_change_permission()` descarta
  uma permissão igual à herdada; corrigido com `assign_capability()`); não foram usadas nas medições.

## Revisão de tom (T029) — pendente de aprovação

Strings novas (contracts/potential-ui.md), en e pt_br:

| Chave | en | pt_br |
| --- | --- | --- |
| `potential:heading` | Potential of assignments | Potencial das tarefas |
| `potential:intro` | What the current settings of the assignments allow to be stored in submitted files. Calculated now, with the current settings. | O que as configurações atuais das tarefas permitem guardar em arquivos enviados. Calculado agora, com as configurações atuais. |
| `potential:meaning` | The theoretical potential is a ceiling set by the settings: it is not the space used, a forecast or the amount that will be submitted. Different settings serve different teaching goals. | O potencial teórico é um teto das configurações: não é o espaço usado, uma previsão nem o volume que será enviado. Configurações diferentes atendem objetivos pedagógicos diferentes. |
| `potential:scope` | This section covers assignments only. | Esta seção considera só as tarefas. |
| `potential:recorded` | In this course, files already submitted in activities add up to {$a->size} (analysis of {$a->date}). This total is not compared assignment by assignment with the potential. | Nesta sala, os arquivos já enviados em atividades somam {$a->size} (análise de {$a->date}). Esse total não se compara tarefa a tarefa com o potencial. |
| `potential:none` | No assignment in this course accepts files at the moment. | Nenhuma tarefa desta sala aceita arquivos no momento. |
| `potential:hidden` | Hidden from students: nobody can submit while it is hidden. | Oculta para os estudantes: ninguém pode enviar enquanto ela estiver oculta. |
| `potential:files_participant` | Up to {$a->files} files per participant, of up to {$a->size} each. | Até {$a->files} arquivos por participante, de até {$a->size} cada. |
| `potential:files_participant_one` | Up to 1 file per participant, of up to {$a->size}. | Até 1 arquivo por participante, de até {$a->size}. |
| `potential:files_group` | Up to {$a->files} files per group, of up to {$a->size} each. | Até {$a->files} arquivos por grupo, de até {$a->size} cada. |
| `potential:files_group_one` | Up to 1 file per group, of up to {$a->size}. | Até 1 arquivo por grupo, de até {$a->size}. |
| `potential:limit_activity` | The size limit per file is set in the assignment. | O limite por arquivo é definido na tarefa. |
| `potential:limit_default` | The size limit per file is the site default for assignments. | O limite por arquivo é o padrão do site para tarefas. |
| `potential:limit_course` | The size limit per file comes from the course settings. | O limite por arquivo vem das configurações da sala. |
| `potential:limit_site` | The size limit per file comes from the site settings. | O limite por arquivo vem das configurações do site. |
| `potential:limit_server` | The size limit per file comes from the server settings. | O limite por arquivo vem das configurações do servidor. |
| `potential:attempts_one` | 1 attempt. | 1 tentativa. |
| `potential:attempts` | Up to {$a} attempts. | Até {$a} tentativas. |
| `potential:attempts_unlimited` | Unlimited attempts. | Tentativas ilimitadas. |
| `potential:units_participants` | {$a} participants can submit. | {$a} participantes podem enviar. |
| `potential:units_participants_one` | 1 participant can submit. | 1 participante pode enviar. |
| `potential:units_groups` | {$a} groups can submit. | {$a} grupos podem enviar. |
| `potential:units_groups_one` | 1 group can submit. | 1 grupo pode enviar. |
| `potential:value` | Theoretical potential: {$a->size} ({$a->formula}). | Potencial teórico: {$a->size} ({$a->formula}). |
| `potential:formula` | {$a->units} × {$a->files} × {$a->size} | {$a->units} × {$a->files} × {$a->size} |
| `potential:formula_attempts` | {$a->units} × {$a->attempts} attempts × {$a->files} × {$a->size} | {$a->units} × {$a->attempts} tentativas × {$a->files} × {$a->size} |
| `potential:unlimited` | No defined ceiling: with unlimited attempts, each attempt can hold up to {$a->size} ({$a->formula}). | Sem teto definido: com tentativas ilimitadas, cada tentativa pode guardar até {$a->size} ({$a->formula}). |
| `potential:nobody` | Nobody can submit in this assignment at the moment. | Ninguém pode enviar nesta tarefa no momento. |
| `potential:nofiles` | No file can be submitted with the current settings. | Nenhum arquivo pode ser enviado com a configuração atual. |
| `potential:unknown` | The potential of this assignment cannot be calculated with the available information. | O potencial desta tarefa não pode ser calculado com as informações disponíveis. |
| `potential:how` | How the potential is calculated, and its limits | Como o potencial é calculado e seus limites |
| `potential:how_formula` | Participants (or groups) × attempts × files per submission × size limit per file, with the current settings. | Participantes (ou grupos) × tentativas × arquivos por envio × limite por arquivo, com as configurações atuais. |
| `potential:how_attempts` | Each attempt keeps its own files. When a student starts from the previous submission, the files are copied: the copies count in the files recorded by Moodle, but identical content is stored only once. | Cada tentativa guarda os seus arquivos. Quando o estudante parte do envio anterior, os arquivos são copiados: as cópias contam nos arquivos registrados pelo Moodle, mas conteúdo idêntico é guardado uma vez só. |
| `potential:limit_dates` | Due dates and access restrictions are not considered. | Datas e restrições de acesso não são consideradas. |
| `potential:limit_ignore` | People allowed to ignore file size limits (usually not students) have no ceiling. | Quem pode ignorar os limites de tamanho de arquivo (normalmente não estudantes) não tem teto. |
| `potential:limit_other` | Files inserted in online text and feedback files are not included. | Arquivos inseridos no texto online e arquivos de feedback não entram. |
| `potential:how_server` | Server limits are the ones in force when this page was shown. | Os limites do servidor são os vigentes quando esta página foi mostrada. |
| `potential:limit_change` | Participants, groups and settings can change; the potential is valid for now. | Participantes, grupos e configurações podem mudar; o potencial vale para agora. |

Ponto para decisão: numa tarefa oculta, a frase "Oculta para os estudantes: ninguém pode enviar
enquanto ela estiver oculta." aparece junto de "3 participantes podem enviar." (a contagem descreve a
configuração, como decidido no contrato). Pode ser lida como contradição.

## Validação visual (T030) — pendente de aprovação

Site de demonstração atualizado para a 0.5.0 (Moodle 4.5.15, PHP 8.1, PostgreSQL 17), professor de
demonstração, temas Boost e Classic, en e pt_BR (`pt_br_local` atualizado com `translations/pt_br`).

- **Capturas** em `screenshots/boost/` e `screenshots/classic/` (12 por tema):
  - página de *Projeto integrador* com a seção recolhida e aberta (`page-potential-*`, `pageopen-potential-*`),
    desktop e celular (375 px);
  - página de *Biologia celular* (`page-complete-*`): seção com a frase do armazenamento registrado da sala;
  - bloco de *Projeto integrador* (`block-potential-*`): igual ao da 0.4.0, sem potencial.
- **Verificações** com `tools/dev/demo/checks.py`, resultados em `logs/t030/<tema>-<língua>/`:

| Verificação | Páginas | Boost en | Boost pt_BR | Classic en | Classic pt_BR |
| --- | --- | --- | --- | --- | --- |
| axe-core, WCAG 2.0/2.1/2.2 A e AA | *Projeto integrador*, *Biologia celular* | 0 violações | 0 violações | 0 violações | 0 violações |
| Zoom de 200% e 320 px | *Projeto integrador* | sem rolagem horizontal | idem | idem | idem |
| Teclado | *Projeto integrador* | 12 paradas, todas com indicador; "Como o potencial é calculado" abre com Enter | idem | idem | idem |
| Árvore de acessibilidade | *Projeto integrador* | registrada (`tree-page-potential.json`) | idem | idem | idem |

Pontos para a decisão do proprietário do produto:

1. **Tarefa oculta** (ver a revisão de tom acima).
2. **Limite do servidor quando a tarefa e o site não definem tamanho**: em *Biologia celular*, o
   *Relatório de laboratório* usa "padrão do site" e o padrão do plugin é 0; vale o limite do PHP do
   servidor de desenvolvimento (200 MB), e a página mostra "Potencial teórico: 2,9 GB (3 × 5 × 200,0 MB)"
   ao lado de 1,9 MB de envios registrados. O cálculo segue a regra do Moodle e a frase do que o
   potencial não significa está acima; é o caso que mais pode ser lido como alarme.
3. **Extensão da lista**: cinco linhas por tarefa (configuração, origem do limite, tentativas, quem pode
   enviar, resultado); numa sala com muitas tarefas a seção fica longa.
4. **Leitor de tela**: não realizado (exige uma pessoa); a spec 005 não o exige, mas o roteiro da
   003/004 pode incluir a seção.

## Revisão de código (2026-10-18)

Duas divergências com o comportamento do Moodle 4.5, ambas no número mostrado, corrigidas:

| Achado | Comportamento do Moodle | Correção |
| --- | --- | --- |
| Reabertura "até passar" multiplicava pelo máximo de tentativas | `assign::reopen_submission_if_required()` só reabre se `grade_grade::is_passed()` não for nulo: a tarefa precisa de nota por pontos ou escala e de nota para aprovação (não vazia, diferente da mínima e, na escala, diferente de zero); o pedido manual de nova tentativa só existe na reabertura manual. "Até passar" é o padrão do Moodle 4.5 | O coletor lê do item de nota (`grade_items`, `itemnumber = 0`) o tipo, a nota para aprovação e a mínima; a regra dá 1 tentativa sem critério. Metodologia item 3, FR-013, R5 e contrato atualizados |
| Plugin de envio de arquivos desativado no site ainda listava tarefas | O Moodle só usa um plugin de envio habilitado na tarefa **e** visível no site (`assignsubmission_file \| disabled`) | `enabled` falso com o plugin desativado; nenhuma tarefa na lista. Metodologia item 6, R6 e contrato atualizados |

Testes novos: 12 casos de tentativas em `storage_potential_test` (manual, automática, até passar com e sem
critério, escala, sem nota, ilimitado), leitura do item de nota e plugin desativado em
`assign_settings_test`, e na página (`test_potential_section`, `test_potential_states`). O cenário
`@accessibility` do Behat passou a declarar reabertura manual (o padrão "até passar" sem critério daria 1
tentativa). As tarefas de demonstração não usam "até passar" com mais de uma tentativa: os resultados
renderizados depois da correção são idênticos aos da conferência à mão e das capturas.

## Testes e CI

Sobre o código de `9fbb6ca` mais os arquivos de Polish (sem mudança de código de produção depois dos testes):

- PHPUnit `--testsuite block_coursecare_testsuite`: **234 testes, 2398 asserções, OK** (PHP 8.1, PostgreSQL 17);
  depois da revisão de código, **244 testes, 2429 asserções, OK**.
- Behat `--tags=@block_coursecare`: **61 cenários, 686 passos, todos verdes** (antes e depois da revisão de código) (inclui os 8 da 005, com axe em
  desktop e celular).
- `tools/i18n/check.php`: 0 erros (6 avisos de strings iguais ao inglês: as unidades de tamanho e
  `potential:formula`, por natureza).
- phpcs e Mustache lint: **não rodados localmente** (sem `moodle-plugin-ci`/`phpcs` no ambiente); conferidos à
  mão linha ≤ 132, espaços e sintaxe. Rodam no CI.
- Pacote (T031): `tools/package/build.sh` e `tools/package/check.php build/block_coursecare-0.5.0.zip`:
  **0 erros, 78 arquivos** (a primeira tentativa acusou `phpunit.xml` gerado pelo PHPUnit e versionado por
  engano em `21178d6`; removido e ignorado no commit seguinte).
- CI (T032): **pendente** — a branch não foi publicada; o push depende da autorização do proprietário do
  produto.
