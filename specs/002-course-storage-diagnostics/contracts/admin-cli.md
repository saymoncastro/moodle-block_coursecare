# Contract: ferramenta administrativa de linha de comando

Arquivo: `cli/diagnostics.php` (**dentro** do pacote; FR-039c, FR-054). Convenção do core:
`define('CLI_SCRIPT', true)`, `require(__DIR__ . '/../../../config.php')`, `clilib.php`,
`cli_get_params()`, `cli_error()` em opção desconhecida (`admin/cli/adhoc_task.php:26-57`).

Executada no servidor por quem administra o Moodle (acesso ao shell equivale a acesso total).
Nunca exibe nomes de arquivos, nomes de atividades, caminhos nem identificadores de usuários.

## Modos (exatamente um por execução)

| Opção | Efeito |
| --- | --- |
| `--queue --course=<id>` | Solicita a análise de uma sala elegível (mesma regra de duplicidade da tarefa agendada). Não analisa na hora. |
| `--queue --all` | Solicita a análise de todas as salas elegíveis com `requeststatus = 0`, em lotes de 500 inserções por transação. A execução real segue o limite de concorrência das tarefas ad hoc. |
| `--run --course=<id>` | Analisa a sala **neste processo**, com o mesmo lock por sala. Para verificação e benchmark. Mostra duração e pico de memória. |
| `--show --course=<id>` | Mostra o resultado vigente: estado, datas, limite usado, A, B, contagens, categorias, agregado de backup e lixeira, referências por tipo, cobertura e, por atividade, `cmid` com os totais. |
| `--help`, `-h` | Ajuda. |

Opção `--json` com `--show` ou `--run`: saída em JSON estável (chaves em inglês), para evidências de
teste e benchmark.

## Códigos de saída

| Código | Situação |
| --- | --- |
| 0 | Sucesso |
| 1 | Opções inválidas |
| 2 | Sala inexistente ou não elegível |
| 3 | Análise já em execução (lock ocupado) |
| 4 | Falha da análise (`--run`); resultado anterior preservado |

## Exemplo de `--show` (formato, valores fictícios)

```text
Course 42 — state: D (complete), partial: no
Calculated: 2026-10-16 10:12, last attempt: 2026-10-16 10:12, method v1, large-file threshold: 52428800 bytes
Main scope: 1,204 files, logical 3.1 GB, unique 2.4 GB (980 contents)
  material      812 files  1.9 GB  unique 1.5 GB
  submission    350 files  1.1 GB  unique 0.8 GB
  other          30 files  90 MB   unique 85 MB
  unclassified   12 files  10 MB   unique 10 MB
Large files (>= 50 MB): 9 records, 1.2 GB; 7 contents, 0.9 GB
Backups and recycle bin (recorded in Moodle for this course): backup 2 files 600 MB; recycle bin 1 file 40 MB; unique 620 MB
References: internal 3 (12 MB recorded), external 1 (4 MB recorded), undetermined 1
Activities: cmid 17 1.0 GB | cmid 23 400 MB | course level 120 MB | ...
```

A ajuda e os rótulos da saída de texto passam pela String API (`get_string`, chaves `cli:*`), com
tradução pt_br e paridade, como toda string visível (Princípio VIII). O exemplo acima mostra o texto
em inglês. As chaves do JSON são dados, ficam em inglês e não são traduzidas.
