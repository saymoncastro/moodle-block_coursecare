---

description: "Task list for Feature 008 — Educational Experience"
---

# Tasks: Educational Experience

**Input**: Design documents from `specs/008-educational-experience/` (spec `496f80b`, plan `4f4cbb1`)

**Tests**: obrigatórios (Princípio IX e seção "Validação" da spec): PHPUnit, Behat com e sem JavaScript,
axe, regressão das Features 002–006.

**Organization**: por história de usuário (US1, US2, US4 P1; US3 P2). US4 (detalhamento integrado) é
entregue junto com a reorganização da página da US2, pois as duas mexem nos mesmos templates.

## Regras que valem para todas as tarefas

- Nenhum dado, tabela, configuração, capability, critério, indicador ou consulta nova (FR-050).
- Nenhum item futuro (boas práticas, dicas, vídeos, tarefas, "Principal sugestão", priorização).
- Strings completas em en e pt_BR, sem números na mensagem do bloco (Q1), sem promessas (SC-005).
- Nenhuma aprovação visual declarada antes da avaliação das capturas pelo proprietário do produto.
- Nenhum merge automático.

---

## Phase 1: Setup

- [X] T001 Em `version.php`: `version = 2026102100`, `release = '0.8.0'`, `MATURITY_BETA`, `supported` [405, 503]; entrada 0.8.0 (unreleased) em `CHANGES.md`.
- [X] T002 [P] Criar `specs/008-educational-experience/evidence/README.md` com as seções: inventário de preservação, estados do bloco, abas, acessibilidade, desempenho, capturas, validações humanas.

---

## Phase 2: Foundational

- [X] T003 Inventário de preservação "antes" (FR-031, SC-002): na sala DEMO-COMPLETE (4.5), registrar cada seção, rótulo e número visível da página e do bloco da 0.7.0 em `specs/008-educational-experience/evidence/preservation-before.md` (texto da página via `get_page_text`/`checks.py`), e as medidas de `tools/dev/bench/render.php` (bloco e página).
- [X] T004 Strings novas em `lang/en/block_coursecare.php` e `translations/pt_br/block_coursecare.php` (contracts/ui.md, research R8): `state:allgood_title`, `state:allgood_message`, `state:idea_title`, `state:idea_message`, `state:idea_action`, `block:partial`, `tabs:label`, `tabs:suggestions`, `tabs:details`, `suggestions:band_note`, `overview:closing`; remover as que perderem uso no fim (T020); `php tools/i18n/check.php` sem erro.
- [X] T005 Estado educativo puro (data-model.md, invariantes: "`allgood` ⇒ `state = complete` ∧ `filecount > 0` ∧ ¬`partial` ∧ ¬`hassuggestion`"; "`idea` ⇒ `hasresult` ∧ `filecount > 0` ∧ `hassuggestion`"; "Nenhum estado positivo com nota de falha, desatualização ou parcial") em `classes/output/diagnostic_line.php` (método estático `educational(diagnostic_state $state, bool $hassuggestion): array` com `kind`, `title`, `message`, `notes`, `action`), com testes de tabela em `tests/output/diagnostic_line_test.php` cobrindo todas as combinações de estado × arquivos × parcial × sugestão.

---

## Phase 3: User Story 1 - Bloco educativo (P1) 🎯 MVP

**Goal**: o bloco mostra identidade, estado educativo, indicador, notas e acesso (contracts/ui.md, Bloco).

**Independent Test**: em cada sala de demonstração, o bloco mostra o estado de contracts/ui.md, em en e pt_BR.

- [X] T006 [US1] `block_coursecare.php` e `classes/output/diagnostic_line.php`: passar ao template o estado educativo (T005), as notas e a ação ("Ver sugestão" para `#block_coursecare-suggestions`, link atual sem sugestão, nenhum sem resultado); sem leitura nova do banco.
- [X] T007 [US1] `templates/content.mustache`: identidade → título e mensagem do estado (parágrafos, não títulos) → cartão do indicador (rótulo, volume, data) → notas → acesso como botão (`btn btn-outline-primary`); ilustração pequena só nos estados `idea` e `allgood` (`pix/sprout.svg`, decorativa); exemplo de contexto do template atualizado.
- [X] T008 [US1] `styles.css`: bloco compacto, painel verde para `allgood`/neutro e amarelo discreto para `idea`, texto `#1d2125` nos painéis, sem rolagem horizontal em 320 px, foco visível.
- [X] T009 [US1] Testes: atualizar `tests/block_coursecare_test.php` e os cenários Behat que conferem textos do bloco (`tests/behat/*.feature`) para os novos estados; novo cenário Behat por estado (`idea`, `allgood`, neutro desatualizado, neutro parcial, falha com resultado, sem arquivos, sem análise, em preparação) com axe no bloco.

**Checkpoint**: bloco completo e testado; página ainda como na 0.7.0.

---

## Phase 4: User Stories 2 e 4 - Página educativa e detalhamento integrado (P1)

**Goal**: cabeçalho com identidade, ilustração e indicador; abas "Sugestões para você" e "Em detalhes"
acessíveis, com fragmentos, histórico, sem JavaScript e na impressão; todo o conteúdo 002–006 preservado.

**Independent Test**: na sala completa, a página abre na aba certa, as abas funcionam com teclado e leitor
de tela, `#block_coursecare-history` abre "Em detalhes", sem JavaScript tudo aparece, e o inventário bate
com o T003.

- [X] T010 [P] [US2] `pix/illustration.svg`: ilustração original (plantas e uma tela simples, paleta do broto, sem pessoas, mascotes ou texto), comentário de origem e licença GPL v3+ no arquivo; registrar origem e direitos em `specs/008-educational-experience/evidence/illustration.md`.
- [X] T011 [US2] `classes/output/course_overview.php`: contexto do cabeçalho (identidade = `pluginname`, `tagline`, ilustração), cartão do indicador com título `overview:title` (h3), faixa da aba de sugestões (estado educativo de T005 + `suggestions:band_note` só com sugestão), `initialtab` (`suggestions` com sugestão, `details` sem), nomes das abas, mensagem de fechamento; sem resultado, sem abas.
- [X] T012 [US2] `templates/overview.mustache` e `templates/overview_summary.mustache`: estrutura de contracts/ui.md (h2 identidade, cartão h3, `nav` de abas com `hidden`, duas `section` com `h2` próprios, seções existentes movidas sem mudança de conteúdo para a área certa, fechamento); `{{#js}}` chama `block_coursecare/tabs`.
- [X] T013 [US2] `amd/src/tabs.js`: padrão de abas APG (roles, `aria-selected`, `aria-controls`, `aria-labelledby`, `tabindex` itinerante, setas/Home/End com ativação automática, painel inativo `hidden`, `h2` das áreas ocultos visualmente com `sr-only`), aba inicial (fragmento > `data-initialtab`), fragmentos de seção ativam a aba e focam a seção, `pushState` na troca e `popstate`/`hashchange` restauram.
- [X] T014 [US2] Compilar `amd/build/tabs.min.js` e `.map` com o `grunt amd` do Moodle (contêiner `node:22`, research R5); `moodle-plugin-ci`-equivalente local: eslint sem aviso.
- [X] T015 [US2] `styles.css`: cabeçalho (identidade, tagline, ilustração que reduz ou some em telas estreitas), cartão do indicador, lista de abas (alvos ≥ 24 px, foco visível, aba ativa indicada também por borda/peso, não só cor), faixa amarela discreta, fechamento; `@media print` mostra todos os painéis e esconde as abas; 320 px e zoom 200% sem rolagem horizontal.
- [X] T016 [US2] Testes: PHPUnit do contexto da página (`tests/output/course_overview_test.php`: aba inicial, faixa por estado, sem abas sem resultado); Behat `@javascript` (abas, setas, Home/End, `#block_coursecare-history`, voltar do navegador, axe) e sem JavaScript (as duas áreas e todas as seções visíveis) em `tests/behat/block_coursecare_tabs.feature`; cenários existentes da página (002–006) ajustados só para clicar na aba quando necessário.

**Checkpoint**: página reorganizada; regressão 002–006 verde.

---

## Phase 5: User Story 3 - Cartão de recomendação (P2)

**Goal**: o cartão da 004 responde às quatro perguntas com hierarquia clara.

**Independent Test**: na sala com materiais grandes, o cartão mostra o que observamos, por que, o que fazer
e as atividades com link, com os mesmos números e critérios da 004.

- [X] T017 [US3] `templates/overview_suggestions.mustache` e `classes/output/course_overview.php`: cartão com ícone decorativo, título, quatro blocos com títulos visíveis (o que observamos, por que pode merecer atenção, o que você pode fazer se quiser, onde estão as atividades), links das atividades; mesmos textos e números da 004; sem selo, ranking ou valores.
- [X] T018 [US3] `styles.css` do cartão e testes: Behat de `tests/behat/block_coursecare_suggestions.feature` ajustado e conferindo as quatro partes; axe.

---

## Phase 6: Polish

- [X] T019 Inventário de preservação "depois" (`evidence/preservation-after.md`) comparado ao T003: mesmas seções, rótulos e números; medidas de `render.php` com as mesmas leituras e tempo dentro de 10% (SC-004).
- [X] T020 Limpeza: strings sem uso removidas (`block:suggestion` e outras), paridade de i18n, `tools/package/check.php` aceita `amd/` (ajustar a lista de permissão se preciso) e o pacote passa.
- [X] T021 Suítes locais (PHPUnit, Behat em 4.5), push e CI verde nas quatro combinações (inclui `grunt` e `mustache`); registrar em `evidence/README.md`.
- [X] T022 Auditoria de acessibilidade e responsividade com `tools/dev/demo/checks.py` (axe, teclado, zoom 200%, 320 px, árvore de acessibilidade) em 4.5 Boost, 4.5 Classic e 5.3 Boost, gaveta aberta e tours concluídos; corrigir só defeitos reais.
- [X] T023 Capturas reais (en e pt_BR; 4.5 e 5.3; bloco em cada estado principal, página nas duas abas; desktop e celular) e comparação lado a lado com o mockup aprovado em `evidence/screenshots/` e `evidence/comparison.md`; **aprovado pelo proprietário do produto em 2026-10-19** (registro em `evidence/comparison.md`).
- [X] T024 Documentação: `docs/teacher-guide.md` e `docs/pt_br/guia-do-professor.md` (estados do bloco, abas), `README.md`, `docs/marketplace/listing.md`; capturas do Marketplace trocadas pelas da 0.8.0 depois da aprovação (T023, 2026-10-19).
- [X] T025 Constitution Check do plano contra a entrega e matriz de prontidão da 008 em `evidence/README.md`.

---

## Dependencies & Execution Order

- T001–T002 → T003–T005 (Foundational) → US1 (T006–T009) → US2/US4 (T010–T016) → US3 (T017–T018) → Polish.
- T010 em paralelo com T011–T013. T005 serve ao bloco e à faixa da página.
- T023 depois de T022; T024 depois de T023 para as capturas.

## Parallel opportunities

- T002 com T001; T010 (ilustração) com T011–T013; T004 e T005 em arquivos diferentes.

## Implementation Strategy

MVP = US1 (bloco educativo) com o estado educativo testado. Depois a página (US2/US4) com as abas, e por
fim o refinamento do cartão (US3). Cada fase deixa a suíte verde.

## Cobertura

| Requisito | Tarefas |
| --- | --- |
| FR-001–FR-006 | T004–T009 |
| FR-010–FR-015 | T010–T012, T015 |
| FR-020–FR-022 | T017–T018 |
| FR-030–FR-036 | T011–T016 |
| FR-040–FR-043 | T008, T015, T022 |
| FR-050–FR-053 | T003, T019–T025 |
| SC-001 | T005, T009 |
| SC-002 | T003, T019 |
| SC-003 | T022 |
| SC-004 | T003, T019 |
| SC-005 | T004 |
| SC-006 | T023 |

## Phase 7: Convergence

- [X] T026 Show the closing message on every page that has a result (also without files), not only with the tabs, in `classes/output/course_overview.php` and `templates/overview.mustache`, with its PHPUnit assertion, per FR-015 (partial)
- [X] T027 Record the text type (fato observado, possibilidade, orientação prática, recomendação fundamentada) of each of the 12 new strings in `specs/008-educational-experience/evidence/strings.md` per FR-051 (partial)
