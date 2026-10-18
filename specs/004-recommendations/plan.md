# Implementation Plan: Recomendações

**Branch**: `004-recommendations` | **Date**: 2026-10-18 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/004-recommendations/spec.md` (commit `047e0e0`,
esclarecida pelo proprietário do produto em 2026-10-18)

## Summary

A Feature 004 acrescenta à experiência da Feature 003 a primeira sugestão do produto, **S1 —
materiais de grande porte**, com boas práticas curtas vinculadas a ela:

- **Página "Sua sala em números"**: nova seção "Sugestões para a sua sala", logo depois da
  abertura. Com S1 aplicável, um cartão com três partes rotuladas (o que observamos; por que pode
  merecer atenção; o que você pode fazer, se quiser), a data da análise e links para as atividades.
  Com resultado e sem sugestão, uma frase neutra. Sem resultado, nenhuma seção.
- **Bloco**: quando S1 se aplica, uma frase curta ("Há uma sugestão sobre os arquivos da sua sala.")
  e o link existente passa a abrir a página na seção de sugestões.

**Como fica simples:**
- Uma regra pura (`local\recommendation\large_materials`), sem interface, registro de regras ou
  configuração (Princípio XI).
- A página não lê nada novo: a regra usa a visão de grandes que a Feature 003 já monta.
- O bloco faz uma leitura a mais, por índice, só quando há resultado com arquivos: se existe alguma
  ligação a material de grande porte.
- Um template novo (`overview_suggestions`), 17 strings novas, sem JavaScript, sem tabela,
  sem configuração, sem capability.

## Technical Context

**Language/Version**: PHP 8.1–8.3 (Moodle 4.5 LTS), Mustache, CSS. Sem JavaScript novo.

**Primary Dependencies**: Moodle 4.5 core (Block, Output, String APIs; `userdate()`); os componentes
da Feature 003 (`course_overview`, `overview_builder`, `diagnostic_line`, templates e estilos). Sem
bibliotecas de terceiros.

**Storage**: tabelas existentes da Feature 002, somente leitura. Nenhuma tabela, campo ou
configuração nova.

**Testing**: PHPUnit (regra pura, renderable da página, bloco com e sem sugestão, ausência de
consulta a `files`, número de leituras do bloco), Behat (jornada bloco → seção, estados, acessibilidade
com axe em desktop e celular, teclado), paridade de strings, verificação do pacote, `moodle-plugin-ci`.
Revisão de tom, validação visual e avaliação com professores (junto da 003) manuais, com evidências.

**Target Platform**: Moodle 4.5, PostgreSQL e MariaDB/MySQL, Boost e Classic, desktop e celular.

**Project Type**: plugin de bloco do Moodle (`block_coursecare`), versão 0.4.0.

**Performance Goals**:
- Bloco com no máximo +50 ms em relação à 0.3.0 (SC-005).
- Página de uma sala com 200 atividades em até 2 s (SC-005).

**Constraints**:
- Nenhuma consulta a `files` (FR-032).
- Por atividade, só materiais (FR-030).
- Nenhuma frase montada por concatenação (FR-041).
- Nenhuma ação executada pelo plugin, nenhum dado persistido (FR-006, FR-032).
- Nenhuma configuração nova; o único limiar é o limite de grande porte do resultado (FR-033).

**Scale/Scope**:
- 1 classe nova de regra, 1 método novo no coletor, alterações em 2 renderables e no bloco.
- 1 template novo, 2 templates alterados, estilos da seção.
- 17 strings novas em en e pt_br (15 frases e 2 variantes no singular).

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Princípio | Avaliação | Situação |
| --- | --- | --- |
| I. Educação, não fiscalização | Sugestão opcional ("se quiser"), sem obrigação, prazo ou cobrança; o professor ignora sem consequência (sem dispensar nem registrar); nenhuma ação executada pelo plugin, só links para páginas nativas; sem ranking, comparação ou notificação. | ✅ |
| II. Simplicidade | Nível 1: a frase de sugestão faz parte da mensagem única do bloco sobre os arquivos da sala (o "estado educativo do bloco" aprovado no roadmap): complementa a linha do diagnóstico, não acrescenta número nem indicador, e o volume continua o único indicador. Nível 2: a seção de sugestões com situação concreta e ações práticas. Nível 3 inalterado. Cada nível compreensível sozinho. | ✅ |
| III. Recomendações contextualizadas | S1 tem as três partes; fatos separados das possibilidades; tamanho não tratado como mau uso; critério objetivo e documentado na spec. **Limiar configurável**: S1 usa só o limite de grande porte, configurável pela administração desde a 002 e registrado no resultado; nenhum limiar novo (conflito resolvido na spec). | ✅ |
| IV. Honestidade dos indicadores | S1 cita só o Armazenamento lógico de materiais de grande porte, com a data da análise; sem economia, projeção ou impacto ambiental; "podem demorar mais para abrir" é explicação geral, não medição, e está registrado nas limitações. | ✅ |
| V. Integração nativa | Block e Output APIs; links para páginas nativas do Moodle (atividade e sala); sem patch, sem dependência de tema. | ✅ |
| VI. Desempenho | Página: nenhuma leitura nova. Bloco: uma leitura por índice (`diagid`) só com resultado e arquivos; metas de SC-005 medidas. | ✅ (medição na implementação) |
| VII. Segurança e privacidade | Mesma verificação de acesso e capability da 003; só materiais por atividade (as ligações a grandes só existem para materiais); nenhum nome de estudante ou de arquivo; atividades invisíveis ao professor sem nome; nada armazenado. | ✅ |
| VIII. Internacionalização | Frases completas no catálogo, en e pt_br em paridade; lista de atividades como links, sem concatenação. | ✅ |
| IX. Qualidade e camadas | A camada de **regras de recomendação** passa a existir (`local\recommendation`), pura, sem banco, sessão ou saída; coleta só em `local\collector`; apresentação em `output` e templates. PHPUnit para a regra; Behat para a jornada. | ✅ |
| X. Experiência visual | Seção com o cartão discreto da 003, sem cores ou ícones de alerta; validação visual com aprovação (FR-051). | ✅ |
| XI. Evolução incremental | Uma regra concreta, sem registro de regras, interface ou estrutura para orientações futuras; S2 e as demais ficam fora com dependências registradas. | ✅ |
| XII. Software livre | GPL v3+, cabeçalhos; README e CHANGES 0.4.0. | ✅ |

**Resultado do gate (pré-pesquisa e pós-design)**: **PASSA**, sem violações e sem itens de
Complexity Tracking.

### Registro de tratamento de dados (Princípio VII, LGPD/GDPR)

| Item | Decisão |
| --- | --- |
| Dados lidos | Os mesmos da Feature 003: ligações a materiais de grande porte (atividade e tamanho), limite do resultado, data da análise, estrutura da sala. O bloco lê também se existe ligação a material de grande porte. |
| Dados exibidos | Quantidade e volume dos materiais de grande porte da sala, até três lugares (atividades com nome atual e link, ou "Página da sala, seções e blocos"), quantos outros lugares existem. |
| Dados armazenados | Nenhum. |
| Finalidade e necessidade | Orientar o professor sobre os materiais que ele mesmo publicou. |
| Risco residual | Nenhum novo em relação à 003. |
| Privacy API | `null_provider` mantido. |

## Project Structure

### Documentation (this feature)

```text
specs/004-recommendations/
├── plan.md              # este arquivo
├── research.md          # decisões (Phase 0)
├── data-model.md        # a sugestão S1 (Phase 1)
├── quickstart.md        # validação de ponta a ponta (Phase 1)
├── contracts/
│   ├── recommendation-rule.md   # regra pura S1: entrada e saída
│   └── suggestions-ui.md        # bloco, seção da página, strings
├── checklists/requirements.md
└── tasks.md             # /speckit-tasks
```

### Source Code (repository root)

```text
classes/
├── local/
│   ├── recommendation/
│   │   └── large_materials.php      # NOVO: regra pura de S1
│   └── collector/
│       └── diagnostic_store.php     # + has_large_materials(diagid)
└── output/
    ├── course_overview.php          # + dados da seção de sugestões
    └── diagnostic_line.php          # + frase de sugestão e âncora do link
block_coursecare.php                 # passa ao diagnostic_line se há sugestão
templates/
├── overview_suggestions.mustache    # NOVO: seção de sugestões
├── overview.mustache                # inclui a seção depois da abertura
└── content.mustache                 # frase de sugestão do bloco
styles.css                           # cartão da sugestão
lang/en/block_coursecare.php         # strings novas
translations/pt_br/block_coursecare.php
version.php                          # 0.4.0
README.md, CHANGES.md
tests/
├── recommendation/large_materials_test.php   # NOVO
├── output/course_overview_test.php           # seção de sugestões
├── block_coursecare_test.php                 # frase do bloco, leituras
├── collector/diagnostic_store_test.php       # has_large_materials
└── behat/block_coursecare_suggestions.feature  # NOVO
specs/003-teacher-experience/evaluation/protocol.md  # tarefas sobre a sugestão (FR-052)
```

**Structure Decision**: plugin único com as camadas da constituição. A regra de S1 inaugura
`local\recommendation`, como classe única e pura; não há interface nem lista de regras (Princípio XI).

## Fluxos

**Página**: `course_overview::for_course()` (sem mudança nas leituras) → `overview_builder` monta a
visão (inclui `large`) → `large_materials::evaluate($view['large'])` devolve `null` ou os dados de S1 →
`course_overview` exporta `suggestions` (título, data, cartão ou frase neutra) → `overview_suggestions`.

**Bloco**: `block_coursecare::get_content()` lê o resumo (como hoje) → se há resultado com arquivos,
`diagnostic_store::has_large_materials(diagid)` (uma leitura por índice) → `diagnostic_line` recebe o
indicador e exporta a frase de sugestão e o link com a âncora `#block_coursecare-suggestions`.

## Principais decisões técnicas

Detalhadas em [research.md](research.md): R1 regra pura sobre a visão de grandes; R2 números de S1 =
soma das ligações a materiais (não o resumo da sala, que inclui envios); R3 condição do bloco por
existência de ligação; R4 posição e âncora da seção; R5 estrutura do texto (lista de definições, links
fora das frases); R6 boas práticas como três frases curtas; R7 avaliação junto da 003; R8 versão.

## Estados (resumo do contrato)

Conforme FR-020 da spec e [contracts/suggestions-ui.md](contracts/suggestions-ui.md): sem resultado,
nenhuma seção e bloco da 003; com resultado, seção com S1 ou frase neutra; G (inclusive E/F sem
arquivos) sempre frase neutra; E e F com resultado mostram S1 com a data, além do aviso da abertura.

## Riscos

- **Sugestão lida como ordem de excluir**: textos com "se quiser", possibilidades rotuladas,
  revisão de tom (FR-050).
- **Inconsistência de números entre a sugestão e a seção de grandes**: S1 usa a mesma fonte da lista
  "Materiais de grande porte por atividade"; teste compara os dois (SC-002).
- **Frase do bloco sem sugestão na página** (resultados diferentes entre leituras): bloco e página
  usam o mesmo resultado vigente e a mesma condição (existe ligação a material grande); teste cobre.

## Mapa de testes

| Requisito | Teste |
| --- | --- |
| FR-003, FR-004 (condição, números, até 3 lugares, grupos, truncado) | PHPUnit `large_materials_test` |
| FR-001, FR-002, FR-005, FR-007, FR-008, FR-020 (seção, partes, textos, estados) | PHPUnit `course_overview_test`; Behat |
| FR-010, FR-011, FR-020 (bloco) | PHPUnit `block_coursecare_test`; Behat |
| FR-030 (envios grandes sem sugestão; invisíveis sem nome) | PHPUnit `large_materials_test`, `course_overview_test` |
| FR-032, SC-005 (sem `files`; leituras do bloco; tempos) | PHPUnit; `tools/dev/bench/render.php` |
| FR-042, SC-006 (axe, teclado) | Behat `@accessibility` |
| FR-050, FR-051, FR-052 | Manuais, com evidências em `evidence/README.md` |

## Complexity Tracking

Sem itens: nenhuma violação da constituição e nenhuma abstração a justificar.
