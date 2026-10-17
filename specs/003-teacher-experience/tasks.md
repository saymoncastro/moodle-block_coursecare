---

description: "Tarefas de implementação da Feature 003 — Teacher Experience"
---

# Tasks: Teacher Experience

**Input**: Design documents from `specs/003-teacher-experience/`

**Prerequisites**: [plan.md](plan.md) (commit `b6653ad`, aprovado), [spec.md](spec.md) (commit
`928cd20`, validada), [research.md](research.md), [data-model.md](data-model.md),
[contracts/](contracts/), [quickstart.md](quickstart.md) · Constituição 1.2.0

**Tests**: obrigatórios. A constituição exige PHPUnit para toda regra (Princípio IX) e Behat para
todo fluxo de interface, com verificação de acessibilidade (Quality Gates 2 e 3). Em cada história,
os testes vêm antes da implementação e devem falhar antes dela. Dados de diagnóstico de teste vêm do
gerador do plugin (T004); testes puros do `overview_builder` usam arrays.

**Organization**: tarefas agrupadas pelas histórias US1–US6 da spec, precedidas de Setup e
Foundational e seguidas da fase de validação, que inclui a reconstrução visual, a aprovação do
proprietário do produto e a avaliação com cinco professores.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: pode rodar em paralelo (arquivos diferentes, sem dependência de tarefa incompleta).
- **[Story]**: história a que a tarefa pertence (US1–US6).
- **[MANUAL]**: validação em Moodle real, com procedimento, resultado esperado e evidência descritos
  na própria tarefa.

## Path Conventions

O repositório é a raiz do plugin, instalado em `<moodle>/blocks/coursecare`. As evidências ficam em
`specs/003-teacher-experience/evidence/` (abreviado `evidence/`), fora do pacote. Todo PHP leva o
cabeçalho GPL v3+ padrão do Moodle e PHPDoc. Testes PHPUnit em subdiretórios usam o namespace do
subdiretório (`block_coursecare\analysis`, `block_coursecare\collector`, `block_coursecare\output`) e
`@covers`.

## Regras que valem para todas as tarefas

- **Camadas**: `$DB` só em `classes/local/collector/`; `classes/local/analysis/` é pura (sem `$DB`,
  sessão, `$CFG`, `get_string()` ou saída); `classes/output/` monta frases e o contexto dos templates.
- **FR-030 (privacidade)**: por atividade, só `materialbytes` e as ligações de materiais grandes.
  Nunca usar, por atividade, `filecount`, `logicalbytes`, `submissionbytes`, `otherbytes`,
  `unclassifiedbytes`, `largecount`, `largebytes`, `largeuniquecount` ou `largeuniquebytes`, nem
  ordenar por eles.
- **Nunca**: consultar a tabela `files`, instanciar `stored_file`, exibir nomes de arquivos ou de
  estudantes, criar tabela, campo, configuração, tarefa, cache MUC, JavaScript ou capability.
- **Strings**: toda chave nova entra em `lang/en/block_coursecare.php` (ordem alfabética) e em
  `translations/pt_br/block_coursecare.php` **na mesma tarefa**, como frase completa (parâmetro único
  `{$a}` ou objeto `{$a->...}`), com o texto proposto em contracts/overview-page.md ou
  contracts/block-ui.md, sem termos de infraestrutura nem palavras de avaliação. Nenhum texto fixo
  em template.
- **Visual**: estilos só sob `.block_coursecare` e `.block_coursecare-overview`, com propriedades
  `--block-coursecare-*`; nunca alterar seletores ou variáveis globais do tema (FR-051).

---

## Phase 1: Setup

**Purpose**: versão, registro de evidências, ambiente de demonstração e gerador de dados de teste.

- [X] T001 Atualizar `version.php` para `$plugin->version = 2026101700` e `$plugin->release = '0.3.0'` (sem passo de upgrade: nenhuma mudança em `db/`) e abrir a entrada `0.3.0 (unreleased)` em `CHANGES.md`.
- [X] T002 [P] Criar `evidence/README.md` com a tabela "Item | Teste ou evidência | Data | Moodle | Banco | Status" para CT-01 a CT-24 e SC-001 a SC-011 da spec, todos "pendente", e as seções vazias "Revisão de tom e rótulos", "Reconstrução e validação visual", "Avaliação com professores", "Runs de CI" e "Constitution Check reavaliado".
- [ ] T003 [P] Criar `tools/dev/demo/setup.php` (CLI de desenvolvimento, fora do pacote, cabeçalho GPL e PHPDoc), conforme research R8 e quickstart §1:
  - recusa rodar sem `--confirm` e avisa que é só para site descartável;
  - cria o usuário professor `demo.teacher` (senha do argumento `--password`, nunca fixa no código), estudantes fictícios e as salas: completa (materiais, uma tarefa com envios, um fórum com anexos, área de texto e mídia, página, recurso, pasta), com mais de 10 atividades com materiais, sem arquivos (G), com área não classificada (H), e salas a deixar em A, B, E e F;
  - arquivos reais pela File API, com conteúdo pequeno e tamanho controlado; limite de grande porte opcional `--threshold-mb` para gerar materiais e envios grandes sem ocupar disco;
  - adiciona o bloco às salas, roda as análises com `course_analysis` e ajusta o registro de E (data antiga) e de F (falha simulada pelo `diagnostic_store::save_failure`) e o de B (pedido na fila sem resultado);
  - opção `--activities=N` que cria, além das salas acima, uma sala com N atividades com materiais (páginas e recursos com arquivo), para a medição do SC-003 (`--activities=200`);
  - imprime os ids das salas e o estado esperado de cada uma.
- [X] T004 [P] Estender `tests/generator/lib.php` (`create_diagnostic`) e `tests/generator/behat_block_coursecare_generator.php` para aceitar, além dos campos atuais: `categories` (categoria → `filecount`, `logicalbytes`, `uniquecount`, `uniquebytes`), `activities` (cmid ou, no Behat, o `idnumber` da atividade → campos de `block_coursecare_act`), `largelinks` (lista de `cmid`/`idnumber` e `filesize`, com `fileid` sintético) e os campos de resumo de referências, cópias e cobertura; manter o comportamento atual quando ausentes; PHPDoc de cada coluna.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: coletores, construtor puro da visão, renderable e página com verificação de acesso.

**⚠️ CRITICAL**: nenhuma história começa antes desta fase.

### Tests for Foundational

- [X] T005 [P] Criar `tests/collector/course_structure_test.php`: para uma sala com página, recurso, área de texto e mídia e uma atividade oculta, `get_activities()` devolve `cmid → {name, url|null, uservisible}` com o nome formatado, URL nula na área de texto e mídia, `uservisible` falso para a oculta quando o usuário é estudante e verdadeiro para professor com edição; nenhuma consulta a `files` (`$DB->set_debug`).
- [X] T006 [P] Em `tests/collector/diagnostic_store_test.php`, testar `get_large_materials_by_activity($diagid)`: ligações em três atividades e no `cmid 0` voltam agregadas por `cmid` com `count` e `bytes` corretos; diagnóstico sem ligações devolve array vazio; ligações de outro diagnóstico não entram.
- [X] T007 [P] Criar `tests/analysis/overview_builder_test.php` com os testes da base: `share()` (`whole = 0` → 0; `part = 0` → 0; `0 < x < 1%` → "menos de 1%" como marcador, inclusive 0,5% e 0,99%; 1% → 1; 1,4% → 1; 1,5% → 2; arredondamento ao inteiro mais próximo a partir de 1%; 100% para a parte igual ao todo); sem resultado (estados A, B, C, F sem resultado) a visão não tem `total`, `categories`, `activities`, `large` nem `technical`; com resultado D, `hasresult`, `timecompleted` e `total` vêm do registro.

### Implementation for Foundational

- [X] T008 Criar `classes/local/collector/course_structure.php` com `get_activities(int $courseid): array` a partir de `get_fast_modinfo($courseid)` (usuário atual): para cada `cm_info`, `name` por `get_formatted_name()`, `url` por `$cm->url` (ou `null`), `uservisible`; PHPDoc dizendo que não lê `files` (contracts/overview-data.md).
- [X] T009 Em `classes/local/collector/diagnostic_store.php`, criar `get_large_materials_by_activity(int $diagid): array` com uma consulta `SELECT cmid, COUNT(1), SUM(filesize) ... WHERE diagid = :diagid GROUP BY cmid`, devolvendo `cmid → {count, bytes}` em inteiros.
- [X] T010 Criar `classes/local/analysis/overview_builder.php` (puro), com `build(diagnostic_state $state, ?\stdClass $record, array $categories, array $activities, array $largebyactivity, array $structure): array` devolvendo a visão do data-model; nesta tarefa, só a base: `share(int $part, int $whole)` exatamente como a tabela "Função share" do data-model, o mapeamento de `hasresult`, `outdated`, `failed`, `partial`, `timecompleted` e `total`, e a omissão de todas as seções sem resultado.
- [X] T011 Criar `classes/output/course_overview.php` (renderable, templatable) e `templates/overview.mustache`, que inclui o partial `templates/overview_summary.mustache` (resumo: título `h2` `overview:title`, `overview:intro`, data e link `overview:back` para a sala); exemplos de contexto nos templates; strings `overview:title`, `overview:intro`, `overview:back` (en e pt_br).
- [X] T012 Criar a verificação de acesso e a página em `classes/local/overview_access.php` e `overview.php`, conforme contracts/overview-page.md, "Endereço e acesso":
  - em `classes/local/collector/eligibility.php`, `get_course_instance(int $instanceid): ?\stdClass` (instância com `blockname = 'coursecare'` ou `null`);
  - `classes/local/overview_access.php` com `require_access(int $instanceid): array` que, na ordem do contrato, exige a instância, um contexto pai de curso diferente de `SITEID`, `require_login($course, false)` e `require_capability('block/coursecare:view', context_block::instance($instanceid))`, devolvendo o curso e o contexto do bloco; em qualquer falha, a exceção padrão do Moodle;
  - `overview.php`: `required_param('id', PARAM_INT)`, `overview_access::require_access()` **antes** de qualquer leitura do diagnóstico; depois, `diagnostic_store` (`get_summary`, `get_categories`, `get_activities`, `get_large_materials_by_activity`) e `course_structure`, estado pelo `state_resolver` com `stalenessthreshold`, visão pelo `overview_builder` e renderização; `set_url`, `set_pagelayout('incourse')`, `set_title`, `set_heading`, trilha `overview:title`.

**Checkpoint**: um professor com edição abre `overview.php?id=<instância>` e vê o título e o resumo.

---

## Phase 3: User Story 1 — Entender a sala de relance no bloco (Priority: P1) 🎯 MVP

**Goal**: o bloco mostra a identidade, a linha do diagnóstico e, só com resultado, o link para a
página; sem a mensagem "orientações em breve".

**Independent Test**: com o diagnóstico em D o bloco mostra volume, data e link; em A, B, C e F sem
resultado, só a mensagem, sem link (CT-01 a CT-03).

### Tests for User Story 1

- [X] T013 [P] [US1] Em `tests/block_coursecare_test.php`: o link `overview:link` com a URL `overview.php?id=<instância>` aparece nos estados D, E, F com resultado, G e H e não aparece em A, B, C e F sem resultado; a mensagem `intro` não aparece; a frase de identidade continua; `test_render_reads_no_files` continua verde com o mesmo número de leituras em todos os estados (CT-01 a CT-03, CT-20); atualizar `test_initial_content` e `tests/translation_test.php` para a remoção de `intro`.
- [X] T014 [P] [US1] Criar `tests/behat/block_coursecare_overview.feature` (`@block @block_coursecare`) com: bloco em D mostra "Learn more about the files in your course" e o link abre "Your course in numbers" da mesma sala, e o link de volta leva à sala (CT-06); bloco em A não mostra o link; atualizar `tests/behat/block_coursecare_ui.feature` para a remoção de `intro`.

### Implementation for User Story 1

- [X] T015 [US1] Em `classes/output/diagnostic_line.php`, aceitar a URL da página (`?\moodle_url`) e exportá-la como `overviewurl` só quando o estado tem resultado; em `block_coursecare.php` (`diagnostic_line()`), passar `new moodle_url('/blocks/coursecare/overview.php', ['id' => $this->instance->id])`, sem consulta adicional.
- [X] T016 [US1] Em `templates/content.mustache`, remover o parágrafo `intro` e acrescentar, depois da linha do diagnóstico, o link `overview:link` quando `overviewurl` existir; atualizar o comentário e o exemplo de contexto; remover a chave `intro` de `lang/en` e `translations/pt_br` e criar `overview:link` (contracts/block-ui.md).

**Checkpoint**: US1 verde nos testes; o bloco leva à página.

---

## Phase 4: User Story 2 — Conhecer onde estão os arquivos da sala (Priority: P1)

**Goal**: a página mostra as categorias da sala e os materiais por atividade, sem nada que revele
envios por atividade.

**Independent Test**: sala com materiais, tarefa com envios e fórum com anexos: total e categorias
iguais ao diagnóstico; atividades com o volume dos materiais, 10 primeiras e "Mostrar todas"
(CT-04, CT-07, CT-09, CT-14).

### Tests for User Story 2

- [X] T017 [P] [US2] Em `tests/analysis/overview_builder_test.php`, testar as categorias: ordem `material`, `submission`, `other`, `unclassified`; `bytes` do `logicalbytes` de cada categoria e `0` sem linha; `share` sobre o total; `Σ bytes = total`; categorias de cópias e lixeira nunca nesta lista.
- [X] T018 [P] [US2] Em `tests/analysis/overview_builder_test.php`, testar os materiais por atividade (data-model, `activity_list`): só `materialbytes > 0`; ordem por `materialbytes` decrescente e desempate pelo nome; `first` com até 10 e `rest` com o restante; `cmid 0` em `courselevel`; `uservisible = false` somado em `others` sem nome; `cmid` ausente da estrutura somado em `removed` sem nome; `share` sobre o total de materiais; `Σ bytes` de itens e grupos igual aos bytes da categoria `material`; atividade sem URL sem link.
- [X] T019 [P] [US2] Criar `tests/output/course_overview_test.php` com a renderização no estado D de uma sala do gerador (T004): as quatro categorias com nome, frase explicativa, tamanho e proporção em texto; as atividades com nome e link; `<details>` com `overview:showall` quando há mais de 10; nenhuma consulta a `files` durante a renderização (`$DB->set_debug`).
- [X] T020 [P] [US2] No Behat `tests/behat/block_coursecare_overview.feature`: página em D com categorias e atividades (gerador com `activities` por `idnumber`), 12 atividades mostram 10 e "Show all activities (12)" revela as outras.

### Implementation for User Story 2

- [X] T021 [US2] Em `classes/local/analysis/overview_builder.php`, implementar `categories` conforme o data-model (`category`).
- [X] T022 [US2] Em `classes/local/analysis/overview_builder.php`, implementar `activities` conforme o data-model (`activity_list`, `activity_item`) e a regra FR-030 de contracts/overview-data.md (regras 1 a 5): nenhum campo por atividade além de `materialbytes` e das ligações.
- [X] T023 [US2] Criar `templates/overview_categories.mustache` (seção `h3` `overview:categories`, lista com nome, ajuda, tamanho, proporção em texto e barra decorativa com `aria-hidden="true"`) e `templates/overview_activities.mustache` (seção `h3` `overview:activities`, `overview:activities_help`, lista das 10 primeiras, nível da sala, outras e removidas, e `<details><summary>` `overview:showall` com o restante); em `classes/output/course_overview.php`, exportar o contexto com `display_size()` e `overview:share`/`overview:shareless`; strings `overview:categories`, `overview:category:*` e `*_help`, `overview:share`, `overview:shareless`, `overview:activities`, `overview:activities_help`, `overview:courselevel`, `overview:otheractivities`, `overview:removed`, `overview:showall` (en e pt_br).

**Checkpoint**: US1 e US2 verdes; MVP utilizável (bloco → página com distribuição).

*Execução (MVP, 2026-10-17)*:
- `course_overview::for_course()` reúne as leituras da página (store, estrutura, estado, builder), usada por
  `overview.php` e pelos testes; a apresentação lê pela `diagnostic_store`, como o bloco.
- Um partial a mais, `templates/overview_item.mustache`, para a linha medida (atividade, grupo ou
  categoria). As linhas definem todas as chaves (`help` vazio), para o Mustache não herdar o texto da
  seção.
- **[código 4.5]** A reconstrução do cache de módulos do core lê os arquivos dos recursos (ícone de
  `mod_resource`); o plugin não lê `files` e, com o cache construído pela página da sala, a página do
  plugin também não (research R2, contrato de dados).
- `overview.php` declara `phpcs:ignore moodle.Files.RequireLogin.Missing`: o login e a capability são
  exigidos por `overview_access::require_access()`, na ordem do contrato.
- `styles.css` recebe estilos provisórios da página (barras, cartões), escopados; os valores finais são
  das tarefas T035 e T046.
- T003 (`tools/dev/demo/setup.php`) está escrito e passa no lint; fica sem marcar até rodar de ponta a
  ponta no site limpo de validação (T046).
- `overview:share` (`{$a}%`) é igual em en e pt_br de propósito; o `tools/i18n/check.php` avisa, sem
  erro.

---

## Phase 5: User Story 3 — Confiar nos números e entender seus limites (Priority: P1)

**Goal**: estados claros na página, cobertura explicada e detalhes técnicos recolhidos.

**Independent Test**: estados A a H na página conforme FR-020; detalhes técnicos recolhidos com
Conteúdo único, referências, cópias e cobertura, fora do total (CT-05, CT-11).

### Tests for User Story 3

- [X] T024 [P] [US3] Em `tests/analysis/overview_builder_test.php`, testar a tabela de estados de contracts/overview-page.md: A, B, C e F sem resultado sem seções; D com todas; E e F com resultado com `outdated`/`failed` e todas as seções; G com resumo e `technical`, sem `categories`, `activities` e `large`; H com `partial` e a proporção dos não classificados; resultado com nova análise pendente mostra o resultado vigente.
- [X] T025 [P] [US3] Em `tests/analysis/overview_builder_test.php`, testar `technical`: Conteúdo único; referências internas e externas com quantidade e tamanho e indeterminadas só com quantidade; cópias de segurança e lixeira com quantidade e volume e o conteúdo único do conjunto; limite usado; nenhum desses valores somado a `total`.
- [X] T026 [P] [US3] Em `tests/output/course_overview_test.php`, renderizar cada estado e verificar as frases de contracts/overview-page.md (`diagnostic:*` reaproveitadas, `overview:partial`), a data, `overview:notmeaning` nos detalhes técnicos e o `<details>` técnico sem o atributo `open`; nenhum "0" fora de G e de categoria vazia de resultado concluído.
- [X] T027 [P] [US3] No Behat `tests/behat/block_coursecare_overview.feature`: página em E mostra o aviso de desatualização, em F com resultado o aviso de falha, em G "No files were found…" sem a seção de atividades, sem resultado só a mensagem; "Technical details" recolhido abre ao clicar.

### Implementation for User Story 3

- [X] T028 [US3] Em `classes/local/analysis/overview_builder.php`, implementar a tabela de estados e `technical` conforme o data-model.
- [X] T029 [US3] Completar `templates/overview_summary.mustache` (total `overview:total`, data, aviso do estado, `overview:partial`) e criar `templates/overview_technical.mustache` (`<details><summary>` `overview:technical`, com Conteúdo único, referências, cópias, cobertura, limite e `overview:notmeaning`); exportar o contexto em `classes/output/course_overview.php`; strings `overview:total`, `overview:partial`, `overview:technical`, `overview:unique`, `overview:unique_help`, `overview:references*`, `overview:copies*`, `overview:threshold`, `overview:notmeaning` (en e pt_br).

**Checkpoint**: US1 a US3 verdes; todos os estados da página.

*Execução (US3, 2026-10-17)*: `overview:partial` passa a receber tamanho e proporção
(`{$a->size}`, `{$a->share}`); duas strings novas nos detalhes técnicos, `overview:coverage` e
`overview:copies_unique` (contrato da página atualizado). `tests/behat/behat_block_coursecare.php` resolve a
página nomeada "block_coursecare > overview" (pela sala), para o cenário sem resultado, em que o bloco não
mostra o link. O partial `overview_item` deixou de começar com `<li>` (o lint de Mustache do CI renderiza o
partial sozinho); os templates pais põem o `<li>`.

---

## Phase 6: User Story 4 — Proteger estudantes e restringir o acesso (Priority: P1)

**Goal**: só quem vê o bloco vê a página; nenhuma informação individual de estudante; envios só no
agregado da sala.

**Independent Test**: estudante, professor sem edição, professor de outra sala e visitante negados;
nenhum valor por atividade inclui envios (CT-12 a CT-15).

### Tests for User Story 4

- [ ] T030 [P] [US4] Criar `tests/overview_access_test.php` (`@covers \block_coursecare\local\overview_access`), chamando `overview_access::require_access()` com cada usuário definido por `setUser()`: permitido para professor com edição e gerente; negado (exceção, nenhuma saída de dado) para estudante, professor sem edição, professor de outra sala, visitante e usuário não autenticado; negado para `id` inexistente, instância de outro bloco, instância no curso do site ou fora de um curso; negado quando a capability é proibida por sobreposição na própria instância.
- [ ] T031 [P] [US4] Em `tests/output/course_overview_test.php`, teste de privacidade (FR-030, SC-006): sala do gerador com uma tarefa de `materialbytes` pequeno e `submissionbytes`, `otherbytes` e `largebytes` grandes, e uma atividade só com envios; no HTML, nenhum número igual a `logicalbytes`, `submissionbytes`, `otherbytes`, `filecount` ou `largebytes` por atividade, a atividade só com envios ausente e a ordem igual à de `materialbytes`; nenhum nome de estudante (usuários do gerador) nem nome de arquivo, e nenhum link para arquivo (FR-034).
- [ ] T032 [P] [US4] No Behat `tests/behat/block_coursecare_overview.feature`: estudante e professor de outra sala que abrem o endereço da página recebem a negação e não veem números (CT-12, CT-13).

### Implementation for User Story 4

- [ ] T033 [US4] Em `classes/local/overview_access.php`, tratar os casos de borda de T030: instância cujo contexto pai não existe mais (sala apagada), contexto pai de atividade, de categoria ou de usuário, e `id` de outro bloco; todos terminam com a mesma exceção padrão de registro inexistente (`invalidrecord`), sem revelar se a instância existe, e acrescentar esses casos a `tests/overview_access_test.php`.

**Checkpoint**: US1 a US4 verdes; acesso e privacidade comprovados.

---

## Phase 7: User Story 5 — Usar em qualquer tela e com tecnologia assistiva (Priority: P2)

**Goal**: diretrizes visuais aplicadas, WCAG 2.2 AA, 320 px, Boost e Classic, en e pt_BR.

**Independent Test**: jornada só com teclado; sem violações do axe em desktop e celular; uma coluna a
320 px (CT-16 a CT-19).

### Tests for User Story 5

- [ ] T034 [P] [US5] No Behat `tests/behat/block_coursecare_overview.feature`: "the page should meet accessibility standards" no bloco e na página nos estados D, G e sem resultado, em tamanho de desktop e com `I change viewport size to "mobile"`; `<details>` operáveis pelo teclado (`I press the tab key` até o `summary` e `I press the enter key`).

### Implementation for User Story 5

- [ ] T035 [US5] Em `styles.css`, implementar as diretrizes visuais aprovadas (spec) só sob `.block_coursecare` e `.block_coursecare-overview`: propriedades `--block-coursecare-*` para verdes suaves e neutros, cantos arredondados, cartões discretos (classes `card` do core) para as seções, barras de proporção, broto no cabeçalho da página; contraste mínimo de 4,5:1 para texto e 3:1 para barras; layout de uma coluna até 320 px sem rolagem horizontal e quebra de nomes longos; valores provisórios, finalizados em T046.
- [ ] T036 [US5] Revisar todos os templates (`overview*.mustache`, `content.mustache`): títulos `h2`/`h3` em ordem, listas semânticas, barras com `aria-hidden="true"`, `<details>`/`<summary>` com texto descritivo, nenhum `tabindex` positivo, links com texto completo e alvo de pelo menos 24 × 24 px.
- [ ] T037 [US5] Rodar `php tools/i18n/check.php` e corrigir toda falta de paridade de chaves e parâmetros; revisar a terminologia pt_br ("sala", "AVA Consciente", "panorama de arquivos") de todas as chaves `overview:*`.

**Checkpoint**: US1 a US5 verdes.

---

## Phase 8: User Story 6 — Ver os arquivos de grande porte da sala (Priority: P3)

**Goal**: grandes da sala e materiais grandes por atividade, como informação de triagem.

**Independent Test**: material de 60 MB e envio de 60 MB (limite 50 MB): dois grandes na sala, o
material na atividade, o envio só no total; aviso com mais de 1.000 materiais grandes (CT-10, CT-22).

### Tests for User Story 6

- [ ] T038 [P] [US6] Em `tests/analysis/overview_builder_test.php`, testar `large`: `count` e `bytes` da sala do resumo; `threshold`; `materials` só das ligações, por atividade, ordenados por `largebytes`, com os grupos `others` e `removed` como nas atividades; `truncated` quando `largelinkstruncated = 1`; nenhum `largecount`/`largebytes` da linha de atividade usado.
- [ ] T039 [P] [US6] Em `tests/output/course_overview_test.php`, renderizar a seção de grandes: `overview:large_summary` com limite, quantidade e tamanho; `overview:large_help`; a lista por atividade com link; `overview:large_truncated` só quando truncado; o envio grande não aparece por atividade.
- [ ] T040 [P] [US6] No Behat `tests/behat/block_coursecare_overview.feature`: página com ligações de materiais grandes mostra "Large files" e a atividade do material.

### Implementation for User Story 6

- [ ] T041 [US6] Em `classes/local/analysis/overview_builder.php`, implementar `large` conforme o data-model (`large_summary`).
- [ ] T042 [US6] Criar `templates/overview_large.mustache` (seção `h3` `overview:large`) e exportar o contexto em `classes/output/course_overview.php`; strings `overview:large`, `overview:large_summary`, `overview:large_help`, `overview:large_materials`, `overview:large_truncated` (en e pt_br).

**Checkpoint**: todas as histórias verdes.

---

## Phase 9: Polish, validação visual, avaliação com professores e evidências

**Purpose**: desempenho, documentação, reconstrução e aprovação visual, avaliação com cinco
professores, quality gates e fechamento.

- [ ] T043 [P] Em `tools/dev/bench/render.php`, acrescentar `--page`, que mede a montagem e renderização completas da página (coleta, `overview_builder`, `course_overview`) para um professor, com média, desvio e leituras por chamada.
- [ ] T044 [MANUAL] Medir no ambiente de referência, na sala criada por `tools/dev/demo/setup.php --activities=200` (T003), com 200 atividades com materiais: página com `render.php --page` (SC-003, ≤ 2 s) e bloco 0.2.0 × 0.3.0 com `render.php` na mesma sala (SC-004, ≤ +50 ms); registrar em `evidence/README.md` com as saídas JSON em `evidence/logs/`.
- [ ] T045 [P] Atualizar `README.md`: o bloco e a página "Your course in numbers", quem pode vê-los, o que cada seção mostra, que por atividade só aparecem materiais e por quê, os detalhes técnicos, o que os números não significam e as limitações (spec, "Limitações reconhecidas"); completar `CHANGES.md` 0.3.0.
- [ ] T046 [MANUAL] Reconstrução visual (FR-073): no site de demonstração (T003), propor a interface a partir das diretrizes aprovadas, ajustar os valores de `styles.css` (tons, espaçamentos, cartões, barras) e apresentar capturas reais ao proprietário do produto (bloco e página em D, desktop e celular, en e pt_BR); iterar até a aprovação da direção, registrada em `evidence/README.md` com data, só quando ocorrer.
- [ ] T047 [MANUAL] Revisão de tom e rótulos (SC-008; Princípios I, II e IV) de todas as strings novas e alteradas em en e pt_br, contra contracts/block-ui.md e contracts/overview-page.md; registrar em `evidence/README.md` e obter a aprovação do proprietário do produto.
- [ ] T048 [MANUAL] Validação visual (FR-070, SC-009): capturas reais em `evidence/screenshots/` do bloco e da página nos estados D, E, F com resultado, G e sem resultado, em desktop (1280 px) e celular (375 px), en e pt_BR, Boost e Classic; aprovação registrada do proprietário do produto.
- [ ] T049 [P] Criar `specs/003-teacher-experience/evaluation/protocol.md`: perfil anônimo dos cinco professores (familiaridade tecnológica, área), tarefas observáveis para as seis dimensões de FR-071, inclusive a compreensão do resumo sem abrir os detalhes técnicos (FR-019) (entender o bloco; interpretar o total e as categorias; chegar ao detalhamento e voltar; entender os arquivos de grande porte; entender um resultado desatualizado e uma falha; facilidade geral), perguntas abertas, critério de problema crítico e menor (FR-071a) e o formulário de registro.
- [ ] T050 [MANUAL] Avaliação com cinco professores (FR-071, CT-23, SC-011), conduzida pelo proprietário do produto no site de demonstração com a interface aprovada (T048): registrar em `evidence/evaluation/` o resultado de cada tarefa, as dificuldades e a classificação de cada problema, sem dados pessoais além do perfil.
- [ ] T051 Corrigir todos os problemas críticos da avaliação (FR-071a) nos arquivos afetados, com testes quando houver regra, repetir a validação visual afetada (T048) e confirmar com os professores ou o proprietário do produto; registrar os problemas menores como melhorias futuras em `CHANGES.md` e `evidence/README.md`.
- [ ] T052 [MANUAL] Executar o quickstart.md §1–§9 num ambiente limpo (inclusive teclado, leitor de tela, 320 px, Boost e Classic, pt_BR com e sem a tradução) e registrar o resultado em `evidence/logs/quickstart-003.md`.
- [ ] T053 Gerar e verificar o pacote: `tools/package/build.sh` e `php tools/package/check.php build/block_coursecare-0.3.0.zip`; corrigir a lista de arquivos permitidos em `tools/package/check.php` para `overview.php` e os novos templates, se necessário.
- [ ] T054 Verificar o CI da branch (`moodle-plugin-ci` completo, PHPUnit e Behat em 8.1/pgsql e 8.3/mariadb, `i18n-package`) e registrar os runs em `evidence/README.md`.
- [ ] T055 Reavaliar o Constitution Check do plan.md contra o código entregue (camadas, nenhuma consulta a `files`, FR-030, nenhum dado novo, diretrizes visuais) e completar a tabela de `evidence/README.md` (nenhum item marcado sem evidência). Depende de T044 a T054.

---

## Dependencies & Execution Order

- **Setup (Phase 1)**: T002, T003 e T004 em paralelo, depois de T001.
- **Foundational (Phase 2)**: depende de T004 para os testes; T005–T007 em paralelo; T008 e T009 em
  paralelo; T010 depende de T007; T011 depende de T010; T012 depende de T008, T009 e T011.
- **US1 (Phase 3)**: depende da Phase 2 (o link aponta para a página).
- **US2, US3, US6**: dependem da Phase 2; mexem no mesmo `overview_builder.php`, em
  `course_overview.php` e no mesmo arquivo de testes, então seguem em sequência (US2 → US3 → US6), com
  os templates próprios de cada uma em paralelo.
- **US4 (Phase 6)**: depende da Phase 2; T031 depende de US2 (atividades renderizadas).
- **US5 (Phase 7)**: depende de US1–US3 e US6 (revisa todos os templates).
- **Polish (Phase 9)**:
  - T043, T045 e T049 em paralelo depois das histórias.
  - Ordem principal: T046 → T047 → T048 → T050 → T051 → T052.
  - T053 e T054 vêm depois de T051.
  - T055 é a última.

```text
Setup → Foundational → US1 ┐
                     → US2 → US3 → US6 ┐
                     → US4 (T031 após US2)┤
                                         └→ US5 → T043/T045/T049 → T046 → T047 → T048 → T050 → T051 → T052 → T053 → T054 → T055
```

## Parallel Example

```text
Setup:        T002 [P]  T003 [P]  T004 [P]
Foundational: T005 [P]  T006 [P]  T007 [P]   então   T008 [P]  T009 [P]
US2 (testes): T017 [P]  T018 [P]  T019 [P]  T020 [P]
US4 (testes): T030 [P]  T031 [P]  T032 [P]
Polish:       T043 [P]  T045 [P]  T049 [P]
```

## Implementation Strategy

1. **MVP**: Setup, Foundational, US1 e US2: o bloco leva à página com a distribuição por categoria e
   os materiais por atividade, com o acesso verificado (T012). Validar com o quickstart §2 e §3.
2. **Incrementos**: US3 (estados e detalhes técnicos), US4 (testes de acesso e privacidade), US6
   (grandes) e US5 (acessibilidade e visual), cada um com seus testes verdes.
3. **Encerramento**:
   - A feature não está concluída antes de três aprovações e do quickstart:
     - a aprovação da direção visual (T046);
     - a aprovação do tom (T047);
     - a aprovação da validação visual (T048).
   - Também é preciso concluir a avaliação com cinco professores, sem problema crítico em aberto
     (T050, T051), e o quickstart (T052).
   - Depois vêm o pacote, o CI e o Constitution Check (T053 a T055).

## Cobertura dos cenários (CT) e critérios (SC)

| Item | Tarefas |
| --- | --- |
| CT-01 a CT-03 | T013, T014, T015, T016 |
| CT-04, CT-07, CT-09 | T017–T023 |
| CT-05, CT-11, CT-21 | T007, T024–T029 |
| CT-06 | T014 |
| CT-08 | T018, T022 |
| CT-10, CT-22 | T038–T042 |
| CT-12, CT-13 | T030, T032, T033 |
| CT-14, CT-15 | T018, T031 |
| CT-16, CT-17 | T034, T036, T052 |
| CT-18 | T037, T052 |
| CT-19 | T048, T052 |
| CT-20 | T005, T013, T019 |
| CT-23 | T049, T050, T051 |
| CT-24 | T046, T048 |
| SC-001, SC-002 | T024, T019, T026, T031 |
| SC-003, SC-004 | T043, T044 |
| SC-005, SC-006 | T030–T033 |
| SC-007 | T034, T036, T052 |
| SC-008 | T047 |
| SC-009 | T048 |
| SC-010 | T053, T054 |
| SC-011 | T050, T051 |
