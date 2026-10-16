# Quickstart 002: execução (T068)

- Data: 2026-10-16 (UTC 21:50–22:15).
- Ambiente: `moodle-docker` próprio e descartável (`coursecare-qs`, porta 8050), Moodle 4.5.15
  (Build 20261005), PHP 8.1, PostgreSQL 17.11, tema Boost. O plugin foi montado a partir de uma cópia
  (`coursecare-devenv/qs-plugin`): primeiro `git archive main` (0.1.0, 2026101600), depois a árvore da
  Feature 002 (0.2.0, 2026101602) copiada por cima, sem recriar o contêiner.
- Idiomas: en e pt_BR (pacote oficial pt_br copiado do site da Feature 001 e tradução do projeto
  importada por `tool_customlang`; como na 001, o primeiro `--checkin` não gravou `pt_br_local` e o
  segundo gravou).
- Dados fictícios: QS-A (bloco, recursos de 3 MB e 1 MB), QS-B (bloco, sem arquivos), QS-C (sem bloco,
  recurso de 2 MB), professor `editor` com papel de professor com edição nas três.
- Capturas: `../screenshots/state-{a,b,d,e,f,g}-{en,pt_br}.png`, feitas pelo Selenium do próprio
  stack (WebDriver), como o professor, recortadas no bloco.

## §1 Atualização a partir da 0.1.0 (CT-25)

`admin/cli/upgrade.php --non-interactive`: sucesso. Novas configurações `largefilethreshold`,
`reanalysisinterval` e `stalenessthreshold`; as 4 tabelas `block_coursecare_*` existem; nenhuma linha
de diagnóstico; `queue_analyses` agendada (minuto 54, sorteado). QS-A mostra o estado A
(`state-a-*.png`): "The file overview for this course is not available yet." /
"O panorama de arquivos desta sala ainda não está disponível."

## §2 Primeira análise ao adicionar o bloco

O professor ativou a edição em QS-C e adicionou o bloco (`bui_addblock=coursecare`). Na mesma página:
estado B (`state-b-*.png`), um registro com `requeststatus = 1` e uma tarefa `analyse_course`.
Depois, a tarefa agendada real (`scheduled_task.php --execute=\block_coursecare\task\queue_analyses`)
enfileirou QS-A e QS-B ("2 queued") e `adhoc_task.php --execute` analisou as três salas:

| Sala | Estado | Registros | A | B |
| --- | --- | --- | --- | --- |
| QS-A | complete (D) | 2 | 4.194.304 | 4.194.304 |
| QS-B | nofiles (G) | 0 | 0 | 0 |
| QS-C | complete (D) | 1 | 2.097.152 | 2.097.152 |

Capturas D e G: `state-d-*.png`, `state-g-*.png`. Observação: em pt_BR o tamanho aparece como
"4.0 Mb", com rótulo e separador decimal vindos de `display_size()` e do pacote pt_br do core, não
do plugin.

## §3 Conferência dos números

Coberta pelos testes PHPUnit com a File API real (CT-01–CT-13, CT-21, CT-22, CT-29–CT-35) e pela
conferência manual de US3, US6 e US7 no site de desenvolvimento (`tasks.md`, seção Execução). Aqui,
os valores das três salas acima conferem com os arquivos criados.

## §4 Estados E e F

- E: `stalenessthreshold = 3600` e o resultado de QS-A envelhecido em 7.200 s no banco. Bloco:
  "... 4.0 MB. Calculated on 16/10/26, 20:57. This information may be out of date." (`state-e-*.png`).
- F (falha real, não simulada): a tabela `m_files_reference` foi renomeada durante um
  `diagnostics.php --run --course=<QS-C>` e restaurada logo depois. Saída: "failed
  (dml_read_exception)", código de saída 4; `lastattemptfailed = 1`, `failurereason =
  dml_read_exception`, resultado anterior (2 MB) mantido. Bloco: "... 2.0 MB. Calculated on ... The
  latest update could not be completed." (`state-f-*.png`). Sala inexistente: código 2.

## §5 Ciclo de vida

| Caso | Resultado |
| --- | --- |
| CT-20: remover o bloco de QS-A (analisada; 1 linha de categoria, 2 de atividade) | Diagnóstico e filhas removidos |
| CT-26: desativar e reativar o tipo de bloco | Diagnóstico de QS-B mantido |
| CT-27: excluir QS-C com diagnóstico e análise na fila | Diagnóstico removido; a tarefa depois termina com `ineligible`, sem erro |
| CT-28: sala nova sem bloco + tarefa agendada | "0 queued"; nenhum registro |

## Desinstalação

`admin/cli/uninstall_plugins.php --plugins=block_coursecare --run`: sucesso. As 4 tabelas foram
removidas; nenhuma linha em `config_plugins`, nenhuma tarefa agendada ou ad hoc e nenhuma instância do
bloco restante.

## §6 e §7

Testes automatizados (PHPUnit e Behat) e pacote: ver `tasks.md` (T067, T069) e `../README.md`.
