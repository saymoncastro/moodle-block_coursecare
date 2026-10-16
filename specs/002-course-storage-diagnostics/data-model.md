# Data Model: Course Storage Diagnostics

**Resumo**: a feature cria as primeiras tabelas do plugin (`db/install.xml`, com `db/upgrade.php`
para a atualização a partir da `0.1.0`). Elas guardam **só o resultado vigente** de cada sala
elegível: sem histórico (Feature 006), sem colunas de usuário, sem nomes de arquivos. Os valores em
bytes usam inteiros de 20 dígitos (BIGINT), porque somas de muitos arquivos passam de 2^31.

Decisões e evidências: [research.md](research.md) (R7–R10, R14). Algoritmos:
[contracts/indicators.md](contracts/indicators.md).

## Tabelas

### `block_coursecare_diag` — diagnóstico da sala (um por sala)

| Campo | Tipo | Nulo | Significado |
| --- | --- | --- | --- |
| `id` | int(10) seq | não | Chave primária |
| `courseid` | int(10) | não | Sala. **Único** (índice único) |
| `requeststatus` | int(2) | não, padrão 0 | 0 = nenhuma solicitação; 1 = na fila; 2 = em execução |
| `timerequested` | int(10) | sim | Quando a solicitação atual entrou na fila |
| `timestarted` | int(10) | sim | Início da análise em execução |
| `timeattempted` | int(10) | sim | Fim da última tentativa (sucesso ou falha) |
| `timecompleted` | int(10) | sim | Fim da última análise **concluída**. Nulo = nunca concluída |
| `lastattemptfailed` | int(1) | não, padrão 0 | 1 se a última tentativa falhou |
| `failurecount` | int(4) | não, padrão 0 | Falhas consecutivas (zera no sucesso) |
| `failurereason` | char(30) | sim | Código curto: nome curto da classe da exceção, `interrupted` ou `inconsistent`. Sem mensagem livre |
| `methodversion` | int(4) | sim | Versão da metodologia usada no resultado vigente |
| `largethreshold` | int(20) | sim | Limite de grande porte usado, em bytes (FR-015b) |
| `filecount` | int(10) | sim | Registros do escopo principal |
| `logicalbytes` | int(20) | sim | Indicador A |
| `uniquecount` | int(10) | sim | Conteúdos distintos do escopo principal |
| `uniquebytes` | int(20) | sim | Indicador B |
| `largecount` / `largebytes` | int(10) / int(20) | sim | Indicador D: registros e volume lógico |
| `largeuniquecount` / `largeuniquebytes` | int(10) / int(20) | sim | Indicador D: conteúdos distintos e volume único |
| `unclassifiedcount` / `unclassifiedbytes` | int(10) / int(20) | sim | Cobertura da classificação (FR-024) |
| `refinternalcount` / `refinternalbytes` | int(10) / int(20) | sim | Referências internas e tamanho registrado |
| `refexternalcount` / `refexternalbytes` | int(10) / int(20) | sim | Referências externas sincronizadas e tamanho registrado |
| `refundeterminedcount` | int(10) | sim | Referências indeterminadas (sem tamanho) |
| `brcount` / `brbytes` | int(10) / int(20) | sim | Agregado de backup e lixeira: registros e volume lógico |
| `bruniquecount` / `bruniquebytes` | int(10) / int(20) | sim | Agregado de backup e lixeira: conteúdo único do conjunto |
| `partial` | int(1) | sim | 1 se há Não classificado ou referência indeterminada (estado H) |
| `largelinkstruncated` | int(1) | sim | 1 se a lista de ligações de grande porte foi limitada |
| `timemodified` | int(10) | não | Última alteração do registro |

**Índices**: único `courseid`; `requeststatus, timeattempted` (seleção da tarefa agendada).

Os campos de resultado são nulos até a primeira análise concluída. Um valor nulo nunca é exibido
como zero (FR-047).

### `block_coursecare_cat` — agregado por categoria

| Campo | Tipo | Significado |
| --- | --- | --- |
| `id` | int(10) seq | Chave primária |
| `diagid` | int(10) | FK → `block_coursecare_diag.id` |
| `category` | char(20) | `material`, `submission`, `other`, `unclassified`, `backup`, `recyclebin` |
| `filecount` | int(10) | Registros |
| `logicalbytes` | int(20) | Volume lógico |
| `uniquecount` | int(10) | Conteúdos distintos na categoria |
| `uniquebytes` | int(20) | Volume único na categoria |

**Índices**: único `diagid, category`. Só categorias com ao menos um registro são gravadas.
`backup` e `recyclebin` formam o agregado à parte (FR-019a) e **não** entram no indicador C.

### `block_coursecare_act` — agregado por atividade

| Campo | Tipo | Significado |
| --- | --- | --- |
| `id` | int(10) seq | Chave primária |
| `diagid` | int(10) | FK → `block_coursecare_diag.id` |
| `cmid` | int(10) | Módulo do curso; **0 = nível da sala** (contexto do curso e blocos da sala) |
| `filecount` | int(10) | Registros do escopo principal |
| `logicalbytes` | int(20) | Volume lógico |
| `materialbytes`, `submissionbytes`, `otherbytes`, `unclassifiedbytes` | int(20) | Volume por categoria |
| `largecount` / `largebytes` | int(10) / int(20) | Arquivos de grande porte: registros e volume |
| `largeuniquecount` / `largeuniquebytes` | int(10) / int(20) | Arquivos de grande porte: conteúdos distintos e volume único |

**Índices**: único `diagid, cmid`. Guarda o `cmid`, nunca o nome da atividade (FR-018; o nome é
resolvido na apresentação, com a visibilidade nativa, na Feature 003).

### `block_coursecare_large` — ligação a arquivos grandes de material

| Campo | Tipo | Significado |
| --- | --- | --- |
| `id` | int(10) seq | Chave primária |
| `diagid` | int(10) | FK → `block_coursecare_diag.id` |
| `fileid` | int(10) | `files.id` do registro (só categoria `material`) |
| `cmid` | int(10) | Atividade (0 = nível da sala) |
| `filesize` | int(20) | Tamanho registrado no momento da análise |

**Índices**: `diagid`. No máximo **1.000** linhas por sala, as maiores; se houver mais,
`largelinkstruncated = 1`. Os totais do indicador D não são afetados pelo limite. Nenhuma ligação
para envios, outros, não classificados, referências, backups ou lixeira (R14).

## Regras de validação (verificadas por testes)

- `logicalbytes = Σ cat.logicalbytes` das categorias do escopo principal (FR-014).
- `uniquebytes ≤ logicalbytes`; `uniquebytes ≤ Σ cat.uniquebytes` do escopo principal; para cada
  categoria, `uniquebytes ≤ logicalbytes`.
- `logicalbytes = Σ act.logicalbytes` (nível da sala incluído); `largecount = Σ act.largecount`.
- `largeuniquebytes ≤ largebytes`; `bruniquebytes ≤ brbytes = Σ` das categorias `backup` e
  `recyclebin`.
- `filecount = 0` ⇔ `logicalbytes = 0` e nenhuma linha de categoria do escopo principal.
- Nenhuma coluna aceita identificador de usuário, nome, caminho ou `pathnamehash`.

## Estados

O estado **exibido** é calculado na leitura, a partir do registro, do relógio e das configurações
(função pura `state_resolver`; [contracts/diagnostic-api.md](contracts/diagnostic-api.md)). Nenhuma
escrita é necessária para um resultado "envelhecer".

| Estado (FR-045) | Condição |
| --- | --- |
| A. Ainda não analisado | Sem registro, ou registro com `requeststatus = 0`, `timecompleted` nulo e `lastattemptfailed = 0` |
| B. Aguardando | `timecompleted` nulo e `requeststatus = 1` |
| C. Em andamento | `timecompleted` nulo e `requeststatus = 2` |
| F sem resultado | `timecompleted` nulo e `lastattemptfailed = 1` e `requeststatus = 0` (exibe como A) |
| G. Sem arquivos | Há resultado, `filecount = 0` (após verificar F e E) |
| F com resultado | Há resultado e `lastattemptfailed = 1` |
| E. Desatualizado | Há resultado e `agora − timecompleted > limite de atualidade` |
| D. Concluído | Há resultado, nenhuma das anteriores |
| H. Parcial | Marcador sobre D ou G quando `partial = 1` |

Ordem de precedência com resultado: F → E → G/D. Com resultado e solicitação pendente (B ou C), o
bloco exibe o resultado anterior com seu estado (FR-046).

## Transições do registro

```text
(sem registro) --instance_create / tarefa agendada--> requeststatus=1 (na fila)
na fila --tarefa ad hoc obtém lock e a sala é elegível--> requeststatus=2 (em execução)
em execução --sucesso (transação)--> requeststatus=0, timecompleted=agora, lastattemptfailed=0, failurecount=0, resultado novo
em execução --exceção--> requeststatus=0, lastattemptfailed=1, failurecount+1, failurereason, resultado anterior preservado
em execução --processo morto há > 2 h (tarefa agendada)--> igual à exceção, failurereason='interrupted'
na fila --sem execução há > 24 h (tarefa agendada)--> requeststatus=0 (pode ser reenfileirada)
qualquer --última instância removida / curso excluído / sala não elegível (tarefa agendada)--> registro e filhos apagados
```

## Entidades core apenas lidas

| Tabela | Uso | Índice usado |
| --- | --- | --- |
| `block_instances` | Elegibilidade (`blockname`, `parentcontextid`) | `blocknameindex` |
| `context` | Escopo (curso e descendentes) | único `contextlevel, instanceid`; `path` |
| `files` | Metadados (contexto, componente, área, hash, tamanho, referência, status) | FK `contextid` |
| `files_reference`, `repository_instances`, `repository` | Tipo de referência | Chaves primárias |
| `course_modules` | Nenhum: o `cmid` vem de `context.instanceid` dos contextos de módulo | — |

## Ciclo de vida dos dados

- **Retenção**: enquanto a sala for elegível. Sem histórico.
- **Remoção**: na remoção da última instância (inclui exclusão do curso e desinstalação) e, como
  rede de segurança, pela tarefa agendada (órfãos). A desinstalação também remove as tabelas.
- **Backup e restauração**: os dados **não** fazem parte do backup do bloco (são derivados). A sala
  restaurada é analisada na próxima execução da tarefa agendada.
