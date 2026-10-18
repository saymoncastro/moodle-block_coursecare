---

description: "Task list for Feature 005 — Activity Storage Potential"
---

# Tasks: Activity Storage Potential

**Input**: Design documents from `specs/005-activity-storage-potential/` (spec `bf5395d`, plan `bbc063e`)

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/potential-calculation.md,
contracts/potential-ui.md, quickstart.md

**Tests**: obrigatórios pela constituição (Princípio IX: PHPUnit para toda regra; Behat para todo
fluxo de interface) e por FR-016. As tarefas de teste vêm antes da implementação de cada parte.

**Organization**: por história de usuário da spec (US1 P1, US2 P1, US3 P2), cada uma testável sozinha.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: pode rodar em paralelo (arquivos diferentes, sem dependência de tarefa incompleta)
- **[Story]**: história de usuário (US1, US2, US3)

## Path Conventions

Plugin de bloco do Moodle na raiz do repositório: `classes/`, `templates/`, `lang/`, `tests/`,
`translations/`, `tools/`. Testes PHPUnit e Behat rodam no stack de desenvolvimento (`coursecare-002`).

## Regras que valem para todas as tarefas

- **Camadas**: `$DB`, `$CFG`, `get_config()` e as APIs de inscrição, grupos e limite de upload só em
  `classes/local/collector/`; `classes/local/analysis/storage_potential.php` é pura (sem `$DB`, `$CFG`,
  sessão, `get_string()` ou saída); `classes/output/` monta frases e o contexto dos templates.
- **Privacidade (Q1 = A)**: por tarefa, só configurações, contagem de participantes ou grupos e o
  potencial teórico; nenhum volume, número ou lista de envios por tarefa; nenhum nome de estudante,
  grupo ou arquivo na saída; o armazenamento registrado só como o total da sala (FR-025).
- **Sem total (Q2 = A)** e **sem classificação (Q3 = A)**: nenhuma soma de potenciais, nenhuma ordenação
  por potencial, nenhuma palavra de adequado/inadequado, nenhuma sugestão nova.
- **Nunca**: consultar `files`, `assign_submission` ou notas; alterar configuração ou arquivo; criar
  tabela, campo, configuração, tarefa agendada, cache, capability ou JavaScript; mudar a análise da
  Feature 002 ou o bloco (FR-023).
- **Strings**: toda chave nova entra em `lang/en/block_coursecare.php` (ordem alfabética) e em
  `translations/pt_br/block_coursecare.php` na mesma tarefa, com o texto de contracts/potential-ui.md;
  frases completas, sem concatenação; nenhum texto fixo em template.
- **Nomes de atividade**: já formatados (`get_formatted_name()`); nos templates, `{{{name}}}` (sem escape
  duplo, como na correção `c053e3d` da 003).
- **Tamanhos**: sempre com `diagnostic_line::size()` (formato da localidade, até TB).
- **Visual**: estilos só sob `.block_coursecare-overview`, reaproveitando as propriedades
  `--block-coursecare-*` existentes; sem cor ou ícone de alerta.

---

## Phase 1: Setup

**Purpose**: versão e catálogo de strings.

- [X] T001 Em `version.php`, `$plugin->release = '0.5.0'` e `$plugin->version = 2026101801` (sem mudança de banco, sem upgrade step).
- [X] T002 [P] Acrescentar as 38 strings de contracts/potential-ui.md (`potential:heading`, `potential:intro`, `potential:meaning`, `potential:scope`, `potential:recorded`, `potential:none`, `potential:hidden`, `potential:files_participant`, `potential:files_participant_one`, `potential:files_group`, `potential:files_group_one`, `potential:limit_activity`, `potential:limit_default`, `potential:limit_course`, `potential:limit_site`, `potential:limit_server`, `potential:attempts_one`, `potential:attempts`, `potential:attempts_unlimited`, `potential:units_participants`, `potential:units_participants_one`, `potential:units_groups`, `potential:units_groups_one`, `potential:value`, `potential:formula`, `potential:formula_attempts`, `potential:unlimited`, `potential:nobody`, `potential:nofiles`, `potential:unknown`, `potential:how`, `potential:how_formula`, `potential:how_attempts`, `potential:limit_dates`, `potential:limit_ignore`, `potential:limit_other`, `potential:how_server`, `potential:limit_change`) em `lang/en/block_coursecare.php` e `translations/pt_br/block_coursecare.php`, em ordem alfabética, com o texto do contrato; conferir com `php tools/i18n/check.php` (0 erros).

---

## Phase 2: Foundational (estrutura da sala, regra pura e coletor)

**Purpose**: as leituras e a regra de cálculo que todas as histórias usam. Bloqueia US1, US2 e US3.

- [X] T003 [P] Em `tests/collector/course_structure_test.php`, acrescentar `test_activity_type_and_visibility`: uma tarefa visível, uma oculta e uma página → cada linha tem `modname` (`assign`, `page`), `instance` (id da instância) e `visible` (`false` só na oculta), além dos campos atuais; a ordem das chaves é a ordem da sala (`get_fast_modinfo()->get_cms()`).
- [X] T004 Em `classes/local/collector/course_structure.php`, acrescentar `modname`, `instance` (int) e `visible` (bool, `$cm->visible`) a cada linha de `get_activities()`; atualizar o PHPDoc; T003 e os testes atuais verdes.
- [X] T005 [P] Criar `tests/analysis/storage_potential_test.php` (`@covers \block_coursecare\local\analysis\storage_potential`) com um data provider para todos os casos obrigatórios de contracts/potential-calculation.md: padrão (30 × 3 × 10 MB, 1 tentativa → `potential` 943718400, origem `activity`); padrão do site (tarefa 0, padrão 20 MB, sala 0, site 0, servidor 100 MB → 20 MB, `default`); sala menor (tarefa 50 MB, sala 10 MB → `course`); site menor; servidor menor (2 MB → `server`); empates na ordem tarefa, padrão, sala, site, servidor; nenhum limite > 0 → `unknown`; tentativas: máximo 3 com reabertura `untilpass` → ×3, `-1` → `unlimited` com `attempts` nulo, `potential` nulo e `perattempt` preenchido, reabertura `none` com máximo 5 → ×1, máximo 1 com reabertura `manual` → ×1; `maxfiles` 0 e nulo → `nofiles`; 0 participantes → `nobody`; grupos: 6 grupos com participantes → 6; agrupamento com 2 de 4 grupos usados → 2; agrupamento + participante fora dele com `preventnogroup` falso → 3 (grupo padrão); sem grupos → 1; sem grupos e `preventnogroup` verdadeiro → `nobody`; participante em dois grupos (sem agrupamento) → grupo padrão; agrupamento inexistente (sem entrada em `groupings`) → só o grupo padrão quando permitido; valores grandes (500 × 10 × 20 × 2 GB, 20 tentativas) → inteiro exato, sem `float`. Cada caso confere `status`, `unit`, `units`, `files`, `filebytes`, `limitsource`, `attempts`, `perattempt` e `potential`.
- [X] T006 Criar `classes/local/analysis/storage_potential.php` (namespace `block_coursecare\local\analysis`, classe única, sem interface) com `public static function evaluate(array $settings, array $course): array`, seguindo as regras 1–6 de contracts/potential-calculation.md e devolvendo exatamente `['status' => 'potential'|'unlimited'|'nofiles'|'nobody'|'unknown', 'unit' => 'participant'|'group', 'units' => int, 'files' => int, 'filebytes' => int, 'limitsource' => 'activity'|'default'|'course'|'site'|'server', 'attempts' => ?int, 'perattempt' => int, 'potential' => ?int]`; grupos pela regra de research R3 (com agrupamento: grupos do agrupamento com pelo menos um participante; sem agrupamento: grupos dos participantes em exatamente um grupo; + 1 grupo padrão quando algum participante não tem grupo único entre os grupos considerados e `preventnogroup` é falso); PHPDoc conforme o Moodle; T005 verde.
- [X] T007 [P] Criar `tests/collector/assign_settings_test.php` (`@covers \block_coursecare\local\collector\assign_settings`): `for_course()` devolve por `cmid` `enabled`, `maxfiles`, `activitybytes`, `maxattempts`, `reopenmethod`, `teamsubmission`, `groupingid`, `preventnogroup`, `participants` e, da sala, `limits` (`server` = `get_max_upload_file_size()`, `site` = `$CFG->maxbytes`, `course` = `course.maxbytes`, `default` = `assignsubmission_file/maxbytes`), `memberships` e `groupings`; casos: tarefa com arquivos; tarefa só com texto online (`enabled` falso); registro `maxfilesubmissions` apagado (`maxfiles` nulo); `maxsubmissionsizebytes` apagado (0); participantes: estudante ativo conta, inscrição suspensa e professor sem `mod/assign:submit` não contam (CT-07); sobreposição `CAP_PROHIBIT` de `mod/assign:submit` no contexto de uma tarefa → só ela perde o estudante; estudante inscrito sem papel na sala com papel de estudante atribuído no contexto de uma tarefa → conta só nela; o número de participantes de cada tarefa é igual a `count_enrolled_users($modulecontext, 'mod/assign:submit', 0, true)`; envio em grupo: as unidades de `storage_potential::evaluate()` com os dados do coletor são iguais a `(new \assign($context, $cm, $course))->count_teams()` (sem inscrições suspensas nesses cenários, porque `count_teams()` usa `list_participants()`, que pode incluir suspensos conforme `show_only_active_users()`) nos cenários com 6 grupos, com agrupamento, sem grupos e com participante em dois grupos; leituras (`$DB->perf_get_reads()`) iguais para uma sala com 5 e com 20 tarefas sem sobreposição (custo independente do número de tarefas); nenhuma consulta às tabelas `files`, `assign_submission` ou `assign_grades` (conferir com o log de consultas do `$DB` no teste).
- [X] T008 Criar `classes/local/collector/assign_settings.php` com `public function for_course(int $courseid, array $cmids): array` devolvendo `['assigns' => [cmid => settings], 'limits' => [...], 'memberships' => [userid => groupid[]], 'groupings' => [groupingid => groupid[]]]` como contracts/potential-calculation.md: uma consulta às instâncias `assign` das tarefas indicadas; uma às linhas de `assign_plugin_config` com `plugin = 'file'`, `subtype = 'assignsubmission'` e `name` em `enabled`, `maxfilesubmissions`, `maxsubmissionsizebytes`; participantes da sala com uma consulta `get_enrolled_sql(context_course, 'mod/assign:submit', 0, true)`; uma consulta que encontra, entre os contextos das tarefas, os que têm `role_capabilities` com `capability = 'mod/assign:submit'` ou `role_assignments`; para esses, `get_enrolled_sql` no contexto da tarefa; uma consulta a `groups_members` com `groups` da sala (só quando alguma tarefa tem envio em grupo) e uma a `groupings_groups` para os agrupamentos usados; `limits['server'] = get_max_upload_file_size()`; nenhuma leitura de `files`, `assign_submission` ou notas; PHPDoc; T007 verde.

**Checkpoint**: leituras e regra prontas e testadas, comparadas com `count_enrolled_users()` e `count_teams()` do Moodle.

---

## Phase 3: User Story 1 - Entender o que as configurações de uma tarefa permitem (Priority: P1) 🎯 MVP

**Goal**: a página mostra a seção "Potencial das tarefas" com uma linha por tarefa com arquivos: nome
com link, configurações, origem do limite, tentativas, quem pode enviar e o potencial teórico.

**Independent Test**: numa sala com tarefas de configurações diferentes (padrão, sala menor, grupos,
tentativas ilimitadas, só texto online), a seção mostra cada tarefa com arquivos na ordem da sala, com
as frases e o potencial iguais ao cálculo à mão; a de texto online não aparece.

### Tests for User Story 1

- [ ] T009 [P] [US1] Em `tests/output/course_overview_test.php`, acrescentar `test_potential_section`: sala com tarefa padrão (30 estudantes, 3 arquivos, 10 MB, 1 tentativa), tarefa com 50 MB e sala com `maxbytes` 10 MB, tarefa em grupo com 6 grupos, tarefa com tentativas ilimitadas e tarefa só com texto online → o HTML tem `id="block_coursecare-potential"` depois da classe `block_coursecare-overview-activities` (quando há resultado) e antes de `block_coursecare-overview-technicalsection`; `potential:heading`; as tarefas na ordem da sala com os nomes com link (escapados uma vez); para a padrão, `potential:files_participant` (3, 10 MB), `potential:limit_activity`, `potential:attempts_one`, `potential:units_participants` (30) e `potential:value` com `potential:formula` (900 MB, 30 × 3 × 10 MB); para a da sala, `potential:limit_course`; para a de grupos, `potential:files_group` e `potential:units_groups` (6); para a ilimitada, `potential:attempts_unlimited` e `potential:unlimited`; com 3 tentativas, `potential:attempts` e `potential:formula_attempts`; singulares (`_one`) com 1 arquivo, 1 participante e 1 grupo; a tarefa de texto online não aparece; nenhum total (o HTML não contém a soma dos potenciais) e nenhum texto da seção no bloco (FR-023).
- [ ] T010 [P] [US1] Criar `tests/behat/block_coursecare_potential.feature` (`@block @block_coursecare`) com: sala com uma tarefa de arquivos (3 arquivos, 10 MB) e uma de texto online → na página, "Potential of assignments", o nome da tarefa de arquivos como link, "Up to 3 files per participant, of up to 10.0 MB each." e "Theoretical potential:"; a tarefa de texto online não aparece na seção; `@javascript @accessibility` Scenario Outline em `large` e `mobile` com "the page should meet accessibility standards".

### Implementation for User Story 1

- [ ] T011 [US1] Em `classes/output/course_overview.php`: o construtor aceita `array $potential = []` (lista na ordem da sala de `['name', 'url', 'visible', 'result' => saída de storage_potential::evaluate()]`); `for_course()` filtra em `course_structure::get_activities()` as tarefas (`modname = 'assign'`) visíveis ao professor (`uservisible`), lê `assign_settings::for_course()` e chama `storage_potential::evaluate()` para as que têm `enabled`; `potential_data()` monta `heading`, `items` (`name`, `url`, `hasurl`, `settings`, `limit`, `attempts`, `units`, `result`) com as frases de contracts/potential-ui.md (escolha de singular/plural e participante/grupo; tamanhos com `diagnostic_line::size()`; `potential:formula` com 1 tentativa e `potential:formula_attempts` com mais; `potential:unlimited` com a fórmula por tentativa); `export_for_template()` ganha `haspotential` (há ao menos uma tarefa visível ao professor) e `potential`; PHPDoc atualizado.
- [ ] T012 [P] [US1] Criar `templates/overview_potential.mustache` conforme contracts/potential-ui.md: `<section id="block_coursecare-potential" class="block_coursecare-overview-section block_coursecare-overview-potential">`, `h3`, `ul` com um `li` por tarefa (`{{{name}}}` com link, frases em `p`); documentação e contexto de exemplo válidos para o lint de Mustache.
- [ ] T013 [US1] Em `templates/overview.mustache`, incluir `{{#haspotential}}{{> block_coursecare/overview_potential }}{{/haspotential}}` depois da seção de atividades e antes de `technical`; atualizar documentação e contexto de exemplo.
- [ ] T014 [P] [US1] Em `styles.css`, estilos da seção e dos itens (`.block_coursecare-overview-potential`, `-potential-item`, valor do potencial em destaque tipográfico sem cor de alerta), reaproveitando `--block-coursecare-panel`, `--block-coursecare-border` e os raios da 003; a 320 px uma coluna, sem rolagem horizontal; T009 e T010 verdes.

**Checkpoint**: US1 funcional e testada (PHPUnit e Behat verdes).

---

## Phase 4: User Story 2 - Distinguir potencial, armazenamento registrado e limitações (Priority: P1)

**Goal**: a seção diz o que o potencial é e não é, cobre só tarefas, lembra o armazenamento registrado
só no agregado da sala e explica cálculo e limitações num trecho recolhido.

**Independent Test**: inspecionar a seção: `potential:intro`, `meaning` e `scope` sempre visíveis;
`potential:recorded` com o total da sala e a data só com resultado do diagnóstico; `<details>` recolhido
com a fórmula e as limitações; nenhum número de envios por tarefa.

### Tests for User Story 2

- [ ] T015 [P] [US2] Em `tests/output/course_overview_test.php`, acrescentar `test_potential_meaning_and_recorded`: com resultado do diagnóstico e envios na categoria `submission` → `potential:recorded` com o tamanho da categoria (`diagnostic_line::size()`) e a data (`userdate` de `timecompleted`, `strftimedatetimeshort`), uma única vez na seção; sem resultado (estados A e B) → a seção aparece sem `potential:recorded`; sempre `potential:intro`, `potential:meaning`, `potential:scope`; `<details>` sem atributo `open`, com `potential:how` em `<summary>` e `how_formula`, `how_attempts`, `limit_dates`, `limit_ignore`, `limit_other`, `how_server`, `limit_change`; nenhum tamanho de envios por tarefa dentro dos itens (o tamanho da categoria `submission` aparece só na frase `recorded`).
- [ ] T016 [P] [US2] Em `tests/output/course_overview_test.php`, acrescentar `test_potential_privacy_and_tone`: sala com estudantes nomeados, grupos nomeados e envios com nomes de arquivo → a seção não contém nome de estudante, de grupo ou de arquivo (FR-040, SC-004); nenhuma palavra de julgamento, previsão ou economia nas strings novas: no HTML em en da seção, sem "must", "should", "waste", "save", "too", "will be", "adequate"; nos valores das chaves `potential:` de `translations/pt_br/block_coursecare.php` (lidos do arquivo, como em `tests/translation_test.php`), sem "deve", "desperdício", "economia", "excesso", "adequad", "será enviado".
- [ ] T017 [US2] Em `tests/behat/block_coursecare_potential.feature`, acrescentar: "The theoretical potential is a ceiling set by the settings" visível sem abrir nada; "Due dates and access restrictions are not considered." não visível até clicar em "How the potential is calculated, and its limits", e visível depois; com resultado do diagnóstico, ver "In this course, files already submitted in activities add up to".

### Implementation for User Story 2

- [ ] T018 [US2] Em `classes/output/course_overview.php` (`potential_data()`), acrescentar `intro`, `meaning`, `scope`, `recorded` (só com `view['hasresult']` e categoria `submission` com bytes > 0; tamanho e data como em T015; vazio caso contrário) e `how` (`title` + `items` com as 7 frases); em `templates/overview_potential.mustache`, as frases antes da lista e `<details class="block_coursecare-overview-potential-how"><summary>…</summary><ul>…</ul></details>` depois dela; T015 a T017 verdes.

**Checkpoint**: US1 e US2 completas.

---

## Phase 5: User Story 3 - Saber quando o potencial não pode ser calculado (Priority: P2)

**Goal**: em vez de um número, a frase de cada caso: ninguém pode enviar, nenhum arquivo, não
calculável, sala sem tarefas com arquivos, tarefa oculta.

**Independent Test**: tarefas com `maxfiles` 0, configuração ausente, nenhum participante, grupo sem
grupos com envio fora de grupo impedido, oculta; sala sem tarefas com arquivos → frases de cada caso, sem
"0 bytes".

### Tests for User Story 3

- [ ] T019 [P] [US3] Em `tests/output/course_overview_test.php`, acrescentar `test_potential_states`: `maxfilesubmissions` 0 e registro apagado → `potential:nofiles`, sem `potential:value`; tarefa sem participantes e tarefa em grupo sem grupos com `preventsubmissionnotingroup` → `potential:nobody`, sem "0 bytes"; nenhum limite > 0 (servidor sem valor via dado do construtor) → `potential:unknown`; tarefa oculta → `potential:hidden` e o potencial da configuração; sala só com tarefas sem envio de arquivos → `potential:none` e nenhum item; sala sem nenhuma tarefa → sem `block_coursecare-potential`; em todos os casos de status sem número, as frases de configuração que dependem do fator desconhecido não aparecem (sem `files_*` em `nofiles`).
- [ ] T020 [US3] Em `tests/behat/block_coursecare_potential.feature`, acrescentar um Scenario Outline de estados: tarefa sem estudantes → "Nobody can submit in this assignment at the moment."; tarefa com 0 arquivos → "No file can be submitted with the current settings."; sala só com tarefa de texto online → "No assignment in this course accepts files at the moment."; tarefa oculta → "Hidden from students".

### Implementation for User Story 3

- [ ] T021 [US3] Fazer T019 e T020 passarem em `classes/output/course_overview.php` e `templates/overview_potential.mustache`: `none` quando há tarefas visíveis ao professor e nenhuma com `enabled`; `haspotential` falso sem tarefas; por status, `result` = `potential:nobody` | `potential:nofiles` | `potential:unknown`, sem as frases de fatores que não se aplicam (`nofiles`: sem `files_*` e `limit_*`; `unknown`: sem `limit_*`); `hidden` = `potential:hidden` quando `visible` é falso.

**Checkpoint**: as três histórias funcionam juntas.

---

## Phase 6: Polish, validação e evidências

- [ ] T022 [P] Em `tools/dev/demo/setup.php`, acrescentar às salas de demonstração tarefas com configurações variadas (quickstart §1): padrão (3 arquivos, 10 MB, 1 tentativa); tamanho 0 (padrão do site); tamanho maior que o limite da sala; tentativas ilimitadas com reabertura manual; 3 tentativas; envio em grupo com e sem agrupamento; só texto online; 0 arquivos; uma oculta; sem envios ou nomes de arquivo novos além dos existentes.
- [ ] T023 [P] Criar `tools/dev/bench/potential.php` (CLI, só desenvolvimento): cria uma sala com 200 atividades (150 páginas e 50 tarefas com arquivos, 5 delas com sobreposição de `mod/assign:submit` e 5 com envio em grupo) e 500 estudantes, com a opção `--overrides=N` (padrão 5) para o número de tarefas com sobreposição, e imprime o id da sala, para a medição de SC-003 com `tools/dev/bench/render.php --page`.
- [ ] T024 [P] Atualizar `README.md` (seção "Potencial das tarefas": o que é, o que não é, regra de cálculo, limitações, só tarefas, sem total e sem sugestão) e `CHANGES.md` (0.5.0).
- [ ] T025 Criar `specs/005-activity-storage-potential/evidence/README.md` com a tabela de CT-01 a CT-14 e SC-001 a SC-007 (status "pendente" até haver evidência), conferência à mão dos casos de quickstart §2 e as seções de medições, tom, validação visual e CI.
- [ ] T026 Rodar PHPUnit (`--testsuite block_coursecare_testsuite`), Behat (`--tags=@block_coursecare`, com `--no-interaction`), phpcs (Moodle), `tools/i18n/check.php`; corrigir até tudo verde.
- [ ] T027 [MANUAL] Conferir à mão, na página do site de demonstração, cada tarefa de T022 contra a regra (quickstart §2, SC-001); registrar em `evidence/README.md`.
- [ ] T028 [MANUAL] Medir SC-003: `tools/dev/bench/render.php --page --course=<sala de T023>` (leituras e tempo, em condições normais) no caso típico (`--overrides=5`) e no pior caso (`--overrides=50`); se o pior caso passar de 2 s, levar a decisão ao proprietário do produto antes da entrega (research R2) e o bloco 0.4.0 × 0.5.0 na mesma sala; abrir no navegador; registrar com as saídas em `specs/005-activity-storage-potential/evidence/logs/`.
- [ ] T029 [MANUAL] Revisão de tom das strings novas en e pt_br (FR-052, SC-002) contra FR-021 e FR-031; registrar e obter a aprovação do proprietário do produto.
- [ ] T030 [MANUAL] Validação visual (FR-052, SC-005, SC-006): `tools/dev/demo/checks.py` e capturas com `tools/dev/demo/screenshots.py` da página com a seção (com e sem resultado do diagnóstico, trecho recolhido aberto e fechado), en e pt_BR, Boost e Classic, desktop e celular; registrar e obter a aprovação do proprietário do produto.
- [ ] T031 Gerar e verificar o pacote: `tools/package/build.sh` e `php tools/package/check.php build/block_coursecare-0.5.0.zip`.
- [ ] T032 Publicar a branch (só com autorização do proprietário do produto), verificar o CI (`moodle-plugin-ci`, PHPUnit e Behat em 8.1/pgsql e 8.3/mariadb, `i18n-package`, SC-007) e registrar o run em `evidence/README.md`.
- [ ] T033 Reavaliar o Constitution Check do plan.md contra o código entregue e completar a tabela de `evidence/README.md` (nenhum item marcado sem evidência). Depende de T026 a T032.

---

## Dependencies & Execution Order

- **Setup (T001–T002)**: sem dependências.
- **Foundational (T003–T008)**: T003→T004, T005→T006, T007→T008; T007/T008 usam T006 (comparação com `count_teams()`). Bloqueia as histórias.
- **US1 (T009–T014)**: depois de T002, T004, T006 e T008. T011 antes de T013; T012 e T014 em paralelo com T011.
- **US2 (T015–T018)**: depois de T011–T013 (mesmo renderable e template).
- **US3 (T019–T021)**: depois de T011–T013; pode seguir em paralelo com US2 só nos testes (T019, T020); T021 depois de T018 (mesmos arquivos).
- **Polish (T022–T033)**: T022–T024 em paralelo a qualquer momento depois de T008; T026 depois das histórias; T027–T030 depois de T022 e T026; T033 por último.

## Parallel Example

```text
Depois de T002:
  T003 teste da estrutura | T005 teste da regra | T007 teste do coletor (depois de T006 para a parte de grupos)
Depois de T008:
  T009 [US1] teste da seção | T010 [US1] Behat | T012 [US1] template | T014 [US1] estilos
  T022 demo | T023 bench | T024 README/CHANGES
```

## Implementation Strategy

1. **MVP**: Setup, Foundational e US1 (o potencial por tarefa na página).
2. **US2**: significado, lembrete agregado e limitações (necessário antes de qualquer demonstração, porque
   sem ele o número pode ser lido como consumo ou previsão).
3. **US3**: frases dos casos sem número.
4. **Polish**: demonstração, medição, revisões humanas, pacote e CI. A revisão de tom (T029) e a validação
   visual (T030) dependem do proprietário do produto e ficam pendentes até a aprovação real.

## Cobertura dos cenários (CT) e critérios (SC)

| Item | Tarefas |
| --- | --- |
| CT-01 | T005, T009, T010, T027 |
| CT-02, CT-03, CT-04 | T005, T007, T009, T027 |
| CT-05 | T005, T009, T027 |
| CT-06 | T005, T007, T009, T019, T027 |
| CT-07 | T007 |
| CT-08 | T007, T009, T010 |
| CT-09 | T005, T007, T019, T020 |
| CT-10 | T019, T020 |
| CT-11 | T016 |
| CT-12 | T007, T028 |
| CT-13 | T016, T029 |
| CT-14 | T010, T030 |
| SC-001 | T005, T009, T019, T027 |
| SC-002 | T016, T029 |
| SC-003 | T007, T023, T028 |
| SC-004 | T016 |
| SC-005 | T010, T017, T030 |
| SC-006 | T030 |
| SC-007 | T026, T032 |
| FR-023 (bloco inalterado) | T009 |
| FR-041 (mesmo acesso) | sem página ou capability nova; coberto pelos testes existentes de `tests/overview_access_test.php`, rodados em T026 |
| FR-026 (sem total) | T009 |
