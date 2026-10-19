# Comparação com o mockup aprovado (Feature 008, T023)

Capturas reais em `screenshots/approval/`, tiradas em 2026-10-19 nos sites de demonstração (dados
fictícios): `53-boost-en` (Moodle 5.3, Boost, inglês), `45-boost-ptbr` (Moodle 4.5, Boost, português) e
`45-classic-ptbr` (Moodle 4.5, Classic). Referência: o mockup original aprovado e a proposta visual de
2026-10-19 (https://claude.ai/artifact/G2ZMgZoDgV6L8DxL1YCn1H).

## Aprovação do proprietário do produto (2026-10-19)

**APROVADO**, sem nova rodada de redesign, como evolução da identidade e da experiência educativa do
CourseCare / AVA Consciente, alinhada à direção visual aprovada. Pontos aprovados: estados educativos do
bloco; indicador essencial de armazenamento; mensagens contextualizadas; identidade visual e ilustração;
cabeçalho da página; abas "Sugestões para você" e "Em detalhes"; apresentação das recomendações;
preservação dos indicadores anteriores; responsividade; linguagem educativa e não fiscalizatória.

Observações não bloqueantes, **fora do escopo desta feature** (não implementadas): simplificar no futuro a
apresentação das recomendações no celular; ampliar as orientações educativas (roadmap).

## Bloco

| Elemento do mockup | Implementado | Captura | Diferença deliberada |
| --- | --- | --- | --- |
| Identidade AVA Consciente / CourseCare | Título do bloco (do Moodle) e broto no painel | `block-idea.png` | O nome vem do cabeçalho nativo do bloco, não repetido dentro dele |
| "Tudo bem por aqui!" com frase limitada à análise | Sim, só com resultado completo, atual, classificado e sem sugestão | `block-allgood.png` | — |
| "Há uma ideia para a sua sala" | Sim, com "Encontramos uma sugestão sobre materiais de grande porte." | `block-idea.png` | Sem números nem atividade "principal" (decisão Q1) |
| Indicador principal e data | Cartão com volume e data | todas | — |
| Botão de acesso | "Ver sugestão" com sugestão; "Conheça melhor os arquivos da sua sala" sem | `block-idea.png`, `block-allgood.png` | — |
| Estado neutro com notas | Slogan, indicador e nota (desatualizado, falha, parcial) | `block-outdated.png` | Nunca "Tudo bem por aqui!" nesses casos |
| Estado "Dica para a sua sala" | Não implementado | — | Funcionalidade futura (fora do escopo) |
| Celular | Bloco na gaveta, mesma ordem | `block-idea-mobile.png` | — |

## Página

| Elemento do mockup | Implementado | Captura | Diferença deliberada |
| --- | --- | --- | --- |
| Cabeçalho com identidade, slogan e ilustração | `h2` com o nome, slogan, ilustração original SVG (plantas e tela) | `page-suggestions.png` | Sem pessoa na ilustração (ativo de terceiros/direitos; ver `illustration.md`) |
| Indicador essencial | Cartão "Sua sala em números" com volume e data | `page-suggestions.png` | O título "Sua sala em números" continua identificando a área (decisão Q2) |
| Faixa "Sua sala pode ficar mais leve!" | Faixa "Há uma ideia para a sua sala" com a nota "Sugestões são possibilidades…" | `page-suggestions.png` | Texto sem promessa (princípios I e IV) |
| Abas "Sugestões para você", "Boas práticas", "Em detalhes" | Duas abas reais e acessíveis: "Sugestões para você" e "Em detalhes" | `page-suggestions.png`, `page-details.png` | Sem aba "Boas práticas" (futuro; nenhuma aba vazia) |
| Cartão de sugestão com ícone, título e ações | Cartão da 004 com ícone, "O que observamos", "Atividades relacionadas" (com links), "Por que pode merecer atenção", "O que você pode fazer, se quiser" | `page-suggestions.png` | Sem selo "Principal sugestão" (sem regra aprovada) |
| Cartões "Revisar limites" e "Vídeo grande" | Não implementados | — | Funcionalidades futuras |
| Faixa de fechamento positiva | "Cada sugestão é uma possibilidade. A decisão é sempre sua…" | `page-details.png` | Sem "AVA mais sustentável" |
| "Tudo bem por aqui!" na aba de sugestões | Sim, quando aplicável | `page-allgood-suggestions.png` | — |
| Detalhes técnicos | Toda a página da 0.7.0 na aba "Em detalhes" | `page-details.png` | — |
| Celular | Cabeçalho sem a ilustração (≤ 575 px), abas, faixa e cartão em coluna | `page-suggestions-mobile.png` | Ilustração decorativa oculta em telas estreitas |
| Tema Classic (4.5) | Mesmo layout | `45-classic-ptbr/*` | — |
