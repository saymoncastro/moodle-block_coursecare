# Contract: regra pura de S1

Classe `\block_coursecare\local\recommendation\large_materials` (camada de regras de recomendação,
Princípio IX). Sem banco, sessão, strings ou saída; só dados.

## `public static function evaluate(?array $large): ?array`

**Entrada**: a parte `large` da visão do `overview_builder` ([data-model.md](../data-model.md)), ou
`null` (sem resultado, ou resultado sem arquivos).

**Saída**: `null` quando não há ligação a material de grande porte; caso contrário:

```text
[
  'count'     => int,   // soma das ligações a materiais (materials + courselevel + others + removed)
  'bytes'     => int,   // soma dos tamanhos dos mesmos
  'threshold' => int,   // large.threshold
  'places'    => [      // 1 a 3 lugares com nome, por bytes desc
      ['kind' => 'activity', 'cmid' => int, 'name' => string, 'url' => ?string, 'bytes' => int],
      ['kind' => 'courselevel', 'bytes' => int],
  ],
  'more'      => int,   // lugares com nome além dos 3, + 1 por grupo others/removed presente
  'truncated' => bool,
]
```

**Regras**:
1. `null` se `$large` é `null` ou se a soma de `count` é 0.
2. Lugares com nome: cada item de `materials` e, se existir, `courselevel`. Ordem: `bytes` desc;
   empate entre atividades pelo nome (minúsculas); o nível da sala depois das atividades de mesmo
   tamanho.
3. `places` = os três primeiros; `more` = lugares com nome restantes + `others` (1, se existir) +
   `removed` (1, se existir).
4. `others` e `removed` nunca entram em `places`.
5. `large.count` e `large.bytes` (resumo da sala, com envios) nunca são usados.

**Testes** (`tests/recommendation/large_materials_test.php`): `null` sem ligações e com `null`; soma
com todos os grupos; ordem e corte em três; empate; nível da sala como lugar; `others`/`removed` só em
`more`; `truncated` repassado; resumo da sala com envios grandes e sem ligações → `null`.
