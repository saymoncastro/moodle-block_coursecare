# Benchmark da Feature 002 (T041)

Plano: [`../benchmark.md`](../benchmark.md). Dados brutos: [`logs/benchmark/`](logs/benchmark/).
Tudo o que aparece aqui foi **medido**, salvo quando marcado como **projeção**.

| Meta | Situação |
| --- | --- |
| SC-003 / CT-17: sala de 50.000 registros em ≤ 5 min, memória sem crescimento proporcional | **Atendida** nas duas bases (medida em 2026-10-16) |
| SC-004 / CT-24: 5.001 salas concluídas em ≤ 24 h com o cron real | Pendente (execução de cerca de 11 h) |
| SC-002: renderização do bloco, 0.1.0 × 0.2.0 | Parcial: sem consulta a `files` e mesmas leituras em todos os estados (PHPUnit); microbenchmark pendente |
| Concorrência (`--run` simultâneo, `--queue --all` duas vezes) | Pendente |
| Exatidão por amostragem (20 salas por perfil) | Parcial: as 4 salas do SC-003 conferem com o manifesto nas duas bases; amostragem por perfil pendente |

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

## SC-004, SC-002, concorrência e amostragem

Pendentes; serão registrados nesta página com o mesmo formato.
