# Contract: Capabilities e controle de acesso

Fonte das regras nativas: research.md R2 e R3 (código do Moodle 4.5).

## Definições (`db/access.php`)

| Capability | captype | contextlevel | riskbitmask | archetypes (CAP_ALLOW) | clonepermissionsfrom |
| --- | --- | --- | --- | --- | --- |
| `block/coursecare:addinstance` | `write` | `CONTEXT_BLOCK` | `RISK_SPAM \| RISK_XSS` | `editingteacher`, `manager` | `moodle/site:manageblocks` |
| `block/coursecare:view` | `read` | `CONTEXT_BLOCK` | — | `editingteacher`, `manager` | — |

- `block/coursecare:myaddinstance` **não** é definida: o bloco não é permitido no Painel
  (`'my' => false`), e o core não consulta essa capability nesse caso.
- Strings obrigatórias: `coursecare:addinstance` e `coursecare:view` em `lang/en`.

## Comportamento esperado

| Ação | Condição para permitir | Onde é verificada | Resultado quando negada |
| --- | --- | --- | --- |
| Adicionar | `block/coursecare:addinstance` e `moodle/block:edit` no contexto da página; página `course-view-*`, exceto `course-view-section-*`; sem outra instância na página | Core (`user_can_addto`, `get_addable_blocks`) | CourseCare ausente do seletor de blocos |
| Configurar, mover, ocultar, remover | `moodle/site:manageblocks` (página) e `moodle/block:edit` (bloco) | Core | Controles ausentes |
| Ver conteúdo | `moodle/block:view` **e** `block/coursecare:view`, no contexto do bloco, **e** página principal | Core e `get_content()` do plugin | Conteúdo não é gerado no servidor. Bloco não exibido, exceto pelo caso da invariante 4. |
| Controles de edição | Página principal (`instance_can_be_edited()`) e permissões nativas de edição | Core e plugin | Fora da página principal, sem controles e sem renderização |

## Padrões por arquétipo

| Arquétipo | Adicionar | Ver |
| --- | --- | --- |
| manager | ✅ | ✅ |
| editingteacher | ✅ | ✅ |
| teacher (sem edição) | ❌ | ❌ |
| student | ❌ | ❌ |
| guest / não autenticado | ❌ | ❌ |
| Administrador do site | ✅ (nativo) | ✅ (nativo) |

## Invariantes

1. Nenhuma verificação usa nomes ou ids de papéis. Tudo é decidido por `has_capability()` no
   contexto do bloco.
2. Overrides institucionais, como conceder `:view` a `teacher` em uma categoria, são respeitados
   sem alteração de código.
3. Para quem não tem `:view`, o HTML do conteúdo nunca é gerado (o controle não é apenas visual).
4. **Na página principal**, em modo de edição, quem pode editar o bloco vê o cabeçalho e os
   controles nativos mesmo sem `:view`. Isso é comportamento de gestão do core, e é aceito porque,
   sem os controles, o editor não poderia remover nem configurar o bloco (RK2).
5. **Fora da página principal** (seção, atividade ou qualquer página em que o padrão de exibição
   carregue o bloco), `instance_can_be_edited()` é falso, e o conteúdo é vazio. O core não renderiza
   o bloco nem o cabeçalho, para nenhum usuário e em nenhum modo (research R3).
6. **Unicidade por sala**: o core garante unicidade por página. A unicidade por sala vale nos fluxos
   de usuário (adição, mudança de formato, restauração), e as limitações estão documentadas no
   plano (RK9).
