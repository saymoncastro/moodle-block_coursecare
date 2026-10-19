# Data model: Assignment Guidance (Feature 009)

## (A) Tabela `block_coursecare_assignidea`

| Campo | Tipo | Regra |
| --- | --- | --- |
| `id` | int(10), sequência | |
| `diagid` | int(10), FK `block_coursecare_diag.id`, índice | substituída a cada análise, na mesma transação do resultado (como `_cat`, `_act`, `_large`) |
| `cmid` | int(10) | tarefa que atendia ao critério R1 no momento da análise |

Sem dado pessoal; apagada com o diagnóstico (sala excluída, último bloco removido) e na desinstalação.
Depois da atualização para 0.9.0, vazia até a próxima análise de cada sala.

## Critério (derivado, R1)

Entrada: as configurações da 005 de uma tarefa (`maxfiles`, `maxattempts`, `reopenmethod`, item de nota,
participantes, grupos) e a visibilidade da atividade. Verdadeiro sse `files > 0` ∧ `maxattempts = -1` ∧
reabre (manual, automatic, ou untilpass com critério de aprovação) ∧ `units > 0` ∧ visível para estudantes.

## Orientação informativa (derivada, por tarefa)

Configuração atual (fatores da 005) → significado → implicações (duas direções) → origem do limite
(tarefa, padrão do site, sala, site, servidor) → tentativas (ilimitadas, até passar) → passos (strings do
Moodle) → link (só com `moodle/course:manageactivities`) → perguntas de reflexão.

## Estado do bloco e da faixa (derivado)

| Sugestões aplicáveis | Título | Mensagem | Ação |
| --- | --- | --- | --- |
| nenhuma | 008 (allgood/neutral) | 008 | 008 |
| só materiais grandes | Há uma ideia para a sua sala | 008 | Ver sugestão |
| só tarefas | Há uma ideia para a sua sala | Encontramos sugestões sobre as tentativas das tarefas. | Ver sugestão / Ver sugestões |
| as duas | Há uma ideia para a sua sala | Encontramos sugestões para a sua sala. | Ver sugestões |
