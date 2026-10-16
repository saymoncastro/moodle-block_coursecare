# Specification Quality Checklist: Course Storage Diagnostics

**Purpose**: Validate specification completeness and quality before proceeding to planning
**Created**: 2026-10-16
**Feature**: [spec.md](../spec.md)

## Content Quality

- [x] No implementation details (languages, frameworks, APIs)
- [x] Focused on user value and business needs
- [x] Written for non-technical stakeholders
- [x] All mandatory sections completed

## Requirement Completeness

- [x] No [NEEDS CLARIFICATION] markers remain
- [x] Requirements are testable and unambiguous
- [x] Success criteria are measurable
- [x] Success criteria are technology-agnostic (no implementation details)
- [x] All acceptance scenarios are defined
- [x] Edge cases are identified
- [x] Scope is clearly bounded
- [x] Dependencies and assumptions identified

## Feature Readiness

- [x] All functional requirements have clear acceptance criteria
- [x] User scenarios cover primary flows
- [x] Feature meets measurable outcomes defined in Success Criteria
- [x] No implementation details leak into specification

## Notes

- Validada em 2026-10-16 em uma iteração. Durante a validação, foi acrescentada a seção "Fora do
  escopo" (escopo delimitado) e corrigida uma referência cruzada da matriz (FR-020 → FR-021/FR-025).
- A matriz de inclusão e exclusão nomeia componentes e áreas de arquivos do Moodle (área de envios da
  Tarefa, lixeira, áreas de backup). O pedido da feature exige uma matriz baseada em investigação
  técnica; esses nomes são fatos de domínio sobre onde o Moodle registra os arquivos, não decisões de
  projeto, e ficam fora da interface (Princípio II, SC-008). Consultas, classes, tabelas, tipos de
  tarefa e cache ficam para o plano.
- `lang/en`, `translations/pt_br`, Privacy API, verificação de acessibilidade no Behat e a versão
  `0.1.0` são fixados pela constituição ou pela Feature 001, não escolhas de implementação (mesmo
  precedente do checklist da Feature 001).
- Nenhum marcador `[NEEDS CLARIFICATION]` foi usado. As cinco decisões que a especificação inicial
  deixou com padrão provisório foram resolvidas no `/speckit-clarify` (sessão 2026-10-16) e estão
  registradas em "Clarifications", com rastreabilidade para os requisitos:
  1. Elegibilidade: só salas com o bloco (FR-003 a FR-003d, FR-044b, CT-26 a CT-28).
  2. Cópias de segurança e lixeira: agregado à parte, fora de A e B (M12, M12a, M13, M13a, FR-019a a
     FR-019d, CT-29 a CT-31).
  3. Referências: contadas à parte por tipo, fora de A e B (M16, M17, FR-019e a FR-019h, CT-09,
     CT-32).
  4. Limite de grande porte: 50 MB, configurável para todo o site (FR-015 a FR-015c, CT-33 a CT-35).
  5. Atualização: sem botão para o professor; primeira análise automática ao adicionar o bloco
     (FR-036, FR-036a, FR-038, FR-039c, FR-039d).
- Revalidada em 2026-10-16 após o `/speckit-clarify` e a revisão final de consistência: nenhuma
  decisão provisória restante, todas as referências a FR, CT e SC existem, e todos os itens continuam
  aprovados (16/16).
- SC-002 (50 ms), SC-003 (50.000 registros em 5 minutos) e SC-004 (5.000 salas em 24 horas) são metas
  iniciais. O plano MUST confirmá-las com medições no ambiente de referência ou ajustá-las com
  justificativa.
- Items marked incomplete require spec updates before `/speckit-clarify` or `/speckit-plan`.
