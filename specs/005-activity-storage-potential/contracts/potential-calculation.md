# Contract: cálculo do potencial teórico

Regra da spec ("Metodologia validada", FR-010 a FR-015) dividida entre leitura (coletor) e decisão
(análise pura).

## Coletor: `\block_coursecare\local\collector\assign_settings::for_course(int $courseid, array $cmids): array`

Lê, para as tarefas indicadas (`cmid` → instância), e devolve em `assigns`, por `cmid`:

```text
[
  'enabled'         => bool,      // assign_plugin_config file/assignsubmission/enabled = 1
  'maxfiles'        => ?int,      // maxfilesubmissions (null quando ausente)
  'activitybytes'   => int,       // maxsubmissionsizebytes (0 quando ausente)
  'maxattempts'     => int,       // assign.maxattempts (-1 = ilimitado)
  'reopenmethod'    => string,    // assign.attemptreopenmethod
  'teamsubmission'  => bool,
  'groupingid'      => int,       // teamsubmissiongroupingid
  'preventnogroup'  => bool,      // preventsubmissionnotingroup
  'participants'    => int[],     // ids de quem pode enviar (inscrição ativa, mod/assign:submit no contexto da tarefa)
]
```

E, uma vez por sala:

```text
[
  'limits' => ['server' => int, 'site' => int, 'course' => int, 'default' => int],
  'memberships' => [userid => groupid[]],        // grupos da sala
  'groupings'   => [groupingid => groupid[]],    // grupos de cada agrupamento usado
]
```

Regras de leitura (research R2, R3):
- `participants` da sala: uma consulta (`get_enrolled_sql` no contexto da sala, capability
  `mod/assign:submit`, só inscrições ativas); contextos de tarefa com sobreposição da capability ou papel
  local: consulta no contexto da tarefa; demais tarefas recebem os da sala.
- `server`: `get_max_upload_file_size()` sem limites de site, sala ou tarefa (só os do PHP da requisição).
- Nenhuma leitura de `files`, de envios (`assign_submission`) ou de notas.

## Análise: `\block_coursecare\local\analysis\storage_potential::evaluate(array $settings, array $course): array`

Saída por tarefa:

```text
[
  'status'     => 'potential' | 'unlimited' | 'nofiles' | 'nobody' | 'unknown',
  'unit'       => 'participant' | 'group',
  'units'      => int,
  'files'      => int,
  'filebytes'  => int,           // limite efetivo por arquivo
  'limitsource'=> 'activity' | 'default' | 'course' | 'site' | 'server' | null, // null sem limite determinado
  'attempts'   => ?int,          // null = ilimitado
  'perattempt' => int,           // units × files × filebytes
  'potential'  => ?int,          // perattempt × attempts; null quando ilimitado ou sem número
]
```

Regras:
1. `maxfiles` nulo ou ≤ 0 → `nofiles`.
2. Limite efetivo: menor valor > 0 entre `server`, `site`, `course` e (`activitybytes` > 0 ? ele : `default`);
   origem = nível do mínimo; empate na ordem tarefa, padrão, sala, site, servidor. Sem nenhum valor > 0
   → `unknown`.
3. Tentativas: `maxattempts = 1` ou `reopenmethod = none` → 1; `-1` → ilimitado; `> 1` → `maxattempts`;
   outro valor (0 ou negativo diferente de −1) → `unknown`. Ordem de precedência: `nofiles`, `unknown`, `nobody`.
4. Unidades: sem envio em grupo → número de participantes; em grupo, regra de research R3 (grupos com
   participantes do agrupamento, ou dos participantes em exatamente um grupo; + grupo padrão quando
   houver participante sem grupo único e `preventnogroup` falso).
5. `units = 0` → `nobody`.
6. Ilimitado → `unlimited` com `perattempt`; senão `potential` = `perattempt × attempts`.

## Casos de teste obrigatórios

| Caso | Entrada | Saída |
| --- | --- | --- |
| Padrão | 30 participantes, 3 arquivos, tarefa 10 MB, 1 tentativa, limites maiores | potential 900 MB, origem tarefa |
| Padrão do site | tarefa 0, padrão 20 MB, sala 0, site 0, servidor 100 MB | 20 MB, origem padrão |
| Sala menor | tarefa 50 MB, sala 10 MB | 10 MB, origem sala |
| Servidor menor | tudo maior, servidor 2 MB | 2 MB, origem servidor |
| Tentativas | máximo 3 / ilimitado / reabertura none com máximo 5 | ×3 / unlimited / ×1 |
| Grupos | 6 grupos com participantes; agrupamento com 2 de 4 grupos; sem grupos; sem grupos e envio fora de grupo impedido; participante em dois grupos | 6; 2 (+padrão quando aplicável); 1; nobody; padrão |
| Sem arquivos | maxfiles 0 / ausente | nofiles |
| Ninguém | 0 participantes | nobody |
| Valores grandes | 500 × 10 × 20 × 2 GB | número exato, sem estouro |
