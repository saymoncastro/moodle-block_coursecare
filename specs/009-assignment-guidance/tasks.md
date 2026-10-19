---

description: "Task list for Feature 009 — Assignment Guidance"
---

# Tasks: Assignment Guidance

**Input**: Design documents from `specs/009-assignment-guidance/` (spec, plan, research R4 B2 aprovada em
2026-10-19, commit `8f62c26`)

**Tests**: obrigatórios (Princípio IX): PHPUnit, Behat, i18n, atualização com dados, CI.

## Regras que valem para todas as tarefas

- Um único critério novo (tentativas efetivamente ilimitadas); nenhum valor sugerido; nenhum julgamento.
- Regras da 005 reutilizadas (`assign_settings`, `storage_potential::evaluate`); nenhuma interpretação
  paralela das configurações do `mod_assign`.
- Bloco e página sem leitura nova do banco; nenhuma varredura de arquivos nova.
- `NULL` = não calculado ≠ nenhuma sugestão.
- Nenhuma aprovação visual antes das capturas avaliadas pelo proprietário do produto; nenhum merge.

---

## Phase 1: Setup

- [X] T001 `version.php`: `version = 2026102200`, `release = '0.9.0'`, `MATURITY_BETA`; entrada 0.9.0 (unreleased) em `CHANGES.md`.
- [X] T002 [P] `specs/009-assignment-guidance/evidence/README.md` com seções: persistência e atualização, regra, bloco, página, orientação, acessibilidade, desempenho, capturas, validações humanas.

## Phase 2: Foundational (persistência e regra — B2)

- [X] T003 `db/install.xml` e `db/upgrade.php`: campo `suggestions` TEXT, `NOTNULL="false"`, sem padrão e sem índice em `block_coursecare_diag`; passo `add_field` se não existir, savepoint 2026102200; teste de atualização (campo presente, registros existentes `NULL`).
- [X] T004 `classes/local/analysis/storage_potential.php`: método puro do critério sobre o resultado de `evaluate()` e as configurações ("`files > 0` ∧ `maxattempts = -1` ∧ `attempts = null` ∧ `units > 0`"); teste de tabela (reabertura manual, automática, até passar com e sem critério, máximo 1, envio desabilitado, sem participantes, grupos, limite indeterminado).
- [X] T005 `classes/local/collector/assign_settings.php`: pré-filtro de leitura dos `cmid` da sala com tentativas `-1`; teste.
- [X] T006 `classes/local/analysis/stored_suggestions.php`: formato v1 (`{"v":1,"items":[{"kind","cmid"}]}`): codificar, decodificar (`NULL`, ilegível ou versão desconhecida → `null`) e filtrar pelo `modinfo` (existe, é tarefa, `visible`); a mesma função para bloco e página; testes.
- [X] T007 `classes/local/course_analysis.php` e `classes/local/collector/diagnostic_store.php`: na mesma execução da análise, pré-filtro → coletor da 005 só para as candidatas → `evaluate()` → critério → itens; gravados no mesmo `save_result` (transação e trava da 002); falha no cálculo das sugestões grava `NULL` (não calculado) sem falhar o diagnóstico de arquivos; testes (grava, substitui, falha mantém o anterior, sala sem tarefas grava lista vazia).

## Phase 3: User Story 1 — Orientação informativa (P1) 🎯

- [X] T008 [US1] `classes/output/course_overview.php`: para cada tarefa da 005 nos estados `potential` e `unlimited`, orientação (configuração atual, significado, implicações nos dois sentidos, origem do limite, tentativas/até passar, passos com as strings do Moodle — `assign` e `assignsubmission_file` —, link `course/modedit.php?update=` só com `moodle/course:manageactivities`, perguntas de reflexão), âncora `block_coursecare-guidance-{cmid}`.
- [X] T009 [US1] `templates/overview_potential.mustache` e `styles.css`: `<details>` fechado por tarefa, componentes da 008.
- [X] T010 [US1] Strings en e pt_BR da orientação (sem valores; tipos de texto registrados em `evidence/strings.md`).
- [X] T011 [US1] Testes PHPUnit (orientação por estado, origem do limite, ilimitadas, até passar, link só com permissão, nenhum número sugerido) e Behat (abrir a orientação, passos, axe).

## Phase 4: User Story 2 — Recomendação contextualizada, bloco e página (P2)

- [X] T012 [US2] `classes/output/diagnostic_line.php` e `block_coursecare.php`: estado educativo com as sugestões aplicáveis (004 e 009) pela função comum; ordem `idea` antes de `nofiles`; `allgood` só com sugestões calculadas; mensagens por tipo (materiais, tarefas, ambos) e ação singular/plural; sem leitura nova.
- [X] T013 [US2] `classes/output/course_overview.php` e `templates/overview_suggestions.mustache`: cartões das tarefas (FR-010b, FR-011a) depois da 004, na ordem da sala, com a data da análise, link da tarefa, passos das tentativas, link de configurações com permissão, link da orientação só se existir agora, aviso de divergência quando a configuração atual não atende mais; faixa e aba inicial pelo mesmo estado; abas também sem arquivos quando há sugestão (FR-013a).
- [X] T014 [US2] Strings en e pt_BR (cartão, divergência, mensagens do bloco, ação plural, privacidade) e `evidence/strings.md`.
- [X] T015 [US2] Testes PHPUnit (bloco e página: só tarefas, as duas, nenhuma, `NULL`, sala sem arquivos, divergência, exclusão, ocultação, ordem fixa) e Behat (cartão, bloco, axe).

## Phase 5: Polish

- [X] T016 Gerador de testes (campo `suggestions`) e ferramenta de demonstração (tarefa com tentativas ilimitadas), ambos fora do pacote ou só de teste.
- [X] T017 Documentação: guia do professor en e pt_BR, `docs/privacy.md`, `docs/indicators.md` (critério), README, CHANGES.
- [X] T018 Suítes locais (PHPUnit, Behat, i18n, pacote), atualização 0.8.0 → 0.9.0 com dados (campo `NULL`, sem perda), desempenho (`render.php`: bloco e página com as leituras da 0.8.0; consultas da análise com e sem candidata), push e CI verde.
- [ ] T019 Auditoria de acessibilidade (`checks.py`, três combinações) e capturas reais (en e pt_BR, 4.5 e 5.3) para a avaliação do proprietário do produto; aprovação pendente.
- [X] T020 Constitution Check e matriz de prontidão em `evidence/README.md`.

## Dependencies

T001–T002 → T003–T007 → US1 (T008–T011) → US2 (T012–T015) → Polish (T016–T020). T006 serve ao bloco e à página.

## Cobertura

| Requisito | Tarefas |
| --- | --- |
| FR-001–FR-008 | T008–T011 |
| FR-010–FR-012 | T004, T007, T013, T015 |
| FR-013–FR-014 | T003, T005–T007, T012, T013, T018 |
| FR-020–FR-023 | T004–T007, T010, T014, T018, T019 |
| SC-001–SC-006 | T011, T015, T018, T019 |
