---

description: "Task list for Feature 006 — Usage History"
---

# Tasks: Usage History

**Input**: Design documents from `specs/006-usage-history/` (spec `0525612`, plan `d87cb12`)

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/history-store.md,
contracts/history-view.md, contracts/history-ui.md, quickstart.md

**Tests**: obrigatórios pela constituição (Princípio IX: PHPUnit para toda regra; Behat para todo fluxo
de interface). As tarefas de teste vêm antes da implementação de cada parte.

**Organization**: por história de usuário (US1 P1, US3 P1, US2 P2), cada uma testável sozinha.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: pode rodar em paralelo (arquivos diferentes, sem dependência de tarefa incompleta)
- **[Story]**: história de usuário (US1, US2, US3)

## Regras que valem para todas as tarefas

- **Camadas**: `$DB` só em `classes/local/collector/` e em `db/` (`upgrade.php`, `upgradelib.php`);
  `classes/local/analysis/history_view.php` é pura (sem `$DB`, `$CFG`, `get_string()` ou saída);
  `classes/output/` monta frases, gráfico e contexto dos templates.
- **Nunca**: consultar `files` para o histórico; gravar ponto de análise que falhou; interpolar ou criar
  pontos que não são medições; guardar id de usuário, arquivo ou atividade; incluir o potencial da 005;
  mudar o bloco, as sugestões ou a seção de potencial; criar tarefa agendada, capability ou JavaScript
  próprio (só o gráfico do core).
- **Strings**: toda chave nova em `lang/en/block_coursecare.php` (ordem alfabética) e em
  `translations/pt_br/block_coursecare.php` na mesma tarefa, com o texto de contracts/history-ui.md;
  frases completas, sem concatenação.
- **Formatos**: tamanhos com `diagnostic_line::size()`; datas com `userdate()` e os formatos do
  `langconfig`.
- **Visual**: estilos só sob `.block_coursecare-overview`, com as propriedades `--block-coursecare-*`
  existentes; sem cor ou ícone de alerta ou de tendência.

---

## Phase 1: Setup

- [X] T001 Em `version.php`, `$plugin->release = '0.6.0'` e `$plugin->version = 2026101900`; em `db/install.xml`, a tabela `block_coursecare_hist` exatamente como data-model.md (campos `id`, `courseid`, `timeday`, `timecompleted`, `methodversion`, `partial`, `filecount`, `logicalbytes`, `uniquecount`, `uniquebytes`, `materialbytes`, `submissionbytes`, `otherbytes`, `unclassifiedbytes`, todos `NOTNULL`; índice único `courseid-timeday` e índice `timeday`).
- [X] T002 [P] Acrescentar as 25 strings de contracts/history-ui.md (`history:*`, `settings:historyretention`, `settings:historyretention_desc`) em `lang/en/block_coursecare.php` e `translations/pt_br/block_coursecare.php`, em ordem alfabética; `php tools/i18n/check.php` com 0 erros.
- [X] T003 [P] Em `settings.php`, `admin_setting_configduration` `block_coursecare/historyretention`, padrão `365 * DAYSECS`, `set_min_duration(90 * DAYSECS)`, `set_max_duration(5 * 365 * DAYSECS)`, com as strings de T002.

---

## Phase 2: Foundational (gravação, limpeza, atualização e regra pura)

**Purpose**: os pontos existem, são únicos, somem com a retenção e com a sala; a regra da visão está pronta. Bloqueia todas as histórias.

- [X] T004 [P] Em `tests/collector/diagnostic_store_test.php`, acrescentar `test_history_point_saved_with_result`: `save_result()` grava 1 ponto com `timeday` = `usergetmidnight($now, core_date::get_server_timezone())`, `timecompleted` = `$now` e os valores do resultado (`filecount`, `logicalbytes`, `uniquecount`, `uniquebytes`, `methodversion`, `partial`, e `materialbytes`, `submissionbytes`, `otherbytes`, `unclassifiedbytes` das categorias, 0 para categoria ausente); segunda análise no mesmo dia → ainda 1 ponto, com os valores novos; análise no dia seguinte → 2 pontos; `save_failure()` depois → pontos intactos; falha dentro da transação (como em `test_save_result_is_atomic`) → nem resultado nem ponto mudam; sala sem arquivos → ponto com zeros; `get_history($courseid, $since)` devolve só pontos da sala com `timeday >= $since`, em ordem crescente; `delete_for_course()` apaga também o histórico; `purge_history($before, $limit)` remove no máximo `$limit` pontos com `timeday < $before`, de qualquer sala, e devolve o número; nenhuma consulta à tabela `files` em `save_result()` (parte do histórico), `get_history()` e `purge_history()` (log de consultas do `$DB`).
- [X] T005 Em `classes/local/collector/diagnostic_store.php`: dentro da transação de `save_result()`, gravar ou atualizar o ponto de (sala, dia) a partir de `$result->summary` e `$result->categories`; `get_history(int $courseid, int $since): array`; `purge_history(int $before, int $limit): int` (ids com limite, depois `delete_records_list`); `delete_for_course()` também apaga `block_coursecare_hist` da sala; PHPDoc; T004 verde.
- [X] T006 [P] Criar `tests/upgradelib_test.php` (`@covers ::block_coursecare_seed_history`): com 3 salas — uma com resultado e categorias, uma com resultado de 0 arquivos, uma só com pedido na fila (`timecompleted` nulo) — `block_coursecare_seed_history()` cria 2 pontos com os valores e a data real (`timeday` do `timecompleted`); chamada de novo não duplica; nenhuma consulta a `files`.
- [X] T007 Criar `db/upgradelib.php` com `block_coursecare_seed_history(): int` (lê resultados com `timecompleted` não nulo e as suas categorias em lotes com recordset; insere o ponto quando ainda não existe para (sala, dia)); em `db/upgrade.php`, passo `2026101900` que cria a tabela (XMLDB, como em install.xml), chama a função e grava o savepoint; T006 verde.
- [X] T008 [P] Em `tests/task/queue_analyses_test.php`, acrescentar `test_history_retention`: com `historyretention` = 90 dias e pontos de 100, 89 e 0 dias atrás, `run()` remove só o de 100 dias; com mais pontos antigos que o lote, remove no máximo o lote por execução; o número removido aparece no resultado de `run()`; e em `test_orphans_removed`, o histórico da sala órfã também é removido.
- [X] T009 Em `classes/task/queue_analyses.php`, depois dos órfãos: `purge_history(usergetmidnight($now, core_date::get_server_timezone()) - retenção, self::HISTORY_BATCH)` com `HISTORY_BATCH = 10000` e retenção de `get_config('block_coursecare', 'historyretention')` (padrão 365 dias quando vazio); chave `history` no array de `run()` e na linha de `execute()`; T008 verde.
- [X] T010 [P] Criar `tests/analysis/history_view_test.php` (`@covers \block_coursecare\local\analysis\history_view`) com os casos de contracts/history-view.md: sem pontos → `null`; 1 ponto → `first`; 2 pontos com aumento, redução e igual → `increased`/`decreased`/`unchanged` e `delta` absoluto; versões de método diferentes → `methodchanged` sem `delta`; lacuna de 7 dias → `days` com 6 valores `null` entre os pontos (inclusive atravessando uma mudança de horário de verão); `points` do mais recente ao mais antigo; valores grandes (TB) inteiros exatos.
- [X] T011 Criar `classes/local/analysis/history_view.php` com `public static function build(array $points): ?array` conforme contracts/history-view.md; `days` é uma lista indexada pelo deslocamento em dias desde o primeiro ponto (`round((timeday − primeiro) / DAYSECS)`, robusto a horário de verão), com `logicalbytes` ou `null`; a camada de saída rotula cada posição com a data de `primeiro + i × DAYSECS + 12 h`; PHPDoc; T010 verde.
- [X] T012 [P] Em `tests/generator/lib.php`, `create_diagnostic_history($record)` (sala, `daysago`, valores; `timeday` calculado no fuso do site); em `tests/generator/behat_block_coursecare_generator.php`, a entidade `diagnostic history points` (colunas `course`, `daysago`, `logicalbytes`, `materialbytes`, `submissionbytes`, `otherbytes`, `unclassifiedbytes`, `uniquebytes`, `methodversion`, `partial`).

**Checkpoint**: pontos gravados e únicos, limpeza e atualização testadas, regra da visão pronta.

---

## Phase 3: User Story 1 - Ver como os arquivos registrados da sala evoluíram (Priority: P1) 🎯 MVP

**Goal**: a seção mostra a última medição, a frase de variação, o gráfico e a frase de significado.

**Independent Test**: sala com pontos de 120, 150 e 140 MB em três dias: a seção aparece entre a grade e "Materiais por atividade", com "diminuíram 10,0 MB desde a análise de …", o gráfico e a tabela com as datas.

### Tests for User Story 1

- [X] T013 [P] [US1] Em `tests/output/course_overview_test.php`, `test_history_section`: com 3 pontos (via T012) e resultado atual igual ao último → `id="block_coursecare-history"` depois de `block_coursecare-overview-grid` e antes de `block_coursecare-overview-activities`; `history:heading`, `history:intro`, `history:meaning`, `history:latest` com a data; `history:decreased` com `delta`, `date`, `from`, `to` formatados; o gráfico (`chart-area`) presente e sem a tabela automática (`chart-table` ausente); `<details>` sem `open` com `history:data` e uma linha por ponto (mais recente primeiro) com data e total; variantes de aumento e igualdade.
- [X] T014 [P] [US1] Criar `tests/behat/block_coursecare_history.feature` (`@block @block_coursecare`): com resultado e 3 pontos → ver "How the files of your course have changed", a frase "decreased by" e, depois de abrir "Measurements and categories", as datas; `@javascript @accessibility` Scenario Outline em `large` e `mobile` com "the page should meet accessibility standards".

### Implementation for User Story 1

- [X] T015 [US1] Em `classes/output/course_overview.php`: `for_course()` lê `get_history($courseid, limite da retenção)` e passa a lista ao construtor (novo parâmetro `array $history = []`); `history_data()` usa `history_view::build()` e monta `heading`, `intro`, `meaning`, `latest`, `change` (frase), `haschart` (2 pontos ou mais), `chart` (HTML de `$output->render_chart($chart, false)` com `core\chart_bar` (revisão de R9; de início `core\chart_line`), uma série `history:series`, rótulos com a data curta de cada dia, valores na unidade do maior valor, `null` nos dias sem medição, título `history:chart`, eixo `history:axis`), `rows` (data, total) e `limits`; `export_for_template()` ganha `hashistory` e `history`; PHPDoc.
- [X] T016 [P] [US1] Criar `templates/overview_history.mustache` conforme contracts/history-ui.md (`<section id="block_coursecare-history" …>`, `h3`, frases, `{{{chart}}}`, `<details>` com tabela `<caption>` e `th scope`, lista de limitações), com documentação e contexto de exemplo para o lint.
- [X] T017 [US1] Em `templates/overview.mustache`, incluir `{{#hashistory}}{{> block_coursecare/overview_history }}{{/hashistory}}` depois da grade e antes de `hasactivities`; documentação e contexto de exemplo.
- [X] T018 [P] [US1] Em `styles.css`, estilos da seção (`.block_coursecare-overview-history`, gráfico com altura limitada, tabela com rolagem horizontal própria e foco por teclado a 320 px), sem cor de alerta; T013 e T014 verdes.

**Checkpoint**: US1 funcional e testada.

---

## Phase 4: User Story 3 - Entender um histórico curto, com lacunas ou com mudanças de método (Priority: P1)

**Goal**: frases e gráfico corretos com 0 e 1 ponto, lacunas, falha, cobertura parcial e mudança de método.

**Independent Test**: salas com cada caso mostram a frase do caso e nenhum ponto inventado.

### Tests for User Story 3

- [X] T019 [P] [US3] Em `tests/output/course_overview_test.php`, `test_history_states`: sem pontos → sem `block_coursecare-history`; 1 ponto → `history:first`, sem gráfico; lacuna de 5 dias → os dias sem medição não aparecem na tabela e os dados do gráfico enviados à página (JSON do `core/chart`) têm `null` nesses dias; falha depois de pontos (estado F com resultado anterior) → seção presente com os pontos existentes; ponto parcial → `history:partial` na linha dele; mudança de método → `history:methodchanged` e nenhuma frase de aumento ou redução; nenhuma palavra de julgamento, causa ou alarme no HTML en da seção nem nas chaves `history:` do pt_br ("waste", "should", "must", "problem", "improve"; "desperdício", "deve", "problema", "melhor").
- [X] T020 [US3] Em `tests/behat/block_coursecare_history.feature`, Scenario Outline de estados: 1 ponto → "This is the first measurement kept for this course"; mudança de método → "The way of measuring changed on"; sem pontos → não ver "How the files of your course have changed".

### Implementation for User Story 3

- [X] T021 [US3] Fazer T019 e T020 passarem em `classes/output/course_overview.php` e `templates/overview_history.mustache` (frases `first` e `methodchanged`, `haschart` só com 2 pontos ou mais, marca de parcial na linha).

**Checkpoint**: US1 e US3 completas.

---

## Phase 5: User Story 2 - Ver a evolução por categoria e o conteúdo único (Priority: P2)

**Goal**: a tabela mostra, por medição, as quatro categorias e o conteúdo único.

**Independent Test**: pontos com categorias diferentes mostram os valores de cada categoria e do conteúdo único iguais aos dos pontos; envios só como total da sala.

### Tests for User Story 2

- [X] T022 [P] [US2] Em `tests/output/course_overview_test.php`, `test_history_categories`: a tabela tem os cabeçalhos `history:col_date`, `history:col_total`, os quatro `overview:category:*` e `history:col_unique`, e em cada linha os valores formatados do ponto; o conteúdo único nunca somado ao total; nenhum nome de estudante, arquivo ou atividade na seção.

### Implementation for User Story 2

- [X] T023 [US2] Em `classes/output/course_overview.php` (`rows`) e `templates/overview_history.mustache`, as colunas das categorias e do conteúdo único; T022 verde.

**Checkpoint**: as três histórias funcionam juntas.

---

## Phase 6: Polish, validação e evidências

- [X] T024 [P] Em `tools/dev/demo/setup.php`, pontos de demonstração com datas passadas para as salas de demonstração (valores fictícios do site de demonstração, como os demais dados dele; a proibição de pontos fictícios da spec vale para o comportamento do plugin, e estes dados existem só no site de desenvolvimento, criados pela ferramenta de demonstração): uma sala com 3 meses de pontos diários com lacuna e redução, uma com 1 ponto, uma com mudança de método; aplicar ao site de demonstração existente sem recriá-lo.
- [X] T025 [P] Criar `tools/dev/bench/history.php` (só desenvolvimento): insere pontos para N salas × D dias (`--courses`, `--days`), mede o tamanho da tabela e dos índices, o tempo de `purge_history` e de `get_history` para uma sala com 365 pontos.
- [X] T026 [P] Atualizar `README.md` (seção "Evolution of the files": o que é guardado, quando, retenção e configuração, o que não significa, limitações, ciclo de vida) e `CHANGES.md` (0.6.0).
- [X] T027 Criar `specs/006-usage-history/evidence/README.md` com a tabela de CT-01 a CT-20 e SC-001 a SC-007 (pendente até haver evidência) e seções de medições, tom, visual e CI.
- [X] T028 Rodar PHPUnit, Behat (`--no-interaction`), `tools/i18n/check.php`; atualização do site de desenvolvimento para 0.6.0 sem erros; corrigir até tudo verde.
- [X] T029 [MANUAL] Medir SC-003 e SC-004: `render.php --page` numa sala com 365 pontos e com 1.826 pontos (retenção máxima de 5 anos); tempo de `cli/diagnostics.php --run` de uma sala com e sem a gravação do ponto (0.5.0 × 0.6.0); `history.php` com 5.000 salas × 365 dias (tamanho da tabela, limpeza); tempo do passo de atualização (`block_coursecare_seed_history()`) com 5.000 salas com resultado; bloco 0.5.0 × 0.6.0; registrar em `evidence/logs/`.
- [X] T030 [MANUAL] Conferir à mão, no site de demonstração, as frases e valores das salas de T024 contra os pontos (SC-001, SC-002).
- [ ] T031 [MANUAL] (ajustes de 2026-10-19 aplicados; aguarda a aprovação final) Revisão de tom das strings novas en e pt_br (FR-052) contra FR-024; obter a aprovação do proprietário do produto.
- [ ] T032 [MANUAL] (gráfico de até 90 dias aplicado; capturas finais aguardando aprovação) Validação visual: `checks.py` e `screenshots.py` da seção (com e sem gráfico, trecho aberto), en e pt_BR, Boost e Classic, desktop e celular; obter a aprovação do proprietário do produto.
- [X] T033 Gerar e verificar o pacote: `tools/package/build.sh` e `php tools/package/check.php build/block_coursecare-0.6.0.zip`.
- [X] T034 Publicar a branch (só com autorização do proprietário do produto), verificar o CI (PostgreSQL e MariaDB) e registrar o run.
- [ ] T035 Reavaliar o Constitution Check do plan.md contra o código entregue e completar a evidência. Depende de T028 a T034.

---

## Dependencies & Execution Order

- **Setup (T001–T003)**: T002 e T003 em paralelo; T001 antes de T005 e T007.
- **Foundational (T004–T012)**: T004→T005, T006→T007, T008→T009 (depois de T005), T010→T011; T012 em paralelo. Bloqueia as histórias.
- **US1 (T013–T018)**: depois da Foundational; T015 antes de T017; T016 e T018 em paralelo com T015.
- **US3 (T019–T021)** e **US2 (T022–T023)**: depois de T015–T017 (mesmos arquivos), em sequência.
- **Polish (T024–T035)**: T024–T026 depois da Foundational; T028 depois das histórias; T029–T032 depois de T024 e T028; T035 por último.

## Parallel Example

```text
Depois de T001:
  T004 teste do coletor | T006 teste da atualização | T010 teste da regra | T012 geradores
Depois da Foundational:
  T013 [US1] teste da seção | T014 [US1] Behat | T016 [US1] template | T018 [US1] estilos
```

## Implementation Strategy

1. **MVP**: Setup, Foundational e US1 (pontos gravados e a seção com frase, gráfico e datas).
2. **US3**: casos de histórico curto, lacunas e mudança de método (necessário antes de qualquer
   demonstração, porque o histórico começa com um ponto).
3. **US2**: categorias e conteúdo único na tabela.
4. **Polish**: demonstração, medições, revisões humanas, pacote e CI.

## Cobertura dos cenários (CT) e critérios (SC)

| Item | Tarefas |
| --- | --- |
| CT-01, CT-02, CT-03, CT-05, CT-06, CT-07 | T004 |
| CT-04 | T004 (mesmo dia, reexecução), lock da 002 |
| CT-08 | T010, T019 |
| CT-09 | T006 |
| CT-10 | T004, T008 |
| CT-11 | T004, T008 |
| CT-12 | sem dado copiado: nenhuma tarefa de backup; T004 (ponto por `courseid`) |
| CT-13 | T004, T030 |
| CT-14 | T013, T019, T020 |
| CT-15 | testes de acesso existentes da 003 (página inalterada no acesso), T028 |
| CT-16 | T022 |
| CT-17 | T034 |
| CT-18 | T029 |
| CT-19 | T014, T032 |
| CT-20 | T019, T031 |
| SC-001 | T004, T006, T008 |
| SC-002 | T004, T030 |
| SC-003, SC-004 | T029 |
| SC-005 | T014, T032 |
| SC-006 | T031, T032 |
| SC-007 | T028, T034 |
