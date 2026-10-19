# Data model: Educational Experience (Feature 008)

Nenhuma tabela, campo, configuração ou capability nova. As entidades abaixo são **derivadas** na
renderização.

## Estado educativo (derivado)

| Campo | Origem | Regra |
| --- | --- | --- |
| `kind` | `diagnostic_state` + sugestão | `idea` / `allgood` / `neutral` / `nofiles` / `preparing` / `notavailable` (contracts/ui.md) |
| `title` | string | só `idea` e `allgood` |
| `message` | string | `idea`, `allgood`, `nofiles`, `preparing`, `notavailable` |
| `notes[]` | `state`, `partial` | falha, desatualização, parcial (bloco: sem números) |
| `action` | `kind` | "Ver sugestão" (`idea`), link atual (resultado sem sugestão), nenhum (sem resultado) |

Entradas: `state` (`notanalysed`, `queued`, `running`, `complete`, `outdated`, `failed`, `nofiles`),
`hasresult`, `filecount`, `partial` (marcador H da 002), `hassuggestion` (004). Invariantes:
- `allgood` ⇒ `state = complete` ∧ `filecount > 0` ∧ ¬`partial` ∧ ¬`hassuggestion`.
- `idea` ⇒ `hasresult` ∧ `filecount > 0` ∧ `hassuggestion`.
- Nenhum estado positivo com nota de falha, desatualização ou parcial.

## Área da página (derivada)

| Área | Conteúdo | Aba inicial quando |
| --- | --- | --- |
| `suggestions` | faixa de contexto + seção de sugestões da 004 | `hassuggestion` |
| `details` | seções da 002, 003, 005, 006 (inalteradas em dados) | ¬`hassuggestion` |

Sem resultado: nenhuma área (mensagem de estado).
