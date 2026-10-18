---

description: "Task list for Feature 007 — International Marketplace Release"
---

# Tasks: International Marketplace Release

**Input**: Design documents from `specs/007-marketplace-release/` (spec `6789f4a`, plan `b746f53`)

**Tests**: obrigatórios onde houver mudança de código (Princípio IX); as verificações desta feature são
majoritariamente de compatibilidade, instalação, auditoria e documentação, com evidências em
`specs/007-marketplace-release/evidence/`.

**Organization**: por história de usuário (US1, US2, US3, US5 P1; US4, US6 P2).

## Regras que valem para todas as tarefas

- Nenhuma funcionalidade nova, redesign, dependência, tag, release, submissão ou merge.
- Nenhuma compatibilidade declarada sem todas as verificações da versão verdes.
- Nenhum cabeçalho de copyright alterado (Q3); nenhuma conclusão de titularidade.
- Nenhuma validação humana marcada sem evidência humana.
- Documentação pública em inglês; nenhuma afirmação não demonstrada (redução automática, economia, carbono,
  otimização, avaliação docente); nenhuma referência a "Moodle" que sugira produto oficial.
- Nunca imprimir nem versionar credenciais.

---

## Phase 1: Setup

- [ ] T001 Em `version.php`: `$plugin->version = 2026102000`, `$plugin->release = '0.7.0'`, `$plugin->maturity = MATURITY_BETA`; `supported` continua [405, 405] até T009.
- [ ] T002 [P] Em `.gitattributes`, `/docs export-ignore`; criar `specs/007-marketplace-release/evidence/README.md` com as seções das matrizes (prontidão, conformidade, compatibilidade), auditorias, validações humanas, pacote, CI e recomendação.

---

## Phase 2: Foundational (verificação em 5.3)

**Purpose**: CI e pilha local de 5.3; bloqueiam a declaração de compatibilidade.

- [ ] T003 Em `.github/workflows/ci.yml`, acrescentar à matriz `MOODLE_503_STABLE` × PHP 8.3 × PostgreSQL 17 e × PHP 8.4 × MariaDB 11.4 (serviço com a imagem correspondente), mantendo as duas combinações de 4.5.
- [ ] T004 Em `tests/behat/block_coursecare_ui.feature`, no cenário do Classic, `Given the "theme_classic" plugin is installed` como primeiro passo (roda no 4.5, pulado no 5.3).
- [ ] T005 Pilha local 5.3: `moodle-docker` (PHP 8.3, PostgreSQL 17) sobre `coursecare-devenv/moodle-503`, plugin montado em `public/blocks/coursecare`, instalação do site e do plugin, PHPUnit e Behat inicializados; registrar o ambiente em `evidence/`.
- [ ] T006 Push da branch e execução do CI; corrigir as falhas de 5.3 (sem mudar comportamento; mudança arquitetural apresentada antes) até as quatro combinações e `i18n-package` verdes.

---

## Phase 3: User Story 1 - Administrador instala o plugin num site limpo (P1) 🎯

- [ ] T007 [US1] Instalação limpa pelo ZIP (`tools/package/build.sh`) em 4.5 e 5.3, com depuração de desenvolvedor: sem avisos; tabelas, configurações, tarefa agendada, capabilities e bloco disponíveis; instalação a partir do código (pasta) igual; registrar em `evidence/install.md`.
- [ ] T008 [US1] CLI e diagnóstico no 5.3: `cli/diagnostics.php --run` e `--show` numa sala de demonstração; bloco e página com resultado, sugestão, potencial e histórico; registrar.
- [ ] T009 [US1] Com T006–T008 verdes: `supported` = [405, 503] em `version.php`; README "Compatibility" com a matriz verificada.

## Phase 4: User Story 2 - Mantenedor atualiza uma instalação (P1)

- [ ] T010 [US2] Atualização 0.6.0 → 0.7.0 com dados (diagnósticos e histórico) em 4.5 e 5.3: contagens e somas das tabelas do plugin iguais antes e depois; segunda execução sem mudança; registrar em `evidence/upgrade.md`.
- [ ] T011 [US2] Desinstalação em 4.5 e 5.3: nenhuma tabela, configuração, tarefa ou capability do plugin; registrar.
- [ ] T012 [P] [US2] `docs/upgrading.md` e `docs/installation.md` (requisitos por versão, ZIP, código, cron, efeitos da desinstalação).

## Phase 5: User Story 3 - Professor usa o produto (P1)

- [ ] T013 [US3] Verificação de i18n: paridade, textos fixos (templates e PHP), placeholders, plurais, datas, separador decimal e unidades em en e pt_BR; registrar.
- [ ] T014 [US3] Auditoria visual das jornadas (bloco, página, distribuição, materiais grandes, sugestões, potencial, histórico, sem resultado, desatualizado, falha), desktop e celular, 4.5 (Boost e Classic) e 5.3 (Boost), com `checks.py` (axe, teclado, zoom, 320 px); corrigir só defeitos reais; registrar em `evidence/visual-accessibility.md`.
- [ ] T015 [P] [US3] `docs/teacher-guide.md` (exemplos: números, lógico × único, materiais grandes, sugestões, potencial, histórico, crescimento não é problema, o que o plugin não mede) e `docs/indicators.md` (definições exatas conferidas no código); `docs/pt_br/guia-do-professor.md` (AVA Consciente).

## Phase 6: User Story 5 - Revisor avalia o pacote (P1)

- [ ] T016 [US5] Auditoria de segurança (contracts/audits.md) e correções de achados reais; registrar em `evidence/security-privacy.md`.
- [ ] T017 [US5] Auditoria de privacidade: tabelas × `null_provider`; inferência em turmas pequenas; ciclo de vida; registrar e escrever `docs/privacy.md`.
- [ ] T018 [US5] Auditoria do pacote: conteúdo, metadados, inglês só, GPL e `@copyright` em cada arquivo, nenhum arquivo de desenvolvimento; ajustar `tools/package/check.php` se necessário; registrar.
- [ ] T019 [P] [US5] Governança: `SECURITY.md` (reporte privado de vulnerabilidades), `CONTRIBUTING.md` atualizado (versões, compatibilidade, PRs), `.github/ISSUE_TEMPLATE/bug_report.md` e `.github/PULL_REQUEST_TEMPLATE.md` mínimos.
- [ ] T020 [US5] Matriz de conformidade com o Marketplace (contracts/marketplace-conformance.md) preenchida com evidências em `evidence/README.md`.

## Phase 7: User Story 4 - Administrador configura e entende os dados (P2)

- [ ] T021 [P] [US4] `docs/configuration.md` (as quatro configurações, efeitos, retenção e volume, cron) e `docs/troubleshooting.md` (sem cron, estado "preparando", falhas, limites de upload, desinstalação).

## Phase 8: User Story 6 - Desenvolvedor prepara a submissão (P2)

- [ ] T022 [US6] Relatório de titularidade e licenciamento (contracts/ownership-report.md) em `evidence/ownership-report.md`.
- [ ] T023 [US6] Consolidação das 13 tarefas abertas de 001, 003, 004 e 005 nos grupos A–E em `evidence/human-validations.md`; executar as do grupo A (Constitution Checks de 003, 004 e 005; registro da decisão de formatação de tamanhos da 003, T057) e registrá-las nas respectivas evidências.
- [ ] T024 [US6] `docs/marketplace/listing.md` (nome, descrição curta e longa, tipo, versões verificadas, links, limitações, suporte) e capturas reais com dados fictícios em `docs/marketplace/screenshots/` (5.3 Boost e 4.5), sem dados pessoais; conferência das afirmações proibidas.
- [ ] T025 [US6] Instruções exatas de submissão em `docs/marketplace/submission.md` (conta, campos, ZIP, verificações, chamado de revisão, correções, publicação manual, novas versões, contribuição pt_BR ao AMOS), com as etapas humanas e as conferências pendentes das diretrizes autenticadas.

---

## Phase 9: Polish

- [ ] T026 README público revisado (o que é, para quem, funcionalidades, compatibilidade verificada, instalação, documentação, limitações, privacidade, suporte, licença) e CHANGES 0.7.0.
- [ ] T027 Suítes locais (PHPUnit e Behat em 4.5 e 5.3), i18n, pacote; CI verde; matriz de compatibilidade final com os runs; verificação de desempenho por amostragem no 5.3 (`tools/dev/bench/render.php --page` e bloco, comparados com a 0.6.0 em 4.5); leitura guiada da documentação (SC-008: instalar e configurar seguindo só `docs/`, e cada indicador da página encontrado no guia do professor).
- [ ] T028 Matriz de prontidão final, bloqueios formais e recomendação READY / NOT READY em `evidence/README.md`.
- [ ] T029 Constitution Check do plan.md contra a entrega.

---

## Dependencies & Execution Order

- T001–T002 primeiro; T003–T006 (Foundational) antes de T007–T009 e de toda verificação em 5.3.
- US2, US3, US5 depois da Foundational; documentação (T012, T015, T019, T021) em paralelo.
- T022 e T023 independentes (podem começar a qualquer momento).
- T024 depois de T014 (capturas sobre a interface auditada); T026–T029 por último.

## Cobertura (CT e SC)

| Item | Tarefas |
| --- | --- |
| CT-01, CT-02, SC-001 | T007, T027 |
| CT-03, CT-04, SC-002 | T010 |
| CT-05, SC-003 | T011 |
| CT-06 | T003, T006, T027 |
| CT-07, CT-08, SC-004 | T016, T017 |
| CT-09, SC-005 | T013 |
| CT-10, SC-006 | T018 |
| CT-11 | T014 |
| CT-12, SC-008 | T012, T015, T021, T026 |
| CT-13 | T024 |
| CT-14, SC-007 | T020, T025 |
| SC-009 | T028 |
| FR-090 | T022 |
| FR-120 | T023 |
