# Benchmark da Feature 002 (T041)

Plano: [`../benchmark.md`](../benchmark.md). Dados brutos: [`logs/benchmark/`](logs/benchmark/).
Tudo o que aparece aqui foi **medido**, salvo quando marcado como **projeção**.

| Meta | Situação |
| --- | --- |
| SC-003 / CT-17: sala de 50.000 registros em ≤ 5 min, memória sem crescimento proporcional | **Atendida** nas duas bases (medida em 2026-10-16) |
| SC-004 / CT-24: 5.001 salas concluídas em ≤ 24 h com o cron real | **Atendida** nas duas bases: 9 h 21 min (PostgreSQL) e 9 h 09 min (MariaDB) |
| SC-002: renderização do bloco, 0.1.0 × 0.2.0 | **Atendida**: +0,8 ms por renderização (limite 50 ms); nenhuma consulta a `files` |
| Concorrência (`--run` simultâneo, `--queue --all` duas vezes) | **Atendida** (site de quickstart, 0.2.0) |
| Exatidão por amostragem (20 salas por perfil) | **Atendida**, além da amostra: as 5.001 salas conferem com o manifesto nas duas bases |

## Ambiente

- Hospedeiro: Apple M5 Pro (15 núcleos, 24 GB). VM do Docker: 4 CPUs, 5,8 GB, Ubuntu 24.04.4,
  Docker 29.5.2. Um stack `moodle-docker` descartável por base, mesma árvore do plugin
  (commit `2501d77`).
- Moodle 4.5.15 (Build 20261005), PHP 8.1.34.
- PostgreSQL 17.11, configuração padrão do container (`shared_buffers` 128 MB, `work_mem` 4 MB).
- MariaDB 11.4.13, configuração padrão (`innodb_buffer_pool_size` 128 MB, estatísticas persistentes
  com recálculo automático).
- `task_adhoc_concurrency_limit` = 3, `task_logmode` = 1 (padrão). Pré-verificação em
  `logs/benchmark/<base>/precheck.json`.

## Dados

5.001 salas com o bloco e 5.731.758 registros em `files` em cada base, gerados com
`tools/dev/bench/generate.php` (sementes fixas, script `logs/benchmark/bench-generate.sh`):

| Perfil | Salas | Registros por sala | Atividades | Observação |
| --- | --- | --- | --- | --- |
| pequeno | 4.500 | 10–50 | — | 9 lotes de 500 |
| médio | 449 + 1 de 5.000 | 1.000–5.000 | 20 (sala de 5.000) | Materiais, Envios e Outros; cerca de 25% dos registros repetem um conteúdo |
| grande | 48 + 1 de 20.000 + 1 de 50.000 | 20.000–50.000 | 200 (salas de 20.000 e 50.000) | Igual ao médio, com arquivos grandes (≥ 50 MB) |
| extremo | 1 | 200.000 | 200 | Para observar o limite, sem meta |

**Desvio declarado do plano (modo `metadata`)**: o plano previa a File API real para todos os
arquivos. Milhões de conteúdos excedem os inodes da VM do Docker (cerca de 2,4 milhões livres para
3,4 milhões necessários por base), então os registros foram inseridos diretamente em `files`, com
hash de conteúdo sintético, `pathnamehash` real e o registro de diretório "." que a File API cria
para cada área e item. A análise lê só metadados, então o custo das consultas é o mesmo; os testes
de exatidão (PHPUnit) usam sempre a File API real. Diferença residual conhecida: os registros "."
destes dados têm `mimetype` preenchido (a File API grava nulo); a coluna não é lida por nenhuma
consulta medida, e os dados não foram regenerados (o gerador já foi corrigido).

**Representatividade**: cerca de um registro de diretório por arquivo nas salas grandes (cada
arquivo em seu próprio item), o que dobra as linhas lidas por `contextid` e é pessimista. Arquivos
grandes nunca repetem conteúdo nestes dados (a repetição de conteúdo grande está coberta pelo
PHPUnit, CT-34).

Integridade antes da medição: para as 4 salas do SC-003, a contagem direta de registros (sem ".")
coincide com o manifesto nas duas bases; estatísticas atualizadas pelo próprio banco (PostgreSQL:
autoanalyze às 22:20 UTC, logo após a geração; MariaDB: cardinalidades persistentes de
`m_files` ≈ 5,64 milhões).

## SC-003: análise de uma sala grande

Método (script `logs/benchmark/bench-sc003.sh`): para cada tamanho, reinício do container do
banco, 3 execuções de `cli/diagnostics.php --run --json` (a primeira com o cache do banco frio) e
uma execução de `tools/dev/bench/measure.php --explain`. **Limitação**: o reinício do container
esvazia o cache do banco, não o cache de páginas do sistema operacional da VM; o "frio" é frio para
o banco, não para o disco.

Duração da análise completa (coleta, cálculo, invariantes e gravação) e pico de memória do PHP:

| Registros | PostgreSQL 1ª / 2ª / 3ª (s) | MariaDB 1ª / 2ª / 3ª (s) | Pico PG | Pico MariaDB |
| --- | --- | --- | --- | --- |
| 5.000 | 0,044 / 0,016 / 0,017 | 0,067 / 0,035 / 0,035 | 14,3 MB | 14,5 MB |
| 20.000 | 0,059 / 0,057 / 0,054 | 0,243 / 0,158 / 0,163 | 14,4 MB | 14,6 MB |
| **50.000** | **0,127 / 0,122 / 0,134** | **0,568 / 0,392 / 0,388** | **14,5 MB** | **14,7 MB** |
| 200.000 | 0,516 / 0,489 / 0,503 | 3,855 / 3,350 / 3,355 | 16,0 MB | 16,2 MB |

- **Tempo**: 50.000 registros em no máximo 0,57 s (limite: 300 s). **Atendido.**
- **Memória** (maior pico das 3 execuções): pico de 50.000 / pico de 5.000 = 1,02 nas duas bases
  (limite: 1,5). Mesmo com 200.000 registros, 1,12 nas duas. **Atendido.**

Tempo de cada consulta (`measure.php`, cache quente, segundos), na ordem da análise:

| Registros | Base | Q0 contextos | Q1 por área | Q3 únicos por conjunto | Q2 únicos por categoria | Q4 grandes |
| --- | --- | --- | --- | --- | --- | --- |
| 50.000 | PostgreSQL | 0,0004 | 0,023 | 0,031 | 0,038 | 0,013 |
| 50.000 | MariaDB | 0,0005 | 0,135 | 0,123 | 0,147 | 0,046 |
| 200.000 | PostgreSQL | 0,0003 | 0,096 | 0,152 | 0,176 | 0,063 |
| 200.000 | MariaDB | 0,0006 | 0,659 | 1,206 | 1,468 | 0,490 |

Linhas devolvidas a 50.000 registros: Q0 202 contextos, Q1 200 grupos, Q3 1, Q2 3, Q4 527
arquivos grandes. Nenhuma das 5 consultas escreve em `files` (verificado também pelo PHPUnit, SC-010).

### Planos de execução

Planos completos em `logs/benchmark/<base>/sc003-<tamanho>-measure.json`.

- **PostgreSQL** (`EXPLAIN (ANALYZE, BUFFERS)`): em Q1 a Q4, `m_context` por `BitmapOr` de
  `m_cont_id_pk` e `m_cont_pat_ix_pattern` (o índice `varchar_pattern_ops` de `path`, que atende ao
  `LIKE '/1/3/<id>/%'`), seguido de `Index Scan using m_file_con2_ix` (`files.contextid`) em laço
  aninhado. Nenhuma varredura sequencial de `m_files`; as únicas varreduras sequenciais são das
  tabelas de repositório (8 linhas) e de `files_reference` (vazia). A 50.000 registros, Q1 lê
  53.960 páginas, todas do cache (`shared hit`), em 29,7 ms.
- **MariaDB** (`ANALYZE`): em Q1 a Q4, `m_context` por `index_merge` de `PRIMARY` e
  `m_cont_pat_ix`, seguido de `ref` em `m_file_con2_ix`. A varredura `ALL` que aparece em Q2 e Q3 é
  sobre a tabela derivada da própria consulta (resultado intermediário), não sobre `m_files`.
- **Observação a 200.000 registros (PostgreSQL)**: a agregação por hash de Q2 e Q3 excede o
  `work_mem` padrão de 4 MB e usa cerca de 12 MB de disco temporário (5 lotes), sem impedir a análise
  em 0,5 s. A 50.000 registros não há uso de disco.

### Exatidão

Para as 4 salas, o número de registros, A (armazenamento lógico) e B (conteúdo único) gravados
coincidem exatamente com o manifesto do gerador, nas duas bases, e as duas bases gravam valores
idênticos. Exemplo (50.000 registros): A = 157.849.100.166 bytes, B = 157.842.589.948 bytes, 37.622
conteúdos únicos, 200 atividades, 527 arquivos grandes e 269 ligações (só Materiais). A sala de
200.000 registros atinge o limite de 1.000 ligações (D10).

Depois do SC-003, os diagnósticos dessas 4 salas foram apagados, para que o SC-004 comece com
nenhuma sala analisada (`diagnostics left: 0` nas duas bases).

## SC-002: renderização do bloco

Site de quickstart (`coursecare-qs`, PostgreSQL 17.11, mesma versão do Moodle), sala QS-A com 2
arquivos. Primeiro a 0.1.0 (`git archive main`), depois a atualização para a 0.2.0 (`f59412b`) no
mesmo site, com a sala analisada (estado D). Para cada versão, 3 execuções de
`tools/dev/bench/render.php --iterations=1000` como administrador (`logs/benchmark/qs/sc002-*.json`):

| Versão | Média por renderização (ms), execuções 1 / 2 / 3 | Leituras por renderização |
| --- | --- | --- |
| 0.1.0 | 0,225 / 0,050 / 0,047 | 0 |
| 0.2.0 | 0,913 / 0,786 / 0,847 | 4 |

- **Diferença**: cerca de 0,8 ms por renderização (com cache quente: 0,786 − 0,050 e
  0,847 − 0,047). Limite: 50 ms. **Atendido.**
- **Leituras**: das 4, só uma é do bloco (o registro do diagnóstico da sala, por índice único). As
  outras 3 vêm do próprio `render.php`, que cria um `moodle_page` novo a cada iteração: a primeira
  chamada de `get_renderer('core')` numa página carrega os filtros ativos (verificado: uma segunda
  chamada na mesma página não faz consulta). Numa página real o renderizador já existe.
- **Sem consulta a `files`**: verificado pelo PHPUnit em todos os estados A a H, com o mesmo número
  de leituras em todos (`test_render_reads_no_files`).
- **Limitação**: a medição rodou enquanto o SC-004 usava a mesma VM (cron das duas bases); a carga
  afeta as duas versões do mesmo modo e não altera a conclusão diante do limite de 50 ms.

## Concorrência

Site de quickstart (0.2.0), sala com 200.000 registros gerados (`--mode=metadata`); códigos de saída
lidos com `docker exec`, sem filtros (`logs/benchmark/qs/concurrency-*.txt`):

- **Lock ocupado por outro processo** (o mesmo lock por sala, mantido por 12 s): `--run` termina em
  0,002 s com `locked` e código 3; depois da liberação, a análise roda (código 0).
- **Duas execuções simultâneas de `--run`** da mesma sala, 5 rodadas: em todas, exatamente uma
  analisa (código 0, cerca de 0,6 s) e a outra termina com código 3.
- **`--queue --all` duas vezes seguidas**: a primeira solicita 22 análises (as 22 salas elegíveis do
  site), a segunda 0; ficam 22 tarefas ad hoc, exatamente uma por sala, e 22 registros na fila.

## SC-004: 5.001 salas com o cron real

Método (script `logs/benchmark/bench-sc004.sh`): com as 5.001 salas sem diagnóstico (os 4 do SC-003
apagados), `admin/cli/cron.php` a cada 60 s em cada base, como o Moodle recomenda, até todas as salas
terem diagnóstico concluído e nenhuma solicitação pendente. Configuração padrão: `queue_analyses`
de hora em hora num minuto sorteado (:40 no PostgreSQL, :28 no MariaDB), lote de 500, concorrência 2
de `analyse_course`, `task_adhoc_concurrency_limit` 3. As duas bases rodaram **ao mesmo tempo, na
mesma VM** (4 CPUs); a interferência possível é declarada e não altera a conclusão, porque a duração
é determinada pelo lote horário, não pela CPU. Código em uso: `f59412b`; os commits seguintes
(`f3113f1`, `1cdb3e0`) não tocam o caminho do cron (só a CLI administrativa, `measure.php`, testes e
documentos). Durante a execução também rodaram, na mesma VM, o SC-002, a concorrência (site de
quickstart) e o PHPUnit (site de desenvolvimento).

Relatório (`logs/benchmark/<base>/sc004-report.json`, gerado por `sc004-report.php.txt` (script PHP temporário, guardado como texto) a partir de
`task_log` e das tabelas do plugin), progresso por minuto em `sc004-progress.csv` e saída completa do
cron em `sc004-cron.log.gz`:

| Medida | PostgreSQL | MariaDB |
| --- | --- | --- |
| Salas elegíveis | 5.001 | 5.001 |
| Diagnósticos concluídos | 5.001 | 5.001 |
| Salas elegíveis sem diagnóstico / diagnósticos de salas não elegíveis | 0 / 0 | 0 / 0 |
| Início → última sala concluída | 23:19:09 → 08:40:02 UTC (**9 h 21 min**) | 23:19:09 → 08:28:05 UTC (**9 h 09 min**) |
| Vazão | 500 salas por execução horária (11 execuções: 10 × 500 e 1 × 1); média de 535 salas/h no período | igual por execução; média de 547 salas/h no período |
| Falhas de análise, tarefas com erro no `task_log`, novas tentativas | 0, 0, 0 | 0, 0, 0 |
| Salas analisadas mais de uma vez | 0 | 0 |
| Análises simultâneas (máximo) / sobreposição da mesma sala | 2 / 0 | 2 / 0 |
| `queue_analyses`: execuções, duração máxima | 11, 0,25 s | 11, 0,29 s |
| Linhas "task failed" de qualquer componente na saída do cron | 0 | 0 |

Duração de cada tarefa de análise (`timeend − timestart` do `task_log`, em segundos):

| Perfil | Salas | PostgreSQL média / mediana / p95 / p99 / máx. | MariaDB média / mediana / p95 / p99 / máx. |
| --- | --- | --- | --- |
| Todas | 5.001 | 0,007 / 0,0045 / 0,011 / 0,089 / 0,96 | 0,013 / 0,0034 / 0,031 / 0,276 / 4,53 |
| pequeno | 4.500 | 0,0046 / 0,0045 / 0,0053 / 0,0084 / 0,021 | 0,0036 / 0,0033 / 0,0047 / 0,0089 / 0,023 |
| médio | 450 | 0,011 / 0,011 / 0,015 / 0,018 / 0,022 | 0,030 / 0,029 / 0,046 / 0,052 / 0,061 |
| grande | 50 | 0,17 / 0,17 / 0,24 / 0,29 / 0,29 | 0,58 / 0,58 / 0,81 / 0,83 / 0,83 |
| extremo | 1 | 0,96 | 4,53 |

Leituras por tarefa: 18 (PostgreSQL) e 15 (MariaDB), praticamente constantes; escritas: mediana 7,
máximo 9 (PostgreSQL) e 30 (MariaDB, conforme o número de linhas por atividade e de ligações). Memória: o pico registrado é o do processo
do cron (cerca de 37 MB), não o da análise; o pico da análise isolada está no SC-003 (≤ 16,2 MB).

**Critério (texto da spec)**: "todas as salas têm diagnóstico concluído em até 24 horas, e nenhuma
execução excede o limite de tempo configurado". Medido: 9 h 21 min e 9 h 09 min; a execução mais
longa de tarefa agendada foi de 0,29 s (limite do plano: 30 min) e a de análise, 4,5 s. **Atendido.**

### Integridade

Verificada nas 5.001 salas de cada base, não só pelo término das tarefas:

- **Exatidão**: número de registros, A e B de **todas** as 5.001 salas iguais ao manifesto do
  gerador (4.500 pequenas, 450 médias, 50 grandes e a extrema), nas duas bases, sem divergência.
- **Invariantes**: em todos os diagnósticos, conteúdo único ≤ armazenamento lógico, grandes ≤ total,
  soma das categorias principais = total, soma das atividades = total (registros, bytes e grandes),
  ligações ≤ 1.000 e ≤ grandes, versão do método 1.
- **Estados finais**: 5.001 concluídos, nenhum na fila, em execução ou com falha.
- **Tabela de arquivos intacta**: mesmo número de registros (5.731.758) e mesmo `MAX(id)` e soma de
  `filesize` nas duas bases; nenhum registro com `timemodified` posterior à geração dos dados
  (22:20 e 22:30 UTC), antes do SC-003 e do SC-004.
- **Preservação após falha**: não houve falha nesta execução; o comportamento está coberto pelos
  testes com o executor real do cron (CT-14, `analyse_course_test`).

### Agendamento: o que foi medido e o que vem da leitura do código

- **Medido**: todas as salas processadas, inclusive a última (o lote final de 1 sala, às 08:40 e
  08:28); a vazão é a do lote horário (500 por execução), e cada análise leva milissegundos, então o
  **agendamento**, e não as consultas, determina a duração total; a concorrência 2 foi respeitada
  durante toda a execução; nenhuma análise duplicada.
- **Pelo código, não demonstrado por esta execução**:
  - a seleção horária põe primeiro as salas nunca tentadas (`timeattempted` nulo), antes das
    reanálises devidas, e adicionar o bloco já solicita a primeira análise sem esperar a tarefa
    horária; por isso as reanálises acumuladas não atrasam nem impedem a primeira análise de uma
    sala nova. Esta execução só tinha salas nunca analisadas: **não comprova o comportamento numa
    fila mista** de primeiras análises e reanálises;
  - com o cron atrasado, a seleção é por data e nada se perde (só atrasa); solicitações presas são
    recuperadas pela tarefa agendada (em execução há mais de 2 h, na fila há mais de 24 h).
- **Capacidade nominal**: 500 salas por hora correspondem a no máximo **12.000 salas por dia**, com o
  cron pontual. É um limite da configuração atual, **não uma garantia medida** nessa escala. Com o
  intervalo padrão de reanálise (24 h), um site com mais de cerca de 12.000 salas elegíveis não
  conseguiria reanalisar todas a cada dia.
- **Melhoria futura (não bloqueante, fora da Feature 002)**: permitir ao administrador configurar a
  quantidade de salas por execução, com limites seguros e orientação de desempenho. Relevância:
  instalações grandes; impacto: capacidade diária; prioridade: média, quando houver um caso real
  acima de cerca de 10.000 salas elegíveis.

## Reprodução

Os sites de benchmark e de quickstart eram descartáveis e foram apagados depois do merge (2026-10-17).
Para reproduzir:

1. **Ambiente**: o `moodle-docker` de [CONTRIBUTING.md](../../../CONTRIBUTING.md), com
   `tools/dev/local.yml.dist` copiado para `local.yml`, Moodle 4.5.15 (`215f4438`) e
   `moodle-docker` `f4c2324`. Um stack por base, com `MOODLE_DOCKER_DB=pgsql` ou `mariadb`,
   `MOODLE_DOCKER_PHP_VERSION=8.1`, `COMPOSE_PROJECT_NAME=coursecare-bench-pg` ou
   `coursecare-bench-maria`, uma `MOODLE_DOCKER_WEB_PORT` livre e `COURSECARE_PLUGIN_DIR` apontando
   para o checkout. Site instalado vazio, com o plugin.
2. **Scripts** (`logs/benchmark/`): `mb` e `mbc` executam comandos no stack; os caminhos absolutos
   da máquina original precisam ser ajustados. Ordem: `bench-generate.sh <base>` (sementes fixas),
   `bench-sc003.sh <base>`, apagar os diagnósticos das 4 salas do SC-003, `bench-sc004.sh <base>`
   e, no fim, o relatório `sc004-report.php.txt` copiado como `.php` para a raiz do Moodle e
   executado com o horário de início do laço (Unix) como argumento.
3. **Manifestos**: `logs/benchmark/manifests.tar.gz` guarda os 15 manifestos do gerador (valores
   esperados de registros, A e B por sala), idênticos nas duas bases. Eles usam os ids de sala destes
   sites: um site novo só reproduz os mesmos ids se for gerado vazio e na mesma ordem.

## Limitações gerais do benchmark

- Dados sintéticos (`--mode=metadata`), gerados por perfil; não equivalem à validação numa instalação
  de produção, com seus padrões reais de áreas, repetição e carga concorrente.
- Uma VM de desenvolvimento (4 CPUs, 5,8 GB) com as duas bases ao mesmo tempo e configuração padrão
  dos bancos; o "frio" do SC-003 é frio para o banco, não para o cache do sistema operacional.
- Não houve falhas nem cron atrasado durante o SC-004: esses caminhos estão cobertos por testes e pela
  leitura do código, não por esta execução.
