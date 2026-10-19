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
  `has_pass_criterion` da 005); ao menos um participante ou grupo pode enviar (005: unidades > 0). A
  visibilidade para os estudantes (`cm->visible`) é verificada ao vivo, na exibição (R4), pois muda sem
  nova análise. Reaproveita os campos já devolvidos por
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

## R4 — Arquitetura de consistência bloco × página (FR-013, FR-014) — estudo pedido em 2026-10-19

### Fatos que decidem

- O bloco faz 5 leituras por renderização e já lê o registro da sala em `block_coursecare_diag`; só aparece
  na página principal da sala, onde o `modinfo` da sala já está carregado (cache do Moodle, sem consulta).
- A análise da 002 roda por sala em tarefa ad hoc, com trava por sala e gravação atômica numa transação
  (`save_result`); falha mantém o resultado anterior. Pedidos de nova análise já existem
  (`analyse_course::request`, que não duplica pedidos pendentes).
- `block_coursecare_act` só tem linhas para atividades **com arquivos registrados**: uma tarefa nova, sem
  envios, não tem linha. Marcar a tarefa ali exigiria mudar a semântica da 002.
- A página da 005 já calcula ao vivo, para cada tarefa, os fatores (`assign_settings` +
  `storage_potential::evaluate`); a orientação informativa e a verificação "ainda vale?" da sugestão saem
  desse mesmo cálculo, sem leitura nova.

### Alternativas comparadas

| Critério | A. Sob demanda (renderização) | B1. Pré-cálculo + tabela nova | **B2. Pré-cálculo + 1 campo no registro da sala** | C1. Pré-cálculo + cache (MUC) | C2. Só dados existentes |
| --- | --- | --- | --- | --- | --- |
| Processamento | Participantes e nota a cada renderização com candidata | Na análise: 1 consulta leve + regra 005 só nas candidatas | Igual a B1 | Igual a B1 | — |
| Consultas no bloco | +1 sempre; +3 a 5 com candidata | +1 (ou 0 juntando com a da 004) | **0** (o campo vem no registro já lido) | +1 (cache) | — |
| Consultas na página | 0 (já calcula a 005) | +1 | **0** | +1 | — |
| Milhares de salas | Custo no caminho mais visitado (Princípio VI) | +1 consulta leve por análise | **+1 consulta leve por análise** | Igual a B1 | — |
| Novas tabelas/campos | Nenhum | 1 tabela (`id`, `diagid`, `cmid`, índice em `diagid`) | **1 campo de texto anulável em `block_coursecare_diag`** | Definição de cache | Mudaria a semântica de `_act` (inviável) |
| Consistência bloco × página | Total, ao vivo | Total | **Total (mesma fonte, mesmo filtro)** | Total enquanto o cache existir | — |
| Após alterar a tarefa | Imediata | Próxima análise | **Próxima análise; exclusão e visibilidade imediatas (filtro pelo `modinfo`); a página detecta divergência e pede nova análise** | Igual a B2 | — |
| Concorrência | Nenhuma questão | Trava e transação da 002 | **Trava e transação da 002 (mesmo `UPDATE` do registro)** | Escrita no cache fora da transação | — |
| Ciclo de vida | Nenhum dado | Tabela filha: apagada com o diagnóstico e na desinstalação | **Vive no registro da sala: substituído a cada análise, apagado com ele, removido na desinstalação** | Some em toda limpeza de cache, sem aviso | — |
| 4.5 e 5.3 | Igual | Igual (XMLDB) | **Igual (XMLDB)** | Igual | — |
| Complexidade | Baixa no código, alta no custo | Média (tabela, consultas, limpeza) | **Baixa (campo, 1 consulta na análise, filtro)** | Média-alta (cache, invalidação) | — |

### Recomendação: B2 — pré-cálculo na análise da 002, guardado em um campo do registro da sala

- **Campo**: `assignideas` (TEXT, anulável) em `block_coursecare_diag`: lista dos `cmid` das tarefas que
  atendiam ao critério de configuração na análise, separados por vírgula. `NULL` = ainda não avaliado
  (depois da atualização, até a próxima análise); vazio = avaliado, nenhuma. Tamanho: quase sempre vazio;
  algumas dezenas de bytes numa sala com várias tarefas candidatas. Nenhum índice (nunca é consultado por
  `cmid`; só lido com o registro).
- **Por que não uma tabela**: a lista só é lida inteira, junto do registro que bloco e página já leem;
  uma tabela acrescentaria uma leitura por renderização e uma limpeza própria, sem ganho de consulta.
- **Regras únicas (005)**: a regra do critério vira um método puro de `storage_potential` (a mesma classe
  da 005) aplicado ao resultado de `evaluate()`: `files > 0` ∧ `maxattempts = -1` ∧ reabre (a mesma regra
  `has_pass_criterion`) ∧ `units > 0`. A análise usa o mesmo coletor `assign_settings` e a página usa o
  mesmo método para a orientação: nenhuma interpretação paralela das configurações.

### Fluxo de atualização

1. A análise da 002 (agendada, pedida pelo bloco novo ou pelo CLI) coleta os arquivos como hoje e, na
   mesma execução, faz **1 consulta** às tarefas da sala com `maxattempts = -1`. Sem candidatas (caso comum),
   grava `''`. Com candidatas, roda o coletor da 005 só para elas e aplica a regra; grava a lista no mesmo
   `save_result` (mesma transação, mesma data `timecompleted`). Nenhuma varredura nova de arquivos.
2. Falha da análise: nada é gravado; vale a lista anterior, com a data anterior (como todo o diagnóstico).
3. Bloco e página leem a lista do registro e aplicam **o mesmo filtro ao vivo** pelo `modinfo` em cache:
   a atividade ainda existe, ainda é uma tarefa e está **visível para os estudantes** (`cm->visible`; ocultar a
   seção oculta as atividades). Exclusão e ocultação valem na hora, nos dois lugares.
4. Mudança de configuração (tentativas, envio de arquivos, participantes): reconhecida na próxima análise
   (padrão do site: 1 dia). A página, que já calcula a 005 ao vivo, compara: se uma tarefa da lista não
   atende mais ao critério, o cartão mostra "As configurações desta tarefa mudaram depois da análise de
   {data}; a sugestão será revista na próxima análise." e a página **pede uma nova análise** (o pedido
   existente, sem duplicar). Nenhum observador de eventos novo.

### Dados desatualizados (requisitos 6 e 7)

- Cartão e faixa mostram "These suggestions use the analysis of {date}." (a data `timecompleted`, como a 004).
- O resultado desatualizado da 008 continua com a nota no bloco; a sugestão de tarefa nunca é apresentada
  como verificada agora.
- Visibilidade ≠ participantes: a visibilidade é filtrada ao vivo; participantes elegíveis (`units > 0`)
  só mudam na análise.

### Impacto estimado

- Bloco: **0 leituras novas** (continua 5); página: **0 leituras novas**.
- Análise: **+1 consulta indexada** (`assign.course`) por sala; o coletor da 005 (5 a 7 consultas) só em
  salas com tarefa de tentativas ilimitadas. Em 5.000 salas/dia: +5.000 consultas leves por dia, sem tocar
  na tabela de arquivos.

### Riscos e testes

| Risco | Mitigação / teste |
| --- | --- |
| Regra divergente da 005 | Mesmo método puro para análise e página; teste de tabela com todos os casos (reabertura, até passar, grupos, sem participantes, sem arquivos) |
| Lista antiga mostrada como atual | Data no cartão; nota de divergência; teste com configuração alterada depois da análise |
| Atividade excluída/oculta | Filtro pelo `modinfo` no bloco e na página; testes de exclusão, ocultação e seção oculta |
| Atualização 0.8.0 → 0.9.0 | Campo `NULL` = não avaliado, sem sugestão até a análise; teste de atualização com dados (como na 007) |
| Limites do servidor na tarefa em segundo plano | A regra não depende do limite em bytes; teste com limite indeterminado |
| Concorrência | Gravação no mesmo `save_result`; teste de falha mantendo a lista anterior |

### Alternativas descartadas

- **A**: custo no caminho mais visitado, contrário ao Princípio VI.
- **B1**: uma leitura a mais por renderização e uma tabela a manter, sem ganho.
- **C1**: dado que some em toda limpeza de cache, sem aviso; escrita fora da transação da análise.
- **C2**: inviável sem mudar o significado de `block_coursecare_act`.

**Status**: aguardando aprovação do proprietário do produto para a mudança de persistência (B2).

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

0.9.0 BETA (versão 2026102200). Com B2, passo de atualização que acrescenta o campo `assignideas` (anulável) a `block_coursecare_diag`.
