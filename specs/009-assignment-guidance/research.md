# Research: Assignment Guidance (Feature 009)

## R1 — Semântica das tentativas no Moodle 4.5 e 5.3 (critério FR-010a)

Código conferido: `mod/assign/locallib.php`, `assign::reopen_submission_if_required()` (4.5.15, linha ≈8800;
5.3+, linha ≈10180), `mod/assign/mod_form.php`.

| Fato | 4.5 | 5.3 |
| --- | --- | --- |
| Métodos de reabertura | `manual`, `automatic`, `untilpass` (a constante `none` ainda existe; o 4.4 converteu tarefas "nenhuma" para máximo 1) | `manual`, `automatic`, `untilpass` (`none` removida) |
| Teto de tentativas | `maxattempts`; `-1` = ilimitado (`ASSIGN_UNLIMITED_ATTEMPTS`) | igual |
| Nova tentativa | `automatic`: sempre que avaliado; `untilpass`: só se o item de nota tem critério de aprovação e a nota não passa; `manual`: quando o professor reabre; e só se o máximo não foi atingido | idêntico |
| Formulário | "Allowed attempts" e "Grant attempts" na seção "Submission settings"; "Grant attempts" oculto quando o máximo é 1 | igual |

- **Decision**: o critério é **efetivamente ilimitado** quando, ao mesmo tempo: envio de arquivos
  habilitado com ao menos um arquivo (005: estado diferente de `nofiles`); `maxattempts = -1`; o método
  reabre (manual ou automatic; `untilpass` só com critério de aprovação — a mesma regra
  `has_pass_criterion` da 005); ao menos um participante ou grupo pode enviar (005: unidades > 0); e a
  tarefa está visível para os estudantes (`cm->visible`). Reaproveita os campos já devolvidos por
  `storage_potential::evaluate()` (`files`, `attempts = null`, `units`) e as configurações da 005; não
  depende do limite em bytes (que, numa tarefa em segundo plano, viria do PHP de linha de comando).
- **Rationale**: decisão Q2 (comportamento efetivo, nunca o campo isolado); a mesma regra nas duas versões.
- **Alternatives**: só `maxattempts = -1` (rejeitado pelo proprietário do produto); exigir também que haja
  envios reais (dado de estudante, proibido).

## R2 — Nomes dos campos nos passos de revisão (FR-005, SC-003)

- **Decision**: os passos citam seções e campos pelas **strings do próprio Moodle** (`assign`:
  `submissionsettings`, `maxattempts`, `attemptreopenmethod`, `submissiontypes`, `teamsubmission`;
  `assignsubmission_file`: `pluginname`, `maxfilessubmission`, `maximumsubmissionsize`), obtidas na
  renderização. Assim o nome é sempre o da versão e do idioma do site (4.5 e 5.3 têm as mesmas chaves e os
  mesmos textos em inglês).
- **Alternatives**: copiar os nomes nas strings do plugin (divergiriam do pacote de idioma do site).

## R3 — Link para revisar

- **Decision**: `course/modedit.php?update=<cmid>` só para quem tem `moodle/course:manageactivities` no
  contexto da tarefa (verificação de capability, sem consulta pesada); para os demais, só os passos.

## R4 — Consistência entre bloco e página sem custo na renderização (FR-013, FR-014) — **escolha do proprietário do produto**

O bloco hoje faz 5 leituras do banco por renderização e decide a sugestão da 004 pela existência de
links de materiais grandes **guardados pela análise** (`block_coursecare_large`). O critério da 009
precisa de quem pode enviar (consulta de inscrições e capability) e do item de nota — custo que a
página da 005 aceita, mas que o bloco (renderizado em toda visita à sala por quem edita) não deve ter
(Princípio VI: a renderização "lê dados pré-calculados").

| | A. Guardar na análise (recomendada) | B. Calcular na renderização | C. Calcular e guardar em cache (MUC) |
| --- | --- | --- | --- |
| Como | A análise em segundo plano (que já roda por sala) avalia as tarefas com a regra R1 e guarda os `cmid` que atendem ao critério numa tabela filha nova, junto do resultado, na mesma transação | O bloco consulta as tarefas da sala com tentativas ilimitadas e, se houver, avalia participantes e nota a cada renderização | Como B, com o resultado guardado no cache do Moodle e invalidado por eventos (tarefa alterada, inscrições, papéis, grupos, notas) |
| Leituras no bloco | 0 a mais (uma consulta passa a responder às duas sugestões) | +1 sempre; +3 a 5 quando há tarefa candidata | +1 (cache) ou as de B quando o cache expira |
| Página | Cartão a partir dos `cmid` guardados ("com base na análise de …", como a 004); orientação informativa ao vivo (005) | Tudo ao vivo | Cache |
| Consistência bloco × página | Total (mesma fonte) | Total | Depende da invalidação |
| Atualidade | Até a próxima análise (padrão: 1 dia), como todo o diagnóstico | Imediata | Imediata se os eventos cobrirem todos os casos |
| Mudanças | 1 tabela nova (`id`, `diagid`, `cmid`), passo de atualização, 1 string de privacidade; leitura de configurações das tarefas na análise | Nenhuma no banco | Definição de cache e ~6 observadores de eventos |
| Riscos | Sugestão some ou aparece só na análise seguinte à mudança da tarefa | Custo no caminho mais visitado; contraria o Princípio VI | Invalidação incompleta gera incoerência; complexidade |

- **Recommendation**: **A**. É o padrão já usado pela 004 (sugestão a partir do resultado guardado), não
  acrescenta leitura ao bloco, mantém bloco e página coerentes e deixa o custo (participantes, nota) na
  tarefa em segundo plano. A tabela é apagada com o diagnóstico da sala e na desinstalação, como as
  demais. Após a atualização, as sugestões de tarefas aparecem a partir da próxima análise de cada sala.
- **Status**: aguardando a decisão do proprietário do produto (pedido em 2026-10-19: apresentar
  alternativas e custos antes de implementar).

## R5 — Mensagens do bloco e da faixa (FR-013)

- **Decision**: sem números; variantes por tipo: só materiais grandes (texto da 008), só tarefas ("We found
  suggestions about the attempts of assignments." / "Encontramos sugestões sobre as tentativas das
  tarefas."), os dois tipos ("We found suggestions for your course." / "Encontramos sugestões para a sua
  sala."); ação "See the suggestion" / "See the suggestions" conforme haja uma ou mais sugestões (sem
  mostrar a quantidade). Revisão de tom pelo proprietário do produto.

## R6 — Ordem das sugestões (FR-012)

- **Decision**: ordem fixa: materiais grandes (004) primeiro, depois as tarefas na ordem da sala; sem selo
  "principal".

## R7 — Versão

0.9.0 BETA (versão 2026102200). Com a opção A, há passo de atualização (tabela nova); sem ela, só a versão.
