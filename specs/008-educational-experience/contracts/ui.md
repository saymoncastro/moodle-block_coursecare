# Contract: bloco e página (Feature 008)

## Bloco

Ordem fixa: identidade → estado educativo (título + mensagem) → indicador (rótulo, volume, data) → notas →
acesso. Um único número: o volume.

| Estado educativo | Quando | Título (en / pt_BR) | Mensagem | Acesso |
| --- | --- | --- | --- | --- |
| `idea` | resultado com arquivos e sugestão aplicável (qualquer estado com resultado) | "An idea for your course" / "Há uma ideia para a sua sala" | "We found a suggestion about large materials." / "Encontramos uma sugestão sobre materiais de grande porte." | "See the suggestion" / "Ver sugestão" → `overview.php?id=…#block_coursecare-suggestions` |
| `allgood` | estado `complete`, `filecount > 0`, sem `partial`, sem sugestão | "All good here!" / "Tudo bem por aqui!" | "We found no suggestions for the criteria checked in this analysis." / "Não identificamos sugestões nos critérios avaliados nesta análise." | link atual (`overview:link`) → `overview.php?id=…` (abre "Em detalhes") |
| `neutral` | resultado com arquivos, sem sugestão, e `outdated`, `failed` ou `partial` | — | — (só indicador e notas) | link atual |
| `nofiles` | resultado sem arquivos | — | `diagnostic:nofiles` | link atual |
| `preparing` | `queued`/`running` sem resultado | — | `diagnostic:preparing` | nenhum |
| `notavailable` | sem análise ou sem resultado | — | `diagnostic:notavailable` | nenhum |

Notas (só com resultado), nesta ordem: `diagnostic:updatefailed` (falha), `diagnostic:mayoutdated`
(desatualizado), `block:partial` (parcial, sem números).

Os textos em inglês acima são propostos; revisão de tom pelo proprietário do produto (grupo B).

## Página

```
h1 (tema)  nome da sala
<div block_coursecare-overview data-initialtab=suggestions|details>
  cabeçalho:  h2 identidade (pluginname) + tagline + ilustração decorativa
              cartão: h3 "Your course in numbers" + volume + data + notas (partial com números, como hoje)
  [sem resultado: mensagem de estado atual; nada abaixo]
  nav.tablist[hidden] aria-label="…": tab "Suggestions for you" | tab "Details"
  section#block_coursecare-tab-suggestions  h2 "Suggestions for you" (visível sem scripts; oculto visualmente com as abas)
     faixa: título + mensagem do estado + nota "Suggestions are possibilities…"
     section#block_coursecare-suggestions: cartão da 004 (o que observamos / por que / o que fazer / atividades) ou frase neutra da 004
  section#block_coursecare-tab-details  h2 "Details" (idem)
     distribuição (+ arquivos de grande porte), histórico, materiais por atividade, potencial, detalhes técnicos
  mensagem de fechamento
</div>
```

- Sem scripts: a lista de abas fica oculta; as duas seções aparecem, cada uma com o seu título (`h2`
  visível no topo de cada seção, que o módulo oculta visualmente quando as abas estão ativas, pois o nome
  da aba já o anuncia).
- Com scripts: `role="tablist"`/`tab`/`tabpanel`, `aria-selected`, `aria-controls`, `aria-labelledby`,
  `tabindex` itinerante; setas, Home, End; painel inativo `hidden`.
- Aba inicial: fragmento > `data-initialtab`.
- Fragmentos: `#block_coursecare-tab-suggestions`, `#block_coursecare-tab-details` e os ids das seções
  (`#block_coursecare-suggestions` → aba de sugestões; `#block_coursecare-history`,
  `#block_coursecare-potential`, distribuição, materiais, detalhes técnicos → aba de detalhes); foco na
  seção alvo.
- Troca de aba: `history.pushState` com o fragmento da aba; voltar/avançar restauram.
- Impressão: todos os painéis visíveis, lista de abas oculta.
