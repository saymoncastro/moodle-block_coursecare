# Evidências da Feature 003 — Teacher Experience

Registro das evidências de cada cenário (CT) e critério de sucesso (SC) da [spec](../spec.md).
Nenhum item é marcado como atendido sem evidência: teste automatizado verde (com o run de CI) ou
registro manual com data, versão do Moodle e banco de dados. Os arquivos ficam fora do pacote.

| Item | Teste ou evidência | Data | Moodle | Banco | Status |
| --- | --- | --- | --- | --- | --- |
| CT-01 | T013 | | | | pendente |
| CT-02 | T013 | | | | pendente |
| CT-03 | T013, T014 | | | | pendente |
| CT-04 | T017, T019 | | | | pendente |
| CT-05 | T024, T026 | | | | pendente |
| CT-06 | T014 | | | | pendente |
| CT-07 | T018, T020 | | | | pendente |
| CT-08 | T018 | | | | pendente |
| CT-09 | T018 | | | | pendente |
| CT-10 | T038, T039 | | | | pendente |
| CT-11 | T025, T026 | | | | pendente |
| CT-12 | T030, T032 | | | | pendente |
| CT-13 | T030, T032 | | | | pendente |
| CT-14 | T018, T031 | | | | pendente |
| CT-15 | T031, T047 | | | | pendente |
| CT-16 | T034, T052 | | | | pendente |
| CT-17 | T034 | | | | pendente |
| CT-18 | T037, T052 | | | | pendente |
| CT-19 | T048 | | | | pendente |
| CT-20 | T005, T013, T019 | | | | pendente |
| CT-21 | T007 | | | | pendente |
| CT-22 | T038 | | | | pendente |
| CT-23 | T050 | | | | pendente |
| CT-24 | T046, T048 | | | | pendente |
| SC-001 | T024, T026 | | | | pendente |
| SC-002 | T019, T031 | | | | pendente |
| SC-003 | T044 | | | | pendente |
| SC-004 | T044 | | | | pendente |
| SC-005 | T030, T032 | | | | pendente |
| SC-006 | T031 | | | | pendente |
| SC-007 | T034, T052 | | | | pendente |
| SC-008 | T047 | | | | pendente |
| SC-009 | T048 | | | | pendente |
| SC-010 | T053, T054 | | | | pendente |
| SC-011 | T050, T051 | | | | pendente |

## Revisão de tom e rótulos (T047)

**Preparada em 2026-10-17; aguardando a aprovação do proprietário do produto.** Todas as strings novas,
alteradas e removidas pela Feature 003 (comparação com `main`), em en e pt_BR, conferidas contra
`contracts/block-ui.md` e `contracts/overview-page.md` e contra os Princípios I, II e IV:

- nenhuma frase culpa o professor, recomenda ação ou fala em economia, carbono, desperdício ou ranking;
- o número principal nunca é chamado de espaço em disco: o rótulo diz "registrados no Moodle", e o que
  os números não significam fica nos detalhes técnicos (`overview:notmeaning`, `overview:unique_help`);
- "grande porte" sempre vem com a frase de triagem (`overview:large_help`), e a ausência de arquivos
  grandes é dita de forma neutra (`overview:large_none`);
- termos técnicos (conteúdo único, referências, cópias, cobertura) só aparecem nos detalhes técnicos;
- paridade en/pt_br: `tools/i18n/check.php` sem erros; os avisos de texto igual ao inglês são os
  símbolos de unidade e `overview:share` ("{$a}%"), iguais por natureza.

Ajuste já feito após a aprovação visual: `overview:files` passou de "Files in this course, as recorded
by Moodle" / "Arquivos desta sala, conforme registrados no Moodle" para "Course files recorded in
Moodle" / "Arquivos da sala registrados no Moodle".

{table}

## Reconstrução e validação visual (T046, T048)

### Proposta 1 (T046), 2026-10-17 — não aprovada

- Ambiente: site de demonstração limpo e descartável (`moodle-docker`, projeto `coursecare-demo`), Moodle
  4.5.15, PHP 8.1, PostgreSQL 17, tema Boost, pacote oficial pt_br e tradução do projeto instalada por
  `tool_customlang`; salas fictícias criadas por `tools/dev/demo/setup.php --threshold-mb=1
  --activities=200` (limite de grande porte de 1 MB para a demonstração).
- Capturas em `screenshots/proposal-1/`, feitas pelo Chrome do próprio stack com
  `tools/dev/demo/screenshots.py`, como o professor `demo.teacher`, em en e pt_BR:
  - bloco nos estados D e A (`block-d-*`, `block-a-*`);
  - página nos estados D (fechada e com os detalhes técnicos abertos), D com 14 atividades
    (`page-many-*`), E, F, G, H e sem resultado (`page-a-*`), em desktop (1280 px);
  - página em D e G em celular (375 px, emulação do Chrome; `*-mobile-*`).
- O rodapé de desempenho do modo de desenvolvedor (do `config.php` compartilhado pelos sites de
  desenvolvimento) foi ocultado só no momento da captura; ele não existe num site de produção.
- Observação fora do plugin: em pt_BR o rótulo de tamanho vem do pacote oficial do core ("Mb", "Kb").

### Aprovação da direção visual (T046), 2026-10-17

O proprietário do produto aprovou a **proposta 2** como direção visual da Feature 003, em 2026-10-17:
a estrutura, a identidade visual, a hierarquia dos indicadores e a composição geral estão alinhadas à
visão do CourseCare / AVA Consciente, sem necessidade de uma terceira proposta. A aprovação não encerra a
feature. Ajustes finais pedidos na mesma decisão: equilíbrio da abertura da página (sobretudo sem
diagnóstico), simplificação de rótulos técnicos, unidades no pacote pt_BR e conclusão de T048 (ver
"Ajustes finais após a aprovação").

### Proposta 2 (T046), 2026-10-17 — aprovada

A proposta 1 não foi aprovada. A proposta 2 refina a mesma direção visual, sem mudar o diagnóstico, os dados
exibidos nem o escopo: só os templates, os textos de interface, a folha de estilos e a forma como
`diagnostic_line` e `course_overview` entregam os dados aos templates.

- Ambiente e procedimento iguais aos da proposta 1 (mesmo site, mesmas salas, mesmo professor). O script de
  captura passou a esperar a gaveta de blocos abrir e a remover, só na captura, o tour de usuário do core que
  abre sobre ela.
- Capturas em `screenshots/proposal-2/` (38 arquivos), em en e pt_BR:
  - sala com o bloco no contexto (`course-d-*`);
  - bloco nos estados D, E, G, H, A e numa sala com muitos materiais (`block-d/e/g/h/a/many-*`);
  - página em desktop (1280 px) nos estados D (fechada e com os detalhes técnicos abertos), D com 14
    atividades (`page-many-*`), E, F, G, H e sem resultado (`page-a-*`);
  - página em celular (375 px) nos estados D, D com 14 atividades, H e G (`*-mobile-*`).
- Limite de grande porte: nas capturas ele vale 1 MB só porque o site de demonstração foi montado com
  `setup.php --threshold-mb=1`, para que salas pequenas tenham arquivos acima do limite. É um parâmetro da
  demonstração, não um valor recomendado; o padrão do plugin não mudou.
- Verificação: PHPUnit 184 testes e Behat 40 cenários verdes no ambiente de desenvolvimento; phpcs sem
  ocorrências; verificação de i18n sem erros (o único aviso, `overview:share` igual ao inglês, já existia).

#### O que mudou da proposta 1 para a proposta 2

| Ponto | Proposta 1 | Proposta 2 | Por quê |
|-------|------------|------------|---------|
| Bloco | Volume dentro de uma frase ("Files in this course…: 8.0 MB"), no mesmo peso do resto. | Identidade (broto e frase) no topo; abaixo, uma caixa verde clara com o rótulo, o volume em destaque, a data, os avisos e o link. | O volume é o que o professor procura; agora é visto de relance, sem ler a frase. |
| Abertura da página | Cartão com título, frase, volume em frase, data e link de volta, tudo no mesmo nível. | Faixa de abertura com título e frase de acolhida; volume em destaque num quadro próprio com a data; avisos (desatualizado, falha) e cobertura parcial abaixo, em linhas próprias; "Voltar à sala" no topo. | Separar informação principal, data, limitações e navegação. |
| Largura em desktop | Coluna única de 48rem; metade da tela vazia. | Até 72rem; distribuição e grande porte lado a lado (3:2) a partir de 992 px; materiais por atividade e detalhes técnicos em largura total. | Usar a largura sem virar painel de indicadores. |
| Hierarquia | Cinco cartões iguais. | Cartão só onde ajuda: distribuição (cartão com borda) e grande porte (fundo neutro); materiais por atividade e detalhes técnicos sem cartão, separados por espaço e uma linha. | Diferenciar resumo, distribuição, atividades, grande porte e detalhes. |
| Barras | Uma barra por categoria e uma por atividade. | Categorias: uma única barra empilhada e uma legenda com cor, nome, tamanho e percentual. Atividades: mantêm uma barra por linha. | Menos repetição; a barra empilhada mostra que as partes somam o total da sala. |
| Base dos percentuais | Implícita. | Explícita sob cada título: "Quanto cada tipo de arquivo representa no total da sala" e "Só os materiais de cada atividade, em relação a todos os materiais da sala". | Os dois percentuais têm bases diferentes (total da sala × materiais da sala). |
| Linguagem | "Get to know the files of your course…" / "Only the materials of each activity are shown here." | Frases mais curtas e diretas; o texto técnico continua só nos detalhes técnicos, fechados por padrão. | Linguagem simples e acolhedora. |
| Grande porte sem ocorrências | "Files of 1.0 MB or more: 0 (0 bytes)." | "Nenhum arquivo de 1.0 MB ou mais nesta sala." | Mensagem natural, sem julgamento. |
| Celular | Valores sob os nomes, cartões com o mesmo peso. | Mesma regra de valores sob os nomes; abertura e quadros mais compactos; a legenda única substitui as quatro barras. | Menos repetição e rolagem. |

#### O que foi preservado

- Diagnóstico, cálculos, regra de percentual ("menos de 1%"), dados exibidos e privacidade (FR-030): sem mudança.
- Direção visual aprovada: verdes suaves e neutros, cantos arredondados, broto, sem mascotes; CSS restrito ao
  bloco e à página, sem redefinir cores ou variáveis do tema; texto na cor do tema.
- Acessibilidade: títulos em ordem, barras decorativas (`aria-hidden`) com os números sempre em texto, detalhes
  técnicos em `<details>`/`<summary>` nativo (teclado), foco e links do tema.
- Estados A–H, lista das 10 primeiras atividades com "Mostrar todas", seção de grande porte, detalhes técnicos.
- Nada da Feature 004: sem recomendações, potencial, histórico, ranking, economia, carbono, novas consultas ou
  novas funções administrativas.

#### Limitações que permanecem

- Em pt_BR, as unidades "Mb" e "Kb" vêm do pacote oficial do core, fora do plugin.
- Contraste: as quatro cores das categorias têm pelo menos 3:1 contra o fundo branco e contra o trilho da
  barra (4,1 a 4,9), como pede T035; cores vizinhas se distinguem pelo matiz e por um espaço branco, não pela
  luminosidade, e a informação está sempre no texto da legenda. A conferência com ferramenta fica para T048.
- Ainda não validado no tema Classic, em zoom de 200% nem com leitor de tela (T048).
- Na gaveta estreita do bloco, o rótulo do volume ocupa duas linhas.
- A sala com 200 atividades não foi capturada; o caso de 14 atividades mostra a mesma lista com "Mostrar todas".

### Ajustes finais após a aprovação, 2026-10-17

1. **Abertura da página.** O título e a introdução ficam à esquerda e o quadro do volume com a data à
   direita, a partir de 768 px. Avisos e cobertura parcial ficam abaixo, em largura total. Sem
   resultado, a abertura mostra só a frase do estado sob a introdução, sem quadro: a seção caiu de
   cerca de 180 px para 135 px de altura em desktop, sem informação nova.
2. **Rótulo do volume.** `overview:files` passou a "Course files recorded in Moodle" / "Arquivos da
   sala registrados no Moodle". "Registrados no Moodle" mantém a precisão (registro lógico, FR-002).
   A explicação completa continua nos detalhes técnicos (`overview:notmeaning`, `overview:unique_help`).
3. **Unidades.** Investigação no Moodle 4.5.15:
   - `display_size()` monta o número com `sprintf('%.1f')`, sempre com ponto decimal, e a unidade com
     as strings do core `sizekb`, `sizemb` e `sizegb`;
   - o pacote oficial pt_br define `Kb`, `Mb` e `Gb` (mas `TB` e `PB`), símbolos que na notação usual
     são de bits; o separador decimal do pt_br (`langconfig`, `decsep`) é a vírgula;
   - resultado no pt_BR: "8.0 Mb", ambíguo (bit × byte) e com o separador de outra localidade.

   Solução adotada, sem tocar no core: `diagnostic_line::size()` aplica a mesma regra de
   `display_size()` (potências de 1024, uma casa decimal, bytes inteiros abaixo de 1 KB), mas formata o
   número com `format_float()`, a API do Moodle que usa o separador decimal do idioma. As unidades vêm
   de strings do plugin (`size:kb`, `size:mb`, `size:gb`, `size:tb`: KB, MB, GB, TB nas duas
   línguas). Em inglês o texto é idêntico ao de `display_size()`, verificado por teste. Em pt_BR fica
   "8,0 MB". O bloco e a página usam a função; a CLI e as configurações do administrador continuam com
   `display_size()`, como o resto do Moodle. Complemento sugerido, fora do plugin: corrigir `sizekb`,
   `sizemb` e `sizegb` do pt_br no AMOS. Até lá, um site pode ajustá-las com `tool_customlang`.
4. **Barras.** As barras empilhadas não desenham mais segmentos de 0%. Antes, o separador de 2 px
   desses segmentos deixava uma fresta no fim da barra.

### Validação visual e de acessibilidade (T048), 2026-10-17

Site de demonstração (Moodle 4.5.15, PHP 8.1, PostgreSQL 17), Chrome do stack. Professor de
demonstração com o tour de usuário da página da sala concluído, como ficaria após o primeiro acesso:
o tour aberto prende o foco e cobre a gaveta de blocos.

- **Capturas** em `screenshots/final/boost/` e `screenshots/final/classic/` (44 por tema), en e pt_BR:
  - sala com o bloco;
  - bloco nos estados D, E, F com resultado, G e sem resultado (A);
  - página nos estados D (fechada e aberta), E, F, G, H, A, 14 atividades e 200 atividades, em
    desktop (1280 px);
  - bloco e página em celular (375 px, emulação do Chrome): página em D, E, F, G, A e 200 atividades.

  Também pt_BR sem a tradução do projeto (`*-pt_br-notranslation`): o plugin cai para o inglês sem
  chaves cruas, com números e datas no formato pt_BR.
- **Verificações** com `tools/dev/demo/checks.py`, resultados em `logs/t048/<tema>-<língua>/`:

| Verificação | Páginas | Boost en | Boost pt_BR | Classic en | Classic pt_BR |
| --- | --- | --- | --- | --- | --- |
| axe-core 4.10 (cópia do Moodle), regras WCAG 2.0/2.1/2.2 A e AA | página D, E, F, G, A, 200 atividades; sala D e A | 0 violações | 0 violações | 0 violações | 0 violações |
| Zoom de 200% (1280 px → 640 px CSS) | página D, 200 atividades; sala D | sem rolagem horizontal | idem | idem | idem |
| 320 px (400%) | página D; sala D | sem rolagem horizontal | idem | idem | idem |
| Teclado (Tab até repetir; Enter em cada `summary`) | página D | 10 paradas, todas com indicador de foco; detalhes técnicos abrem | idem | idem | idem |
| Teclado | página com 200 atividades | 203 paradas, todas com indicador; "Mostrar todas" e detalhes abrem | idem | idem | idem |
| Teclado | sala D (bloco) | o link do bloco é alcançado, com indicador | idem | idem | idem |
| Árvore de acessibilidade do Chrome | página D e A; bloco | títulos h2 → h3 → h4, listas, links com nome, `summary` com estado expandido/recolhido, barras ausentes, números em texto | idem | idem | idem |

- **Itens que o axe deixou para revisão humana** ("incomplete"):
  - `aria-valid-attr-value` em elementos do tema (menu do usuário, notificações), fora do plugin;
  - `color-contrast` na frase de ajuda da 3ª categoria, só no Classic pt_BR, porque o fundo "sobrepõe
    parcialmente outros elementos". Conferido na captura: texto na cor do tema sobre o painel branco,
    sem sobreposição visível.
- **Leitor de tela: não realizado.** A árvore de acessibilidade mostra o que o leitor de tela recebe,
  mas não substitui o teste com VoiceOver ou NVDA. Falta uma passagem humana, de cerca de 10 minutos,
  num desktop:
  1. abrir a sala *Biologia celular* e chegar ao bloco pelo atalho "Pular AVA Consciente" ou pelos
     marcos;
  2. ouvir o rótulo, o volume e a data;
  3. seguir o link;
  4. navegar a página por títulos (h2 → h3);
  5. ouvir a legenda das categorias (nome, tamanho e percentual);
  6. abrir "Mostrar todas" e "Detalhes técnicos" e conferir que o estado (recolhido/expandido) é
     anunciado;
  7. voltar à sala.

  Registrar o leitor e o navegador usados.
- **Não declaramos conformidade com a WCAG.** As verificações automáticas não encontraram violações e
  as verificações de teclado, zoom e largura passaram, mas a conformidade depende do teste com leitor
  de tela e de uma revisão humana.
- **Pendente para fechar T048:** o teste com leitor de tela acima e a aprovação das capturas finais
  pelo proprietário do produto (SC-009, CT-24).

## Avaliação com professores (T049–T051)

**Não realizada.** Nenhum resultado foi registrado ou presumido.

- **Pronto:**
  - roteiro em [`evaluation/protocol.md`](../evaluation/protocol.md) (T049): perfil anônimo, dez
    tarefas com critério objetivo para as seis dimensões de FR-071, inclusive a leitura do resumo sem
    abrir os detalhes técnicos (FR-019), perguntas abertas, critério de problema crítico e menor
    (FR-071a) e o formulário de registro por participante;
  - site de demonstração com a interface aprovada e as salas usadas no roteiro.
- **Falta (organização externa ao desenvolvimento):**
  - recrutar cinco professores com familiaridade e áreas diferentes;
  - agendar as sessões (20 a 25 minutos cada) e dar acesso ao site de demonstração;
  - conduzir as sessões (proprietário do produto) e registrar `evidence/evaluation/P1.md` a `P5.md`
    (T050);
  - corrigir os problemas críticos, se houver (T051).

## Runs de CI (T054)

Pendente.

## Constitution Check reavaliado (T055)

Pendente.
