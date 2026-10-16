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

## Saída de `--show` (texto)

Uma linha de título traduzida e, depois, uma linha `chave: valor` por dado, com as **mesmas chaves do
JSON** (identificadores de dados, não frases de interface). Tamanhos aparecem também em unidades
legíveis (`display_size()`) e datas em `AAAA-MM-DD HH:MM`; valor não calculado aparece como `-`.
Exemplo (valores fictícios):

```text
Storage diagnostic of course 42
  courseid: 42
  state: complete
  partial: 0
  timecompleted: 1792183952 (2026-10-16 21:52)
  logicalbytes: 13726 (13.4 KB)
  uniquebytes: 12970 (12.7 KB)
  brbytes: 41943040 (40.0 MB)
  backup.logicalbytes: 41943040 (40.0 MB)
  ...
```

A ajuda, as mensagens e o título passam pela String API (`get_string`, chaves `cli:*`), com tradução
pt_br e paridade, como toda frase visível (Princípio VIII). As chaves de dados (texto e JSON) ficam em
inglês e não são traduzidas.
