# Research: Educational Experience (Feature 008)

## R1 — Estado educativo derivado, sem dado novo

- **Decision**: uma função pura escolhe o estado educativo a partir de `diagnostic_state` (estado,
  `hasresult`, `filecount`, `partial`) e do booleano de sugestão que o bloco já calcula
  (`diagnostic_store::has_large_materials`). Estados: `idea` (sugestão aplicável), `allgood` (estado
  `complete`, atual, com arquivos, sem `partial`, sem sugestão), `neutral` (resultado sem sugestão mas
  desatualizado, com falha ou parcial) e os estados sem resultado atuais (sem análise, em preparação, sem
  arquivos), com os textos de hoje.
- **Rationale**: FR-002/FR-004 e a decisão Q1 exigem que "Tudo bem por aqui!" só apareça sem falha,
  desatualização ou cobertura parcial; tudo já está no estado resolvido, sem leitura nova (FR-050).
- **Alternatives**: guardar o estado educativo (dado novo, proibido); calcular na página e no bloco de
  formas diferentes (risco de divergência; a mesma função serve aos dois).

## R2 — Notas do bloco

- **Decision**: notas na ordem falha, desatualização, cobertura parcial; falha e desatualização usam as
  strings atuais; a cobertura parcial no bloco usa uma string nova **sem números** ("Part of the files
  could not be classified; they are included in the total."), pois o único número do bloco é o volume
  (Q1). Na página, a nota parcial com tamanho e percentual (`overview:partial`) continua como está.
- **Alternatives**: reaproveitar `overview:partial` no bloco (traria dois números a mais).

## R3 — Hierarquia de títulos e identidade da página

- **Decision**: o Moodle já renderiza o nome da sala como o único `h1` (`$PAGE->set_heading`). A
  identidade "CourseCare" / "AVA Consciente" vira o `h2` do cabeçalho, com o slogan (`tagline`, string
  existente). O cartão do indicador recebe o título `h3` "Your course in numbers" / "Sua sala em números"
  (string `overview:title`, existente), que continua no título da janela, na trilha e nos links. As seções
  das abas mantêm `h3`. Um título `h2` visualmente oculto não é necessário: as abas têm nomes acessíveis.
- **Rationale**: FR-014 e a decisão Q2 (identidade como elemento principal, sem duplicar o `h1`).
- **Alternatives**: identidade como `h1` (duplicaria o `h1` do tema).

## R4 — Abas acessíveis com melhoria progressiva

- **Decision**: marcação base = duas `<section>` sempre visíveis, cada uma com título, e uma lista de abas
  com o atributo `hidden`. Um módulo AMD próprio (`block_coursecare/tabs`) ativa o padrão de abas da WAI-ARIA
  APG: `role="tablist"`, `role="tab"` com `aria-selected`, `aria-controls` e `tabindex` itinerante,
  `role="tabpanel"` com `aria-labelledby`; setas esquerda/direita, Home e End com **ativação automática**
  (os painéis já estão no DOM); o painel inativo recebe `hidden`. Sem scripts, a lista de abas continua
  oculta e as duas seções aparecem em sequência (FR-036). Impressão: CSS `@media print` mostra todos os
  painéis e esconde a lista de abas.
- **Endereços e histórico**: cada aba tem um fragmento (`#block_coursecare-tab-suggestions`,
  `#block_coursecare-tab-details`); trocar de aba usa `history.pushState`; `popstate`/`hashchange`
  restauram a aba. Um fragmento de seção existente (`#block_coursecare-suggestions`,
  `#block_coursecare-history`, `#block_coursecare-potential` e os demais ids das seções) ativa a aba que a
  contém e move o foco para a seção (`tabindex="-1"` só durante o foco).
- **Aba inicial**: fragmento, se houver; senão "Sugestões para você" com sugestão e "Em detalhes" sem
  sugestão (decisão Q3), informado ao módulo por um atributo `data-` do contêiner.
- **Rationale**: abas reais sem depender do JavaScript do Bootstrap, cujo atributo muda entre o 4.5
  (`data-toggle`, Bootstrap 4) e o 5.3 (`data-bs-toggle`, Bootstrap 5), e que não cobre fragmentos,
  histórico nem o funcionamento sem scripts.
- **Alternatives**: abas do Bootstrap (diferem entre versões; sem histórico); `<details>` (não é aba);
  só CSS com `:target` (sem estado de seleção acessível).

## R5 — Compilação do módulo AMD

- **Decision**: `amd/src/tabs.js` (ES module) compilado para `amd/build/tabs.min.js` e `.map` com o
  `grunt amd` do Moodle, num contêiner Node 22 (`.nvmrc` `lts/jod`). O 4.5 e o 5.3 usam as mesmas versões
  de `@babel/core` 7.17.5, `@babel/preset-env` 7.16.11, `grunt-rollup` 11.9 e `rollup-plugin-terser` 7.0.2,
  então o build é o mesmo e o passo `moodle-plugin-ci grunt` do CI deve passar nas quatro combinações.
- **Risco**: se o CI acusar build diferente em uma versão, rodar o passo `grunt` só na versão de
  referência e registrar o motivo (sem desligar lint).

## R6 — Ilustração

- **Decision**: um SVG original desenhado no projeto (`pix/illustration.svg`): plantas e uma tela de
  computador simples, na paleta do broto, sem pessoas, mascotes, texto ou fontes; comentário de origem e
  licença no arquivo (como `pix/sprout.svg`); exibida com `{{#pix}}` e `alt` vazio (decorativa); oculta
  abaixo de uma largura em que atrapalharia a leitura. Origem registrada nas evidências da 008, como
  complemento do relatório de titularidade da 007.
- **Alternatives**: imagem rasterizada de terceiros ou gerada fora do projeto (direitos não comprovados);
  ilustração de pessoa (fora do escopo aprovado).

## R7 — Cores e contraste

- **Decision**: verdes e neutros da identidade (`#eef6ef`, `#eaf4ec`, `#f5f6f3`), amarelo discreto para
  sugestões (`#fdf4e4`); texto sobre os painéis fixado em `#1d2125` (lição da 007: o 5.3 acinzenta o texto
  na gaveta); botões e links com as classes do tema (`btn btn-primary`, `btn-outline-primary`), sem
  redefinir variáveis do tema. Falha e desatualização sem vermelho; ícones decorativos com `aria-hidden`.

## R8 — Strings novas

Todas em `lang/en` e `translations/pt_br`, completas e classificadas por tipo de texto: títulos e
mensagens dos estados `allgood` e `idea`, ação "Ver sugestão", nota parcial do bloco, nomes das abas, nota
da faixa ("Sugestões são possibilidades…"), mensagem de fechamento, rótulo da lista de abas. Strings
antigas sem uso (por exemplo `block:suggestion`) são removidas com a verificação de paridade.

## R9 — Testes

- PHPUnit: tabela da função de estado educativo (todas as combinações de estado, arquivos, parcial e
  sugestão), e o contexto dos templates do bloco e da página.
- Behat: textos do bloco por estado (cenários existentes atualizados); página com as duas abas, aba
  inicial com e sem sugestão, teclado (setas), fragmento de seção, cenário sem JavaScript (todo o conteúdo
  visível); axe nas páginas e no bloco; regressão das features 002–006 com os passos existentes.
- Verificações manuais e automáticas da 007 (`checks.py`): axe, teclado, zoom 200%, 320 px, nas três
  combinações; capturas reais para a aprovação do proprietário do produto.

## R10 — Versão

0.8.0, `MATURITY_BETA`, versão 2026102100; sem mudança de banco (sem passo de atualização); `supported`
[405, 503] mantido.
