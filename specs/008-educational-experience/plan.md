# Implementation Plan: Educational Experience

**Branch**: `008-educational-experience` | **Date**: 2026-10-19 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/008-educational-experience/spec.md` (clarificada em
2026-10-19: Q1 personalizada, Q2 A, Q3 A)

## Summary

Reapresentar os dados existentes como experiência educativa: o bloco ganha um estado educativo derivado
(título, mensagem, notas, acesso) sobre o indicador atual; a página ganha cabeçalho com identidade,
ilustração original e cartão do indicador, e organiza o conteúdo em duas abas acessíveis ("Sugestões para
você" e "Em detalhes") com melhoria progressiva, fragmentos e histórico; o cartão da sugestão da 004 ganha
hierarquia mais clara. Nenhum dado, critério, indicador ou consulta nova.

## Technical Context

**Language/Version**: PHP 8.1–8.4 (Moodle 4.5 e 5.3); Mustache; CSS; JavaScript ES2015+ como módulo AMD
compilado pelo `grunt amd` do Moodle.

**Primary Dependencies**: APIs do núcleo (output, strings, `{{#pix}}`, `{{#js}}`, AMD). Nenhuma biblioteca
de terceiros; nenhum JavaScript do Bootstrap.

**Storage**: nenhuma mudança (tabelas da 002/006 só lidas, como hoje).

**Testing**: PHPUnit (função de estado educativo, contexto dos templates), Behat com e sem JavaScript
(estados, abas, teclado, fragmentos, axe), `checks.py` da 007, inventário de preservação, CI
`moodle-plugin-ci` (inclui `grunt` e `mustache`) em 4.5 e 5.3.

**Target Platform**: Moodle 4.5 LTS (Boost, Classic) e 5.3 LTS (Boost); desktop e celular.

**Project Type**: plugin de bloco Moodle.

**Performance Goals**: bloco e página com as mesmas leituras do banco da 0.7.0 e tempo médio dentro de 10%
(SC-004); módulo AMD pequeno, carregado só na página.

**Constraints**: WCAG 2.2 AA; 320 px e zoom 200% sem rolagem horizontal; funcionamento sem JavaScript e
na impressão; estilos restritos ao plugin; nenhum recurso externo.

**Scale/Scope**: 1 função de estado, ~6 templates alterados, 1 módulo AMD, 1 SVG, ~15 strings novas por
idioma, CSS, testes e documentação.

## Constitution Check

| Princípio | Avaliação | Situação |
| --- | --- | --- |
| I. Educação, não fiscalização | Estados positivos limitados ao avaliado; sugestão como possibilidade; nenhuma ação automática | ✅ |
| II. Simplicidade | Bloco: uma mensagem e um indicador; página em dois níveis (sugestões, detalhes); detalhes técnicos recolhidos | ✅ |
| III. Recomendações | Só a sugestão da 004, critério inalterado; sem priorização nem valores | ✅ |
| IV. Honestidade | Nenhuma promessa; indicadores e textos de medida inalterados; positivo nunca esconde falha, desatualização ou parcial | ✅ |
| V. Integração nativa | APIs do núcleo; classes do tema para botões; sem Bootstrap JS | ✅ |
| VI. Desempenho | Nenhuma leitura nova; JS só na página | ✅ |
| VII. Segurança e privacidade | Nenhum dado novo; mesmo controle de acesso | ✅ |
| VIII. Internacionalização | Strings completas en e pt_BR; identidade por string | ✅ |
| IX. Qualidade | PHPUnit, Behat, CI completo, `grunt` | ✅ |
| X. Experiência visual | Identidade aprovada; WCAG 2.2 AA; ludicidade discreta, sem mascote | ✅ |
| XI. Evolução incremental | Itens futuros fora; nenhuma abstração sem uso | ✅ |
| XII. Software livre | Ilustração original GPL com origem documentada | ✅ |

**Resultado do gate (pré-pesquisa e pós-design)**: **PASSA**, sem violações.

## Project Structure

### Documentation (this feature)

```text
specs/008-educational-experience/
├── spec.md, plan.md, research.md, data-model.md, quickstart.md
├── contracts/ui.md
├── checklists/requirements.md
├── evidence/            (capturas, auditorias, inventário de preservação)
└── tasks.md
```

### Source Code (repository root)

```text
classes/output/diagnostic_line.php      estado educativo do bloco (função pura) e contexto
classes/output/course_overview.php      cabeçalho, faixa, áreas, aba inicial
block_coursecare.php                    passa sugestão/parcial ao bloco (dados já lidos)
templates/content.mustache              bloco educativo
templates/overview.mustache             cabeçalho, abas, seções, fechamento
templates/overview_summary.mustache     cartão do indicador
templates/overview_suggestions.mustache faixa e cartão da 004
amd/src/tabs.js, amd/build/tabs.min.js(.map)
pix/illustration.svg
styles.css
lang/en/block_coursecare.php, translations/pt_br/block_coursecare.php
tests/ (PHPUnit e Behat)
version.php, CHANGES.md, README.md, docs/ (guias), docs/marketplace/
```

**Structure Decision**: a estrutura existente do plugin; nenhum diretório novo além de `amd/`.

## Complexity Tracking

Nenhuma violação a justificar.
