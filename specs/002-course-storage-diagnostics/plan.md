# Implementation Plan: Course Storage Diagnostics

**Branch**: `002-course-storage-diagnostics` | **Date**: 2026-10-16 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/002-course-storage-diagnostics/spec.md` (commit
`cbb6de1`, clarificada, checklist 16/16)

**Constituição**: 1.2.0 · **Pesquisa**: [research.md](research.md) · **Dados**:
[data-model.md](data-model.md) · **Contratos**: [contracts/](contracts/) · **Benchmark**:
[benchmark.md](benchmark.md) · **Validação**: [quickstart.md](quickstart.md)

## Summary

Introduzir o primeiro diagnóstico de armazenamento do `block_coursecare` para as salas que têm o
bloco. O processamento é inteiramente em segundo plano:
- uma **tarefa agendada horária** escolhe até 500 salas devidas e enfileira uma **tarefa ad hoc por
  sala**;
- cada tarefa ad hoc obtém um lock da sala e faz **cinco consultas agregadas** sobre os metadados de
  `files` (sem File API de leitura, sem sincronizar referências, sem ler conteúdo);
- ela classifica os grupos por uma **tabela de regras explícita** e grava o resultado vigente em
  **uma transação**.

O bloco lê um registro por índice e mostra **uma linha**: o volume registrado da sala e a data, ou
o estado (não disponível, em preparação, desatualizado, atualização não concluída, sem arquivos).
Categorias, atividades, arquivos grandes, referências e o agregado de backup e lixeira ficam
persistidos para as Features 003 e 004 e são verificáveis por testes e por uma CLI administrativa.

## Technical Context

**Language/Version**: PHP 8.1–8.3 (faixa do Moodle 4.5), sem recursos posteriores ao 8.1. Mustache.

**Primary Dependencies**: Moodle 4.5 (`requires = 2024100700`, `supported = [405, 405]`). APIs
core: Block, Task (agendada e ad hoc), Lock, DML/XMLDB, Admin settings, String, Output, Privacy.
Sem bibliotecas de terceiros.

**Storage**: 4 tabelas próprias (`block_coursecare_diag`, `_cat`, `_act`, `_large`), só com o
resultado vigente; leitura de `files`, `files_reference`, `repository_instances`, `repository`,
`context` e `block_instances`. Sem cache MUC (R14).

**Testing**: PHPUnit (`advanced_testcase`, File API real e geradores core), Behat (estados no bloco,
permissões, acessibilidade), `moodle-plugin-ci` completo, benchmark com dados sintéticos
([benchmark.md](benchmark.md)).

**Target Platform**: Moodle 4.5 LTS com PostgreSQL ou MariaDB/MySQL (todos os bancos do core via
DML), cron a cada minuto, qualquer sistema de armazenamento de arquivos suportado.

**Project Type**: plugin Moodle do tipo bloco (repositório = raiz do plugin).

**Performance Goals**: SC-002 (≤ 50 ms a mais na página, nenhuma consulta a `files`), SC-003 (50.000
registros em ≤ 5 min, memória sem crescimento proporcional), SC-004 (5.000 salas elegíveis em
≤ 24 h). Metas a **medir**, não declaradas.

**Constraints**: nenhuma análise durante requisição; nenhuma alteração ou leitura de conteúdo de
arquivos; nenhuma sincronização de referência; nenhum dado pessoal persistido; sem fila própria;
sem alteração do core.

**Scale/Scope**: até milhares de salas elegíveis e dezenas de milhares de registros por sala;
5 consultas por análise; 3 configurações; 2 tarefas; 1 CLI; cerca de 15 strings novas.

Nenhum item está como NEEDS CLARIFICATION: todos foram resolvidos em [research.md](research.md).

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Princípio | Avaliação | Situação |
| --- | --- | --- |
| I. Educação, não fiscalização | Nenhuma alteração de arquivo ou atividade (FR-040, SC-010); sem avaliação, alerta, ranking ou notificação; o tamanho grande é informativo (FR-015c). | ✅ |
| II. Simplicidade | Bloco com uma linha e no máximo um número (nível 1). Nenhum termo de infraestrutura nas strings (SC-008). Detalhe só na CLI de administrador. | ✅ |
| III. Recomendações | Não há recomendações. O limite de grande porte é configurável, com padrão neutro documentado. | ✅ N/A |
| IV. Honestidade dos indicadores | Exibe só Armazenamento lógico, rotulado como "registrado pelo Moodle", com data em todos os estados com número; calcula Conteúdo único sem exibir; nenhum consumo físico, potencial ou economia; ausência ≠ zero; referências e backup/lixeira à parte; metodologia em [contracts/indicators.md](contracts/indicators.md). | ✅ |
| V. Integração nativa | Task API, Lock API, DML/XMLDB, Block API, settings, String e Output API. Consultas portáveis. Sem core patch, sem dependência de tema. A File API é lida pela tabela que ela mantém, sem efeitos colaterais (R2). | ✅ |
| VI. Desempenho | Página: uma leitura por índice. Cálculo: tarefas ad hoc por sala, lote de 500 por hora, concorrência 2, falha isolada por sala. Custo das consultas avaliado (R1, R12) e medido ([benchmark.md](benchmark.md)). Nenhuma operação global síncrona. | ✅ (medição pendente na implementação) |
| VII. Segurança e privacidade | Capability da Feature 001 no contexto do bloco; sala sempre do próprio bloco; nenhuma coluna de usuário, nome ou caminho; ligações só para arquivos grandes de Materiais; log sem dado pessoal. LGPD/GDPR registrado abaixo. `null_provider` com razão revisada. | ✅ |
| VIII. Internacionalização | Strings novas (bloco, configurações, tarefas, CLI) em `lang/en` e `translations/pt_br`, com paridade; frases completas com um parâmetro; fallback nativo. | ✅ |
| IX. Qualidade e camadas | Coleta (`local\collector`, único acesso a banco) e análise (`local\analysis`, pura) introduzidas agora (C6); regras de recomendação continuam inexistentes; apresentação no bloco e no template. PHPUnit para toda regra; Behat para o bloco. | ✅ |
| X. Experiência visual | Linha de texto simples, sem cor de alerta; acessibilidade verificada pelo axe no Behat. | ✅ |
| XI. Evolução incremental | Sem cache, sem fila própria, sem hooks redundantes, sem abstração de "políticas" além de uma função de seleção isolada (FR-003c). Constantes em vez de configurações sem caso concreto. | ✅ |
| XII. Software livre | GPL v3+, cabeçalhos, README/CHANGES atualizados; sem dependências. | ✅ |

**Gates de integração**: os cinco da constituição. O CI já cobre 8.1/pgsql e 8.3/mariadb no 4.5 e
roda `savepoints` (exigido agora que há `db/upgrade.php`).

**Resultado do gate (pré-pesquisa e pós-design)**: **PASSA**, sem violações e sem itens de
Complexity Tracking.

### Registro de tratamento de dados (Princípio VII, LGPD/GDPR)

| Item | Decisão |
| --- | --- |
| Dados lidos | Metadados de `files` dos contextos da sala, inclusive de envios (tamanho, hash, componente, área, contexto, referência). `files.userid`, `filename`, `filepath`, `author` e `source` **não** são lidos. |
| Finalidade | Indicadores agregados de armazenamento por sala e atividade, para orientação educativa. |
| Necessidade e minimização | Só agregados; ligação a arquivo apenas para arquivos grandes de Materiais (conteúdo publicado por quem edita a sala); limite de 1.000 ligações por sala. |
| Dados pessoais armazenados | Nenhum. Sem identificadores de usuário, nomes ou caminhos. Agregados de envios são por atividade. |
| Retenção | Resultado vigente enquanto a sala for elegível; apagado na remoção da última instância, na exclusão da sala e na desinstalação. |
| Risco residual | Agregado de envios de uma atividade com um único estudante permite inferir o volume enviado por ele. Nesta feature ele só aparece na CLI de administrador (que já tem acesso total). A regra de exibição para professores é decisão da Feature 003 (spec, Riscos). |
| Privacy API | `null_provider`; a string `privacy:metadata` passa a dizer que o bloco guarda só medições agregadas de armazenamento por sala e atividade, sem dados pessoais. |

## Project Structure

### Documentation (this feature)

```text
specs/002-course-storage-diagnostics/
├── spec.md                  # Especificação clarificada
├── plan.md                  # Este arquivo
├── research.md              # Fase 0: decisões com evidência do código 4.5
├── data-model.md            # Fase 1: tabelas, estados, transições
├── benchmark.md             # Plano de benchmark reproduzível
├── quickstart.md            # Fase 1: validação ponta a ponta
├── contracts/
│   ├── coverage-matrix.md   # Tabela de regras de classificação, com evidência
│   ├── indicators.md        # Metodologia e forma das consultas
│   ├── diagnostic-api.md    # Camadas, API interna, tarefas, callbacks, configurações
│   ├── block-ui.md          # Linha de estado no bloco
│   └── admin-cli.md         # CLI administrativa
├── checklists/requirements.md
├── evidence/                # Criado na implementação (benchmark, capturas, logs)
└── tasks.md                 # Fase 2 (/speckit-tasks)
```

### Source Code (repository root)

Novos itens marcados com `+`, alterados com `~`. Itens com ✗ ficam fora do pacote.

```text
.
├── version.php                          ~ nova versão (AAAAMMDDXX), release 0.2.0
├── block_coursecare.php                 ~ has_config, instance_create, instance_delete, linha de estado
├── settings.php                         + 3 configurações (contracts/diagnostic-api.md)
├── cli/
│   └── diagnostics.php                  + CLI administrativa (contracts/admin-cli.md)
├── db/
│   ├── access.php                         (inalterado)
│   ├── install.xml                      + 4 tabelas (data-model.md)
│   ├── upgrade.php                      + criação das tabelas a partir da 0.1.0, com savepoint
│   └── tasks.php                        + queue_analyses (horária, minuto R)
├── classes/
│   ├── local/
│   │   ├── collector/                   + Coleta: único acesso a banco
│   │   │   ├── course_files.php         +   Q0–Q4 (contexts, aggregates, uniques, large files)
│   │   │   ├── eligibility.php          +   seleção de salas com o bloco (FR-003c)
│   │   │   └── diagnostic_store.php     +   leitura/gravação das tabelas próprias
│   │   ├── analysis/                    + Análise: pura
│   │   │   ├── classification_rules.php +   tabela de regras (contracts/coverage-matrix.md)
│   │   │   ├── indicator_calculator.php +   agregados → resultado; invariantes
│   │   │   ├── diagnostic_result.php    +   objeto de valor do resultado
│   │   │   └── state_resolver.php       +   estado exibido (A–H)
│   │   └── course_analysis.php          + orquestração (lock, elegibilidade, frescor, gravação)
│   ├── task/
│   │   ├── analyse_course.php           + tarefa ad hoc por sala
│   │   └── queue_analyses.php           + tarefa agendada
│   ├── output/
│   │   └── diagnostic_line.php          + templatable da linha de estado
│   └── privacy/provider.php             ~ razão revisada (mesmo null_provider)
├── templates/content.mustache           ~ linha de estado; exemplo de contexto por estado
├── lang/en/block_coursecare.php         ~ strings de estado, configurações, tarefas, CLI, privacidade
├── tests/
│   ├── analysis/                        + testes puros: regras, cálculo, estados
│   ├── collector/                       + testes com File API real (CT-01–CT-13, CT-21, CT-22, CT-29–CT-35)
│   ├── task/                            + tarefas: elegibilidade, lock, falha, presos, órfãos, devidas
│   ├── block_coursecare_test.php        ~ ciclo de vida do bloco, leituras na renderização
│   ├── privacy/provider_test.php        ~ razão revisada; inspeção de colunas (SC-006)
│   └── behat/block_coursecare_diagnostic.feature  + estados no bloco, permissões, axe
├── README.md                            ~ metodologia, matriz, estados, configurações, limites
├── CHANGES.md                           ~ 0.2.0
├── translations/pt_br/block_coursecare.php  ✗ ~ novas chaves
└── tools/                               ✗
    ├── package/check.php                ~ allowlist: cli/, settings.php, db/install.xml, db/upgrade.php, db/tasks.php, classes/**
    └── dev/bench/                       + generate.php, render.php (benchmark.md)
```

**Structure Decision**: plugin único na raiz, como na Feature 001. Coleta e análise entram em
`classes/local/` com os namespaces anunciados no plano da 001. A orquestração fica fora das duas
camadas, sem regra de negócio. `classes/output/` entra agora porque o template recebe dados
derivados de estado.

## Arquitetura

```text
         ┌────────────────────── requisição de página ───────────────────────┐
         │ block_coursecare::get_content()                                   │
         │   capability (001) → diagnostic_store::get_summary(courseid)      │ 1 leitura por índice
         │   → state_resolver::resolve(registro, agora, atualidade)          │ pura
         │   → output\diagnostic_line → content.mustache                     │
         │ instance_create(): store::request() + queue_adhoc_task()          │ escrita leve
         │ instance_delete(): última instância? store::delete_for_course()   │ escrita leve
         └───────────────────────────────────────────────────────────────────┘
cron (a cada minuto)
  ├─ queue_analyses (horária): órfãos → presos → devidas (≤ 500) → request() + queue_adhoc_task()
  └─ analyse_course (ad hoc, concorrência 2, uma por sala)
        course_analysis::run(courseid)
          lock sala (0 s, 2 h) → elegível? → já fresca? → mark_running
          collector\course_files: Q0 contextos, Q1 por área, Q2 únicos por categoria,
                                  Q3 únicos por conjunto, Q4 arquivos ≥ T
          analysis\indicator_calculator (classification_rules) → invariantes (refaz 1×)
          diagnostic_store::save_result()  — uma transação
          exceção → save_failure() (resultado anterior intacto); término normal
CLI administrativa: --queue / --run / --show (mesma orquestração e mesmo lock)
```

## Principais decisões técnicas

| # | Decisão | Alternativa rejeitada | Fonte |
| --- | --- | --- | --- |
| D1 | Metadados por DML com agregação no banco; sem `stored_file` | `get_area_files()` (memória, sincronização de referências) | R2, R12 |
| D2 | Escopo por `ctx.id = :c OR ctx.path LIKE :p` | `IN (lista de contextos)` | R1 |
| D3 | Tabela de regras única, gerando também o `CASE` SQL | Classificação só em PHP sobre cada registro (memória no MariaDB) | R5, R12 |
| D4 | Agendada horária distribui ad hoc por sala | Laço numa agendada; fila própria | R8 |
| D5 | Duplicidade: status próprio + lock por sala + frescor | `checkforexisting` (sem índice, sem atomicidade) | R9 |
| D6 | Falha registrada sem relançar; nova tentativa com espera crescente controlada pela agendada | 12 tentativas nativas sem coordenação com o estado | R10 |
| D7 | Resultado gravado em uma transação; estado calculado na leitura | Gravar por partes; tarefa para "envelhecer" resultados | R10, data-model |
| D8 | Sem cache MUC | Cache do registro | R14 |
| D9 | Limpeza por `instance_delete()` + órfãos na agendada | Hooks de exclusão de curso | R7 |
| D10 | Ligação só para arquivos grandes de Materiais, até 1.000 | Ligações para todas as categorias permitidas pela FR-016 | R14 |
| D11 | `question/response_*` → Envios; `mod_*/intro` e `block_html/content` → Materiais; feedback de nota → Outros | Deixar como Não classificado | R5; aceita pelo proprietário |

## Estratégia de testes e rastreabilidade

Princípio: **File API real**. Os testes criam salas, módulos, envios e retornos pelos geradores e
APIs do core, e os arquivos pela `file_storage` nas áreas reais. Fluxos completos onde o core os
oferece: envio de tarefa pela API da tarefa, exclusão de atividade com a lixeira ativa
(`course_delete_module`) e um backup real (`backup_controller`) para CT-29/CT-30. Testes puros
(sem banco) só para regras, cálculo e estados.

| Cenário | Teste |
| --- | --- |
| CT-01–CT-08, CT-10, CT-12, CT-13 | `tests/collector/course_files_test.php` (File API real) |
| CT-09, CT-22, CT-32 (referências) | `course_files_test.php` com repositório `user` (interna) e instância de repositório externo criada pelo gerador de repositórios; sem sincronização (verificado: `files_reference.lastsync` inalterado) |
| CT-11, matriz completa | `tests/analysis/classification_rules_test.php` (todas as linhas, precedência, igualdade PHP × SQL) + `course_files_test.php` (componente fictício) |
| CT-14 (falha) | `tests/task/analyse_course_test.php` com coletor substituído que lança exceção; resultado anterior intacto; outras salas do lote analisadas |
| CT-15, CT-23, estados A–H | `tests/analysis/state_resolver_test.php` (tempo fixo) + Behat |
| CT-16 (acesso) | Behat (estudante, professor sem edição, professor de outra sala) + PHPUnit (`get_content()` usa só o curso do bloco) |
| CT-17, CT-24 | Benchmark ([benchmark.md](benchmark.md)), evidência registrada |
| CT-18 (simultaneidade) | `analyse_course_test.php` com lock já ocupado; `queue_analyses_test.php` com duas execuções |
| CT-19 | `course_files_test.php` com atividade oculta e com restrição; Behat confirma ausência de nomes |
| CT-20, CT-26, CT-27, CT-28 | `tests/block_coursecare_test.php` (exclusão de curso, remoção, desativação do tipo, sala sem bloco) + `queue_analyses_test.php` (órfãos) |
| CT-21, CT-29–CT-31, CT-33–CT-35 | `course_files_test.php` |
| CT-25 | Quickstart §1 (atualização real a partir da 0.1.0) + `savepoints` no CI |
| SC-002 | `block_coursecare_test.php` (leituras e ausência de `{files}`) + microbenchmark |
| SC-006 | `tests/privacy/provider_test.php` (colunas sem dados de usuário após CT-07 e CT-19) |
| SC-010 | `course_files_test.php`: cópia de `files` antes e depois da análise, idêntica |

## Riscos e limitações

| # | Risco ou limitação | Mitigação |
| --- | --- | --- |
| RK1 | Planos de consulta ruins em `path LIKE` no MariaDB (sem `varchar_pattern_ops`) | `EXPLAIN` no benchmark; o índice B-tree de `path` atende prefixo no InnoDB; se não atender, alternativa medida: `contextid IN` por lotes de contextos de Q0 |
| RK2 | Salas extremas (200.000+ registros) passam do tempo de execução de tarefa ad hoc (30 min por processador; não interrompe a tarefa) | Medir no perfil "extremo"; documentar limite; isolamento por sala mantém as demais |
| RK3 | Corrida rara entre `instance_create` e a agendada | Lock por sala + frescor (R9) |
| RK4 | Lista de tipos internos de repositório pode não cobrir repositórios de terceiros | Desconhecido → externo, nunca interno; documentado |
| RK5 | Áreas não classificadas em cursos com módulos fora do conjunto inicial | `partial = 1`; proporção registrada; ampliação por nova regra com teste |
| RK6 | `db_record_lock_factory` mantém lock de processo morto até expirar | Validade de 2 h; detecção de presos na agendada |
| RK7 | Inferência do volume de um estudante por agregado de atividade | Não exibido ao professor nesta feature; decisão na Feature 003 |
| RK8 | Fila de 500 por hora limita salas por dia a 12.000 | Documentado; acima disso, reanálise mais espaçada que o intervalo (resultados viram "desatualizados", nunca falsos) |
| RK9 | Benchmark com tamanhos ajustados nos dados sintéticos | Declarado na evidência; exatidão sempre com conteúdo real em PHPUnit |

## Revisão crítica

- **Adequada para 5.000 salas?** Sim, por projeção: 500 por hora na fila e 2 análises
  simultâneas. Fica pendente a medição de SC-004 (benchmark com 5.001 salas).
- **Operações custosas**: Q1–Q4 sobre salas grandes (agregação em `files` via `context.path`) e a
  ordenação implícita dos `GROUP BY`. Na página: nenhuma. Na agendada: uma consulta de elegibilidade
  com junção ao diagnóstico, limitada a 500.
- **Uma sala grande bloqueia as demais?** Não. Cada sala é uma tarefa; com concorrência 2, a outra
  vaga continua andando. Uma sala grande ocupa uma vaga enquanto dura, e sua falha não afeta as
  outras.
- **Duplicidade**: status próprio, lock por sala e frescor (R9).
- **Cron atrasado**: nada se perde nem se duplica. A seleção é por datas, a agendada roda uma vez
  ao voltar, e o bloco mostra "desatualizado" quando passa o limite. A página nunca analisa.
- **Resultados após falhas**: a transação única preserva o anterior; a falha fica registrada; nova
  tentativa limitada.
- **Dupla contagem**: impossível dentro de uma consulta. Referências e backup/lixeira ficam fora de
  A e B, e o conteúdo único é por hash. Os invariantes protegem contra alterações entre consultas.
- **Consistência**: gravação atômica; estado derivado na leitura; órfãos limpos pela agendada.
- **Dependência de tema**: nenhuma (texto simples no template da 001).
- **Moodle 4.5**: todas as APIs usadas existem e foram verificadas no 4.5.15. O plano não usa nada
  introduzido depois.

## Decisões do proprietário (2026-10-16)

1. **D11 — ampliações da matriz aceitas**: respostas de estudantes em questões como Envios
   (regra 21), descrição de qualquer módulo e bloco de texto como Materiais (regras 17 e 18) e
   feedback de nota como Outros (regra 26). São classificações comprováveis (R5), dentro da FR-025,
   sem mudar o cálculo.
2. **D10 — aceita**: ligações a arquivos grandes só para a categoria Materiais, até 1.000 por sala
   (mais restritivo que a FR-016, que permite e não exige).

Não restam pendências que bloqueiem `/speckit-tasks`.

## Critérios de conclusão

1. FR-001 a FR-055 implementados, sem itens fora do escopo.
2. CI verde (8.1/pgsql e 8.3/mariadb), inclusive `savepoints`, PHPUnit e Behat com axe.
3. Cenários CT-01 a CT-35 com teste ou evidência, conforme a tabela de rastreabilidade.
4. Benchmark executado nas duas bases, com medido e projetado separados (SC-002 a SC-004).
5. Quickstart executado (atualização da 0.1.0, estados, ciclo de vida, en e pt_BR).
6. README com metodologia, matriz, estados, configurações e limites medidos.
7. Constitution Check reavaliada sem violações.

## Complexity Tracking

Nenhuma violação a justificar.
