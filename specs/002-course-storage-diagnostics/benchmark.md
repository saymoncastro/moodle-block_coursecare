# Plano de benchmark: Course Storage Diagnostics

Objetivo: medir, com dados sintéticos reproduzíveis, as metas SC-002, SC-003 e SC-004, e
documentar os limites de escalabilidade (FR "Escalabilidade", CT-17, CT-24). **Nenhuma meta é
declarada atendida sem a execução registrada em `evidence/`.** Valores calculados a partir de
medições aparecem como **projeção**, separados dos medidos.

## Ambiente de referência

- `moodle-docker` com `MOODLE_405_STABLE` (versão exata registrada), PHP 8.1, nas duas bases do CI:
  PostgreSQL 17 e MariaDB 11. Configuração padrão do container, sem ajuste de banco.
- Registro obrigatório em cada evidência: CPU e memória do hospedeiro, versões, número de registros
  em `files` no site, `task_adhoc_concurrency_limit` e data.
- `$CFG->task_logmode` padrão (MODE_ALL), para que a duração e as consultas de cada tarefa fiquem no
  log do core.

## Gerador de dados (`tools/dev/bench/generate.php`, fora do pacote)

CLI de desenvolvimento (precisa de `config.php`, como os CLIs do core), só para ambientes de teste.
Usa os geradores de dados do core (`core_course`, `mod_resource`, `mod_folder`, `mod_assign`,
`mod_forum`) e a **File API real** (`file_storage::create_file_from_string`) nas áreas reais dos
módulos criados. Conteúdos são cadeias pseudoaleatórias de tamanho controlado com semente fixa, para
que a deduplicação seja controlada:

| Perfil | Salas | Registros por sala | Distribuição |
| --- | --- | --- | --- |
| pequeno | 4.500 | 10–50 | 80% Materiais, 20% Envios; 10% de conteúdo repetido |
| médio | 450 | 1.000–5.000 | 60% Materiais, 30% Envios, 10% Outros; 25% repetido; 1% ≥ 50 MB (registrado, conteúdo pequeno: ver abaixo) |
| grande | 50 | 20.000–50.000 | Igual ao médio, com 200 atividades |
| extremo | 1 | 200.000 | Para observar o limite, sem meta |

Total: 5.001 salas com o bloco. Parâmetros: `--profile`, `--courses`, `--seed`. O gerador grava um
manifesto JSON com os valores esperados de A e B de cada sala (para a amostragem de exatidão).

**Arquivos grandes sem ocupar disco**: o tamanho real do conteúdo não muda o custo da análise (só
metadados são lidos). Os arquivos "grandes" do benchmark são criados com conteúdo pequeno e
`filesize` ajustado por `$DB->set_field` **apenas nos dados sintéticos de benchmark**. Isso é
declarado na evidência; os testes de exatidão (PHPUnit) usam sempre conteúdo real.

## Medições

### SC-003 — sala grande

- `php blocks/coursecare/cli/diagnostics.php --run --course=<id> --json` para salas com 5.000,
  20.000, 50.000 e 200.000 registros, 3 execuções cada (a primeira com cache de banco frio, após
  reiniciar o container do banco).
- Registra: duração total, duração de cada consulta Q0–Q4, pico de memória do PHP e consultas ao
  banco.
- **Aprovação**: 50.000 registros em ≤ 5 min nas duas bases; pico de memória de 50.000 ≤ 1,5 × o de
  5.000 (sem crescimento proporcional).
- `EXPLAIN` (PostgreSQL) e `EXPLAIN` (MariaDB) das consultas Q1–Q4 na sala de 50.000, anexados à
  evidência (índices `files.contextid` e `context.path` usados, sem varredura completa de `files`).

### SC-004 — 5.000 salas em 24 h

- Com as 5.001 salas sem diagnóstico, rodar o cron real em laço (`admin/cli/cron.php` a cada minuto,
  como recomenda o Moodle) até não restarem salas devidas, registrando em que momento cada sala foi
  concluída (`timecompleted`).
- **Aprovação**: todas concluídas em ≤ 24 h; nenhuma execução de tarefa agendada com mais de 30 min;
  nenhuma análise simultânea da mesma sala (verificado no log: um único início por sala e por
  solicitação).
- Como 24 h de relógio é caro, a execução pode ser acelerada reduzindo o lote ou o intervalo apenas
  se for **declarada como projeção**. A evidência mínima medida é a fila completa das 5.001 salas com
  a configuração padrão, com o tempo total real.

### SC-002 — renderização do bloco

- PHPUnit: diferença de leituras (`$DB->perf_get_reads()`) entre o bloco sem diagnóstico e com
  diagnóstico, e ausência de `{files}` no log de consultas.
- Microbenchmark (`tools/dev/bench/render.php`): 1.000 chamadas de `get_content()` para um usuário
  professor, com a versão 0.1.0 e com a nova versão, mesma sala. **Aprovação**: diferença média
  ≤ 50 ms (esperado: ordem de 1 ms).

### Concorrência

- Duas execuções simultâneas de `--run` para a mesma sala: uma analisa, a outra termina com código 3.
- `--queue --all` duas vezes seguidas: uma única solicitação por sala.

## Exatidão por amostragem

Para 20 salas de cada perfil (semente fixa), comparar A e B com o manifesto do gerador. Qualquer
diferença reprova o benchmark.

## Relatório

`specs/002-course-storage-diagnostics/evidence/benchmark.md`, com tabelas por base de dados, os
JSON brutos em `evidence/logs/`, o que foi medido e o que é projeção, e os limites documentados no
README (registros por sala testados, salas por dia na configuração padrão, memória observada).
