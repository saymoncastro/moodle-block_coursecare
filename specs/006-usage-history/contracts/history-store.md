# Contract: registro e leitura do histórico

## Coletor `\block_coursecare\local\collector\diagnostic_store` (estendido)

- `save_result(int $courseid, diagnostic_result $result, int $now)`: além do que já faz, grava o ponto do
  dia (`timeday` = meia-noite de `$now` no fuso padrão do site) na **mesma transação**: atualiza o ponto
  existente de (sala, dia) ou insere um novo. Se a transação falhar, nem o resultado nem o ponto mudam.
- `save_failure(...)`: inalterado; nunca toca no histórico.
- `get_history(int $courseid, int $since): array`: pontos com `timeday >= $since`, em ordem crescente de
  `timeday`, campos de data-model.md. Uma consulta, pelo índice (`courseid`, `timeday`).
- `delete_for_course(int $courseid)`: apaga também o histórico da sala (Q3).
- `purge_history(int $before, int $limit): int`: remove até `$limit` pontos com `timeday < $before`;
  devolve quantos removeu.

## Tarefa `queue_analyses` (estendida)

A cada execução, depois dos órfãos: `purge_history(meia-noite de hoje − retenção, 10000)`; o número
removido entra na linha de log.

## Atualização (`db/upgrade.php`, 0.6.0)

Cria `block_coursecare_hist`; para cada resultado atual com `timecompleted` não nulo, insere um ponto
com os valores do resultado e das suas categorias e a data real (`timeday` do `timecompleted`).

## Casos de teste obrigatórios

| Caso | Resultado |
| --- | --- |
| Primeira análise | 1 ponto, valores iguais ao resultado |
| Análises em dois dias | 2 pontos |
| Duas análises no mesmo dia | 1 ponto, com a última |
| Reexecução forçada no mesmo dia | 1 ponto |
| Falha depois de um ponto | ponto intacto, nenhum novo |
| Falha dentro da transação do resultado | nem resultado nem ponto mudam |
| Cobertura parcial | `partial` = 1 |
| Sala sem arquivos | ponto com zeros |
| Retenção | só pontos anteriores ao limite removidos, no máximo `$limit` |
| Órfão (sala excluída, bloco removido) | resultado e histórico removidos |
| Atualização com resultados existentes | 1 ponto por sala com resultado, data real; nenhum para salas sem resultado |
| Leituras | nenhuma consulta à tabela `files` em gravação e leitura do histórico |
