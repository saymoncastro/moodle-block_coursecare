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

## R4 — Arquitetura de consistência bloco × página (FR-013, FR-014) — B2 detalhada (revisão de 2026-10-19)

### 1. Conteúdo do campo

**Escolha**: uma **estrutura de sugestões pequena e versionada**, em um campo de texto anulável
`suggestions` de `block_coursecare_diag`:

```json
{"v": 1, "items": [{"kind": "attempts", "cmid": 123}, {"kind": "attempts", "cmid": 456}]}
```

| Valor | Significado |
| --- | --- |
| `NULL` | **Ainda não calculado** (registros existentes depois da atualização, até a próxima análise da sala) |
| `{"v":1,"items":[]}` | Calculado nesta análise: **nenhuma** sugestão dessas categorias |
| `{"v":1,"items":[…]}` | Calculado: as atividades e o tipo de cada sugestão |

- **Por que não um booleano ou uma contagem**: a página precisa saber **quais** tarefas mostrar como
  cartão; com só um sinal, ela teria de recalcular a lista ao vivo e poderia divergir do bloco.
- **Por que não uma lista de `cmid` sem tipo**: amarraria o campo a uma única recomendação; com `kind`,
  uma categoria futura acrescenta entradas de outro tipo sem mudar o campo nem a tabela (sem framework:
  só um formato).
- **Limitações**: não é consultável por SQL (não precisa: é lido com o registro da sala); a versão `v`
  permite mudar o formato depois; um valor ilegível é tratado como `NULL` (não calculado), nunca como
  "sem sugestões". A sugestão de materiais grandes (004) continua derivada de `block_coursecare_large`,
  sem migração.
- **Tamanho**: `{"v":1,"items":[]}` (19 bytes) na maioria das salas; ~35 bytes por tarefa sugerida.

### 2. Consistência entre bloco e página: a mesma referência

Uma **única função** de leitura, usada pelo bloco e pela página, devolve as sugestões de tarefas
aplicáveis **agora**: ela lê o campo do registro da sala (o mesmo registro que bloco e página já leem) e
aplica o **mesmo filtro ao vivo**, pelo `modinfo` em cache: a atividade ainda existe, é uma tarefa e está
visível para os estudantes. Bloco e página nunca usam outra fonte para decidir **se** há sugestão de
tarefa.

```
Análise da 002 (tarefa ad hoc, trava por sala)
  → arquivos (como hoje) + tarefas da sala (seleção e coletor da 005) → evaluate() da 005
  → regra do critério (método puro da 005) → itens {kind, cmid}
  → save_result: resultado + histórico + sugestões, numa transação, com a data timecompleted
Bloco  → registro da sala → função de leitura (campo + filtro) → estado educativo (008/009)
Página → registro da sala → a mesma função → faixa e cartões "com base na análise de {data}"
         → orientação informativa de cada tarefa: 005 ao vivo (configuração atual, "agora")
Atualização → próxima análise (agendada pela 002; padrão 1 dia; ou CLI) substitui o campo
```

O cálculo ao vivo da página (005) **nunca remove nem acrescenta** um cartão: só acrescenta uma nota quando
a configuração atual já não corresponde (item 3). Assim, se o bloco diz que há sugestão, a página mostra a
mesma sugestão.

### 3. Comportamento em cada situação

| Situação | Bloco | Página |
| --- | --- | --- |
| Professor altera a tarefa (qualquer configuração) | Inalterado até a próxima análise | Cartão continua, com a data da análise; se a configuração atual não atende mais ao critério, nota: "As configurações desta tarefa mudaram depois da análise de {data}; a sugestão será revista na próxima análise." A orientação informativa mostra a configuração atual |
| Limite de tentativas modificado | Igual à linha anterior | Igual; de limitado para ilimitado: sem cartão até a próxima análise (a orientação já mostra "ilimitado") |
| Tarefa excluída | A sugestão some na hora (filtro) | Some na hora, nos dois lugares |
| Tarefa ocultada (ou a seção) | Some na hora (filtro de visibilidade) | Some na hora; volta se a tarefa voltar a ficar visível e ainda estiver na lista |
| Diagnóstico desatualizado | Nota da 008 ("pode estar desatualizada"); sugestão mantida com a sua data | Cartão "com base na análise de {data}" |
| Análise falha | Nada é gravado: lista e data anteriores; nota de falha da 008 | Igual |
| Sala sem diagnóstico | Sem resultado: estados da 008, sem sugestão | Sem abas nem cartões (008) |
| Campo `NULL` (antes da primeira análise depois da atualização) | Sem sugestão de tarefa e **sem "Tudo bem por aqui!"** (o critério ainda não foi avaliado); se houver materiais grandes, "Há uma ideia" | Sem cartão de tarefa; a orientação informativa já aparece |

A data de referência é sempre `timecompleted` do registro: as sugestões nunca aparecem como verificadas
agora.

### 4. Reutilização da Feature 005

| Reutilizado sem mudança de regra | Acrescentado |
| --- | --- |
| `assign_settings::for_course()` (coletor) | Extração, sem mudança de lógica, da seleção das tarefas que hoje está em `course_overview::potential_for()`, para que página e análise usem a mesma (na análise, sem o filtro do usuário atual) |
| `storage_potential::evaluate()` (arquivos, limite, tentativas, reabertura, "até passar", participantes, grupos) | Um método puro na própria `storage_potential`: critério = `files > 0` ∧ `maxattempts = -1` ∧ `attempts = null` (o `evaluate()` já resolveu reabertura e "até passar") ∧ `units > 0` |
| Frases dos fatores da 005 na página | Leitura/escrita do campo (formato v1) e a função de leitura com o filtro (item 2) |

Nenhuma consulta SQL nova sobre as configurações do `mod_assign`: a análise chama o mesmo coletor que a
página.

### 5. Evolução futura

Uma categoria nova = um `kind` novo, a sua regra (na classe do domínio correspondente) e as suas strings;
o campo, a gravação, a leitura, o filtro e a consistência bloco × página não mudam. Nada de registro de
regras genérico nesta feature.

### 6. Compatibilidade e migração

| Aspecto | Impacto |
| --- | --- |
| XMLDB | `install.xml`: campo `suggestions` TEXT, `NOTNULL="false"`, sem padrão, sem índice; `upgrade.php`: `add_field` se não existir, savepoint 2026102200 |
| Registros existentes | Ficam `NULL` = não calculado; preenchidos na próxima análise de cada sala (nenhum preenchimento em massa na atualização) |
| Desconhecido × ausente | `NULL`/ilegível = não calculado (nem sugestão, nem "Tudo bem por aqui!"); `items: []` = calculado, nenhuma |
| Atualização do plugin | Só o campo; nenhum dado reescrito; idempotente; testada com dados (como na 007) |
| Concorrência e atomicidade | Gravado no mesmo `save_result` da 002 (transação delegada) sob a trava por sala; leitores veem o registro antes ou depois, nunca pela metade |
| PostgreSQL e MariaDB | TEXT do XMLDB (text/longtext); JSON codificado e lido em PHP, sem função JSON do banco |
| Desinstalação | O campo some com a tabela |
| Desempenho | Bloco e página: 0 leituras novas. Análise: o coletor da 005 (5 a 7 consultas) em salas com tarefas que aceitam arquivos; nenhuma leitura da tabela de arquivos além da 002 |
| Privacidade | Só `cmid` e tipo; nenhum dado pessoal; `privacy:metadata` atualizado |

### 7. Comparação final

| Critério | A. Sob demanda | B1. Tabela auxiliar | **B2. Campo no diagnóstico** | C. Cache independente |
| --- | --- | --- | --- | --- |
| Simplicidade | Simples no código; custo escondido na renderização | Tabela, índice, limpeza, consulta própria | **1 campo, 1 formato, 1 função de leitura** | Definição de cache, chaves e invalidação |
| Consistência | Total, ao vivo | Total | **Total (mesma referência e mesmo filtro)** | Depende da invalidação; perde tudo em cada limpeza de cache |
| Desempenho | Pior no caminho mais visitado (participantes, notas a cada renderização) | +1 leitura por renderização | **0 leituras por renderização** | +1 leitura de cache; recálculo após limpeza |
| Evolução futura | Cada critério novo pesa na renderização | Nova coluna ou tabela por categoria | **Novo `kind`, mesmo campo** | Novas chaves e eventos |
| Manutenção | Baixa | Média | **Baixa** | Alta (observadores de eventos) |

**Recomendação definitiva: B2**, com a estrutura versionada `{"v":1,"items":[{kind,cmid}]}`. É a opção
mais simples que mantém bloco e página na mesma referência, não acrescenta leitura à renderização, reusa o
processamento da 002 e as regras da 005, distingue "não calculado" de "nenhuma sugestão" e aceita novas
categorias sem nova tabela.

**Status**: aguardando aprovação do proprietário do produto para a mudança de persistência.

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

0.9.0 BETA (versão 2026102200). Com B2, passo de atualização que acrescenta o campo `suggestions` (TEXT, anulável) a `block_coursecare_diag`.
