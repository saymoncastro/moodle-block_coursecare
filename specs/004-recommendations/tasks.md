---

description: "Task list for Feature 004 — Recomendações"
---

# Tasks: Recomendações

**Input**: Design documents from `specs/004-recommendations/` (spec `047e0e0`, plan `2a5bfda`)

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/recommendation-rule.md,
contracts/suggestions-ui.md, quickstart.md

**Tests**: obrigatórios pela constituição (Princípio IX: PHPUnit para toda regra; Behat para todo
fluxo de interface). As tarefas de teste vêm antes da implementação de cada parte.

**Organization**: por história de usuário da spec (US1 P1, US3 P1, US2 P2), cada uma testável sozinha.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: pode rodar em paralelo (arquivos diferentes, sem dependência de tarefa incompleta)
- **[Story]**: história de usuário (US1, US2, US3)

## Path Conventions

Plugin de bloco do Moodle na raiz do repositório: `classes/`, `templates/`, `lang/`, `tests/`,
`translations/`. Testes PHPUnit e Behat rodam no stack de desenvolvimento (`coursecare-002`).

## Regras que valem para todas as tarefas

- **Camadas**: `$DB` só em `classes/local/collector/`; `classes/local/recommendation/` é pura (sem
  `$DB`, sessão, `$CFG`, `get_string()` ou saída); `classes/output/` monta frases e o contexto dos
  templates.
- **Privacidade**: S1 usa só as ligações a materiais de grande porte (a parte `large` da visão do
  `overview_builder`). Nunca usar o resumo da sala (`large.count`, `large.bytes`, `largecount`,
  `largebytes`), que inclui envios, nem nomear atividades invisíveis ao professor ou removidas.
- **Nunca**: consultar a tabela `files`, criar tabela, campo, configuração, tarefa, cache, capability,
  JavaScript, interface ou registro de regras; botão de dispensar sugestão; ação executada pelo plugin.
- **Strings**: toda chave nova entra em `lang/en/block_coursecare.php` (ordem alfabética) e em
  `translations/pt_br/block_coursecare.php` na mesma tarefa, com o texto de
  contracts/suggestions-ui.md; frases completas, sem concatenação; nenhum texto fixo em template.
- **Nomes de atividade**: já formatados por `format_string()`; nos templates, `{{{name}}}` (sem escape
  duplo, como na correção `c053e3d` da 003).
- **Visual**: estilos só sob `.block_coursecare` e `.block_coursecare-overview`, reaproveitando as
  propriedades `--block-coursecare-*` existentes; sem cor ou ícone de alerta.

---

## Phase 1: Setup

**Purpose**: versão e catálogo de strings.

- [X] T001 Em `version.php`, `$plugin->release = '0.4.0'` e `$plugin->version = 2026101800` (sem mudança de banco, sem upgrade step).
- [X] T002 [P] Acrescentar as 17 strings de contracts/suggestions-ui.md (`block:suggestion`, `suggestions:heading`, `suggestions:basedon`, `suggestions:none`, `suggestion:observed`, `suggestion:why`, `suggestion:todo`, `suggestion:large:title`, `suggestion:large:observed`, `suggestion:large:observed_one`, `suggestion:large:more`, `suggestion:large:more_one`, `suggestion:large:why`, `suggestion:large:todo`, `suggestion:large:option1`, `suggestion:large:option2`, `suggestion:large:option3`) em `lang/en/block_coursecare.php` e `translations/pt_br/block_coursecare.php`, em ordem alfabética; conferir com `php tools/i18n/check.php` (0 erros).

---

## Phase 2: Foundational (regra pura de S1)

**Purpose**: a regra que a página e os testes usam. Bloqueia US1 e US3.

- [X] T003 [P] Criar `tests/recommendation/large_materials_test.php` (`@covers \block_coursecare\local\recommendation\large_materials`) com os casos de contracts/recommendation-rule.md: `evaluate(null)` e `large` sem ligações → `null`; soma de `count` e `bytes` de `materials` + `courselevel` + `others` + `removed`; `places` com no máximo 3 itens ordenados por `bytes` desc, empate entre atividades pelo nome em minúsculas e nível da sala depois das atividades de mesmo tamanho; `courselevel` como lugar (`kind` = `courselevel`); `others` e `removed` só contados em `more` (1 cada), nunca em `places`; `more` = lugares com nome além de 3 + grupos; `truncated` repassado; `large.count`/`large.bytes` altos com `materials` vazio e grupos nulos → `null` (envios grandes não geram S1).
- [X] T004 Criar `classes/local/recommendation/large_materials.php` (namespace `block_coursecare\local\recommendation`, classe única, sem interface) com `public static function evaluate(?array $large): ?array`, devolvendo `null` ou `['count', 'bytes', 'threshold', 'places', 'more', 'truncated']` exatamente como contracts/recommendation-rule.md; cada lugar de atividade `['kind' => 'activity', 'cmid', 'name', 'url', 'bytes']`, nível da sala `['kind' => 'courselevel', 'bytes']`; PHPDoc conforme o Moodle; T003 verde.

**Checkpoint**: regra pronta e testada.

---

## Phase 3: User Story 1 - Entender uma sugestão sobre os materiais grandes da sala (Priority: P1) 🎯 MVP

**Goal**: a página mostra a seção "Sugestões para a sua sala" com o cartão de S1 (três partes,
lugares com link, boas práticas como possibilidades).

**Independent Test**: numa sala com ligações a materiais grandes em duas atividades, a página mostra a
seção depois da abertura, com os rótulos, a quantidade e o volume iguais à soma da lista "Materiais de
grande porte por atividade", as duas atividades com link e as três possibilidades; numa sala com só
envios grandes, sem o cartão.

### Tests for User Story 1

- [X] T005 [P] [US1] Em `tests/output/course_overview_test.php`, acrescentar `test_suggestion_large_materials`: sala com materiais grandes em 2 atividades (`create_diagnostic_largelink`) e um envio grande só no resumo (`largecount` e `largebytes` maiores que a soma das ligações) → o HTML tem `id="block_coursecare-suggestions"` antes de `overview:categories`; `suggestion:large:title`; os três rótulos (`suggestion:observed`, `suggestion:why`, `suggestion:todo`) em `<dt>`; `suggestion:large:observed` com `count`, `threshold` e `size` iguais à soma das ligações (o envio grande não entra); os dois nomes com link (escapados uma vez); `suggestion:large:why`, `suggestion:large:todo` e as três opções; `suggestions:basedon` com a data. Variantes: 1 ligação → `suggestion:large:observed_one`; 5 lugares → 3 links e `suggestion:large:more` com 2; 4 lugares → `suggestion:large:more_one`; ligação no nível da sala → `overview:courselevel` com link para `/course/view.php`; atividade oculta ao professor e removida → sem nome, só em `more`; `largelinkstruncated = 1` → `overview:large_truncated` dentro do cartão.
- [X] T006 [P] [US1] Em `tests/output/course_overview_test.php`, acrescentar `test_suggestion_privacy`: sala com envio grande (resumo com `largecount`/`largebytes` > 0, categoria `submission`) e nenhuma ligação a material → sem `suggestion:large:title`; o HTML não contém nome de estudante nem de arquivo; nenhuma palavra de julgamento ou promessa nas strings novas: no HTML renderizado em en, sem "must", "should", "waste", "save", "delete"; nos valores das chaves novas de `translations/pt_br/block_coursecare.php` (lidos do arquivo, como em `tests/translation_test.php`), sem "deve", "desperdício", "excluir", "economia", "economizar".

### Implementation for User Story 1

- [X] T007 [US1] Em `classes/output/course_overview.php`, acrescentar `suggestions_data()` e as chaves `hassuggestionsection`, `suggestions` em `export_for_template()`: só com resultado (`view['hasresult']`); chama `large_materials::evaluate($this->view['large'])`; com S1, monta `heading`, `basedon` (`userdate` de `view['timecompleted']` com `strftimedatetimeshort`), `hassuggestion`, `title`, `labels`, `observed` (escolhendo `observed_one` quando `count` = 1; tamanhos com `diagnostic_line::size()`), `places` (`name`, `url`, `hasurl`; nível da sala com `overview:courselevel` e a URL da sala), `more` (`more_one` quando 1; vazio quando 0), `truncated` (`overview:large_truncated` ou vazio), `why`, `todo`, `options`; sem S1, `none` = `suggestions:none`; PHPDoc atualizado.
- [X] T008 [P] [US1] Criar `templates/overview_suggestions.mustache` conforme contracts/suggestions-ui.md: `<section id="block_coursecare-suggestions" class="block_coursecare-overview-section block_coursecare-overview-suggestions">`, `h3`, frase da data, cartão com `h4` e `dl` (`dt`/`dd`), lugares em `ul` com `{{{name}}}`, possibilidades em `ul`; frase neutra quando não há sugestão; documentação e contexto de exemplo válidos para o lint de Mustache.
- [X] T009 [US1] Em `templates/overview.mustache`, incluir `{{#hassuggestionsection}}{{> block_coursecare/overview_suggestions }}{{/hassuggestionsection}}` logo depois de `overview_summary` e antes da grade; atualizar a documentação e o contexto de exemplo.
- [X] T010 [P] [US1] Em `styles.css`, estilos da seção e do cartão (`.block_coursecare-overview-suggestions`, `-suggestion`, `dt`/`dd`, listas), reaproveitando `--block-coursecare-panel`, `--block-coursecare-border` e os raios da 003; a 320 px uma coluna, sem rolagem horizontal.
- [X] T011 [US1] Criar `tests/behat/block_coursecare_suggestions.feature` (`@block @block_coursecare`) com: sala com ligações a materiais grandes em duas atividades (`block_coursecare > diagnostic large links`) → na página, "Suggestions for your course", "Large materials", os três rótulos, as duas atividades como links e "Size alone says nothing"; sala só com envio grande → sem "Large materials" na seção; `@javascript @accessibility` Scenario Outline em `large` e `mobile` com "the page should meet accessibility standards".

**Checkpoint**: US1 funcional e testada (PHPUnit e Behat verdes).

---

## Phase 4: User Story 3 - Entender quando não há sugestão ou o diagnóstico não permite sugerir (Priority: P1)

**Goal**: a seção segue a tabela de FR-020 em todos os estados.

**Independent Test**: estados A a H: seção ausente sem resultado; frase neutra com resultado e sem S1
(inclusive G e E/F sem arquivos); S1 com a data em E e F com resultado.

### Tests for User Story 3

- [X] T012 [P] [US3] Em `tests/output/course_overview_test.php`, acrescentar `test_suggestion_states`: A e B (sem resultado) → sem `block_coursecare-suggestions`; D e H sem ligações → `suggestions:none`; G e E/F de um resultado sem arquivos → `suggestions:none`; E e F com ligações → cartão de S1 com `suggestions:basedon`, além de `diagnostic:mayoutdated`/`diagnostic:updatefailed` na abertura; nenhuma palavra de avaliação positiva ou negativa na frase neutra.
- [X] T013 [US3] Em `tests/behat/block_coursecare_suggestions.feature`, acrescentar um Scenario Outline de estados (sem resultado: não ver "Suggestions for your course"; com resultado sem ligações: ver "There are no suggestions for this course at the moment."; resultado antigo com ligações: ver "Large materials" e "This information may be out of date.").

### Implementation for User Story 3

- [X] T014 [US3] Fazer T012 e T013 passarem em `classes/output/course_overview.php` e `templates/overview_suggestions.mustache`: `hassuggestionsection` só com `view['hasresult']`; frase neutra (`suggestions:none`, sem data) quando `large_materials::evaluate()` devolve `null`, inclusive com `view['large']` nulo (G e E/F sem arquivos).

**Checkpoint**: US1 e US3 completas: a página atende à tabela de FR-020.

---

## Phase 5: User Story 2 - Saber pelo bloco que há uma sugestão (Priority: P2)

**Goal**: o bloco mostra a frase de sugestão e o link leva à seção.

**Independent Test**: sala com ligação a material grande → bloco com "There is a suggestion about the
files of your course." e link com `#block_coursecare-suggestions`; sem ligação ou sem resultado →
bloco igual ao da 003.

### Tests for User Story 2

- [X] T015 [P] [US2] Em `tests/collector/diagnostic_store_test.php`, acrescentar `test_has_large_materials`: `true` com uma ligação no diagnóstico; `false` sem ligação; `false` para ligações de outro diagnóstico.
- [X] T016 [P] [US2] Em `tests/block_coursecare_test.php`, acrescentar `test_block_suggestion`: com resultado e ligação a material grande → `block:suggestion` e `overview.php?id=<instância>#block_coursecare-suggestions`; com resultado sem ligação → sem `block:suggestion` e link sem âncora; sem resultado → sem frase e sem link. Atualizar `test_render_reads_no_files`: continua sem consulta a `files`; as leituras são iguais nos estados sem resultado e em G, e têm exatamente 1 a mais nos estados com resultado e arquivos (D, E, F com resultado, H).

### Implementation for User Story 2

- [X] T017 [US2] Em `classes/local/collector/diagnostic_store.php`, acrescentar `public function has_large_materials(int $diagid): bool` com `$DB->record_exists('block_coursecare_large', ['diagid' => $diagid])` (índice da chave estrangeira `diagid`); PHPDoc.
- [X] T018 [US2] Em `classes/output/diagnostic_line.php`, aceitar `bool $hassuggestion = false` no construtor; em `link()`, com resultado e sugestão, `overviewurl` com a âncora `block_coursecare-suggestions` (`moodle_url::set_anchor()` numa cópia) e `suggestion` = `block:suggestion`; caso contrário, `suggestion` = ''.
- [X] T019 [US2] Em `templates/content.mustache`, mostrar `{{#suggestion}}<p class="block_coursecare-suggestionline">{{suggestion}}</p>{{/suggestion}}` antes do link, sem ícone ou cor de alerta; atualizar documentação e contexto de exemplo; estilo mínimo em `styles.css` sob `.block_coursecare`.
- [X] T020 [US2] Em `block_coursecare.php`, guardar o registro lido por `get_summary()`; quando o estado tem resultado e `filecount > 0`, calcular `$hassuggestion = (new diagnostic_store())->has_large_materials((int)$record->id)` e passá-lo ao `diagnostic_line`; T015 e T016 verdes.
- [X] T021 [US2] Em `tests/behat/block_coursecare_suggestions.feature`, acrescentar: sala com ligação a material grande → ver "There is a suggestion about the files of your course." no bloco "CourseCare", clicar em "Learn more about the files in your course" e ver "Suggestions for your course"; sala sem ligação → não ver a frase no bloco.

**Checkpoint**: as três histórias funcionam juntas.

---

## Phase 6: Polish, validação e evidências

- [X] T022 [P] Atualizar `README.md` (seção sobre sugestões: S1, condição, o que não significa, o que fica para depois, frase do bloco) e `CHANGES.md` (0.4.0).
- [X] T023 [P] Em `specs/003-teacher-experience/evaluation/protocol.md`, acrescentar as tarefas sobre a sugestão (FR-052 da 004): na página de *Biologia celular*, "o que a sugestão diz que foi observado?" (sucesso: fatos medidos) e "o que dela é obrigatório?" (sucesso: nada; são possibilidades); pergunta aberta sobre a utilidade; erro crítico se a sugestão for lida como ordem de excluir ou de corrigir.
- [X] T024 Criar `specs/004-recommendations/evidence/README.md` com a tabela de CT-01 a CT-13 e SC-001 a SC-009 (status "pendente" até haver evidência) e as seções de medições, tom, validação visual, avaliação e CI.
- [X] T025 Rodar PHPUnit (`--testsuite block_coursecare_testsuite`), Behat (`--tags=@block_coursecare`, com `--no-interaction`), phpcs (Moodle), `tools/i18n/check.php`; corrigir até tudo verde.
- [X] T026 [MANUAL] Medir SC-005 com `tools/dev/bench/render.php` na mesma sala com materiais grandes, 0.3.0 × 0.4.0 (bloco), e `--page` na sala de 200 atividades; registrar em `specs/004-recommendations/evidence/README.md` com as saídas em `evidence/logs/`.
- [X] T027 [MANUAL] Revisão de tom de todas as strings novas en e pt_br (FR-050, SC-004) contra FR-005; registrar e obter a aprovação do proprietário do produto.
- [X] T028 [MANUAL] Validação visual (FR-051, SC-007): capturas com `tools/dev/demo/screenshots.py` e verificações com `tools/dev/demo/checks.py` do bloco e da página com e sem sugestão, en e pt_BR, Boost e Classic, desktop e celular; registrar e obter a aprovação do proprietário do produto.
- [X] T029 Gerar e verificar o pacote: `tools/package/build.sh` e `php tools/package/check.php build/block_coursecare-0.4.0.zip`.
- [X] T030 Publicar a branch, verificar o CI (`moodle-plugin-ci`, PHPUnit e Behat em 8.1/pgsql e 8.3/mariadb, `i18n-package`) e registrar o run em `specs/004-recommendations/evidence/README.md`.
- [ ] T031 [MANUAL] Avaliação com cinco professores junto da 003 (FR-052, SC-009), conduzida pelo proprietário do produto; registro em `specs/003-teacher-experience/evidence/evaluation/`; pendente, não bloqueia a implementação nem o teste local.
- [ ] T032 Reavaliar o Constitution Check do plan.md contra o código entregue e completar a tabela de `specs/004-recommendations/evidence/README.md` (nenhum item marcado sem evidência). Depende de T025 a T031.

---

## Dependencies & Execution Order

- **Setup (T001–T002)**: sem dependências.
- **Foundational (T003–T004)**: sem dependências (a regra é pura e não usa strings). Bloqueia US1 e US3.
- **US1 (T005–T011)**: depois de T002 e T004. T007 antes de T009; T008 e T010 em paralelo com T007.
- **US3 (T012–T014)**: depois de T007–T009 (mesmo renderable e template).
- **US2 (T015–T021)**: depende só de T002; pode correr em paralelo com US1/US3 (arquivos diferentes), exceto T021, que usa a seção da página (depois de T009).
- **Polish (T022–T032)**: depois das histórias; T032 por último.

## Parallel Example

```text
Depois de T004:
  T005 [US1] testes da seção       | T008 [US1] template da seção | T010 [US1] estilos
  T015 [US2] teste do coletor      | T016 [US2] teste do bloco
```

## Implementation Strategy

1. **MVP**: Setup, Foundational e US1 (a sugestão na página). Valida o valor central: o professor lê
   uma sugestão com fatos, motivo e possibilidades.
2. **US3**: comportamento em todos os estados (necessário antes de qualquer demonstração).
3. **US2**: a frase do bloco, que torna a sugestão encontrável.
4. **Polish**: documentação, medições, validações humanas e CI. A avaliação com professores (T031)
   fica pendente sem bloquear a entrega técnica.

## Cobertura dos cenários (CT) e critérios (SC)

| Item | Tarefas |
| --- | --- |
| CT-01, CT-02, CT-03, CT-06 | T003, T005 |
| CT-04 | T003, T006, T011 |
| CT-05 | T012, T013, T016 |
| CT-07 | T016, T021 |
| CT-08 | T006, T027 |
| CT-09 | T016 |
| CT-10 | T011, T028 |
| CT-11 | T028 |
| CT-12 | T005, T027 |
| CT-13 | T023, T031 |
| SC-001 | T012, T013, T016 |
| SC-002 | T003, T005 |
| SC-003 | T006 |
| SC-004 | T027 |
| SC-005 | T016, T026 |
| SC-006 | T011, T028 |
| SC-007 | T028 |
| SC-008 | T025, T029, T030 |
| SC-009 | T031 |
