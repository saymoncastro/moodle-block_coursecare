# Contract: API interna do diagnóstico, tarefas e configurações

Ponto de acesso único aos resultados (FR-053) e contrato das peças de processamento. Nomes de
classes são os previstos; assinaturas finais ficam para a implementação, mantendo o comportamento.

## Camadas (Princípio IX, resolução C6)

| Camada | Namespace | Responsabilidade | Acesso a banco |
| --- | --- | --- | --- |
| Coleta | `\block_coursecare\local\collector\` | Ler metadados do Moodle (`files`, `context`, `block_instances`, repositórios) e ler/gravar as tabelas próprias | **Única** camada com `$DB` |
| Análise | `\block_coursecare\local\analysis\` | Regras de classificação, cálculo dos indicadores a partir dos agregados, verificação de invariantes e resolução de estado | Nenhum (funções puras) |
| Regras de recomendação | — | Não existe nesta feature | — |
| Apresentação | `block_coursecare.php`, `\block_coursecare\output\`, `templates/` | Mensagem do bloco por estado | Nenhum direto (lê pela API abaixo) |

Orquestração (sem regra de negócio própria): `\block_coursecare\local\course_analysis` (usada pela
tarefa ad hoc e pela CLI), `\block_coursecare\task\analyse_course`,
`\block_coursecare\task\queue_analyses`.

## Leitura do resultado (para o bloco e para as Features 003 e 004)

`\block_coursecare\local\collector\diagnostic_store`:
- `get_summary(int $courseid): ?diagnostic_record` — uma leitura por índice único; nunca consulta
  `files`. Retorna nulo se não houver registro.
- `get_categories(int $diagid): array`, `get_activities(int $diagid): array`,
  `get_large_material_links(int $diagid): array` — leituras por `diagid`; não usadas pelo bloco
  nesta feature.

`\block_coursecare\local\analysis\state_resolver::resolve(?diagnostic_record $r, int $now, int
$stalenessseconds): diagnostic_state` — pura. Devolve o estado A–G, o marcador H e se há resultado
exibível, conforme a tabela de [data-model.md](../data-model.md). Testada com tempo fixo.

**Regra de acesso**: a API não verifica permissões; quem exibe (bloco, futuras páginas) MUST
verificar login, contexto e capability antes de chamar e MUST usar o `courseid` do próprio contexto
do bloco, nunca um parâmetro de requisição (FR-041).

## Escrita (somente orquestração)

`diagnostic_store`:
- `request(int $courseid, int $now): bool` — cria ou atualiza o registro para "na fila" só se
  `requeststatus = 0`; devolve se a solicitação foi aceita (o chamador só enfileira a tarefa se
  for).
- `mark_running(...)`, `save_result(int $courseid, diagnostic_result $r, int $now)` (uma transação
  delegada: filhos apagados e reinseridos, registro atualizado), `save_failure(int $courseid,
  string $reason, int $now)`.
- `delete_for_course(int $courseid)` — apaga registro e filhos (leve: só por índice).

## Tarefa ad hoc `\block_coursecare\task\analyse_course`

- `customdata`: `{"courseid": <int>}`. Nada mais (o core registra `customdata` no log).
- `get_default_concurrency_limit()`: 2.
- Fluxo: lock por sala (`block_coursecare`, `analyse_course_<id>`, espera 0, validade 7.200 s) →
  sem lock: sai → sala inexistente ou não elegível: apaga diagnóstico e sai (FR-003b) → resultado
  concluído depois da criação da tarefa: sai → `mark_running` → coleta e análise → invariantes →
  `save_result`. Qualquer `\Throwable`: rollback, `save_failure` e término normal. O lock é
  liberado em `finally`.
- Saída no log (`mtrace`): id da sala, duração, pico de memória, número de registros e código de
  falha. Nunca nomes de arquivos, de atividades ou de usuários.

## Tarefa agendada `\block_coursecare\task\queue_analyses`

`db/tasks.php`: minuto `R`, hora `*`, dia `*`, mês `*`, dia da semana `*`.

Em cada execução, nesta ordem:
1. **Órfãos**: apaga até 500 diagnósticos de salas inexistentes ou não elegíveis.
2. **Presos**: "em execução" com `timestarted` há mais de 2 h → falha `interrupted`; "na fila" com
   `timerequested` há mais de 24 h → `requeststatus = 0`.
3. **Devidas**: salas elegíveis sem registro, ou com `requeststatus = 0` e
   - sem falha e `timecompleted` nulo ou anterior a `agora − intervalo de reanálise`, ou
   - `methodversion` diferente da atual, ou
   - com falha e `timeattempted` anterior a `agora − min(2^(falhas−1), 24) h` (a partir de 5 falhas,
     o intervalo de reanálise),
   ordenadas por `timeattempted` (nulos primeiro), até **500**. Para cada uma: `request()` e,
   se aceita, `queue_adhoc_task(new analyse_course(...))` sem `checkforexisting` (R9).

## Callbacks do bloco

- `instance_create()`: se o contexto pai é de curso (e não o do site): `request()` +
  `queue_adhoc_task`. Nenhuma consulta a `files`.
- `instance_delete()`: se não houver outra instância `coursecare` com o mesmo `parentcontextid`
  (excluindo a própria): `delete_for_course()`.
- `has_config()`: verdadeiro (configurações abaixo).

## Configurações (`settings.php`, administração do site)

| Nome | Tipo | Padrão | Validação | Requisito |
| --- | --- | --- | --- | --- |
| `block_coursecare/largefilethreshold` | inteiro, em MB (1 MB = 1.048.576 bytes) | 50 | ≥ 1 | FR-015a |
| `block_coursecare/reanalysisinterval` | inteiro, em horas | 24 | ≥ 1 | FR-037 |
| `block_coursecare/stalenessthreshold` | inteiro, em horas | 72 | ≥ 1; recomendado > intervalo (texto de ajuda) | FR-038 |

Lote por execução (500), concorrência (2), validade do lock (2 h), detecção de presos (2 h / 24 h),
limite de ligações (1.000) e regra de nova tentativa são **constantes documentadas** no README
(limites de escalabilidade), não configurações, até haver necessidade concreta (Princípio XI).
