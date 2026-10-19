# Implementation Plan: Assignment Guidance

**Branch**: `009-assignment-guidance` | **Date**: 2026-10-19 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/009-assignment-guidance/spec.md` (clarificada em 2026-10-19)

**Pending decision**: arquitetura de consistência bloco × página (research R4). Este plano descreve a
opção recomendada **A (guardar na análise)**; com B ou C, as partes marcadas "(A)" mudam.

## Summary

Orientação informativa recolhida em cada tarefa da seção "Potencial das tarefas" (fatos da 005,
significado, implicações nos dois sentidos, passos de revisão com os nomes reais do Moodle, link para
quem pode editar, perguntas de reflexão). Uma única recomendação contextualizada nova — tentativas
efetivamente ilimitadas numa tarefa com envio de arquivos — decidida pela regra R1, guardada pela análise
em segundo plano (A) e lida pelo bloco e pela página, que passam a refletir as sugestões da 004 e da 009
sem números novos.

## Technical Context

**Language/Version**: PHP 8.1–8.4 (Moodle 4.5 e 5.3); Mustache; CSS. Sem JavaScript novo (o recolher usa
`<details>`, como a 005).

**Primary Dependencies**: APIs do núcleo; `mod_assign` e `assignsubmission_file` (constantes e strings).

**Storage**: (A) uma tabela filha nova do diagnóstico, `block_coursecare_assignidea` (`id`, `diagid`,
`cmid`), substituída a cada análise na mesma transação, apagada com o diagnóstico e na desinstalação.

**Testing**: PHPUnit (regra R1 em tabela, guarda na análise, bloco e página), Behat (orientação, cartão,
estados do bloco, axe), checagem dos nomes dos campos no 4.5 e no 5.3, CI.

**Target Platform**: Moodle 4.5 (Boost, Classic) e 5.3 (Boost).

**Project Type**: plugin de bloco Moodle.

**Performance Goals**: bloco com as mesmas 5 leituras (uma consulta responde às duas sugestões); página
com as leituras atuais + 1 (os `cmid` guardados); análise com a leitura das configurações das tarefas da
sala (já feita pela página da 005).

**Constraints**: nenhum valor sugerido; nenhum dado de estudante; Princípios I, III, IV, VI; WCAG 2.2 AA.

**Scale/Scope**: 1 regra pura, 1 tabela (A), ~4 templates, ~25 strings por idioma, testes, documentação.

## Constitution Check

| Princípio | Avaliação | Situação |
| --- | --- | --- |
| I. Educação | Orientação e sugestão como possibilidades; link para a interface nativa; nada alterado | ✅ |
| II. Simplicidade | Bloco: uma mensagem, um número; orientação recolhida no nível de detalhe | ✅ |
| III. Recomendações | Critério objetivo, documentado (R1) e testável, aprovado; três elementos no cartão | ✅ |
| IV. Honestidade | Potencial sempre como teto; sem desperdício, desempenho ou economia | ✅ |
| V. Integração nativa | Mesma regra do `mod_assign`; strings do núcleo para os campos | ✅ |
| VI. Desempenho | (A) cálculo em segundo plano; bloco lê dado pré-calculado sem leitura extra | ✅ |
| VII. Privacidade | Só `cmid` guardado; nenhum dado pessoal; declaração de privacidade atualizada | ✅ |
| VIII. i18n | Strings en/pt_BR; nomes de campos pelo idioma do site | ✅ |
| IX. Qualidade | PHPUnit, Behat, CI | ✅ |
| X. Visual | Componentes da 008 | ✅ |
| XI. Incremental | Um critério; nenhum mecanismo genérico de regras | ✅ |
| XII. Software livre | Sem dependência nova | ✅ |

**Resultado do gate**: **PASSA** com a opção A. A opção B tensiona o Princípio VI (cálculo na
renderização do bloco); a C acrescenta complexidade de invalidação (Princípio XI).

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
classes/local/analysis/assignment_guidance.php   regra R1 (pura) e orientação informativa (fatos → frases)
classes/local/course_analysis.php                (A) avalia as tarefas e entrega os cmid ao armazenamento
classes/local/collector/diagnostic_store.php     (A) tabela filha nova; consulta única das sugestões
db/install.xml, db/upgrade.php, version.php       (A) tabela e passo de atualização; 0.9.0
block_coursecare.php, classes/output/diagnostic_line.php   estado do bloco por tipo de sugestão
classes/output/course_overview.php               cartão da tarefa, orientação por tarefa, faixa
templates/overview_potential.mustache, overview_suggestions.mustache
lang/en, translations/pt_br, styles.css, tests/, docs/
```

**Structure Decision**: estrutura existente; uma classe de análise nova para a regra e as frases.

## Complexity Tracking

Nenhuma violação com a opção A.
