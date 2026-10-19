# Instalação e desinstalação (Feature 007, T005, T007, T008, T011)

Executado em 2026-10-18 pelo agente, em sites descartáveis `moodle-docker` (sem a pasta do repositório
montada), com `$CFG->debug = DEBUG_DEVELOPER` e `debugdisplay = 1`. Pacote: `build/block_coursecare-0.7.0.zip`
gerado por `tools/package/build.sh` no commit `aa6bcc4` (84 arquivos; `tools/package/check.php`: 0 erro).
Fotografia das tabelas por um script descartável (`inspect.php`, fora do repositório): contagem e md5 das
linhas de cada tabela do plugin, configurações, tarefa, capabilities e instâncias.

## Ambientes

| Site | Moodle | PHP | Banco | Uso |
| --- | --- | --- | --- | --- |
| `coursecare-zip` | 4.5.15 (Build 20261005) | 8.3.35 | PostgreSQL 17.11 | ZIP: instalação limpa, atualização, desinstalação |
| `coursecare-z503` | 5.3+ (Build 20261013, `344c92d5b`) | 8.3.35 | PostgreSQL 17.11 | ZIP: instalação limpa, atualização, desinstalação |
| `coursecare-503` (T005) | 5.3+ (Build 20261013) | 8.3.35 | PostgreSQL 17.11 | código-fonte montado em `public/blocks/coursecare`; demonstração e capturas |
| `coursecare-002` | 4.5.15 | 8.1 | PostgreSQL 17 | código-fonte montado em `blocks/coursecare` (features 001–006) |

O ambiente local de 5.3 (T005) usa `moodle-docker/local.yml` com o destino da montagem parametrizado
(`COURSECARE_PLUGIN_SUBDIR=/public`). PHPUnit e Behat de 5.3 rodam no CI (T006), não localmente.

## Instalação limpa pelo ZIP (T007)

Site sem o plugin → ZIP extraído na pasta de blocos → `admin/cli/upgrade.php --non-interactive`.

| Verificação | 4.5 | 5.3 |
| --- | --- | --- |
| Instalação | `++ Success ++`, saída 0 | `++ Success ++`, saída 0 |
| Avisos, notices, depuração | nenhum | nenhum |
| Tabelas | 5 (`_diag`, `_cat`, `_act`, `_large`, `_hist`), vazias | idem |
| Configurações | 4 (+ versão), com os padrões | idem |
| Tarefa agendada | `\block_coursecare\task\queue_analyses` | idem |
| Capabilities | `block/coursecare:addinstance`, `block/coursecare:view` | idem |
| Tarefa executada (`scheduled_task.php --execute`) | saída 0, sem aviso | saída 0, sem aviso |
| CLI `--help` | saída 0 | saída 0 (caminho `public/blocks/coursecare/cli/diagnostics.php`) |
| Segunda execução do upgrade | "No upgrade needed" | "No upgrade needed" |

Instalação a partir do código-fonte: os sites `coursecare-002` (4.5) e `coursecare-503` (5.3) instalam o
plugin a partir da pasta do repositório; no 5.3 a instalação e a demonstração rodaram sem aviso nesta
feature.

## CLI, bloco e página no 5.3 (T008)

Site `coursecare-z503` com os dados de demonstração, sala `DEMO-COMPLETE` (id 2):

- `cli/diagnostics.php --run --course=2`: "done in 0.014 s, peak memory 13.1 MB", saída 0.
- `--show --course=2`: estado `complete`; 13 arquivos, 8,0 MB lógico; 3 grandes (5,1 MB); materiais
  5,9 MB, envios 1,9 MB, outros 146,5 KB; 9 atividades; 2 ligações de arquivos grandes; saída 0.
- Renderização no servidor (`tools/dev/bench/render.php`, ferramenta de desenvolvimento copiada
  temporariamente e removida depois), mesma sala e mesmo código 0.7.0:

| Medida | 4.5 | 5.3 |
| --- | --- | --- |
| Bloco (300 iterações) | média 1,50 ms, 5,04 leituras | média 1,46 ms, 5,03 leituras |
| Página "Your course in numbers" (100 iterações) | média 10,85 ms, 11,41 leituras | média 11,34 ms, 11,39 leituras |

O resultado visual do bloco e da página (resultado, sugestão, potencial, histórico) está na auditoria
visual (T014).

## Desinstalação (T011)

`admin/cli/uninstall_plugins.php --plugins=block_coursecare --run`, depois remoção da pasta do plugin.

| Verificação | 4.5 sem dados | 4.5 com dados | 5.3 sem dados | 5.3 com dados |
| --- | --- | --- | --- | --- |
| Antes | — | 12 instâncias, 2253 pontos de histórico | — | 12 instâncias, 2253 pontos |
| Desinstalação | `++ Success ++` | `++ Success ++` | `++ Success ++` | `++ Success ++` |
| Tabelas do plugin | ausentes | ausentes | ausentes | ausentes |
| Configurações, tarefa, capabilities, `role_capabilities` | 0 | 0 | 0 | 0 |
| Instâncias do bloco | 0 | 0 (removidas na hora) | 0 | 0 depois da tarefa ad hoc (abaixo) |
| Contextos e posições órfãos | — | 0 | — | 0 |

No 5.3 o núcleo não remove as instâncias na desinstalação: enfileira
`\core\task\delete_block_instances_task`, que roda no cron seguinte. Enquanto a pasta do plugin existe,
o Moodle considera haver uma instalação pendente e suspende o cron; depois de remover a pasta (e uma
execução do upgrade para atualizar o registro de plugins), a tarefa removeu as 12 instâncias, sem
contextos nem posições órfãos. Comportamento do núcleo, documentado em `docs/installation.md`.

Observação: a ferramenta de demonstração (desenvolvimento, fora do pacote) gera no 5.3 um aviso de
depreciação (inclusão de `lib/phpunit/classes/util.php`) e mensagens de depuração do núcleo sobre e-mails
de boas-vindas não enviados aos endereços `.invalid` fictícios; nenhuma vem do código do pacote.
