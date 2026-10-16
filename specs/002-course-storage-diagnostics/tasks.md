---

description: "Tarefas de implementação da Feature 002 — Course Storage Diagnostics"
---

# Tasks: Course Storage Diagnostics

**Input**: Design documents from `specs/002-course-storage-diagnostics/`

**Prerequisites**: [plan.md](plan.md) (commit `af47d4c`, D10 e D11 aceitas), [spec.md](spec.md)
(commit `cbb6de1`), [research.md](research.md), [data-model.md](data-model.md),
[contracts/](contracts/), [benchmark.md](benchmark.md), [quickstart.md](quickstart.md) ·
Constituição 1.2.0

**Tests**: obrigatórios. A spec exige teste automatizado para cada cenário CT (exceto os de
benchmark) e a constituição exige PHPUnit para toda regra de negócio e Behat para a interface
(Quality Gates 2 e 3). Em cada história, os testes vêm antes da implementação e devem falhar antes
dela. Os testes usam a **File API real** e os geradores do core; dados artificiais só em testes puros
da camada de análise (plan.md, "Estratégia de testes").

**Organization**: tarefas agrupadas pelas histórias US1–US7 da spec, precedidas de Setup e
Foundational e seguidas de uma fase de validação final.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: pode rodar em paralelo (arquivos diferentes, sem dependência de tarefa incompleta).
- **[Story]**: história a que a tarefa pertence (US1–US7).
- **[MANUAL]**: validação em Moodle real ou benchmark, com procedimento, resultado esperado e
  evidência descritos na própria tarefa.

## Path Conventions

O repositório é a raiz do plugin, instalado em `<moodle>/blocks/coursecare`. As evidências ficam em
`specs/002-course-storage-diagnostics/evidence/` (abreviado `evidence/`), fora do pacote. Todo PHP
leva o cabeçalho GPL v3+ padrão do Moodle e PHPDoc. Testes PHPUnit em subdiretórios usam o namespace
que o `moodle-cs` espera para o subdiretório (`block_coursecare\analysis`, `block_coursecare\collector`,
`block_coursecare\task`) e `@covers` nas classes testadas.

## Regras que valem para todas as tarefas

- **Camadas** (contracts/diagnostic-api.md): só `classes/local/collector/` usa `$DB`;
  `classes/local/analysis/` é pura (sem `$DB`, sessão, `$CFG` ou saída); a apresentação lê pela
  `diagnostic_store` e pelo `state_resolver`. Não criar a camada de regras de recomendação.
- **Não criar**: cache MUC, fila própria, hooks ou observadores de eventos, interfaces ou classes
  abstratas sem dois casos concretos, configurações além das três do contrato, JavaScript.
- **Nunca** instanciar `stored_file`, chamar `get_area_files()`, ler conteúdo de arquivo ou chamar
  qualquer API que sincronize referências (research.md R2).
- **Strings**: toda chave nova entra em `lang/en/block_coursecare.php` (ordem alfabética) e em
  `translations/pt_br/block_coursecare.php` **na mesma tarefa**, como frase completa com no máximo um
  parâmetro `{$a}`, sem termos de infraestrutura nem palavras de avaliação (contracts/block-ui.md).
- **Log de tarefas** (`mtrace`): só id da sala, contagens, duração, memória e códigos. Nunca nomes de
  arquivos, de atividades ou de usuários.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: esquema, versão, configurações, pacote e estrutura de evidências.

- [X] T001 Atualizar `version.php`: `$plugin->version` para a data da implementação no formato
  AAAAMMDDXX (maior que `2026101600`), `$plugin->release = '0.2.0'`, mantendo `requires =
  2024100700`, `supported = [405, 405]` e `MATURITY_ALPHA`.
- [X] T002 Criar `db/install.xml` com as 4 tabelas de data-model.md, campos, tipos, nulidade e
  padrões exatamente como na tabela: `block_coursecare_diag` ("`courseid` … **Único** (índice
  único)"; "`requeststatus` int(2) não, padrão 0: 0 = nenhuma solicitação; 1 = na fila; 2 = em
  execução"; "`failurereason` char(30) … Código curto … Sem mensagem livre"; campos de bytes
  int(20); índices único `courseid` e `requeststatus, timeattempted`), `block_coursecare_cat`
  ("`category` char(20): `material`, `submission`, `other`, `unclassified`, `backup`,
  `recyclebin`"; índice único `diagid, category`), `block_coursecare_act` ("`cmid` … **0 = nível
  da sala**"; índice único `diagid, cmid`) e `block_coursecare_large` (índice `diagid`). Chaves
  estrangeiras `diagid → block_coursecare_diag.id`. Validar com o editor XMLDB (formato canônico).
- [X] T003 Criar `db/upgrade.php` com `xmldb_block_coursecare_upgrade($oldversion)`, que cria as
  4 tabelas a partir da definição de T002 quando `$oldversion` é menor que a versão de T001 e chama
  `upgrade_block_savepoint(true, <versão>, 'coursecare')`. Depende de T001 e T002.
- [X] T004 [P] Criar `settings.php` com as três configurações de contracts/diagnostic-api.md
  ("`largefilethreshold` inteiro, em MB (1 MB = 1.048.576 bytes), padrão 50, ≥ 1";
  "`reanalysisinterval` inteiro, em horas, padrão 24, ≥ 1"; "`stalenessthreshold` inteiro, em
  horas, padrão 72, ≥ 1; recomendado > intervalo (texto de ajuda)") usando
  `admin_setting_configtext` com `PARAM_INT` e validação de mínimo; strings `settings:*` (nome e
  descrição de cada uma) em en e pt_br. Acrescentar `has_config()` retornando `true` em
  `block_coursecare.php`.
- [X] T005 [P] Atualizar a allowlist `COURSECARE_PACKAGE_ALLOWED` em `tools/package/check.php` para
  aceitar `settings.php`, `cli/diagnostics.php`, `db/install.xml`, `db/upgrade.php`,
  `db/tasks.php` e `classes/**`, mantendo proibidos `tools/` (inclusive `tools/dev/bench/`),
  `translations/`, `specs/` e os demais itens; ajustar os casos de `tools/package/` se houver teste
  da allowlist.
- [X] T006 [P] Criar `specs/002-course-storage-diagnostics/evidence/README.md` com a tabela de
  CT-01 a CT-35 e SC-001 a SC-010 (colunas: item, teste ou evidência, data, Moodle, banco, status =
  "pendente") e as pastas `evidence/logs/` e `evidence/screenshots/` com `.gitkeep`.

**Checkpoint**: `php admin/cli/upgrade.php` a partir da 0.1.0 cria as tabelas sem erro; `moodle-plugin-ci
validate` e `savepoints` passam.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: o pipeline mínimo de análise — regras, coleta do escopo, cálculo do Armazenamento
lógico, gravação atômica e tarefa ad hoc com lock. Bloqueia todas as histórias.

**⚠️ CRITICAL**: nenhuma história começa antes do checkpoint desta fase.

### Tests for Foundational (escrever antes de T012)

- [X] T007 [P] Criar `tests/analysis/classification_rules_test.php` (teste puro): para cada uma das
  30 regras de contracts/coverage-matrix.md, um caso que casa e um que não casa (componente, área
  ou nível de contexto diferente); precedência (regras 1–7 antes de 13, 15, 22 e 24; regra 21
  antes da 28; `mod_*/intro` para um módulo qualquer, por exemplo `mod_lesson`); componente
  desconhecido e área desconhecida de componente conhecido → `unclassified`.
- [X] T008 [P] Criar `tests/analysis/indicator_calculator_test.php` (teste puro, linhas de
  agregado montadas à mão): A e `filecount` somam só o escopo principal P; grupos excluídos (regras
  1–7), de backup/lixeira (regras 8–10) e de referência não entram em A; invariante `A = Σ A_c`
  detectado quando violado.
- [X] T009 [P] Criar `tests/collector/course_files_test.php` (`advanced_testcase`, File API real)
  com helpers privados para criar sala, módulos pelos geradores do core e arquivos com
  `get_file_storage()->create_file_from_string()` na área real de cada módulo. Casos: CT-01 (sala
  sem arquivos → 0 registros), CT-02 (1 arquivo de 1 MB em `mod_resource/content` → A = 1.048.576),
  CT-03 (1, 2 e 3 MB → A = 6 MB), CT-10 (diretórios, rascunho do usuário, arquivo privado e
  `mod_folder/temp` não entram), arquivo em outra sala não entra, e **igualdade PHP × SQL**: para
  cada regra, um arquivo real na área e a categoria retornada pela expressão `CASE` igual à de
  `classification_rules::classify()`. Inclui SC-010: cópia de todas as linhas de `files` antes e
  depois da análise, idênticas.
- [X] T010 [P] Criar `tests/collector/diagnostic_store_test.php`: `request()` só aceita com
  `requeststatus = 0` (segunda chamada devolve falso); `save_result()` grava registro e filhos numa
  transação e substitui o resultado anterior por completo; exceção dentro de `save_result()` deixa o
  resultado anterior intacto; `save_failure()` mantém os campos de resultado e incrementa
  `failurecount`; `delete_for_course()` remove registro e filhos; `get_summary()` faz uma única
  leitura.
- [X] T011 [P] Criar `tests/task/analyse_course_test.php`: execução normal grava resultado e zera
  `requeststatus`; sala inexistente ou sem bloco → nenhum resultado e diagnóstico apagado (FR-003b);
  resultado concluído depois da criação da tarefa → sai sem analisar; lock da sala já ocupado
  (obtido no teste com a mesma fábrica e o mesmo recurso) → sai sem analisar (CT-18); a tarefa
  termina sem exceção mesmo com falha interna.

### Implementation for Foundational

- [X] T012 [P] Criar `classes/local/analysis/classification_rules.php`: constantes das categorias
  (`material`, `submission`, `other`, `unclassified`) e dos resultados especiais (`excluded`,
  `backup`, `recyclebin`); tabela das 30 regras de contracts/coverage-matrix.md em ordem
  (componente exato ou prefixo, área exata ou prefixo, níveis de contexto); método
  `classify(string $component, string $filearea, int $contextlevel): string` e acesso de leitura à
  tabela para a coleta. Sem `$DB`.
- [X] T013 [P] Criar `classes/local/analysis/diagnostic_result.php`: objeto de valor com todos os
  campos de resultado de `block_coursecare_diag`, as linhas de categoria, de atividade e de
  ligações (data-model.md), com valores inteiros (nunca string) e sem setters públicos depois de
  construído.
- [X] T014 Criar `classes/local/analysis/indicator_calculator.php`: recebe o mapa de contextos (Q0) e
  as linhas por área (Q1), classifica cada grupo com `classification_rules`, separa P, excluídos,
  backup/lixeira e referências, calcula A e `filecount` e expõe `check_invariants()` com os
  invariantes de contracts/indicators.md. Depende de T012 e T013.
- [X] T015 Criar `classes/local/collector/course_files.php`: Q0 (contextos da sala por `ctx.id =
  :ctxid OR ctx.path LIKE :path`) e Q1 (agrupamento por `contextid, component, filearea, reftype`,
  com `COUNT`, `SUM(filesize)` e os `SUM(CASE …)` do limite com parâmetros `:t1` e `:t2`,
  `f.filename <> '.'`), e o gerador da expressão `CASE` de categoria a partir da tabela de T012,
  portável. **Cada parâmetro nomeado aparece uma única vez por instrução** (a DML rejeita repetição
  com `duplicateparaminsql`, contracts/indicators.md): o gerador recebe um prefixo por uso e numera
  os parâmetros de cada regra (`:<prefixo>c<n>`, `:<prefixo>a<n>`, nomes com até 30 caracteres).
  Converter todos os agregados para `int`. Sem `stored_file`. Depende de T012.
- [X] T016 [P] Criar `classes/local/collector/eligibility.php`: `is_eligible(int $courseid): bool` e
  `get_eligible_courses_query()` (junção `block_instances.blockname = 'coursecare'` com `context`
  de nível curso, excluindo `SITEID`; sem filtro por `block.visible`), usados pela tarefa ad hoc,
  pela agendada e pela CLI (FR-003, FR-003a, FR-003c).
- [X] T017 Criar `classes/local/collector/diagnostic_store.php` com `get_summary()`, `request()`,
  `mark_running()`, `save_result()` (uma transação delegada: apaga filhos, insere os novos,
  atualiza o registro com `requeststatus = 0`, `timecompleted`, `timeattempted`,
  `lastattemptfailed = 0`, `failurecount = 0`, `methodversion`), `save_failure(int $courseid,
  string $reason, int $now)` e `delete_for_course()`, conforme contracts/diagnostic-api.md.
  Depende de T013.
- [X] T018 Criar `classes/local/course_analysis.php` (orquestração): lock
  `\core\lock\lock_config::get_lock_factory('block_coursecare')`, recurso `analyse_course_<id>`,
  espera 0, validade 7.200 s; elegibilidade (T016); frescor (`timecompleted >= $requestedsince`);
  `mark_running`; coleta (T015) e cálculo (T014); invariantes com uma nova coleta se falharem e
  falha `inconsistent` se falharem de novo; `save_result`; `\Throwable` → rollback, `save_failure`
  com o nome curto da classe da exceção (sem namespace, truncado em 30 caracteres), sem relançar; lock liberado em `finally`. Aceita a
  coleta por injeção no construtor (para o teste de falha da US5). Depende de T014–T017.
- [X] T019 Criar `classes/task/analyse_course.php` (`\core\task\adhoc_task`): `customdata`
  `{"courseid": <int>}`, `get_default_concurrency_limit()` = 2, `execute()` chama
  `course_analysis` com o `timecreated` da tarefa como referência de frescor e faz `mtrace` só de
  id, duração, memória, registros e código de falha; string `task:analysecourse` em en e pt_br.
  Depende de T018.

**Checkpoint**: T007–T011 verdes; uma sala com o bloco, ao rodar `analyse_course`, grava A e
`filecount` corretos.

---

## Phase 3: User Story 1 — Conhecer o volume de arquivos da sala (Priority: P1) 🎯 MVP

**Goal**: o professor vê no bloco o volume registrado e a data, sem fazer nada; a primeira análise
é pedida ao adicionar o bloco.

**Independent Test**: adicionar o bloco a uma sala com 1, 2 e 3 MB em materiais, executar as
tarefas ad hoc e ver "6 MB" com data; numa sala sem análise ver "não disponível"; numa sala vazia
ver "nenhum arquivo encontrado".

### Tests for User Story 1 (escrever antes de T024)

- [X] T020 [P] [US1] Criar `tests/analysis/state_resolver_test.php` (puro, tempo fixo) para os
  estados A (sem registro; registro sem resultado e sem solicitação), B (na fila sem resultado),
  C (em execução sem resultado), D (resultado dentro do limite), G (`filecount = 0`) e FR-046
  (resultado existente com nova solicitação pendente exibe D).
- [X] T021 [P] [US1] Em `tests/block_coursecare_test.php`: `instance_create()` grava a solicitação e
  enfileira **uma** tarefa `analyse_course` (verificar `task_adhoc`); segunda adição impossível já
  coberta pela 001; bloco no contexto do site não enfileira; `get_content()` com diagnóstico faz no
  máximo 2 leituras a mais que sem diagnóstico (`$DB->perf_get_reads()`) e nenhuma consulta à tabela
  `files`: `$DB->set_debug(true)`, `ob_start()` em volta de `get_content()`, `ob_get_clean()`,
  `$DB->set_debug(false)` e verificação de que `{$CFG->prefix}files` não aparece no texto capturado
  (SC-002, contracts/block-ui.md).
- [X] T022 [P] [US1] Criar `tests/behat/block_coursecare_diagnostic.feature` (tag `@block_coursecare`)
  com: professor com edição adiciona o bloco e vê a mensagem de preparação; `I run all adhoc tasks`;
  vê o volume e a data numa sala com arquivos gerados; vê a mensagem de nenhum arquivo numa sala
  vazia; o bloco com o diagnóstico passa em `the "CourseCare" "block" should meet accessibility
  standards` (`@accessibility`).

### Implementation for User Story 1

- [X] T023 [P] [US1] Criar `classes/local/analysis/state_resolver.php` com
  `resolve(?object $record, int $now, int $stalenessseconds)` devolvendo estado, marcador parcial e
  se há resultado exibível, pela tabela de estados de data-model.md (A, B, C, D, G nesta tarefa;
  E e F em T052). Sem `$DB`.
- [X] T024 [US1] Criar `classes/output/diagnostic_line.php` (`templatable`, `renderable`) que
  recebe o estado resolvido e monta, com `get_string()`, as frases `diagnostic:notavailable`,
  `diagnostic:preparing`, `diagnostic:volume` (parâmetro: `display_size($logicalbytes)`),
  `diagnostic:calculatedon` (parâmetro: `userdate($timecompleted,
  get_string('strftimedatetimeshort', 'langconfig'))`) e `diagnostic:nofiles`, com os textos de
  contracts/block-ui.md, em en e pt_br. Depende de T023.
- [X] T025 [US1] Atualizar `templates/content.mustache`: depois da mensagem `intro`, um parágrafo
  com a linha de estado recebida do contexto; atualizar o bloco de documentação e o "Example
  context (json)" com um exemplo de estado D. Sem cor, ícone ou `role="alert"`.
- [X] T026 [US1] Atualizar `block_coursecare.php`: `get_content()` mantém as guardas da 001 e acrescenta
  `diagnostic_store::get_summary($this->page->course->id)` (curso do próprio bloco, nunca parâmetro),
  `state_resolver` com `stalenessthreshold` da configuração e o `diagnostic_line`; `instance_create()`
  chama `diagnostic_store::request()` e, se aceita, `queue_adhoc_task(new analyse_course(...))` sem
  `checkforexisting`, apenas quando o contexto pai é de curso e diferente do site. Depende de
  T019, T024 e T025.

**Checkpoint**: MVP — T020–T022 verdes; quickstart §2 reproduzível.

---

## Phase 4: User Story 2 — Receber informações confiáveis (Priority: P1)

**Goal**: conteúdo único por hash, referências à parte por tipo e agregado de backup e lixeira, sem
dupla contagem.

**Independent Test**: CT-04, CT-05, CT-09, CT-12, CT-13, CT-22 e CT-29–CT-32 com valores calculados à
mão, em PHPUnit com File API real.

### Tests for User Story 2 (escrever antes de T031)

- [X] T027 [P] [US2] Em `tests/collector/course_files_test.php`: CT-04 (mesmo conteúdo de 10 MB em
  3 recursos → A = 30 MB, B = 10 MB), CT-05 (mesmo conteúdo nas salas X e Y → B = 10 MB em cada),
  CT-12 e CT-13 (arquivo de 5 MB adicionado/removido entre duas análises → resultado anterior até a
  nova análise; a nova mostra A ± 5 MB).
- [X] T028 [P] [US2] Em `tests/collector/course_files_test.php`, referências, sem rede:
  - **Interna** (CT-09): `create_repository_type('user')` e `create_repository('user')` pelo gerador
    de dados; arquivo de 3 MB em `user/private` do professor; atalho num recurso com
    `get_file_storage()->create_file_from_reference($record, $repositoryid,
    file_storage::pack_reference(<registro do arquivo privado>))`.
  - **Externa** (CT-09): `create_repository_type('dropbox')` e `create_repository('dropbox')`
    (gerador de teste do core em `repository/dropbox/tests/generator/lib.php`); atalho criado com
    `create_file_from_reference()` e uma referência fictícia (a criação não sincroniza; ela só reusa
    o resultado de sincronização já gravado, `lib/filestorage/file_storage.php:1540-1665`);
    tamanho e `lastsync` gravados com `file_storage::update_references($referencefileid, time(),
    null, <hash vazio>, 4 MB, 0)`, sem chamar o repositório.
  - Esperado: 1 interna com 3 MB e 1 externa com 4 MB, fora de A, B, categorias e atividades;
    `files_reference.lastsync` e `files.timemodified` dos atalhos inalterados depois da análise.
  - CT-22: arquivo de 0 byte conta sem volume; atalho com `status = 666` (gravado por
    `update_references(..., status 666)`) → indeterminado, sem tamanho; atalho externo com
    `lastsync` nulo → indeterminado.
  - CT-32: recurso de 10 MB + atalho interno para ele → A = B = 10 MB; 1 interna com 10 MB.
- [X] T029 [P] [US2] Em `tests/collector/course_files_test.php`, backup e lixeira: CT-29 (10 MB de
  materiais + backup real da sala por `backup_controller` + atividade de 5 MB excluída com a lixeira
  de curso ativada via `course_delete_module` → A = 10 MB; agregado com subtotais de backup e
  lixeira iguais aos `filesize` registrados), CT-30 (mesmo conteúdo na lixeira e num backup →
  único do conjunto conta uma vez) e CT-31 (backup do usuário em `user/backup` → fora do agregado).
- [X] T030 [P] [US2] Em `tests/analysis/indicator_calculator_test.php`: B a partir de linhas de Q3;
  invariantes `B ≤ A`, `B ≤ Σ B_c`, `bruniquebytes ≤ brbytes`; grupos de referência por tipo
  somados só para interna e externa.

### Implementation for User Story 2

- [X] T031 [US2] Em `classes/local/collector/course_files.php`: `reftype` de Q1 por junção
  `files_reference → repository_instances → repository` com a lista interna {`user`, `local`,
  `coursefiles`, `contentbank`, `recent`} como constante documentada, indeterminada para
  `status = 666`, junção nula ou externa com `lastsync` nulo (research.md R4); e Q3 (únicos por
  conjunto `main`/`br` agrupando por grupo e `contenthash`, `MAX(filesize)`, só `referencefileid IS
  NULL`, por subconsultas aninhadas sem `GROUP BY` por apelido, com a expressão `CASE` gerada com um
  prefixo de parâmetros próprio). A lista interna entra por `$DB->get_in_or_equal(...,
  SQL_PARAMS_NAMED, '<prefixo>')`, com prefixo diferente em cada uso na mesma instrução.
- [X] T032 [US2] Em `classes/local/analysis/indicator_calculator.php`: B e `uniquecount` de Q3;
  contagens e bytes de referências por tipo; agregado de backup e lixeira (`brcount`, `brbytes`,
  `bruniquecount`, `bruniquebytes` e linhas de categoria `backup` e `recyclebin`); invariantes de
  T030. Depende de T031.
- [X] T033 [US2] Em `classes/local/course_analysis.php` e `diagnostic_store.php`: gravar os campos
  novos (B, referências, agregado) e as linhas `backup`/`recyclebin` de `block_coursecare_cat`.
  Depende de T032.

**Checkpoint**: T027–T030 verdes nas duas bases do CI.

---

## Phase 5: User Story 3 — Preservar o desempenho (Priority: P1)

**Goal**: fila horária limitada, concorrência controlada, recuperação de presos, CLI administrativa e
benchmark medido.

**Independent Test**: com milhares de salas elegíveis, cada execução da tarefa agendada enfileira
no máximo 500, nenhuma sala é analisada duas vezes ao mesmo tempo e o benchmark registra SC-002 a
SC-004.

### Tests for User Story 3 (escrever antes de T037)

- [X] T034 [P] [US3] Criar `tests/task/queue_analyses_test.php`: salas elegíveis sem registro são
  enfileiradas; sala com resultado recente não; resultado mais antigo que `reanalysisinterval` sim;
  `methodversion` antiga sim; limite de 500 por execução (com 501 salas, ordem por `timeattempted`
  com nulos primeiro); "em execução" há mais de 2 h vira falha `interrupted`; "na fila" há mais de
  24 h volta a 0; diagnóstico de sala sem bloco ou excluída é apagado (até 500); duas execuções
  seguidas não duplicam solicitações (CT-18); sala sem bloco nunca recebe registro (CT-28).
  **Nova tentativa limitada** (FR-039a): com 1 falha, a sala não volta à fila antes de 1 h e volta
  depois; com 3 falhas, espera de 4 h; com 5 ou mais falhas, só depois de `reanalysisinterval`; um
  sucesso zera a contagem. **Sala restaurada** (edge case da spec): curso restaurado com o bloco por
  `restore_controller` não tem registro e é enfileirado na execução seguinte.
- [X] T035 [P] [US3] Criar `tests/task/cli_diagnostics_test.php` executando a CLI como processo
  (`proc_open` com o PHP do teste) ou pelas funções que ela chama: `--queue --course` respeita
  elegibilidade e duplicidade; `--run` com lock ocupado sai com código 3; sala não elegível sai com
  código 2; `--show --json` tem as chaves do contrato e nenhum nome de arquivo, atividade ou usuário.

### Implementation for User Story 3

- [X] T036 [US3] Criar `classes/task/queue_analyses.php` (`\core\task\scheduled_task`) com a ordem do
  contrato: órfãos (até 500), presos (2 h / 24 h), devidas (até 500, ordenadas por `timeattempted`
  com nulos primeiro), cada uma com `request()` e `queue_adhoc_task()` sem `checkforexisting`; a
  regra de espera por falha usa `min(2^(falhas−1), 24)` horas e, a partir de 5 falhas, o intervalo
  de reanálise; constantes nomeadas para 500, 2 h, 24 h e 5; `get_name()` com a string
  `task:queueanalyses` em en e pt_br. Depende de T016 e T017.
- [X] T037 [US3] Criar `db/tasks.php` registrando `\block_coursecare\task\queue_analyses` com
  `'minute' => 'R'`, `'hour' => '*'`, `'day' => '*'`, `'month' => '*'`, `'dayofweek' => '*'`.
  Depende de T036.
- [X] T038 [US3] Criar `cli/diagnostics.php` conforme contracts/admin-cli.md: modos `--queue
  --course=<id>`, `--queue --all` (lotes de 500 por transação), `--run --course=<id>` (mesma
  orquestração e lock; mostra duração e pico de memória), `--show --course=<id>`, `--json`, `-h`;
  códigos de saída 0–4; ajuda e rótulos por `get_string()` com chaves `cli:*` em en e pt_br;
  JSON com chaves em inglês. **Validação de entrada** (Princípio VII): exatamente um modo por
  execução; `--course` obrigatório com `--queue` sem `--all`, com `--run` e com `--show`, convertido
  com `clean_param(..., PARAM_INT)` e maior que zero; `--all` só com `--queue`; `--json` só com
  `--run` ou `--show`; qualquer violação → `cli_error()` com código 1. Depende de T018 e T033.
- [X] T039 [P] [US3] Criar `tools/dev/bench/generate.php` (CLI de desenvolvimento, fora do pacote)
  conforme benchmark.md: perfis pequeno/médio/grande/extremo, `--profile`, `--courses`, `--seed`,
  geradores do core e `create_file_from_string()` com conteúdo pseudoaleatório de semente fixa,
  bloco adicionado a cada sala, manifesto JSON com A e B esperados; arquivos "grandes" com
  `filesize` ajustado só nesses dados, com aviso na saída.
- [X] T040 [P] [US3] Criar `tools/dev/bench/render.php`: 1.000 chamadas de `get_content()` para um
  professor numa sala, com média e desvio, para comparar a 0.1.0 e a nova versão (SC-002).
- [ ] T041 [US3] [MANUAL] Executar o benchmark de benchmark.md nas duas bases (PostgreSQL 17 e
  MariaDB 11): SC-003 (5.000, 20.000, 50.000 e 200.000 registros, 3 execuções, `EXPLAIN` de Q1–Q4),
  SC-004 (5.001 salas até a fila esvaziar com o cron real a cada minuto), SC-002 (T040), concorrência
  e exatidão por amostragem. Registrar `evidence/benchmark.md` e os JSON em `evidence/logs/`,
  separando medido e projetado. **Aprovação**: critérios de benchmark.md; qualquer reprovação vira
  ajuste antes da fase final (por exemplo, RK1 do plano).
  *Execução (2026-10-16)*: SC-003 medido e atendido nas duas bases (50.000 registros em ≤ 0,57 s,
  memória 1,02 ×; `evidence/benchmark.md`), com dados em `--mode=metadata` (desvio declarado).
  Pendentes: SC-004, SC-002 (microbenchmark), concorrência e amostragem por perfil.

**Checkpoint**: T034–T035 verdes; evidência de benchmark registrada.

---

## Phase 6: User Story 4 — Preservar a privacidade (Priority: P1)

**Goal**: nenhum dado pessoal persistido ou exibido, acesso restrito ao bloco da própria sala e
limpeza dos dados no fim do ciclo de vida.

**Independent Test**: inspecionar as tabelas depois de uma sala com envios, tentar acessar com
papéis sem permissão e remover o bloco ou a sala.

### Tests for User Story 4 (escrever antes de T046)

- [X] T042 [P] [US4] Em `tests/privacy/provider_test.php`: a razão do `null_provider` é a nova chave
  de privacidade; depois de analisar uma sala com envios de estudantes (CT-07) e com atividade oculta
  e com restrição (CT-19), nenhuma coluna das 4 tabelas contém id de usuário dos participantes, nome
  de arquivo, `pathnamehash` ou `filepath`, e `block_coursecare_large` só tem `fileid` de arquivos da
  categoria Materiais (SC-006, D10).
- [X] T043 [P] [US4] Em `tests/block_coursecare_test.php`: remover a única instância apaga o
  diagnóstico e os filhos (CT-26), com tarefa pendente que depois termina sem gravar; com duas
  instâncias na mesma sala (criadas por código), remover uma mantém o diagnóstico; `delete_course()`
  de sala com diagnóstico e tarefa pendente → dados apagados e a tarefa termina sem erro (CT-20);
  desativar e reativar o tipo de bloco (`block.visible`) mantém o diagnóstico e a elegibilidade
  (CT-27); desinstalação remove as tabelas; bloco removido e adicionado de novo → nenhum dado da
  análise anterior (registro recriado na fila, sem resultado) (edge case da spec).
- [X] T044 [P] [US4] Em `tests/behat/block_coursecare_diagnostic.feature`: estudante e professor sem
  edição não veem o diagnóstico; professor da sala A não vê dados da sala B (CT-16); a sala com
  atividade oculta mostra só o total, sem nome de atividade (CT-19).

### Implementation for User Story 4

- [X] T045 [US4] Em `block_coursecare.php`: `instance_delete()` verifica outra instância
  `coursecare` com o mesmo `parentcontextid` excluindo `$this->instance->id` e, se não houver, chama
  `diagnostic_store::delete_for_course()` (operação leve por índice).
- [X] T046 [US4] Atualizar `classes/privacy/provider.php` (continua `null_provider`) e a string
  `privacy:metadata` em en e pt_br: o bloco guarda só medições agregadas de armazenamento por sala e
  atividade, sem dados pessoais (plan.md, "Registro de tratamento de dados").
- [X] T047 [US4] Revisar `classes/local/collector/course_files.php` e `diagnostic_store.php`: Q4 e
  `save_result()` só aceitam ligações da categoria `material`; nenhuma consulta seleciona
  `userid`, `filename`, `filepath`, `author` ou `source`; documentar a regra no PHPDoc da classe.

**Checkpoint**: T042–T044 verdes.

---

## Phase 7: User Story 5 — Saber se a informação é atual (Priority: P2)

**Goal**: estados desatualizado (E) e falha (F), com o resultado anterior preservado e nova
tentativa limitada.

**Independent Test**: provocar idade acima do limite e uma falha forçada, e ver no bloco o valor
anterior com data e aviso.

### Test infrastructure for User Story 5 (antes dos testes)

- [X] T048 [US5] Criar o gerador de dados de teste `tests/generator/lib.php`
  (`block_coursecare_generator::create_diagnostic()`, que grava `block_coursecare_diag` com datas,
  `requeststatus`, falha e resultado controlados, sem passar pela análise) e o passo de dados Behat
  em `tests/generator/behat_block_coursecare_generator.php` (entidade `diagnostics`, com colunas
  `course`, `logicalbytes`, `filecount`, `timecompleted` relativo em segundos e `lastattemptfailed`).
  Usado por T050 e T051.

### Tests for User Story 5 (escrever antes de T052)

- [X] T049 [P] [US5] Em `tests/analysis/state_resolver_test.php`: E (idade > limite), F com resultado
  (aviso de atualização não concluída), F sem resultado (exibido como A), precedência F → E → G/D, e
  CT-23 (sem processamento, o resultado passa a E após o limite).
- [X] T050 [P] [US5] Em `tests/task/analyse_course_test.php`: CT-14 — coleta injetada que lança
  exceção numa sala com resultado anterior → resultado intacto, `lastattemptfailed = 1`,
  `failurecount` incrementado, `failurereason` com o nome curto da classe (no máximo 30 caracteres);
  outra sala na mesma execução de cron é analisada; falha de invariante duas vezes → `inconsistent`.
  Depende de T048.
- [X] T051 [P] [US5] Em `tests/behat/block_coursecare_diagnostic.feature`: com o limite de
  atualidade em 1 hora e um diagnóstico criado pelo passo de dados de T048 com `timecompleted` de
  2 horas atrás, o bloco mostra o valor, a data e o aviso de desatualização (CT-15); com falha
  registrada, o aviso de atualização não concluída. Depende de T048.

### Implementation for User Story 5

- [X] T052 [US5] Em `classes/local/analysis/state_resolver.php`: estados E e F e a precedência de
  data-model.md. Depende de T023.
- [X] T053 [US5] Em `classes/output/diagnostic_line.php`: frases `diagnostic:mayoutdated` e
  `diagnostic:updatefailed` (en e pt_br) acrescentadas à linha de D. Depende de T052.

**Checkpoint**: T049–T051 verdes.

---

## Phase 8: User Story 6 — Entender de onde vem o volume (Priority: P2)

**Goal**: distribuição por categoria com conteúdo único por categoria e cobertura conhecida.

**Independent Test**: sala com arquivos em cada área da matriz; categorias e volumes conferidos em
PHPUnit e na CLI `--show`.

### Tests for User Story 6 (escrever antes de T057)

- [X] T054 [P] [US6] Em `tests/collector/course_files_test.php`: CT-06 (resumo, seção, Arquivo, Pasta,
  Página, Livro, Rótulo e bloco de texto → Materiais), CT-07 (envio de arquivo e texto online com
  imagem feitos pela API da tarefa, e resposta de redação com anexo num Questionário → Envios,
  regra 21), CT-08 (retorno em arquivo, comentário de retorno com arquivo, `grade/feedback` e
  fórum → Outros), CT-11 (componente fictício `local_fake` → Não classificado, `partial = 1`) e
  CT-21 (mesmo conteúdo num material e num envio → nas duas categorias; B da sala conta uma vez;
  Σ B_c > B).
- [X] T055 [P] [US6] Em `tests/analysis/indicator_calculator_test.php`: `Σ A_c = A`; únicos por
  categoria a partir de Q2; `unclassifiedcount`, `unclassifiedbytes` e `partial` (também para
  referência indeterminada).

### Implementation for User Story 6

- [X] T056 [US6] Em `classes/local/collector/course_files.php`: Q2 (únicos por categoria a partir da
  expressão `CASE` gerada com um prefixo de parâmetros próprio desta consulta, excluindo
  `excluded`, por subconsultas aninhadas).
- [X] T057 [US6] Em `classes/local/analysis/indicator_calculator.php` e `diagnostic_store.php`:
  linhas `material`, `submission`, `other` e `unclassified` de `block_coursecare_cat` (só categorias
  com registros), `unclassifiedcount`, `unclassifiedbytes` e `partial`. Depende de T056.

**Checkpoint**: T054–T055 verdes.

---

## Phase 9: User Story 7 — Identificar atividades e arquivos de grande porte (Priority: P3)

**Goal**: indicador D com deduplicação, concentração por atividade (E) e ligações a arquivos grandes
de Materiais.

**Independent Test**: atividades com 100, 10 e 1 MB e arquivos acima e abaixo do limite; agregados
por `cmid` e contagens de grande porte conferidos.

### Tests for User Story 7 (escrever antes de T061)

- [X] T058 [P] [US7] Em `tests/collector/course_files_test.php`: US7 cenário 1 (atividades com 100,
  10 e 1 MB → três linhas de atividade com esses volumes) e cenário 2 (envios de 60 e 70 MB numa
  tarefa → 2 de grande porte, 130 MB, sem ligação); CT-03 por atividade; arquivo em bloco de uma
  atividade atribuído à atividade (FR-019); arquivos do curso e dos blocos da sala em `cmid = 0`;
  CT-33 (52.428.799 e 52.428.800 bytes → só o segundo); CT-34 (mesmo conteúdo de 60 MB em 2
  recursos → 2 registros, 120 MB; 1 conteúdo, 60 MB); CT-35 (limite alterado para 100 MB depois de
  uma análise → resultado vigente mantém `largethreshold` de 50 MB; a próxima usa 100 MB); mais de
  1.000 arquivos grandes de material → 1.000 ligações, `largelinkstruncated = 1`, totais inalterados.
- [X] T059 [P] [US7] Em `tests/analysis/indicator_calculator_test.php`: mapa contexto → `cmid` pelo
  caminho (módulo, bloco dentro de módulo, curso, bloco do curso); `A = Σ A_cm`; D único por
  atividade e geral.

### Implementation for User Story 7

- [X] T060 [US7] Em `classes/local/collector/course_files.php`: Q4 (registros com `filesize >= :t1`,
  parâmetro único na instrução, sem referências, por recordset fechado ao fim), lendo o limite de `largefilethreshold` × 1.048.576
  no início da análise.
- [X] T061 [US7] Em `classes/local/analysis/indicator_calculator.php`: mapa contexto → `cmid` (Q0);
  linhas de atividade com volume por categoria; D (`largecount`, `largebytes`, `largeuniquecount`,
  `largeuniquebytes`) geral e por atividade; até 1.000 ligações de Materiais, as maiores, com
  `largelinkstruncated`. Depende de T060.
- [X] T062 [US7] Em `diagnostic_store.php` e `course_analysis.php`: gravar `block_coursecare_act`,
  `block_coursecare_large`, os campos de D e `largethreshold`; incluir atividades e grande porte no
  `--show` da CLI. Depende de T061 e T038.

**Checkpoint**: T058–T059 verdes; `--show` mostra atividades por `cmid`.

---

## Phase 10: Polish, validação final e evidências

- [ ] T063 [P] Atualizar `README.md`: o que o diagnóstico mede e não mede (consumo físico, economia),
  metodologia de cada indicador, matriz resumida com link para os critérios, estados do bloco,
  configurações com padrões e unidades (limite de grande porte em MB = 1.048.576 bytes, como
  triagem e não limite pedagógico), CLI, privacidade, limites de escalabilidade **medidos** em T041 e
  limitações (backups fora do Moodle, referências, Fórum, cobertura da classificação).
- [X] T064 [P] Atualizar `CHANGES.md` com a entrada 0.2.0.
- [X] T065 Executar `php tools/i18n/check.php` e corrigir qualquer falta de paridade, ordem de
  chaves ou parâmetros; revisar o pt_br das novas strings.
- [ ] T066 [MANUAL] Revisão de tom e rótulos (SC-008; Princípios I, II e IV): ler todas as strings
  novas em en e pt_br contra as restrições de contracts/block-ui.md e registrar o resultado em
  `evidence/README.md`.
- [X] T067 Gerar e verificar o pacote: `tools/package/build.sh` e `php tools/package/check.php
  build/block_coursecare-0.2.0.zip` (contém `db/install.xml`, `db/upgrade.php`, `db/tasks.php`,
  `settings.php`, `cli/diagnostics.php` e `classes/**`; não contém `tools/` nem `translations/`).
- [X] T068 [MANUAL] Executar o quickstart.md (§1–§7) num Moodle 4.5 real, em en e pt_BR: atualização
  a partir da 0.1.0 (CT-25), primeira análise, conferência dos números, estados, ciclo de vida.
  Capturas do bloco nos estados A, B, D, E, F e G em `evidence/screenshots/` e saídas da CLI em
  `evidence/logs/`.
- [ ] T069 Verificar o CI da branch: `moodle-plugin-ci` completo (phplint, phpcs, phpdoc, validate,
  savepoints, mustache, grunt), PHPUnit e Behat verdes em 8.1/pgsql e 8.3/mariadb, e o job
  `i18n-package`. Registrar o run em `evidence/README.md`.
- [ ] T070 Reavaliar o Constitution Check do plan.md contra o código entregue (camadas, nenhuma
  consulta a `files` na renderização, nenhum dado pessoal) e preencher a tabela de `evidence/README.md`
  (nenhum item marcado sem evidência). Depende de T041, T063–T069.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: T003 depende de T001 e T002. T004, T005 e T006 em paralelo.
- **Foundational (Phase 2)**: depende do Setup. Testes T007–T011 primeiro; T014 depende de T012/T013;
  T015 de T012; T017 de T013; T018 de T014–T017; T019 de T018.
- **US1 (Phase 3)**: depende da Phase 2. É o MVP.
- **US2 (Phase 4)**: depende da Phase 2. Independente de US1 (não toca bloco nem saída).
- **US3 (Phase 5)**: depende da Phase 2; T038 depende de T033 (US2), e T041 depende de US1, US2,
  US6 e US7 implementadas (o benchmark mede a análise completa).
- **US4 (Phase 6)**: T045 depende de T026 (US1, mesmo arquivo); T047 depende de T060 (US7, Q4) para a
  parte de ligações e pode ser feita no fim da US7.
- **US5 (Phase 7)**: depende de US1 (T023, T024).
- **US6 (Phase 8)**: depende da Phase 2; edita os mesmos arquivos de US2, então vem depois de T033.
- **US7 (Phase 9)**: depende de US6 (volume por categoria nas atividades) e de T038 para a CLI.
- **Polish (Phase 10)**: depende de todas as histórias.

### Grafo resumido

```text
Setup: T001,T002 → T003 ; T004 T005 T006
Phase 2: T007..T011 (testes) → T012 T013 → T014,T015 → T016,T017 → T018 → T019
US1: T020 T021 T022 → T023 → T024 → T025 → T026            (MVP)
US2: T027..T030 → T031 → T032 → T033
US6: T054 T055 → T056 → T057                               (após T033)
US7: T058 T059 → T060 → T061 → T062                        (após T057, T038)
US3: T034 T035 → T036 → T037 ; T038 (após T033) ; T039 T040 → T041 (após US7)
US4: T042 T043 T044 → T045 (após T026) → T046 → T047 (após T060)
US5: T048 → T049 T050 T051 → T052 → T053
Final: T063..T070
```

### Within Each User Story

- Testes escritos antes e falhando antes da implementação.
- Tarefas [MANUAL] só depois dos testes automatizados da história passarem.
- Strings novas entram em en e pt_br na mesma tarefa que as usa.

### Parallel Opportunities

- Setup: T004, T005 e T006.
- Phase 2: T007–T011 (testes), depois T012, T013 e T016.
- Depois da Phase 2: US1 (bloco, saída, template) em paralelo com US2 (coleta e cálculo); T039 e
  T040 (ferramentas de benchmark) em paralelo com qualquer história.
- US4: T042, T043 e T044. US5: T049, T050 e T051 (depois de T048).
- Polish: T063 e T064.

## Parallel Example: Phase 2

```text
Task: "T007 tests/analysis/classification_rules_test.php"
Task: "T008 tests/analysis/indicator_calculator_test.php"
Task: "T009 tests/collector/course_files_test.php"
Task: "T010 tests/collector/diagnostic_store_test.php"
Task: "T011 tests/task/analyse_course_test.php"
```

## Parallel Example: User Story 2

```text
Task: "T027 CT-04, CT-05, CT-12, CT-13 em tests/collector/course_files_test.php"
Task: "T030 B e invariantes em tests/analysis/indicator_calculator_test.php"
(T028 e T029 editam o mesmo arquivo de T027: em sequência se forem feitos por um único agente)
```

## Rastreabilidade

### Cenários de teste → tarefas

| CT | Tarefa | CT | Tarefa | CT | Tarefa |
| --- | --- | --- | --- | --- | --- |
| CT-01 | T009, T022 | CT-13 | T027 | CT-25 | T003, T068 |
| CT-02 | T009 | CT-14 | T050 | CT-26 | T043 |
| CT-03 | T009, T058 | CT-15 | T049, T051 | CT-27 | T043 |
| CT-04 | T027 | CT-16 | T044 | CT-28 | T034 |
| CT-05 | T027 | CT-17 | T041 | CT-29 | T029 |
| CT-06 | T054 | CT-18 | T011, T034 | CT-30 | T029 |
| CT-07 | T042, T054 | CT-19 | T042, T044 | CT-31 | T029 |
| CT-08 | T054 | CT-20 | T043 | CT-32 | T028 |
| CT-09 | T028 | CT-21 | T054 | CT-33 | T058 |
| CT-10 | T009 | CT-22 | T028 | CT-34 | T058 |
| CT-11 | T054 | CT-23 | T049 | CT-35 | T058 |
| CT-12 | T027 | CT-24 | T041 | | |

### Requisitos funcionais → tarefas

| FR | Tarefas |
| --- | --- |
| FR-001, FR-002 | T009, T015 |
| FR-003, FR-003a–d | T016, T026, T034, T036, T043 |
| FR-004, FR-024 | T055, T057 |
| FR-010–FR-012 | T008, T014, T027, T030–T032 |
| FR-013, FR-014 | T054–T057 |
| FR-015, FR-015a–c, FR-016, FR-017 | T004, T058–T062, T047 |
| FR-018, FR-019 | T058, T059, T061 |
| FR-019a–d | T029, T032, T033 |
| FR-019e–h | T028, T031, T032, T047 |
| FR-020 | T017, T049 |
| FR-021–FR-023, FR-025 | T007, T009, T012, T015, T054 |
| FR-030–FR-033 | T028 (FR-031), T063, T066 |
| FR-034 | T021, T026 |
| FR-035–FR-037, FR-039, FR-039c, FR-039d | T019, T026, T034–T038 |
| FR-036a | T026, T036 |
| FR-038 | T049, T052 |
| FR-039a, FR-039b | T018, T034, T050 |
| FR-040 | T009 (SC-010) |
| FR-041, FR-044a | T026, T044 |
| FR-042, FR-043 | T019, T042, T047 |
| FR-044 | T046 |
| FR-044b | T043, T045 |
| FR-045–FR-048 | T020, T023, T049, T052 |
| FR-049–FR-051 | T022, T024–T026, T065 |
| FR-052 | T003, T068 |
| FR-053 | T017 |
| FR-054 | T035, T038, T062 |
| FR-055 | T063 |

### Critérios de sucesso → tarefas

| SC | Tarefas |
| --- | --- |
| SC-001 | T009, T027–T029, T054, T058 |
| SC-002 | T021, T040, T041 |
| SC-003, SC-004 | T041 |
| SC-005 | T044 |
| SC-006 | T042 |
| SC-007 | T020, T049, T068 |
| SC-008 | T066 |
| SC-009 | T069 |
| SC-010 | T009 |

## Implementation Strategy

### MVP First (User Story 1)

1. Phase 1 e Phase 2.
2. Phase 3 (US1): o bloco mostra volume e data depois da primeira análise.
3. **Parar e validar**: quickstart §2; demonstrável ao proprietário.

### Incremental Delivery

1. Setup + Foundational → pipeline mínimo.
2. US1 → MVP visível.
3. US2 → números confiáveis (deduplicação, referências, backup e lixeira).
4. US6 → categorias. US7 → atividades e grande porte.
5. US3 → fila horária, CLI e benchmark completo (mede a análise final).
6. US4 → limpeza e verificações de privacidade. US5 → estados de atualidade e falha.
7. Polish → README, pacote, quickstart, CI e evidências.

### Parallel Team Strategy

Depois da Phase 2: uma pessoa em US1 → US5 (bloco, saída, estados); outra em US2 → US6 → US7
(coleta e cálculo); as ferramentas de benchmark (T039, T040) em paralelo. US3 e US4 fecham depois.

## Notes

- Valores em bytes sempre como inteiros; agregados do banco convertidos com `(int)`.
- Nenhum teste depende de rede; nenhuma tarefa sincroniza referências.
- Commits por tarefa ou grupo lógico, com a mensagem no padrão do repositório
  (`feat(002): …`, `test(002): …`, `docs(002): …`).
- Nenhuma integração à `main` sem os Quality Gates e o T070.

## Execução

### MVP (T001–T026) — 2026-10-17

Ambiente: `moodle-docker` próprio da feature (`coursecare-002`, porta 8047), Moodle 4.5.15, PHP 8.1,
PostgreSQL 17, montando este worktree em `blocks/coursecare`. Resultado local: PHPUnit 110 testes
verdes (inclui os da 001), Behat 20 cenários verdes (inclui axe), `moodle-cs` sem erros, paridade
en/pt_br sem erros. CI pendente (push).

Decisões de implementação, sem mudar requisitos:
- **Indicadores ainda não calculados ficam nulos.** No MVP, B, referências, agregado de backup e
  lixeira e os únicos de grande porte são gravados como nulo ("não calculado"), nunca como zero. Eles
  entram em US2, US6 e US7. O bloco só exibe A.
- **Frescor pelo registro.** A tarefa ad hoc do core não expõe a data de criação. A tarefa sai como
  "fresca" quando não há solicitação pendente e a última análise concluída é posterior à última
  solicitação (`course_analysis::is_fresh`). Contrato `diagnostic-api.md` mantido no efeito.
- **Configurações de duração** usam `admin_setting_configduration` (gravadas em segundos, unidade
  padrão horas, mínimo 1 h); o limite de grande porte usa `admin_setting_configtext` com
  validação `/^[1-9][0-9]{0,6}$/`. Contrato atualizado.
- **Códigos de estado da solicitação** ficam em `analysis\diagnostic_state` (0, 1, 2); o
  `diagnostic_store` reutiliza as constantes, para a análise não depender da coleta.
- **Testes**: o teste de atomicidade usa `preventResetByRollback()` (o PHPUnit envolve cada teste numa
  transação externa); o teste de lock usa `db_record_lock_factory`, porque o lock consultivo do
  PostgreSQL é reentrante dentro do mesmo processo. Em produção, cada processador de cron é um
  processo separado.
- **Behat**: `display_size()` separa número e unidade com espaço não separável; o cenário verifica a
  frase com o número e a unidade em passos separados.
- Teste da 001 ajustado: `has_config()` passa a ser verdadeiro (T004), e a verificação de "nenhum
  texto fixo no template" passa a ignorar variáveis Mustache (as frases vêm traduzidas do PHP).
- CI do MVP verde: run 38962129016 (8.1/pgsql, 8.3/mariadb e i18n-package), commit `84daca5`.

### US2 (T027–T033) — 2026-10-17

Resultado local: PHPUnit 118 testes verdes, Behat 20 cenários verdes, `moodle-cs` sem erros.

- Referências tipadas em Q1 por junção `files_reference → repository_instances → repository`.
  Os testes criam atalhos reais com `create_file_from_reference()` nos repositórios `local`, `user` e
  `dropbox` (geradores do core) e gravam a sincronização com `update_references()`, sem rede.
  `files_reference` fica idêntico depois da análise.
- CT-29 usa um backup real (`backup_controller`) e a lixeira real (`course_delete_module` com a lixeira
  de curso ativada).
- **Q2 (únicos por categoria) foi implementada já na US2**, porque as linhas `backup` e `recyclebin`
  exigem o conteúdo único de cada subcategoria. Na US6, resta gravar as linhas das categorias do
  escopo principal (T056 fica reduzida a reaproveitar a consulta).
- As constantes de tipo de referência e de grupo ficam na camada de análise
  (`indicator_calculator`); a coleta as reutiliza.

### US3 (T034–T040) — 2026-10-17

Resultado local: PHPUnit 130 testes verdes, Behat 20 cenários verdes, `moodle-cs` sem erros. CI da US2
verde: run 38964414578 (8.1/pgsql, 8.3/mariadb e i18n-package), commit `93be1c4`.

- Regra de "sala devida" e espera após falhas em `analysis\due_policy` (pura, `due_policy_test`); a
  tarefa agendada só orquestra. As consultas de órfãos, presos e candidatas ficam em `diagnostic_store`.
- `queue_analyses::run()` aceita o tamanho do lote como parâmetro, para testar o limite com poucas
  salas; o padrão continua 500.
- Enfileirar uma sala (`analyse_course::request()`) é uma função única usada pelo bloco, pela tarefa
  agendada e pela CLI.
- A CLI tem a lógica em `local\admin_cli` (testável) e o script só lê opções. A saída de texto do
  `--show` usa as chaves de dados do JSON; frases passam pela String API (contrato atualizado).
- Teste de sala restaurada com backup e restauração reais (`MODE_IMPORT`): a sala não tem registro e
  entra na fila na execução seguinte.
- Verificação manual no site de desenvolvimento (`coursecare-002`): `generate.php` (3 salas, perfil
  pequeno) e `diagnostics.php --run/--show/--queue --all` com códigos de saída 0, 1 e 2 conferidos; A e B
  iguais ao manifesto do gerador; `queue_analyses` e `analyse_course` executados pelos CLIs do cron,
  inclusive um "fresh" para uma sala já analisada.
- **T041 (benchmark) continua pendente**: depende de US6 e US7 (mede a análise completa).

### US4 (T042–T046) — 2026-10-17

Resultado local: PHPUnit 137 testes verdes, Behat 21 cenários verdes, `moodle-cs` sem erros.
CI da US3 verde: run 38966216241 (8.1/pgsql, 8.3/mariadb e i18n-package), commit `482830d`.

- `instance_delete()` apaga o diagnóstico só quando não resta outra instância no mesmo contexto pai
  (a própria linha ainda existe durante a chamada). Cobre remoção, exclusão da sala e desinstalação.
- Desinstalação: em vez de desinstalar o plugin dentro do PHPUnit (removeria o esquema para os testes
  seguintes), o teste confirma que as 4 tabelas estão declaradas no `install.xml`, que o core remove na
  desinstalação; a verificação real fica no quickstart (T068).
- SC-006 verificado pelo esquema (nenhuma coluna de usuário, nome, caminho ou hash) e pelos valores
  gravados (nenhum nome de arquivo, atividade ou participante).
- `privacy:metadata` revisada em en e pt_br; o provider continua `null_provider`.
- **T047 continua aberta**: a revisão das ligações de arquivos grandes depende da Q4 (T060, US7). A parte
  de consultas já está cumprida (nenhuma consulta seleciona `userid`, `filename`, `filepath`, `author` ou
  `source`).

CI da US4 verde: run 38966922553 (8.1/pgsql, 8.3/mariadb e i18n-package), commit `1f90ae8`.

### US5 (T048–T053) — 2026-10-17

Resultado local: PHPUnit 142 testes verdes, Behat 23 cenários verdes, `moodle-cs` sem erros.

- Estados E (desatualizado) e F (falha com resultado) com a precedência F → E → G/D. Num estado E ou
  F sem arquivos, a linha mantém "nenhum arquivo encontrado" (o `diagnostic_state` passou a carregar
  `filecount`).
- Geradores de teste: `block_coursecare_generator::create_diagnostic()` (estende
  `testing_block_generator`, então também cria instâncias do bloco) e a entidade Behat
  `block_coursecare > diagnostics`, com `timecompletedago` em segundos.
- CT-14 com coleta injetada que lança `dml_read_exception`: resultado anterior intacto, código curto
  sem a mensagem, e outra sala analisada normalmente pelo cron em seguida; invariantes que falham duas
  vezes gravam `inconsistent`.

CI da US5 verde: run 38967792551 (8.1/pgsql, 8.3/mariadb e i18n-package), commit `17b0a97`.

### US6 (T054–T057) — 2026-10-17

Resultado local: PHPUnit 146 testes verdes, Behat 23 cenários verdes, `moodle-cs` sem erros, i18n sem
erros.

- O cálculo grava uma linha de `block_coursecare_cat` para cada categoria do escopo principal com
  registros (`material`, `submission`, `other`, `unclassified`), antes das linhas de backup e lixeira.
  Os únicos de cada categoria vêm da Q2, que já existia desde a US2; T056 ficou reduzida a excluir as
  áreas `excluded` dentro da própria consulta (`WHERE c.category <> :q2excluded`).
- Nova invariante: quando há linhas do escopo principal, a soma de A_c é A e a soma das contagens é
  `filecount`. Σ B_c pode passar de B (conteúdo em mais de uma categoria) e não é verificada.
- CT-07 usa a API da tarefa (`assign::save_submission`) para o arquivo enviado e para o texto online
  com imagem; a imagem é referenciada pela URL de rascunho, como faz o editor, porque o envio descarta
  rascunhos não referenciados. A resposta de redação do Questionário e os retornos de CT-08 são
  gravados direto nas áreas reais (`question/response_attachments`, `assignfeedback_file`,
  `assignfeedback_comments`, `grade/feedback`, `mod_forum/attachment`).
- Conferência manual no site de desenvolvimento (sala 2, `--run` e `--show`): Materiais 15 registros,
  Outros 5, Envios 5; soma 25 = `filecount` e 13.726 bytes = A; Σ B_c 13.726 > B 12.970.

CI da US6 verde: run 38968677593 (8.1/pgsql, 8.3/mariadb e i18n-package), commit `531d8f3`.

### US7 (T058–T062) e T047 — 2026-10-17

Resultado local: PHPUnit 156 testes verdes, Behat 23 cenários verdes, `moodle-cs` sem erros, i18n sem
erros.

- Q4 (`course_files::get_large_files()`) lê só `id`, `contextid`, `component`, `filearea`,
  `contenthash` e `filesize` dos registros que não são referências, com `filesize >= :q4threshold`,
  por recordset fechado ao fim. O limite é o mesmo valor lido no início da análise para a Q1.
- `indicator_calculator::cmid_map()` atribui cada contexto ao módulo ancestral pelo caminho (bloco
  dentro de atividade → atividade; curso e blocos da sala → `cmid = 0`). As linhas de atividade
  trazem volume por categoria e D; D único (geral e por atividade) é calculado em memória com os
  hashes da Q4, que nunca são gravados.
- Ligações: só registros da categoria `material`, ordenados por tamanho decrescente e `fileid`
  crescente, no máximo `MAX_LARGE_LINKS = 1000`; `largelinkstruncated = 1` quando houver mais. Os
  totais de D continuam contando todos os arquivos.
- Novas invariantes: Σ A_cm = A, Σ das contagens e de D por atividade = totais da sala, D único ≤ D
  lógico (também por atividade), ligações ≤ 1.000 e ≤ `largecount`.
- `--show` da CLI: linhas `activity.<cmid>.<campo>` e `largelinks` (só a quantidade; nenhum arquivo é
  listado). Conferência manual no site de desenvolvimento (sala 2): 5 atividades, Σ A_cm = 13.726 = A.
- Os testes de grande porte criam arquivos em blocos de 1 MB (`add_big_file()`), sem manter o
  conteúdo inteiro em memória; o teste de 1.001 arquivos usa o limite de 1 MB e cópias do mesmo
  conteúdo (`create_file_from_storedfile()`), sem gravar 1 GB.
- **T047**: a regra das ligações ficou documentada no PHPDoc de `course_files` e de
  `diagnostic_store`. Diferença de redação em relação à tarefa: a Q4 devolve todos os registros
  grandes (D precisa de todos) e o filtro `material` fica num só lugar, no cálculo; o `save_result()`
  grava as ligações que o resultado traz. Nenhuma consulta seleciona `userid`, `filename`,
  `filepath`, `author` ou `source` (`filename` só aparece no filtro `<> '.'`).
- **T041 (benchmark)** agora pode ser executada: US6 e US7 estão completas.
