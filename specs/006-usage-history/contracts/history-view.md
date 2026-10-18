# Contract: visão da evolução (regra pura)

`\block_coursecare\local\analysis\history_view::build(array $points): ?array`

Entrada: pontos de `get_history()` em ordem crescente. Sem pontos → `null` (a seção não aparece).

Saída:

```text
[
  'latest'   => ponto mais recente,
  'previous' => ponto anterior ou null,
  'change'   => 'first' | 'increased' | 'decreased' | 'unchanged' | 'methodchanged',
  'delta'    => int (|latest.logicalbytes − previous.logicalbytes|; 0 quando não se aplica),
  'days'     => [timeday => logicalbytes|null]  // do primeiro ao último ponto, um por dia
  'points'   => pontos do mais recente ao mais antigo
]
```

Regras:
1. Um ponto → `first`.
2. Versões de método diferentes entre `latest` e `previous` → `methodchanged`, sem `delta`.
3. Senão, compara `logicalbytes`: maior → `increased`; menor → `decreased`; igual → `unchanged`.
4. `days`: cada dia entre o primeiro e o último ponto; `null` onde não há ponto (sem interpolação).

Casos de teste: 0, 1, 2 (aumento, redução, igual), mudança de método, lacuna de vários dias, pontos
parciais, valores grandes sem estouro.
