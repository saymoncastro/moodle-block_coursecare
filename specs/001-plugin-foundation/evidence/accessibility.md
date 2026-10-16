# Acessibilidade e responsividade — Feature 001 (T045)

**Declaração**: este documento registra a **verificação dos critérios WCAG 2.2 AA aplicáveis ao bloco
(axe + verificações manuais proporcionais)**. Ele **não** é uma declaração de conformidade integral
com a WCAG (FR-017, SC-007).

Ambiente: Moodle 4.5.15 (Build 20261005), moodle-docker com PHP 8.1 e PostgreSQL, temas core Boost e
Classic, idiomas en e pt_BR, dados fictícios. As capturas automatizadas foram feitas com Playwright
1.48, usando o Chromium headless shell 1234 contra o sistema real em http://localhost:8045, em
2026-10-16.

## Verificação automatizada (axe-core via Behat)

| Cenário | Tema / largura | Resultado | Fonte |
| --- | --- | --- | --- |
| `the "CourseCare" "block" should meet accessibility standards` | Boost, desktop | ✅ passou | CI 38914763604 e 38922204004 (MOODLE_405_STABLE, PHP 8.1/pgsql e 8.3/mariadb) |
| idem, tablet (768×1024) | Boost | ✅ passou | idem |
| idem, mobile (425×750) | Boost | ✅ passou | idem |
| idem | Classic, desktop | ✅ passou | idem |

As tags axe usadas pelo Moodle 4.5 são `wcag2a`, `wcag21a`, `wcag22a`, `wcag2aa`, `wcag21aa`,
`wcag22aa`, `section508`, `cat.aria`, `cat.sensory-and-visual-cues` e `wcag134`. O log do CI registra
"Accessibility: Yes" e 16/16 cenários aprovados.

## Verificações manuais e semiautomatizadas no sistema real

| Verificação | Resultado | Evidência |
| --- | --- | --- |
| Contraste do texto (Boost) | tagline e intro `rgb(29,33,37)` sobre `#fff`: **16,20:1** (≥ 4,5:1) | `logs/playwright-visual.json` |
| Contraste do texto (Classic) | `rgb(29,33,37)` sobre `rgb(248,249,250)`: **15,37:1** | idem |
| Reflow a 320 px (Boost e Classic, en e pt_BR) | sem rolagem horizontal (`scrollWidth = 320`), bloco dentro da viewport, sem overflow de texto | `screenshots/*-320*.png`, `logs/playwright-visual.json` |
| Zoom de 200% (1280 px a 2× = 640 px CSS) | sem rolagem horizontal, texto completo | `screenshots/boost-{en,pt_br}-1280-zoom200*.png` |
| Tablet (768) e smartphone (375), en e pt_BR | texto completo, ícone de 40×40, sem rolagem horizontal | `screenshots/boost-*-768*.png`, `screenshots/*-375*.png` |
| Teclado: conteúdo do bloco | 0 elementos focáveis no conteúdo (nada a alcançar, nenhuma armadilha de foco) | `logs/playwright-visual.json` |
| Teclado: controles nativos (modo de edição) | Tab alcança "Move CourseCare block" e "Actions menu", ambos com `:focus-visible` e anel `rgba(15,108,191,0.75) 0 0 0 3.2px` | `logs/keyboard-focus-boost-en.json`, `screenshots/boost-en-1280-keyboard-focus-{1,2}.png` |
| Árvore de acessibilidade (Chromium) | região "CourseCare" › título nível 3 "CourseCare" › dois parágrafos (tagline e intro); **0** nós de imagem (ícone oculto com `aria-hidden="true"`) | `logs/accessibility-tree-boost-en.json` |
| Ativo que não carrega (US3-6) | requisição do ícone bloqueada: mensagem completa e compreensível | `logs/playwright-e5.json`, `screenshots/boost-en-1280-icon-not-loaded-block.png` |
| Cores forçadas (`forced-colors: active`, emulado) | texto legível | `screenshots/boost-en-1280-forced-colors*.png` |
| Tema Classic | texto completo; na coluna estreita, o ícone fica acima do texto | `screenshots/classic-*.png` |

## Pendente de verificação humana

- **Leitor de tela real (VoiceOver ou NVDA)**: não executado nesta sessão. Ele foi substituído
  provisoriamente pela árvore de acessibilidade do Chromium, que é um indicador, não um teste com
  tecnologia assistiva. A leitura real deve ser feita por uma pessoa antes da aprovação final.
- **Modo de alto contraste do sistema operacional**: apenas emulado (`forced-colors`) no Chromium
  headless. A verificação com o modo real do sistema fica pendente.
