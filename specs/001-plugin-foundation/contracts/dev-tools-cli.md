# Contract: Ferramentas de verificação (fora do pacote)

Requisitos: PHP 8.1+ com a extensão `zip` (apenas para o verificador de pacote). Não dependem de
Moodle instalado nem de bibliotecas externas.

## `php tools/i18n/check.php [--root=<dir>] [--strict]`

- **Entrada**: `<root>/lang/en/*.php` (canônico) e `<root>/translations/<idioma>/*.php` (todos os
  idiomas presentes). `root` é, por padrão, a raiz do repositório.
- **Análise**: por tokens (`token_get_all`), sem executar os arquivos. Aceita apenas comentários,
  a abertura `<?php`, `defined('MOODLE_INTERNAL') || die();` e atribuições
  `$string['<chave>'] = '<literal>';` com literal sem interpolação.
- **Regras**:

  | Código | Severidade | Condição |
  | --- | --- | --- |
  | `missing-key` | erro | Chave do inglês ausente na tradução |
  | `extra-key` | erro | Chave da tradução ausente no inglês |
  | `placeholder-mismatch` | erro | Conjunto de `{$a}` / `{$a->nome}` diferente do inglês |
  | `empty-value` | erro | Valor vazio ou só com espaços |
  | `invalid-syntax` | erro | Qualquer construção fora do formato de dados |
  | `duplicate-key` | erro | Chave atribuída mais de uma vez no mesmo arquivo |
  | `missing-file` | erro | Arquivo do inglês sem correspondente na tradução |
  | `same-as-english` | aviso (erro com `--strict`) | Valor idêntico ao inglês |

- **Saída** (stdout, uma linha por problema, ordenada por idioma, arquivo e chave):
  `ERROR|WARNING <código> <idioma> <arquivo>:<chave> <detalhe>`, seguida de um resumo
  `i18n: <n> error(s), <m> warning(s) in <k> language(s)`.
- **Códigos de saída**: `0` sem erros (pode haver avisos); `1` com erros; `2` em erro de uso ou de
  leitura (diretório inexistente, `lang/en` ausente).

## `tools/package/build.sh [<ref>]`

- Gera `build/block_coursecare-<release>.zip` a partir de `<ref>` (padrão `HEAD`) com
  `git archive --format=zip --prefix=coursecare/`. `<release>` é lido de `version.php`.
- Falha se a árvore de trabalho tiver alterações em arquivos rastreados, pois o pacote deve
  corresponder a um commit.

## `php tools/package/check.php <arquivo.zip>`

- Lê o conteúdo real do ZIP.
- **Falha** se:
  - existir qualquer entrada fora de `coursecare/`;
  - houver um arquivo fora da allowlist (ver [plan.md](../plan.md));
  - houver um diretório `lang/<x>/` com `x ≠ en`;
  - houver qualquer entrada em `translations/`, `tools/`, `specs/`, `.specify/`, `.claude/`,
    `.github/`, `build/`, `vendor/` ou `node_modules/`;
  - existir um arquivo oculto (`.*`);
  - faltar algum dos arquivos obrigatórios (`version.php`, `block_coursecare.php`,
    `db/access.php`, `lang/en/block_coursecare.php`).
- **Saída**: `ERROR <regra> <caminho>` por problema e o resumo `package: <n> error(s), <k> file(s)`.
- **Códigos de saída**: `0` válido; `1` inválido; `2` em erro de uso ou de leitura.

## Testes das ferramentas

`php tools/i18n/tests/run.php`:
- Executa o verificador sobre `tools/i18n/tests/fixtures/<caso>/` (um caso válido e um por código
  de regra) e confirma o código de saída e a presença da linha esperada.
- Executa o verificador de pacote sobre ZIPs montados no próprio teste (um válido e um por regra).
- Termina com o código `0` somente se todos os casos passarem.

As ferramentas e as fixtures (estas, inválidas de propósito) não são código do plugin. Por isso,
`tools/` e `translations/` são excluídos do `moodle-plugin-ci` via `IGNORE_PATHS: 'tools,translations'`.
No job `i18n-package`, os PHP de `tools/` (exceto fixtures) e de `translations/` passam por
`php -l`.
