# Implementation Plan: Assignment Guidance

**Branch**: `009-assignment-guidance` | **Date**: 2026-10-19 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/009-assignment-guidance/spec.md` (clarificada em 2026-10-19)

**Architecture**: **B2 aprovada pelo proprietário do produto em 2026-10-19** (research R4, commit `8f62c26`):
pré-cálculo na análise da 002, guardado em um campo do registro da sala.

## Summary

Orientação informativa recolhida em cada tarefa da seção "Potencial das tarefas" (fatos da 005,
significado, implicações nos dois sentidos, passos de revisão com os nomes reais do Moodle, link para
quem pode editar, perguntas de reflexão). Uma única recomendação contextualizada nova — tentativas
efetivamente ilimitadas numa tarefa com envio de arquivos — decidida pela mesma regra da 005 (R1),
calculada na análise da 002, guardada no registro da sala (B2), filtrada ao vivo pela existência e
visibilidade da atividade e lida pelo bloco e pela página, que passam a refletir as sugestões da 004 e da
009 sem números novos.

## Technical Context

**Language/Version**: PHP 8.1–8.4 (Moodle 4.5 e 5.3); Mustache; CSS. Sem JavaScript novo (o recolher usa
`<details>`, como a 005).

**Primary Dependencies**: APIs do núcleo; `mod_assign` e `assignsubmission_file` (constantes e strings).

**Storage**: (B2) um campo de texto anulável `suggestions` em `block_coursecare_diag` (estrutura versionada
`{"v":1,"items":[{kind,cmid}]}`; `NULL` = não calculado), gravado no mesmo `save_result` da 002; nenhuma
tabela nova.

**Testing**: PHPUnit (regra R1 em tabela, guarda na análise, bloco e página), Behat (orientação, cartão,
estados do bloco, axe), checagem dos nomes dos campos no 4.5 e no 5.3, CI.

**Target Platform**: Moodle 4.5 (Boost, Classic) e 5.3 (Boost).

**Project Type**: plugin de bloco Moodle.

**Performance Goals**: bloco e página sem leitura nova (o campo vem no registro já lido; o filtro usa o
`modinfo` em cache; o coletor que a página já roda não muda); análise: +1 consulta (pré-filtro) em salas
com tarefas e o coletor da 005 só para as candidatas (5 de base, +1 por candidata com sobreposição ou papel
local, +2 com envio em grupo).

**Constraints**: nenhum valor sugerido; nenhum dado de estudante; Princípios I, III, IV, VI; WCAG 2.2 AA.

**Scale/Scope**: 1 regra pura na classe da 005, 1 campo (B2), ~4 templates, ~25 strings por idioma,
testes, documentação.

## Constitution Check

| Princípio | Avaliação | Situação |
| --- | --- | --- |
| I. Educação | Orientação e sugestão como possibilidades; link para a interface nativa; nada alterado | ✅ |
| II. Simplicidade | Bloco: uma mensagem, um número; orientação recolhida no nível de detalhe | ✅ |
| III. Recomendações | Critério objetivo, documentado (R1) e testável, aprovado; três elementos no cartão | ✅ |
| IV. Honestidade | Potencial sempre como teto; sem desperdício, desempenho ou economia | ✅ |
| V. Integração nativa | Mesma regra do `mod_assign`; strings do núcleo para os campos | ✅ |
| VI. Desempenho | (B2) cálculo na análise em segundo plano; bloco e página leem dado pré-calculado sem leitura extra | ✅ |
| VII. Privacidade | Só `cmid` guardado; nenhum dado pessoal; declaração de privacidade atualizada | ✅ |
| VIII. i18n | Strings en/pt_BR; nomes de campos pelo idioma do site | ✅ |
| IX. Qualidade | PHPUnit, Behat, CI | ✅ |
| X. Visual | Componentes da 008 | ✅ |
| XI. Incremental | Um critério; nenhum mecanismo genérico de regras | ✅ |
| XII. Software livre | Sem dependência nova | ✅ |

**Resultado do gate**: **PASSA** com B2. O cálculo sob demanda tensiona o Princípio VI; o cache acrescenta
invalidação e perda silenciosa (Princípio XI); uma tabela nova acrescenta leitura sem ganho.

## Project Structure

### Documentation (this feature)

```text
specs/009-assignment-guidance/
├── spec.md, plan.md, research.md, data-model.md, quickstart.md
├── contracts/ui.md
├── checklists/requirements.md
└── evidence/
```

### Source Code (repository root)

```text
classes/local/analysis/storage_potential.php     critério (método puro, junto do evaluate() da 005)
classes/local/analysis/assignment_guidance.php   orientação informativa (fatos da 005 → frases e passos)
classes/local/course_analysis.php                (B2) seleção + coletor + evaluate + critério, na mesma execução
classes/local/collector/assign_settings.php      (B2) pré-filtro: cmid com tentativas -1 (leitura; regras no evaluate)
classes/local/collector/diagnostic_store.php     (B2) grava `suggestions` no save_result; função de leitura + filtro
classes/output/diagnostic_line.php               estado educativo: idea antes de nofiles; allgood só com sugestões calculadas
db/install.xml, db/upgrade.php, version.php       (B2) campo e passo de atualização; 0.9.0
block_coursecare.php, classes/output/diagnostic_line.php   estado do bloco por tipo de sugestão
classes/output/course_overview.php               cartão da tarefa, orientação por tarefa, faixa
templates/overview_potential.mustache, overview_suggestions.mustache
lang/en, translations/pt_br, styles.css, tests/, docs/
```

**Structure Decision**: estrutura existente; uma classe de análise nova para a regra e as frases.

## Complexity Tracking

Nenhuma violação com B2.
