# Implementation Plan: Teacher Experience

**Branch**: `003-teacher-experience` | **Date**: 2026-10-17 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/003-teacher-experience/spec.md` (commit `928cd20`,
validada pelo proprietário do produto em 2026-10-17)

## Summary

A Feature 003 apresenta ao professor o diagnóstico que a Feature 002 já calcula, em dois pontos:

- **Bloco compacto**: mantém a identidade, remove a mensagem "orientações em breve" e, quando há
  resultado, acrescenta à linha da Feature 002 um link para a nova página.
- **Página "Sua sala em números"** (`overview.php`, acessada pela instância do bloco), nesta ordem:
  1. resumo (total, data, estado, cobertura);
  2. categorias da sala;
  3. materiais por atividade;
  4. arquivos de grande porte;
  5. detalhes técnicos recolhidos.

**Restrições da feature:**
- Sem tabela nova, sem coleta nova e sem JavaScript.
- Os números vêm só das tabelas da Feature 002.
- Por atividade, só o volume dos materiais (FR-030).
- A estrutura da sala vem do cache de módulos do core.

**Etapas finais:** a interface é reconstruída a partir das diretrizes visuais aprovadas, validada
visualmente pelo proprietário do produto e avaliada com cinco professores num Moodle real, antes do
encerramento.

## Technical Context

**Language/Version**: PHP 8.1–8.3 (Moodle 4.5 LTS), Mustache, CSS. Sem JavaScript novo.

**Primary Dependencies**: Moodle 4.5 core: Block, Access, Output (renderables, templates, `pix_icon`),
String, Navigation APIs e `get_fast_modinfo()`. Componentes `card` do Bootstrap 4.6.2 dos temas Boost
e Classic (Classic herda do Boost). Sem bibliotecas de terceiros.

**Storage**: tabelas existentes da Feature 002 (`block_coursecare_diag`, `_cat`, `_act`, `_large`),
somente leitura. Nenhuma tabela, campo ou configuração nova.

**Testing**: PHPUnit (builder puro, coletores, renderable, acesso, ausência de consulta a `files`),
Behat com verificação de acessibilidade (axe) e tamanhos de janela de desktop e celular,
`moodle-plugin-ci`, paridade de strings (`tools/i18n/check.php`), verificação do pacote. Validação
visual e avaliação com professores manuais, com evidências.

**Target Platform**: Moodle 4.5 (`$plugin->requires = 2024100700`), PostgreSQL e MariaDB/MySQL; temas
Boost e Classic e derivados; navegadores de desktop e celular.

**Project Type**: plugin de bloco do Moodle (`block_coursecare`), versão 0.3.0.

**Performance Goals**:
- Página de uma sala com 200 atividades com materiais em até 2 s (SC-003).
- Bloco com no máximo +50 ms em relação à 0.2.0 (SC-004).

**Constraints**:
- Nenhuma consulta a `files` na renderização (FR-040).
- Nenhum valor por atividade que inclua envios ou outros arquivos (FR-030).
- WCAG 2.2 AA (FR-050).
- Nenhum texto fixo em templates (FR-060).
- Nenhuma alteração das cores globais do tema (FR-051).

**Scale/Scope**:
- 1 página nova, 1 template de página com partes, o template do bloco alterado.
- Cerca de 40 strings novas em en e pt_br.
- Coleta: 2 métodos novos (`diagnostic_store`, `eligibility`) e 1 classe nova (`course_structure`); 1 verificação de acesso (`overview_access`), 1 classe de análise e 1 renderable.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Princípio | Avaliação | Situação |
| --- | --- | --- |
| I. Educação, não fiscalização | Sem avaliação da sala ou do professor, sem ranking, sem alerta, sem ação sobre arquivos; arquivos grandes apresentados como informação de triagem. | ✅ |
| II. Simplicidade | Nível 1 no bloco (uma frase, um número, data, link); nível 2 na página (situações medidas); nível 3 recolhido. Cada nível compreensível sozinho. As orientações do nível 2 ficam para a 004. | ✅ |
| III. Recomendações | Nenhuma recomendação. | ✅ N/A |
| IV. Honestidade dos indicadores | Armazenamento lógico e Conteúdo único rotulados e nunca somados com referências ou cópias; data em toda visão; ausência ≠ zero; cobertura parcial explicada; valores omitidos quando os dados não permitem mostrar sem engano (FR-041). | ✅ |
| V. Integração nativa | Block, Access, Output, String e Navigation APIs; `get_fast_modinfo()`; componentes do Bootstrap do core; sem dependência de tema e sem patch. | ✅ |
| VI. Desempenho | Página lê só tabelas próprias (até 5 leituras por índice) e o cache de módulos; nenhuma consulta a `files`; metas SC-003 e SC-004 medidas. | ✅ (medição na implementação) |
| VII. Segurança e privacidade | `require_login` no curso e `require_capability` no contexto da instância; sala derivada da instância; nenhuma informação individual de estudante; envios só no agregado da sala; parâmetro `id` validado com `PARAM_INT`; saída escapada pelos templates. Nenhum dado novo armazenado. | ✅ |
| VIII. Internacionalização | Toda frase no catálogo, completas e com parâmetros, en e pt_br em paridade; CourseCare / AVA Consciente. | ✅ |
| IX. Qualidade e camadas | Coleta em `local\collector`; regras de apresentação (proporções, FR-030, grupos) em `local\analysis\overview_builder`, pura; apresentação em `output` e templates. PHPUnit para toda regra; Behat para os fluxos. | ✅ |
| X. Experiência visual | Diretrizes aprovadas (verdes suaves e neutros, cantos arredondados, broto, cartões discretos), componentes do Moodle, WCAG 2.2 AA, sem ludicidade infantil; reconstrução e aprovação visual (FR-073). | ✅ |
| XI. Evolução incremental | Sem abstrações novas; `<details>` nativo em vez de componente próprio; nada da 004 a 006. | ✅ |
| XII. Software livre | GPL v3+, cabeçalhos, README e CHANGES atualizados; sem dependências. | ✅ |

**Resultado do gate (pré-pesquisa e pós-design)**: **PASSA**, sem violações e sem itens de
Complexity Tracking.

### Registro de tratamento de dados (Princípio VII, LGPD/GDPR)

| Item | Decisão |
| --- | --- |
| Dados lidos | Resultado da Feature 002 da sala (agregados por sala, categoria e atividade; ligações a materiais grandes: atividade e tamanho) e estrutura da sala do Moodle (nome, link e visibilidade das atividades para o usuário atual). |
| Dados exibidos | Totais e categorias da sala; por atividade, só o volume dos materiais e os materiais de grande porte. Nenhum nome de estudante ou de arquivo. |
| Dados armazenados | Nenhum dado novo. |
| Finalidade e necessidade | Compreensão do armazenamento da própria sala pelo professor; por atividade, só o que vem de quem edita a sala (materiais). |
| Risco residual | Numa sala com uma única atividade com envios, ou com um único estudante, o agregado de envios da sala coincide com o da atividade ou do estudante. Aceito pelo proprietário do produto; quem vê a página já acessa os envios pelo Moodle. |
| Privacy API | `null_provider` mantido; a razão (`privacy:metadata`) continua exata, porque nada novo é armazenado. |

## Project Structure

### Documentation (this feature)

```text
specs/003-teacher-experience/
├── spec.md
├── plan.md              # este arquivo
├── research.md          # Fase 0
├── data-model.md        # Fase 1: visão da sala (estrutura exibida, sem persistência)
├── quickstart.md        # Fase 1: validação de ponta a ponta, visual e com professores
├── contracts/
│   ├── block-ui.md      # bloco compacto (estende o da Feature 002)
│   ├── overview-page.md # página: acesso, seções, estados, acessibilidade
│   └── overview-data.md # entrada e saída do overview_builder, regra FR-030
├── evaluation/          # criado na implementação: roteiro da avaliação com professores
├── evidence/            # criado na implementação: capturas, avaliações, aprovações
├── checklists/requirements.md
└── tasks.md             # /speckit-tasks
```

### Source Code (repository root)

```text
blocks/coursecare/                       # raiz do repositório
├── block_coursecare.php                 # diagnostic_line com link; sem intro
├── overview.php                         # NOVO: página "Sua sala em números"
├── classes/
│   ├── local/overview_access.php        # NOVO: verificação de acesso da página (ordem do contrato)
│   ├── local/collector/
│   │   ├── diagnostic_store.php         # + get_large_materials_by_activity()
│   │   ├── eligibility.php              # + get_course_instance()
│   │   └── course_structure.php         # NOVO: atividades da sala via get_fast_modinfo()
│   ├── local/analysis/
│   │   └── overview_builder.php         # NOVO, puro: proporções, grupos, FR-030, estados
│   └── output/
│       ├── diagnostic_line.php          # + URL da página quando há resultado
│       └── course_overview.php          # NOVO: renderable/templatable da página
├── templates/
│   ├── content.mustache                 # bloco: sem intro, com link
│   ├── overview.mustache                # NOVO: página
│   ├── overview_summary.mustache        # NOVO (partial): resumo e estados
│   ├── overview_categories.mustache     # NOVO (partial)
│   ├── overview_activities.mustache     # NOVO (partial): 10 + <details> "Mostrar todas"
│   ├── overview_large.mustache          # NOVO (partial)
│   └── overview_technical.mustache      # NOVO (partial): <details> recolhido
├── styles.css                           # estilos escopados (.block_coursecare, .block_coursecare-overview)
├── lang/en/block_coursecare.php         # + strings da página; − intro
├── translations/pt_br/block_coursecare.php
├── version.php                          # 0.3.0
├── tests/
│   ├── analysis/overview_builder_test.php
│   ├── collector/course_structure_test.php
│   ├── collector/diagnostic_store_test.php     # + agrupamento dos grandes
│   ├── output/course_overview_test.php         # renderização, sem files, sem nomes
│   ├── overview_access_test.php                # acesso por papel e por instância
│   ├── behat/block_coursecare_overview.feature
│   └── generator/                              # + categorias, atividades e ligações
├── tools/dev/demo/setup.php             # NOVO (fora do pacote): salas fictícias por estado
└── tools/dev/bench/render.php           # + --page
```

**Structure Decision**: plugin único, camadas da constituição. A página é um script na raiz do bloco,
padrão de páginas próprias de plugins do Moodle. Nada novo em `db/`: sem upgrade de esquema; a
versão sobe para 0.3.0 por causa dos templates e strings.

## Fluxos

```text
Página da sala
  block_coursecare::get_content()
    diagnostic_store::get_summary(courseid)          ← 1 leitura (igual à 0.2.0)
    state_resolver → diagnostic_line(+URL se há resultado) → content.mustache

overview.php?id=<instância>
  required_param(id, PARAM_INT) → overview_access::require_access(id):
    eligibility::get_course_instance (blockname coursecare) → contexto pai = curso ≠ site
    → require_login(curso) → require_capability(block/coursecare:view, contexto do bloco)
  diagnostic_store: get_summary, get_categories, get_activities, get_large_materials_by_activity
  course_structure: atividades (nome formatado, URL, uservisible) — cache de módulos do core
  state_resolver → overview_builder (puro) → course_overview → overview.mustache (+ partials)
```

## Principais decisões técnicas

| # | Decisão | Alternativa rejeitada | Fonte |
| --- | --- | --- | --- |
| D1 | Página por instância do bloco, capability no contexto do bloco | `?course=` e busca da instância | R1 |
| D2 | Regras de apresentação e FR-030 numa classe pura | Calcular no script ou no template | R2 |
| D3 | Por atividade, só `materialbytes` e grandes das ligações de materiais | Total da atividade (revela envios) | R3 |
| D4 | `<details>` nativo para detalhes técnicos e "Mostrar todas" | Bootstrap collapse ou AMD | R4 |
| D5 | `card` do core + CSS escopado com propriedades próprias | SCSS de tema, cores inline | R5 |
| D6 | Proporção em texto + barra decorativa; "menos de 1%" | Gráfico de pizza; só texto | R6 |
| D7 | Bloco sem `intro`, link só com resultado | Botão; link em todos os estados | R7 |
| D8 | Site de demonstração reproduzível para validação e avaliação | Dados de benchmark; maquetes | R8 |

## Estados (resumo do contrato)

O estado vem do `state_resolver` da Feature 002. A tabela completa de bloco e página está em
[contracts/overview-page.md](contracts/overview-page.md) e reproduz FR-020: sem resultado (A, B, C,
F sem resultado) só mensagem e sem números; D, E, F com resultado e H com todas as seções e o aviso
do estado; G com o resumo sem arquivos e os detalhes técnicos.

## Riscos

| # | Risco | Mitigação |
| --- | --- | --- |
| RK1 | A página parecer um painel administrativo | Diretrizes visuais, pouca informação por vez, seções recolhidas, reconstrução com aprovação (FR-073) e avaliação com professores (FR-071) |
| RK2 | Vazamento de envios por atividade por um valor derivado | Regra FR-030 no `overview_builder` com teste que compara todo valor exibido por atividade com `materialbytes` (CT-14) |
| RK3 | Nomes longos de áreas de texto e mídia (o nome vem do conteúdo) | Nome formatado do core, com quebra de linha; sem link quando a atividade não tem URL |
| RK4 | Contraste dos verdes suaves | Paleta em propriedades escopadas, conferida na reconstrução (4,5:1 texto, 3:1 gráfico) e pelo axe |
| RK5 | Diferenças entre Boost e Classic | Componentes do core, validação nos dois temas (FR-070) |
| RK6 | Avaliação com professores atrasar o encerramento | Ambiente de demonstração pronto antes; roteiro curto (FR-071); o proprietário do produto organiza o recrutamento |

## Mapa de testes

| Requisito / cenário | Onde |
| --- | --- |
| CT-01 a CT-03 (bloco por estado, link) | PHPUnit `tests/block_coursecare_test.php`; Behat |
| CT-04, CT-05, CT-21 (valores, estados, menos de 1%) | PHPUnit `overview_builder_test`, `course_overview_test`; Behat |
| CT-06 (navegação) | Behat |
| CT-07, CT-08, CT-09, CT-22 (lista, grupos, limite de ligações) | PHPUnit `overview_builder_test`, `course_structure_test` |
| CT-10, CT-14 (grandes; FR-030) | PHPUnit `overview_builder_test`, `course_overview_test` |
| CT-11 (detalhes técnicos) | PHPUnit; Behat (recolhido por padrão) |
| CT-12, CT-13 (acesso) | PHPUnit `overview_access_test`; Behat |
| CT-15, SC-006 (nomes e termos) | PHPUnit `course_overview_test`; revisão de tom |
| CT-16, CT-17 (teclado, 320 px, axe) | Behat com acessibilidade e tamanho de celular; verificação manual de teclado e leitor de tela no quickstart |
| CT-18 (pt_BR e fallback) | Behat em pt_BR com a tradução; PHPUnit de paridade; quickstart sem tradução |
| CT-19 (temas) | Validação visual (FR-070) |
| CT-20, SC-003, SC-004 (sem `files`, tempo) | PHPUnit (`set_debug`); `tools/dev/bench/render.php --page` |
| CT-23, CT-24 (avaliação, validação visual) | Manual, `evaluation/` e `evidence/` |

## Complexity Tracking

Nenhuma violação a justificar.
