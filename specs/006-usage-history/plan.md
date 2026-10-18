# Implementation Plan: Usage History

**Branch**: `006-usage-history` | **Date**: 2026-10-18 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/006-usage-history/spec.md` (commit `0525612`, esclarecida em
2026-10-18: Q1 A, Q2 A, Q3 A; padrões confirmados)

## Summary

Cada análise bem-sucedida da Feature 002 grava, na mesma transação em que substitui o resultado atual da
sala, um **ponto do histórico** (um por sala e dia, a última análise do dia vale) com o armazenamento
lógico, o conteúdo único, as quatro categorias, a cobertura parcial e a versão do método. A página "Sua
sala em números" ganha a seção **"Evolução dos arquivos da sua sala"**: data da última medição, uma
frase neutra de variação, um gráfico nativo do Moodle e, recolhida, a tabela com todos os dados e as
limitações. Retenção configurável (365 dias por padrão, de 90 dias a 5 anos), limpeza em lotes na tarefa
horária existente; o histórico é apagado com o resultado quando a sala deixa de ser elegível.

**Como fica simples e correto** (research R1–R9):
- nenhuma leitura de arquivos: os valores vêm do resultado que está sendo salvo;
- atomicidade e unicidade: a transação existente, o lock por sala existente e um índice único
  (sala, dia);
- nenhuma tarefa nova: a limpeza entra na tarefa horária que já remove órfãos;
- uma linha por ponto, uma consulta por sala na página;
- gráfico do próprio Moodle, uma série; categorias só na tabela.

## Technical Context

**Language/Version**: PHP 8.1–8.3 (Moodle 4.5 LTS), Mustache, CSS. JavaScript só o do gráfico do core.

**Primary Dependencies**: Moodle 4.5 core (DML, XMLDB, Task API, Admin settings, `core\chart_line` com o
Chart.js do core, `usergetmidnight`, `core_date`, Output, String). Sem bibliotecas de terceiros.

**Storage**: uma tabela nova `block_coursecare_hist` (data-model.md); um passo de atualização; uma
configuração nova `historyretention`.

**Testing**: PHPUnit (gravação e unicidade, falha e rollback, retenção, órfãos, atualização, regra pura
`history_view`, renderable, privacidade, leituras), Behat (seção nos estados, acessibilidade com axe em
desktop e celular), paridade de strings, pacote, `moodle-plugin-ci` (PostgreSQL e MariaDB). Medições com
`tools/dev/bench`. Validação visual e de tom manuais.

**Target Platform**: Moodle 4.5, PostgreSQL e MariaDB/MySQL, Boost e Classic, desktop e celular.

**Project Type**: plugin de bloco do Moodle (`block_coursecare`), versão 0.6.0.

**Performance Goals**: página com histórico completo (365 pontos) em até 2 s em condições normais
(SC-003); gravação do ponto ≤ 5% do tempo de análise (SC-004); volume ≤ 1 ponto por sala e dia da
retenção.

**Constraints**: nenhuma consulta a `files` para o histórico; nenhum dado por estudante, arquivo ou
atividade; nenhuma interpolação; nenhum ponto de análise que falhou; nada da Feature 005 no histórico;
bloco inalterado.

**Scale/Scope**: 5.000 salas × 365 dias ≈ 1,8 milhão de linhas (≈ 0,3 GB estimados); 1 tabela, 1
classe pura, extensões no coletor, na tarefa, no renderable e nos templates, cerca de 25 strings.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Princípio | Avaliação | Situação |
| --- | --- | --- |
| I. Educação, não fiscalização | Frase neutra com valores reais; frase do que a variação não significa sempre visível; nenhuma causa, meta, alerta, comparação ou ranking. | ✅ |
| II. Simplicidade | Bloco inalterado (nível 1); seção com uma frase, um gráfico de uma série (nível 2); tabela e limitações recolhidas (nível 3). | ✅ |
| III. Recomendações | Nenhuma recomendação nova; retenção configurável pela administração. | ✅ |
| IV. Honestidade | Só Armazenamento lógico e Conteúdo único, rotulados; sem soma entre categorias diferentes; datas de cada medição; sem interpolação nem passado inventado; mudança de método indicada e não comparada; cobertura parcial marcada. | ✅ |
| V. Integração nativa | DML/XMLDB, Task API existente, configuração do admin, gráficos do core. | ✅ |
| VI. Desempenho | Gravação dentro da análise em segundo plano; página com uma consulta indexada e limitada; limpeza em lotes; volume avaliado (R5) e medido (SC-004). | ✅ (medição na implementação) |
| VII. Segurança e privacidade | Mesmo acesso da página; só totais da sala; nenhum dado pessoal guardado (`null_provider` continua correto); envios só como total da sala. | ✅ |
| VIII. Internacionalização | Frases completas, en e pt_br; datas com `userdate`, tamanhos com `diagnostic_line::size()`. | ✅ |
| IX. Qualidade e camadas | `$DB` só no coletor (e em `db/upgrade.php`); regra pura em `local\analysis\history_view`; apresentação em `output`; PHPUnit e Behat. | ✅ |
| X. Experiência visual | Mesma linguagem da página; gráfico discreto; sem cores de alerta; validação visual com aprovação. | ✅ |
| XI. Evolução incremental | Só os indicadores existentes; sem agregações múltiplas, exportações ou painéis. | ✅ |
| XII. Software livre | GPL, README e CHANGES 0.6.0. | ✅ |

**Resultado do gate (pré-pesquisa e pós-design)**: **PASSA**, sem violações e sem Complexity Tracking.

### Registro de tratamento de dados (Princípio VII)

| Item | Decisão |
| --- | --- |
| Dados guardados | Por sala e dia: totais do armazenamento lógico, conteúdo único, quatro categorias, cobertura parcial, versão do método, data da análise. |
| Dados pessoais | Nenhum (nenhum id de usuário, nome, arquivo ou atividade). |
| Retenção | 365 dias por padrão, de 90 dias a 5 anos (configuração do site); limpeza horária em lotes. |
| Remoção | Com o resultado atual quando a sala deixa de ser elegível; na desinstalação. |
| Privacy API | `null_provider` mantido (nenhum dado pessoal). |

## Project Structure

### Documentation (this feature)

```text
specs/006-usage-history/
├── plan.md, research.md, data-model.md, quickstart.md
├── contracts/
│   ├── history-store.md   # gravação, leitura, limpeza, atualização
│   ├── history-view.md    # regra pura da visão
│   └── history-ui.md      # seção da página, strings
├── checklists/requirements.md
└── tasks.md
```

### Source Code (repository root)

```text
db/install.xml, db/upgrade.php                 # tabela block_coursecare_hist; passo 0.6.0 com o primeiro ponto
settings.php                                   # historyretention
classes/local/collector/diagnostic_store.php   # ponto no save_result; get_history; purge_history; delete_for_course
classes/local/analysis/history_view.php        # NOVO: regra pura (variação, método, dias, pontos)
classes/task/queue_analyses.php                # limpeza da retenção
classes/output/course_overview.php             # dados da seção e do gráfico
templates/overview_history.mustache            # NOVO
templates/overview.mustache                    # inclui a seção
styles.css, lang/en, translations/pt_br, version.php (0.6.0), README.md, CHANGES.md
tests/collector/diagnostic_store_test.php      # + histórico
tests/analysis/history_view_test.php           # NOVO
tests/task/queue_analyses_test.php             # + retenção e órfãos
tests/upgrade_history_test.php                 # NOVO: primeiro ponto da atualização (lógica extraída para função testável)
tests/output/course_overview_test.php          # + seção
tests/generator/lib.php, behat generator       # + pontos do histórico
tests/behat/block_coursecare_history.feature   # NOVO
tools/dev/bench/                               # medição de volume e de tempo
```

## Fluxo

Análise (002) → `save_result()` (transação: resultado + ponto do dia) → página: `course_overview::for_course()`
→ `diagnostic_store::get_history($courseid, limite da retenção)` → `history_view::build()` →
frases, gráfico (`core\chart_line`) e tabela → template. Tarefa horária: órfãos (resultado + histórico)
→ limpeza da retenção.

## Riscos

- **Gráfico com muitos dias em tela pequena**: rótulos do eixo reduzidos pelo Chart.js; tabela
  equivalente; validação a 320 px.
- **Dia no fuso do site × fuso do professor**: a data mostrada é a da análise, no fuso do professor; o
  agrupamento por dia usa o fuso do site (documentado).
- **Atualização em instalações grandes**: o passo de atualização lê os resultados em lotes; medido com
  5.000 salas.

## Complexity Tracking

Sem itens.
