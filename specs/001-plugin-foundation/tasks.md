---

description: "Tarefas de implementação da Feature 001 — CourseCare Plugin Foundation"
---

# Tasks: CourseCare Plugin Foundation

**Input**: Design documents from `specs/001-plugin-foundation/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md),
[data-model.md](data-model.md), [contracts/](contracts/), [quickstart.md](quickstart.md) ·
Constituição 1.2.0

**Tests**: obrigatórios. A spec e a constituição exigem PHPUnit, Behat, os testes das ferramentas
de repositório e as verificações manuais com evidência (CA-01 a CA-13, FR-028, FR-030, Quality
Gates 1–5). Os testes de cada história vêm antes da implementação correspondente.

**Organization**: tarefas agrupadas por história de usuário (US1–US5 da spec), precedidas de Setup
e Foundational e seguidas de uma fase de validação final.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: pode rodar em paralelo (arquivos diferentes, sem dependência de tarefa incompleta).
- **[Story]**: história a que a tarefa pertence (US1–US5).
- **[MANUAL]**: validação em Moodle real, com procedimento, resultado esperado, evidência e
  critério de aprovação descritos na própria tarefa.

## Path Conventions

O repositório é a raiz do plugin, instalado em `<moodle>/blocks/coursecare` (plan.md, "Project
Structure"). As evidências ficam em `specs/001-plugin-foundation/evidence/`, abreviado aqui como
`evidence/`, fora do pacote distribuível. Todo arquivo PHP leva o cabeçalho GPL v3+ padrão do Moodle
e PHPDoc (Princípio XII, Quality Gate 1).

## Regras de complexidade (valem para todas as tarefas)

Não criar classes vazias, interfaces, serviços, renderers, `classes/local/`, `settings.php`,
`edit_form.php`, `db/install.xml`, `amd/`, `thirdpartylibs.xml` nem dependências externas
(plan.md, Constituição C6/XI). O único método auxiliar previsto é privado, dentro de
`block_coursecare.php`.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: base do repositório, do ambiente reproduzível e da estrutura de evidências.

- [X] T001 Criar `.gitattributes` na raiz com `export-ignore` para `translations/`, `tools/`,
  `specs/`, `.specify/`, `.claude/`, `.github/`, `.gitattributes`, `.gitignore`, `CONTRIBUTING.md` e
  `build/`, e acrescentar `build/` ao `.gitignore` existente (plan.md, "Estratégia de distribuição").
- [X] T002 [P] Adicionar `LICENSE` (texto integral da GNU GPL v3 obtido de gnu.org) e `CHANGES.md`
  com a entrada "0.1.0 (não publicada) — fundação do bloco" na raiz.
- [X] T003 [P] Criar `tools/dev/local.yml.dist` (serviço `webserver` com o volume
  `${COURSECARE_PLUGIN_DIR}:/var/www/html/blocks/coursecare`) e os CSVs fictícios
  `tools/dev/demo/courses.csv` (curso `CC-DEMO`, formato `topics`, categoria 1) e
  `tools/dev/demo/users.csv` (usuários `editor`, `assistente`, `aluno` e `gestor`; e-mails
  `@example.com`; senhas de teste; `course1=CC-DEMO`; `role1` = `editingteacher`, `teacher`,
  `student` e `manager`), conforme quickstart.md §3.
- [X] T004 [P] Criar `specs/001-plugin-foundation/evidence/README.md` com a tabela CA-01 a CA-13
  (colunas: CA, evidência, data, Moodle, tema, idioma, navegador, status = "pendente"), a seção
  "Aprovações" **vazia** (ativo visual, textos en, textos pt_BR) e os diretórios `screenshots/` e
  `logs/` com `.gitkeep`, conforme quickstart.md §10. Nenhuma aprovação pode ser pré-preenchida
  (FR-014, FR-015).
- [X] T005 [MANUAL] Provisionar o Moodle 4.5 local conforme quickstart.md §2: `moodle-docker`,
  clone `MOODLE_405_STABLE`, PHP 8.1, pgsql e `local.yml` copiado de T003.
  - **Procedimento**: executar os comandos do quickstart §2 (até `install_database.php`) e §3
    (carga dos CSVs).
  - **Esperado**: site em http://localhost:8000, curso `CC-DEMO` com 4 usuários matriculados.
  - **Evidência**: `evidence/logs/environment.txt` (versão exata do core em `moodle/version.php`,
    PHP, banco, commit do plugin).
  - **Aprovação**: o ambiente sobe a partir de um clone limpo seguindo apenas o quickstart.
  - Depende de T003.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: o menor plugin instalável e renderizável, do qual todas as histórias dependem.

**⚠️ CRITICAL**: nenhuma história começa antes desta fase.

- [X] T006 Criar `version.php` com `$plugin->component = 'block_coursecare'`, `$plugin->version`
  no formato AAAAMMDDXX da data de implementação, `$plugin->requires = 2024100700`,
  `$plugin->supported = [405, 405]`, `$plugin->maturity = MATURITY_ALPHA` e
  `$plugin->release = '0.1.0'` (FR-001, FR-003; research R1).
- [X] T007 [P] Criar `lang/en/block_coursecare.php` no padrão core (research R5):
  - cabeçalho GPL e docblock do arquivo;
  - **sem** `defined('MOODLE_INTERNAL')` e sem comentários entre as strings;
  - só dados (`$string['chave'] = 'texto';`), sem title case;
  - chaves **nesta ordem alfabética**, exigida pelo `LangFilesOrderingSniff` do `moodle-cs`:
    `coursecare:addinstance` = "Add a new CourseCare block"; `coursecare:view` = "View the
    CourseCare block"; `intro` = "Soon you will find guidance here on using your course resources
    more mindfully."; `pluginname` = "CourseCare"; `privacy:metadata` = "The CourseCare block does
    not store any personal data."; `tagline` = "Small choices, a better learning space for
    everyone."
  (FR-019, FR-020; data-model "Catálogo de strings").
- [X] T008 [P] Criar `db/access.php` conforme [contracts/capabilities.md](contracts/capabilities.md):
  - `block/coursecare:addinstance`: `captype` `write`, `CONTEXT_BLOCK`, `RISK_SPAM | RISK_XSS`,
    arquétipos `editingteacher` e `manager`, `clonepermissionsfrom` `moodle/site:manageblocks`;
  - `block/coursecare:view`: `captype` `read`, `CONTEXT_BLOCK`, arquétipos `editingteacher` e
    `manager`, sem risco;
  - **não** definir `myaddinstance` (FR-007, FR-009).
- [X] T009 [P] Criar `classes/privacy/provider.php` com `\block_coursecare\privacy\provider`
  implementando `\core_privacy\local\metadata\null_provider`, em que `get_reason()` retorna
  `'privacy:metadata'` (FR-005).
- [X] T010 [P] Criar `templates/content.mustache` com a estrutura de
  [contracts/block-ui.md](contracts/block-ui.md): parágrafos `{{#str}} tagline, block_coursecare
  {{/str}}` e `{{#str}} intro, block_coursecare {{/str}}`, sem textos fixos, e bloco de documentação
  com "Example context (json)" `{}` para o `mustache lint`. O ícone do ativo entra em T026
  (FR-012, FR-019).
- [X] T011 Criar `block_coursecare.php` com `class block_coursecare extends block_base`:
  - `init()` define `$this->title = get_string('pluginname', 'block_coursecare')`;
  - `has_config()` retorna `false`;
  - `get_content()` retorna o template de T010 via
    `$OUTPUT->render_from_template('block_coursecare/content', [...])`;
  - sem outras sobrescritas nesta tarefa (FR-001, FR-011). Depende de T006–T010.
- [X] T012 Criar `.github/workflows/ci.yml` com o job `moodle`, a partir do `gha.dist.yml` oficial do
  `moodle-plugin-ci` ^4:
  - matriz `MOODLE_405_STABLE` × {PHP 8.1 + pgsql, PHP 8.3 + mariadb};
  - etapas phplint, phpmd (`continue-on-error`), phpcs `--max-warnings 0`, phpdoc
    `--max-warnings 0`, validate, savepoints, mustache, grunt `--max-lint-warnings 0`, phpunit
    `--fail-on-warning` e behat `--profile chrome`;
  - env `IGNORE_PATHS: 'tools,translations'` na etapa de install. Esses diretórios não são código
    do plugin, e o `MoodleInternalSniff` reprovaria seus arquivos. Na primeira execução, confirmar
    nos logs de phpcs e phpdoc que nenhum arquivo deles foi analisado
  (research R11; Quality Gates 1 e 5). Depende de T011.

**Checkpoint**: o job `moodle` passa em phplint, phpcs, phpdoc, validate, savepoints e mustache.
O plugin instala sem avisos no `moodle-plugin-ci install`.

---

## Phase 3: User Story 1 — Instalação pelo administrador (Priority: P1) 🎯 MVP

**Goal**: o administrador instala, habilita, desabilita e desinstala o CourseCare pelos fluxos
nativos, sem alterar o core.

**Independent Test**: em um Moodle 4.5 limpo, instalar o plugin, conferir o bloco habilitado,
desabilitar, reabilitar e desinstalar, sempre pelos fluxos nativos (spec US1).

### Tests for User Story 1

- [X] T013 [P] [US1] Criar `tests/privacy/provider_test.php` (`\core_privacy\tests\provider_testcase`
  ou `advanced_testcase`). Ele confirma que `\block_coursecare\privacy\provider` implementa
  `null_provider` e que `get_reason()` aponta para uma string existente em `lang/en` (FR-005).

### Implementation and validation for User Story 1

- [X] T014 [US1] [MANUAL] Validar a instalação e o ciclo habilitar/desabilitar no ambiente de T005
  (quickstart §2 e V11).
  - **Procedimento**: confirmar que `block_coursecare` está instalado; desabilitar e reabilitar em
    Administração › Plugins › Blocos; rodar `git -C moodle status --porcelain`.
  - **Esperado**: sem erros nem avisos de depuração; bloco listado como "CourseCare"; saída do git
    vazia (core intacto).
  - **Evidência**: `evidence/logs/install.txt`, `evidence/logs/core-git-status.txt` e
    `evidence/screenshots/boost-en-1280-admin-blocks.png`.
  - **Aprovação**: CA-01 e CA-11 marcados com os links das evidências. O efeito de
    desabilitar/reabilitar sobre instâncias existentes (US1-2) é validado em T043, quando já há
    instâncias.
  - Depende de T005 e T011.
- [X] T015 [US1] [MANUAL] Validar a desinstalação e a reinstalação sem instâncias (quickstart §9).
  - **Procedimento**: `admin/cli/uninstall_plugins.php --plugins=block_coursecare --run` e, depois,
    `admin/cli/upgrade.php --non-interactive`.
  - **Esperado**: desinstalação sem erros, capabilities `block/coursecare:*` removidas e reinstalação
    limpa.
  - **Evidência**: `evidence/logs/uninstall-empty.txt`.
  - **Aprovação**: os logs não têm erros. A desinstalação **com** instâncias é feita em T044.
  - Depende de T014.

**Checkpoint**: US1 entregável. O plugin instala e desinstala de forma nativa (MVP técnico).

---

## Phase 4: User Story 2 — Adição do bloco a uma sala (Priority: P1)

**Goal**: o bloco só pode ser adicionado e visto por quem tem permissão, aparece apenas na página
principal da sala e tem no máximo uma instância por sala nos fluxos de usuário.

**Independent Test**: com usuários de papéis diferentes em `CC-DEMO`, verificar quem adiciona,
configura e vê o bloco, e que ele não aparece em seções nem em atividades (spec US2 e US3-7).

### Tests for User Story 2 (escrever antes de T019)

- [X] T016 [P] [US2] Criar `tests/block_coursecare_test.php` (`advanced_testcase`, gerador padrão
  de blocos do core, sem `tests/generator/`). Casos:
  - `applicable_formats()` retorna exatamente `['course-view' => true, 'course-view-section' => false]`;
  - `instance_allow_multiple()` e `has_config()` retornam `false`;
  - `get_content()` gera texto para usuários com `editingteacher` e `manager` e conteúdo vazio para
    `teacher`, `student` e visitante;
  - conteúdo vazio após `assign_capability('block/coursecare:view', CAP_PROHIBIT, ...)` no contexto
    do bloco; conteúdo gerado após conceder `:view` a `teacher`;
  - com tipo de página `course-view-topics`: conteúdo presente e `instance_can_be_edited()`
    verdadeiro;
  - com `course-view-section-topics` e `mod-page-view`: conteúdo vazio e `instance_can_be_edited()`
    falso.
  (FR-006 a FR-010; contracts/capabilities.md, invariantes 1–5).
- [X] T017 [P] [US2] Criar `tests/behat/block_coursecare_permissions.feature`
  (`@block @block_coursecare`). Cenários:
  - `editor` adiciona "CourseCare" e vê o conteúdo;
  - com o bloco presente, `the add block selector should not contain "CourseCare" block`;
  - `assistente`, `aluno` e visitante não veem o bloco (edição desligada);
  - com `addinstance` proibido para `editingteacher`, o bloco não é oferecido em outro curso;
  - com `:view` concedido a `teacher` no curso, `assistente` vê o bloco
  (US2-1 a US2-5; CA-02, CA-04).
- [X] T018 [P] [US2] Criar `tests/behat/block_coursecare_display.feature`
  (`@block @block_coursecare`). Cenários:
  - página principal com edição desligada (`editor` vê; `aluno` não);
  - página principal com edição ligada (`editor` vê bloco e controles);
  - página de seção com edição ligada: CourseCare não é oferecido e `"CourseCare" "block" should not
    exist`;
  - bloco configurado para qualquer página: em uma atividade, com edição ligada,
    `"CourseCare" "block" should not exist`;
  - formato do curso alterado de `topics` para `weeks`: o bloco continua na página principal e não
    é oferecido de novo
  (FR-006, FR-010; US3-7; research R3).

### Implementation for User Story 2

- [X] T019 [US2] Completar `block_coursecare.php`:
  - `applicable_formats()` retorna `['course-view' => true, 'course-view-section' => false]`;
  - `instance_allow_multiple()` retorna `false`;
  - método privado `is_main_page()`: tipo de página começa com `course-view-` e não com
    `course-view-section-`;
  - `get_content()` retorna conteúdo vazio se `!is_main_page()` ou
    `!has_capability('block/coursecare:view', $this->context)`, antes de renderizar;
  - `instance_can_be_edited()` retorna `is_main_page()`;
  - nenhuma verificação por nome de papel.
  (research R2/R3; contracts/capabilities.md). Depende de T016–T018. Os testes devem passar no
  ambiente de T005 (quickstart §7).
- [X] T020 [US2] [MANUAL] Validar a matriz de exibição e permissões em Moodle real (quickstart V1–V6,
  V3, V3a).
  - **Procedimento**: em `CC-DEMO`, percorrer {página principal, página de seção, atividade com
    "Exibir nos tipos de página" = qualquer página} × {edição ligada, desligada} × {`editor`,
    `gestor`, `assistente`, `aluno`, visitante} e, em seguida, `assistente` com `:view` concedido;
    tentar adicionar uma segunda instância; mudar o formato para semanal; repetir com
    `addinstance` proibido.
  - **Esperado**: conforme [contracts/capabilities.md](contracts/capabilities.md). O bloco só aparece
    na página principal e só para quem tem `:view`. Fora dela, não aparece nem como cabeçalho
    vazio, em qualquer modo. Não há segunda instância. Única exceção aceita: editor sem `:view` na
    página principal, com edição ligada, vê cabeçalho e controles (RK2).
  - **Evidência**: `evidence/display-matrix.md` (tabela preenchida) e capturas
    `evidence/screenshots/boost-en-1280-<cena>.png`.
  - **Aprovação**: todas as células iguais ao esperado. Qualquer divergência bloqueia CA-02 e CA-04.
  - Depende de T019.
- [X] T021 [US2] [MANUAL] Validar a restauração de backup sobre sala que já tem o bloco (quickstart
  V3b; decisão registrada no plano).
  - **Procedimento**: fazer backup de `CC-DEMO` com blocos e restaurar em "mesclar com o curso
    existente" no próprio `CC-DEMO`.
  - **Esperado**: continua havendo uma única instância do CourseCare (FR-010).
  - **Evidência**: `evidence/logs/restore-merge.txt` (contagem de `block_instances` com
    `blockname='coursecare'` para o contexto do curso, antes e depois) e captura da página principal.
  - **Aprovação**: a contagem é 1 antes e 1 depois.
  - Depende de T019.

**Checkpoint**: US2 entregável. Permissões e exibição verificadas por testes automatizados e em
Moodle real.

---

## Phase 5: User Story 3 — Experiência inicial (Priority: P2)

**Goal**: bloco acolhedor e discreto, com ativo decorativo próprio, sem indicadores, responsivo e
acessível nos temas core.

**Independent Test**: com o bloco renderizado em Moodle real, conferir identidade, textos, ausência
de indicadores e links, acessibilidade e responsividade (spec US3). A aprovação do proprietário
acontece em T046.

### Tests for User Story 3 (escrever antes de T026)

- [X] T022 [P] [US3] Acrescentar a `tests/block_coursecare_test.php`:
  - o HTML de `get_content()` contém `tagline` e `intro` na ordem;
  - o texto visível, sem marcação, não contém dígitos;
  - não há `<a`, `<button`, `<form` nem `<input`;
  - o ativo é gerado pela Pix Icon API (elemento com a classe `icon` e `aria-hidden="true"`, sem
    texto alternativo), e não há marcação de imagem escrita à mão no template (Princípio X);
  - nenhum texto fixo fora das strings (FR-012, FR-013, FR-016, FR-019; contracts/block-ui.md).
  Depende de T016, por ser o mesmo arquivo.
- [X] T023 [P] [US3] Criar `tests/behat/block_coursecare_ui.feature` (`@block @block_coursecare`):
  - conteúdo correto em inglês, sem links;
  - `@javascript`: `the "CourseCare" "block" should meet accessibility standards`;
  - `I change viewport size to "mobile"` e `"tablet"` com o conteúdo visível;
  - cenário com `theme` = `classic` (via `the following config values are set as admin`) e
    verificação de acessibilidade
  (US3-1, US3-3, US3-5; CA-03, CA-10, CA-12).

### Implementation for User Story 3

- [X] T024 [P] [US3] Criar `pix/sprout.svg`:
  - broto original de duas folhas em tons de verde suaves, traço simples;
  - `viewBox` quadrado, sem texto, sem script, sem referências externas;
  - comentário de licença GPL v3+;
  - legível a 40 px em fundo claro e escuro;
  - sem personagens, emojis ou elementos de alerta (FR-015, FR-015a).
- [X] T025 [P] [US3] Criar `styles.css` com seletores exclusivamente sob `.block_coursecare`:
  - layout flexível (ativo de 40 px ao lado do texto e empilhado em larguras estreitas);
  - tamanho do ícone em `.block_coursecare .icon` (largura e altura de cerca de 40 px), sem alterar
    a classe `icon` fora do bloco;
  - tagline com destaque moderado, sem cor própria;
  - sem `!important`, sem cores de texto e sem utilitários que divergem entre BS4 e BS5
    (research R4; contracts/block-ui.md, regras 1, 5–7).
- [X] T026 [US3] Completar `templates/content.mustache`:
  - acrescentar o ativo com `{{#pix}} sprout, block_coursecare {{/pix}}` (sem texto alternativo:
    o core gera `aria-hidden="true"`), usando apenas utilitários comuns a BS4 e BS5;
  - nenhuma marcação de imagem escrita à mão nem `image_url()`;
  - `get_content()` em `block_coursecare.php` continua passando contexto vazio
  (contracts/block-ui.md; research R4; Princípio X). Depende de T019, T024 e T025. T022 e T023
  devem passar.

**Checkpoint**: US3 implementada. A validação visual completa (en/pt_BR, larguras, temas, teclado,
leitor de tela) acontece em T045, depois de US4.

---

## Phase 6: User Story 4 — Idioma do professor e fallback (Priority: P2)

**Goal**: o bloco aparece em pt_BR com a tradução de desenvolvimento e cai para o inglês, string a
string, quando a tradução falta.

**Independent Test**: alternar o idioma entre en, pt_BR com a tradução, pt_BR sem a tradução e um
terceiro idioma, conferindo o texto em cada caso (spec US4).

### Tests for User Story 4 (escrever antes de T028)

- [X] T027 [P] [US4] Criar `tests/translation_test.php` (`advanced_testcase`, research R7):
  - criar no `dataroot` de testes um `lang/pt_br/langconfig.php` mínimo;
  - aplicar `translations/pt_br/block_coursecare.php` como `lang/pt_br_local/block_coursecare.php`;
  - reiniciar o string manager (`get_string_manager(true)`);
  - verificar (a) `get_string('tagline', 'block_coursecare', null, 'pt_br')` em português, (b) a
    chave removida volta ao inglês e (c) um idioma sem tradução retorna o inglês;
  - `markTestSkipped` quando `translations/` não existe (instalação a partir do pacote).
  Se (a) e (b) se mostrarem instáveis, aplicar o plano B do RK3 (manter só (c) e registrar a
  limitação em `evidence/README.md`) (FR-023; CA-07).

### Implementation for User Story 4

- [X] T028 [US4] Criar `translations/pt_br/block_coursecare.php` com o mesmo formato de T007:
  cabeçalho GPL e docblock, sem `MOODLE_INTERNAL`, sem comentários entre as strings e as mesmas
  chaves na mesma ordem alfabética:
  - `coursecare:addinstance` = "Adicionar um novo bloco AVA Consciente";
  - `coursecare:view` = "Ver o bloco AVA Consciente";
  - `intro` = "Em breve, você encontrará aqui orientações para utilizar os recursos da sua sala de
    forma mais consciente.";
  - `pluginname` = "AVA Consciente";
  - `privacy:metadata` = "O bloco AVA Consciente não armazena dados pessoais.";
  - `tagline` = "Pequenas escolhas, um AVA melhor para todos.";
  - terminologia genérica, sem referência institucional (FR-021, FR-024).
  T027 deve passar.
- [X] T029 [P] [US4] Criar `CONTRIBUTING.md`: como contribuir; tradução mantida em
  `translations/pt_br/`; instalação da tradução de desenvolvimento por
  `admin/tool/customlang/cli/import.php --lang=pt_br --source=<plugin>/translations/pt_br --checkin`
  (exclusiva de desenvolvimento e homologação, nunca para produção); contribuição ao AMOS após a
  publicação; proibição de concatenação de strings (FR-022; Princípio VIII).
- [X] T030 [US4] [MANUAL] Validar idiomas e fallback em Moodle real (quickstart §4 e V7–V10).
  - **Procedimento**: instalar o pacote oficial pt_br; importar a tradução por `tool_customlang` com
    o comando de T029; purgar caches; conferir o bloco como `editor` em English e em Português -
    Brasil; remover toda a personalização pt_br; depois remover só a chave `intro`; conferir com um
    terceiro idioma (ex.: es, instalado só para o teste).
  - **Esperado**: textos de T007 e T028 exatos; sem tradução, tudo em inglês; sem `intro`, só a intro
    em inglês, inteira; idioma sem tradução em inglês; nenhum `notice_inexitentstring` no log do
    importador.
  - **Evidência**: `evidence/logs/customlang-import.txt` e capturas
    `evidence/screenshots/boost-{en,pt_br}-1280-*.png`, incluindo os casos de fallback.
  - **Aprovação**: CA-05, CA-06 e CA-07 marcados com os links. Esta validação **complementa** e não
    substitui T034/T039 (paridade automatizada).
  - Depende de T005, T026 e T028.

**Checkpoint**: US4 entregável. Idiomas e fallback verificados de forma automatizada (T027) e em
Moodle real (T030).

---

## Phase 7: User Story 5 — Verificação automatizada pelo mantenedor (Priority: P3)

**Goal**: verificador de paridade de traduções e verificador do pacote real, executáveis localmente
e no CI, sem dependências.

**Independent Test**: executar as verificações sobre fixtures válidas e inválidas e sobre um ZIP
gerado, conferindo os códigos de saída e as mensagens (spec US5;
[contracts/dev-tools-cli.md](contracts/dev-tools-cli.md)).

### Tests for User Story 5 (escrever antes de T033–T036)

- [X] T031 [P] [US5] Criar as fixtures em `tools/i18n/tests/fixtures/`:
  - `valid/`;
  - um diretório por código de regra (`missing-key`, `extra-key`, `placeholder-mismatch` com
    `{$a}` e `{$a->name}`, `empty-value`, `invalid-syntax` com concatenação, heredoc, variável e
    chamada de função, `duplicate-key`, `missing-file`, `same-as-english`);
  - cada um com `lang/en/` e `translations/<idioma>/` (FR-025, FR-028).
- [X] T032 [US5] Criar `tools/i18n/tests/run.php` (PHP puro):
  - executar `tools/i18n/check.php --root=<fixture>` para cada fixture e comparar o código de saída
    (`0`, `1` ou `2`) e a linha `ERROR|WARNING <código>` esperada;
  - testar `--strict` com `same-as-english`;
  - montar com `ZipArchive` um ZIP válido e um por regra do verificador de pacote (raiz incorreta,
    `translations/`, `lang/pt_br/`, `tools/`, `specs/`, arquivo oculto, arquivo fora da allowlist,
    obrigatório ausente) e comparar os resultados de `tools/package/check.php`;
  - sair com `0` só se todos os casos passarem (FR-028; SC-005).
  Depende de T031.

### Implementation for User Story 5

- [X] T033 [P] [US5] Criar `tools/i18n/check.php` conforme contracts/dev-tools-cli.md:
  - análise por `token_get_all` sem executar os arquivos;
  - regras `missing-key`, `extra-key`, `placeholder-mismatch`, `empty-value`, `invalid-syntax`,
    `duplicate-key`, `missing-file` e `same-as-english` (aviso; erro com `--strict`);
  - saída `ERROR|WARNING <código> <idioma> <arquivo>:<chave> <detalhe>` mais o resumo;
  - códigos de saída 0/1/2; `--root`;
  - cabeçalho GPL e PHPDoc (FR-025, FR-027).
- [X] T034 [US5] Rodar `php tools/i18n/tests/run.php` (casos do verificador de traduções) e
  `php tools/i18n/check.php` sobre o repositório (`lang/en` × `translations/pt_br`).
  - **Esperado**: todos os casos passam; repositório com `0` erros.
  - **Evidência**: `evidence/logs/i18n-check.txt` (CA-08).
  - Depende de T028, T032 e T033.
- [X] T035 [P] [US5] Criar `tools/package/build.sh`:
  - `git archive --format=zip --prefix=coursecare/` de `<ref>` (padrão `HEAD`) para
    `build/block_coursecare-<release>.zip`, com `<release>` lido de `version.php`;
  - falha se houver alterações em arquivos rastreados;
  - respeita `.gitattributes` de T001 (contracts/dev-tools-cli.md).
- [X] T036 [P] [US5] Criar `tools/package/check.php` conforme contracts/dev-tools-cli.md:
  - lê o ZIP real com `ZipArchive`;
  - aplica a allowlist do plan.md (`version.php`, `block_coursecare.php`, `db/access.php`,
    `classes/privacy/provider.php`, `lang/en/block_coursecare.php`, `templates/*.mustache`,
    `pix/*.svg`, `styles.css`, `tests/**`, `README.md`, `CHANGES.md`, `LICENSE`);
  - falha com raiz diferente de `coursecare/`, `lang/<≠en>/`, `translations/`, `tools/`, `specs/`,
    `.specify/`, `.claude/`, `.github/`, `build/`, `vendor/`, `node_modules/`, arquivos ocultos ou
    obrigatório ausente;
  - códigos de saída 0/1/2 (FR-026).
- [X] T037 [US5] Acrescentar o job `i18n-package` a `.github/workflows/ci.yml`: PHP 8.1 com a
  extensão zip, sem Moodle; passos `php -l` em todos os PHP de `tools/` (exceto
  `tools/i18n/tests/fixtures/`) e de `translations/`, que ficam fora do `moodle-plugin-ci` (T012),
  `php tools/i18n/tests/run.php`, `php tools/i18n/check.php`,
  `tools/package/build.sh`, `php tools/package/check.php build/block_coursecare-*.zip` e upload do
  ZIP como artefato (FR-027; Quality Gate 4). Depende de T012, T032, T033, T035 e T036.
- [X] T038 [US5] Gerar e inspecionar o pacote real:
  - rodar `tools/package/build.sh && php tools/package/check.php build/block_coursecare-*.zip`
    e `unzip -l build/block_coursecare-*.zip`;
  - **Esperado**: `check.php` retorna `0`; a raiz é `coursecare/`; só há arquivos da allowlist;
    `lang/` contém apenas `en/`; não há `translations/`, `tools/`, `specs/` nem arquivos ocultos;
  - **Evidência**: `evidence/logs/package-check.txt` com as duas saídas (CA-09; SC-006).
  - Depende de T026, T028, T035 e T036.

**Checkpoint**: US5 entregável. Quality Gate 4 automatizado localmente e no CI.

---

## Phase 8: Polish, validação final e aprovação

**Purpose**: documentação, CI verde, validações manuais transversais em Moodle real, evidências e
aprovação do proprietário do produto.

- [X] T039 Confirmar o CI verde nos dois jobs (`moodle` e `i18n-package`) no último commit da branch
  e registrar os links das execuções em `evidence/README.md` (Quality Gates 1–5). Depende de T037 e
  de todas as tarefas de código.
- [X] T040 [P] Criar `README.md`:
  - finalidade;
  - compatibilidade (apenas Moodle 4.5 validado; PHP 8.1–8.3);
  - instalação pelo ZIP de release;
  - permissões e padrões por arquétipo;
  - onde o bloco aparece;
  - limitações aceitas (RK2: editor sem `:view`; RK9: blocos padrão do administrador e criação
    por código);
  - ausência de serviços externos e de dados pessoais;
  - idiomas (inglês no pacote; pt_BR via AMOS após a publicação);
  - ponteiro para `CONTRIBUTING.md`
  (Princípio XII; FR-004).
- [X] T041 [P] Revisar o código de `block_coursecare.php`, `templates/`, `styles.css` e `lang/`:
  - nenhum texto de interface fixo (SC-003);
  - nenhuma URL ou chamada externa (FR-004);
  - nenhuma consulta ao banco em `get_content()` (SC-009);
  - nenhuma concatenação de strings (Princípio VIII);
  - nenhum arquivo ou classe fora da estrutura do plan.md (Regras de complexidade).
  Registrar o resultado em `evidence/logs/code-review.txt`.
- [X] T042 [MANUAL] Instalar o ZIP gerado em T038 em um site limpo (quickstart §8).
  - **Procedimento** (US1-1, "Moodle 4.5 sem o plugin"):
    1. Remover o `local.yml` e recriar os containers do zero (`bin/moodle-docker-compose down` e
       `up -d`, com banco novo).
    2. Instalar **primeiro o site sem o plugin** (`install_database.php`) e confirmar que
       `block_coursecare` não aparece em Administração › Plugins › Blocos.
    3. Só então extrair o ZIP em `moodle/blocks/`, rodar `admin/cli/upgrade.php --non-interactive`
       (ou concluir pela página de Notificações) e registrar o tempo de instalação.
    4. Carregar os dados de T003, adicionar o bloco em `CC-DEMO` e rodar a suíte PHPUnit do plugin.
  - **Esperado**: plugin ausente antes do passo 3; instalação do plugin sem erros nem avisos; bloco
    funcional em inglês; `translation_test` ignorado por falta de `translations/`.
  - **Evidência**: `evidence/logs/zip-install.txt` e
    `evidence/screenshots/boost-en-1280-zip-install.png`.
  - **Aprovação**: CA-01 e CA-09 confirmados também a partir do pacote.
  - Depende de T038.
- [X] T043 [MANUAL] Simular a atualização e validar desabilitar/reabilitar com instâncias
  (quickstart §9 e V11; US1-2).
  - **Procedimento**:
    1. Com o bloco presente em `CC-DEMO` e `:view` concedido a `teacher`, incrementar
       temporariamente `$plugin->version`, rodar `admin/cli/upgrade.php --non-interactive` e
       desfazer com `git restore version.php`.
    2. Em seguida, desabilitar o bloco em Administração › Plugins › Blocos, abrir `CC-DEMO` como
       `editor` (edição ligada e desligada), reabilitar e abrir de novo.
  - **Esperado**: atualização sem erros, com instância, posição e override de permissão
    preservados. Com o bloco desabilitado, a instância não aparece e o CourseCare não é oferecido
    para adição. Ao reabilitar, a mesma instância volta, sem perda.
  - **Evidência**: `evidence/logs/upgrade.txt` e capturas
    `evidence/screenshots/boost-en-1280-disabled.png` e `-reenabled.png`.
  - **Aprovação**: os logs não têm erros, o estado foi preservado, e as duas capturas correspondem
    ao esperado (CA-01).
  - Depende de T020.
- [X] T044 [MANUAL] Validar a desinstalação **com** instâncias (quickstart §9; US1-3).
  - **Procedimento**: com o bloco em `CC-DEMO`, rodar
    `admin/cli/uninstall_plugins.php --plugins=block_coursecare --run` e consultar `block_instances`
    e `capabilities`.
  - **Esperado**: 0 registros com `blockname = 'coursecare'` e 0 capabilities `block/coursecare:%`.
  - **Evidência**: `evidence/logs/uninstall-with-instances.txt`.
  - **Aprovação**: CA-01 (ciclo completo) confirmado.
  - Depende de T043.
- [ ] T045 [MANUAL] Fazer a validação visual, responsiva e de acessibilidade completa (quickstart §6;
  FR-017, FR-018; SC-007, SC-008).
  - **Procedimento**: para cada combinação {Boost, Classic} × {en, pt_BR}, capturar o bloco em 1280,
    768, 375 e 320 px e com zoom de 200% no desktop. Percorrer só com o teclado (ordem e foco
    visível). Ler a interface com VoiceOver ou NVDA. Ativar o modo de alto contraste do sistema.
  - **Esperado**: sem rolagem horizontal, sobreposição ou perda de texto; foco visível; leitor
    anunciando cabeçalho, tagline e intro, sem anunciar o ativo; texto legível em alto contraste
    (o ativo pode sumir, porque é decorativo; registrar o resultado conforme RK6). Qualquer troca
    do `pix_icon` por outra técnica exige Complexity Tracking justificado no plan.md antes de ser
    aplicada (Princípio X).
  - **Evidência**: `evidence/screenshots/<tema>-<idioma>-<largura>-block.png` (16 capturas, mais zoom)
    e `evidence/accessibility.md` com o resultado, registrado como "verificação dos critérios WCAG
    2.2 AA aplicáveis ao bloco (axe + manual)", nunca como conformidade integral.
  - **Aprovação**: CA-10 e CA-12 marcados com os links.
  - Depende de T026 e T030.
  - **Estado (2026-10-16)**: executada com Playwright no sistema real, com axe no CI. Falta só a parte
    humana: leitor de tela real (VoiceOver/NVDA) e alto contraste real do sistema operacional
    (ver `evidence/accessibility.md`). Por isso a tarefa continua aberta.
- [ ] T046 Apresentar as evidências visuais de T030 e T045 ao proprietário do produto para revisão
  da clareza dos textos (SC-002) e aprovação do ativo visual e dos textos en/pt_BR (FR-014, FR-015).
  - Registrar em `evidence/README.md`, seção "Aprovações", **somente depois** da manifestação do
    proprietário: data, itens aprovados ou ajustes pedidos.
  - Se houver ajustes, aplicá-los nas tarefas de origem (T007, T024–T028) e reapresentar.
  - A revisão opcional por um ou dois docentes é registrada quando houver e não bloqueia a entrega.
  - Não registrar nenhuma aprovação antecipada. Depende de T045.
  - **Estado (2026-10-16)**: evidências prontas em `evidence/`. Aguarda a manifestação do proprietário do
    produto. Nenhuma aprovação registrada.
- [ ] T047 Fechar a feature:
  - preencher a tabela CA-01 a CA-13 em `evidence/README.md` só com itens que tenham link de
    evidência (SC-010);
  - reexecutar a Constitution Check do plan.md contra a constituição 1.2.0;
  - confirmar o CI verde (T039) e a ausência de pendências bloqueantes;
  - atualizar `CHANGES.md`;
  - deixar a branch pronta para PR à `main`.
  Depende de T039–T046.
  - **Estado (2026-10-16)**: depende de T045 (parte humana) e T046 (aprovação). O CI está verde no
    último commit validado.
---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sem dependências. T005 depende de T003.
- **Foundational (Phase 2)**: depende do Setup e bloqueia todas as histórias. T011 depende de
  T006–T010, e T012 depende de T011.
- **US1 (Phase 3)**: depende da Phase 2 e de T005.
- **US2 (Phase 4)**: depende da Phase 2. É independente de US1 para implementar, mas T020/T021 usam
  o ambiente de T005.
- **US3 (Phase 5)**: depende de US2 (T026 edita `block_coursecare.php` depois de T019). T022 depende
  de T016 (mesmo arquivo).
- **US4 (Phase 6)**: T027/T028/T029 dependem só da Phase 2. T030 depende de T026 (bloco completo).
- **US5 (Phase 7)**: T031–T036 dependem só do Setup (T001). T034 depende de T028, T037 de T012 e
  T038 de T026/T028.
- **Polish (Phase 8)**: depende de todas as histórias.

### Grafo resumido

```text
T001─┬─T002 T003─T005 T004
     └─(Phase 2) T006 T007 T008 T009 T010 → T011 → T012
                                          │
       US1: T013 → T014 → T015            │
       US2: T016 T017 T018 → T019 → T020, T021
       US3: T022 T023 T024 T025 → T026 (após T019)
       US4: T027 → T028 ; T029 ; T030 (após T026, T028)
       US5: T031 → T032 ; T033 T035 T036 → T034, T037, T038
       Final: T039 … T047 (T045 após T026+T030; T046 após T045; T047 por último)
```

### Within Each User Story

- Testes escritos antes da implementação, e devem falhar antes dela.
- Tarefas [MANUAL] só depois de a implementação da história passar nos testes automatizados.
- Uma história fecha no seu checkpoint antes da próxima prioridade, exceto pelo paralelismo
  indicado abaixo.

### Parallel Opportunities

- Setup: T002, T003 e T004 em paralelo.
- Foundational: T007, T008, T009 e T010 em paralelo (T006 pode ir junto).
- Depois da Phase 2:
  - US5 (T031–T036) pode correr inteiramente em paralelo com US1–US4, pois só toca `tools/`;
  - US4 (T027–T029) em paralelo com US2/US3, pois toca `translations/`, `tests/translation_test.php`
    e `CONTRIBUTING.md`;
  - T013 (US1) em paralelo com T016–T018 (US2).
- Polish: T040 e T041 em paralelo.

---

## Parallel Example: User Story 2

```bash
# Testes de US2 em paralelo (arquivos distintos):
Task: "PHPUnit de formatos, permissões, guarda e instance_can_be_edited em tests/block_coursecare_test.php"
Task: "Behat de permissões em tests/behat/block_coursecare_permissions.feature"
Task: "Behat de exibição por página e modo de edição em tests/behat/block_coursecare_display.feature"
```

## Parallel Example: User Story 5

```bash
Task: "Fixtures em tools/i18n/tests/fixtures/"
Task: "Verificador de traduções em tools/i18n/check.php"
Task: "Geração do ZIP em tools/package/build.sh"
Task: "Verificador do ZIP em tools/package/check.php"
```

---

## Rastreabilidade

### Requisitos funcionais → tarefas

| Requisito | Implementação | Verificação |
| --- | --- | --- |
| FR-001 | T006, T011 | T012 (validate), T014, T042, T043 |
| FR-002 | — (nativo) | T015, T044 |
| FR-003 | T006 | T012, T040 |
| FR-004 | T011, T026 | T041 |
| FR-005 | T009 | T013 |
| FR-006 | T019 | T016, T018, T020 |
| FR-007 | T008 | T016, T017, T020 |
| FR-008 | — (nativo) | T017, T020 |
| FR-009 | T008, T019 | T016, T017, T020 |
| FR-010 | T019 | T016, T017, T018, T020, T021 |
| FR-011 | T011 | T016, T041 |
| FR-012 | T010, T026 | T022, T023, T046 |
| FR-013 | T010, T026 | T022, T046 |
| FR-014 | T007, T028 | T030, T046 |
| FR-015 / FR-015a | T024, T025, T026 | T045, T046 |
| FR-016 | T010, T026 | T022, T023 |
| FR-017 | T025, T026 | T023, T045 |
| FR-018 | T025 | T023, T045 |
| FR-019 | T007, T010 | T022, T041 |
| FR-020 | T007 | T034 |
| FR-021 | T028 | T034, T038 |
| FR-022 | T029 | T030 |
| FR-023 | — (nativo) | T027, T030 |
| FR-024 | T028 | T046 |
| FR-025 | T033 | T031, T032, T034 |
| FR-026 | T035, T036 | T032, T038 |
| FR-027 | T033, T036, T037 | T039 |
| FR-028 | T031, T032 | T034, T039 |
| FR-029 | T011, T026 (sem indicadores nem dados) | T022, T041 |
| FR-030 | T004 | T047 |

### Critérios de aceitação → tarefas

| CA | Tarefas |
| --- | --- |
| CA-01 Instalação | T014, T015, T042, T043, T044 |
| CA-02 Adição | T016, T017, T018, T020, T021 |
| CA-03 Identidade e mensagem | T022, T023, T046 |
| CA-04 Bloqueio sem permissão | T016, T017, T020 |
| CA-05 Inglês | T023, T030 |
| CA-06 pt_BR | T027, T030 |
| CA-07 Fallback | T027, T030 |
| CA-08 Paridade | T032, T034, T037 |
| CA-09 Pacote | T038, T042 |
| CA-10 Responsivo e acessível | T023, T045 |
| CA-11 Sem modificar o core | T014 |
| CA-12 Independente do tema | T023, T045 |
| CA-13 Evidências | T004, T047 |

### Critérios de sucesso → tarefas

SC-001 → T014 · SC-002 → T046 · SC-003 → T022, T041 · SC-004 → T027, T030 · SC-005 → T032,
T034 · SC-006 → T038 · SC-007 → T023, T045 · SC-008 → T023, T045 · SC-009 → T041 · SC-010 → T047.

---

## Implementation Strategy

### MVP First

1. Phase 1 e Phase 2: o plugin instala e renderiza o texto em inglês.
2. US1: instalação e desinstalação nativas validadas (MVP técnico).
3. US2: permissões e exibição corretas. **Este é o primeiro incremento utilizável em uma sala.**

### Incremental Delivery

1. Setup + Foundational → fundação pronta.
2. US1 → validar → instalação comprovada.
3. US2 → validar → bloco utilizável e seguro.
4. US3 → identidade visual e acessibilidade.
5. US4 → pt_BR e fallback.
6. US5 → Quality Gate 4 automatizado (pode correr desde o início, em paralelo).
7. Phase 8 → ZIP instalado, atualização e desinstalação, validação visual, aprovação e fechamento.

### Parallel Team Strategy

Com mais de uma pessoa, depois da Phase 2:
- A: US1 → US2 → US3.
- B: US5 inteira, mais T027–T029 de US4.
- Juntos: T030 e Phase 8.

---

## Notes

- Commit por tarefa ou grupo lógico, sempre na branch `001-plugin-foundation`.
- Nenhum CA é marcado como atendido sem link de evidência. Nenhuma aprovação é registrada antes da
  manifestação do proprietário.
- Validações manuais **complementam** e não substituem a paridade automatizada (T034, T037).
- Fora do escopo (Feature 002+): coleta, análise, indicadores, tarefas agendadas, recomendações,
  tabelas, `classes/local/` e renderers.

---

## Execução (2026-10-16)

- **Concluídas**: 44 de 47 (T001–T044), cada uma com evidência em `evidence/`.
- **Abertas**: T045 (parte humana de acessibilidade), T046 (aprovação do proprietário) e T047
  (fechamento).
- **Desvios registrados**:
  - T020/V3: a configuração "qualquer página + exibir em subcontextos" foi aplicada no banco (campos
    nativos `pagetypepattern` e `showinsubcontexts` da instância), não pelo formulário. O Behat do CI
    cobre o mesmo cenário com o gerador.
  - T021: a mesclagem usou a API de backup/restauração com `TARGET_EXISTING_ADDING` (mesmo
    `restore_block_instance_structure_step` da interface), com controle de restauração em outra sala.
  - T030: a extração manual do pacote pt_br exige purgar os caches antes do import do
    `tool_customlang`. Documentado no quickstart e no CONTRIBUTING.
  - T027: `get_string_manager()->get_string(..., 'pt_br')` substitui `get_string(..., 'pt_br')`, que
    não aceita idioma.
- **Pendências LOW da análise**:
  - Resolvidas: E4 (Behat), E5 (`logs/playwright-e5.json`), E6 (fixture `multi-language`), B1 (7 s no
    E2), F1, F2, F3 e F4 (0 senhas fracas).
  - Abertas: B2 (sem medição numérica de consultas; T041 confirma ausência de acesso ao banco em
    `get_content()`) e E3 (proteção de branch, decisão do proprietário).
