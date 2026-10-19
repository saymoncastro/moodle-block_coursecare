# Data model: Assignment Guidance (Feature 009)

## (B2) Campo `suggestions` em `block_coursecare_diag`

| Campo | Tipo | Regra |
| --- | --- | --- |
| `suggestions` | TEXT, anulável, sem índice | JSON `{"v":1,"items":[{"kind":"attempts","cmid":<int>}]}`; `NULL` (ou ilegível) = **não calculado**; `items: []` = calculado, nenhuma |

Gravado no mesmo `save_result` (transação e trava da 002), com a mesma data `timecompleted`; falha mantém o
valor anterior; apagado com o registro da sala e na desinstalação. Sem dado pessoal. Lido por **uma**
função usada pelo bloco e pela página, que aplica o filtro ao vivo pelo `modinfo` (atividade existe, é
tarefa, `visible`). Detalhes e comportamento por situação: research R4.

## Critério (derivado, R1)

Entrada: as configurações da 005 de uma tarefa (`maxfiles`, `maxattempts`, `reopenmethod`, item de nota,
participantes, grupos) e o resultado de `evaluate()`. Na análise: `files > 0` ∧ `maxattempts = -1` ∧ reabre
(manual, automatic, ou untilpass com critério de aprovação) ∧ `units > 0`. Na exibição: ∧ a atividade
existe e está visível para os estudantes.

## Orientação informativa (derivada, por tarefa)

Configuração atual (fatores da 005) → significado → implicações (duas direções) → origem do limite
(tarefa, padrão do site, sala, site, servidor) → tentativas (ilimitadas, até passar) → passos (strings do
Moodle) → link (só com `moodle/course:manageactivities`) → perguntas de reflexão.

## Estado do bloco e da faixa (derivado)

| Sugestões aplicáveis | Título | Mensagem | Ação |
| --- | --- | --- | --- |
| nenhuma (campo calculado) | 008 (allgood/neutral) | 008 | 008 |
| nenhuma, campo `NULL` (não calculado) | 008, mas **nunca allgood** (neutro) | 008 | 008 |
| só materiais grandes | Há uma ideia para a sua sala | 008 | Ver sugestão |
| só tarefas | Há uma ideia para a sua sala | Encontramos sugestões sobre as tentativas das tarefas. | Ver sugestão / Ver sugestões |
| as duas | Há uma ideia para a sua sala | Encontramos sugestões para a sua sala. | Ver sugestões |
