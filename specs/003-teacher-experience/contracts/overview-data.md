# Contract: dados da visão da sala

Estrutura completa em [data-model.md](../data-model.md). Este contrato fixa as interfaces internas e
as regras verificáveis.

## Coleta (`\block_coursecare\local\collector`)

- `diagnostic_store::get_large_materials_by_activity(int $diagid): array` — linhas
  `cmid → {count, bytes}` agregadas de `block_coursecare_large` por `cmid`, numa consulta por
  `diagid` (índice da chave estrangeira). As ligações já são só de materiais (Feature 002, D10).
- `course_structure::get_activities(int $courseid): array` — `cmid → {name, url|null, uservisible}`
  para o usuário atual, de `get_fast_modinfo($courseid)`; `name` por `get_formatted_name()`; `url`
  nula quando a atividade não tem página (por exemplo, área de texto e mídia). Nenhuma consulta a
  `files` pelo plugin; se o cache de módulos estiver frio, o core o reconstrói e essa reconstrução lê os
  arquivos dos recursos (comportamento do core, o mesmo da página da sala).

## Análise (`\block_coursecare\local\analysis\overview_builder`, pura)

Entrada: o `diagnostic_state` resolvido, o registro do diagnóstico (ou `null`), as linhas de
categoria e de atividade (`get_categories`, `get_activities` da Feature 002), os grandes de materiais
por atividade e a estrutura da sala. Saída: a visão da sala do data-model.

Regras (cada uma com teste PHPUnit):

1. **FR-030 (privacidade)**: todo valor por atividade (`bytes`, `share`, `largecount`, `largebytes`)
   vem só de `materialbytes` e das ligações de materiais. Nenhum campo de atividade usa `filecount`,
   `logicalbytes`, `submissionbytes`, `otherbytes`, `unclassifiedbytes`, `largecount` ou
   `largebytes` da linha de atividade. Teste: uma sala com envios grandes numa tarefa com poucos
   materiais; nenhum valor da tarefa difere do derivado de `materialbytes`, e a ordenação é a de
   `materialbytes`.
2. Atividades com `materialbytes = 0` não aparecem.
3. Ordenação por `materialbytes` decrescente, desempate pelo nome; `first` com até 10; `rest` com o
   restante.
4. `cmid 0` → `courselevel`; `uservisible = false` → somado em `others`, sem nome; `cmid` ausente da
   estrutura → somado em `removed`, sem nome.
5. `Σ bytes` de itens e grupos = bytes da categoria `material`; `Σ bytes` das categorias = total.
6. `share()` conforme o data-model: "menos de 1%" para `0 < x < 1%`, nunca "0%" nem "1%" para
   esses valores; a partir de 1%, inteiro mais próximo.
7. Sem resultado: nenhuma seção com números; G: só resumo e detalhes técnicos.
8. Grandes de materiais por atividade ordenados por `largebytes`, com atividades ocultas ao usuário
   em `others` e removidas em `removed`, como na regra 4; `truncated` quando
   `largelinkstruncated = 1`.

## Apresentação (`\block_coursecare\output`)

- `course_overview` (renderable e templatable) recebe a visão e exporta o contexto dos templates com
  todas as frases já resolvidas por `get_string()` (frases completas, parâmetros únicos ou objetos
  `{$a->...}`), tamanhos por `diagnostic_line::size()` (R9) e datas por `userdate()`.
- `diagnostic_line` (Feature 002) recebe a URL da página quando há resultado e a exporta para
  `content.mustache`.
- Templates escapam toda saída (`{{ }}`); nenhum texto fixo; exemplos de contexto cobrindo os estados
  para o lint de Mustache.
