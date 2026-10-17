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

Pendente.

## Reconstrução e validação visual (T046, T048)

### Proposta 1 (T046), 2026-10-17 — aguardando a revisão do proprietário do produto

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
- A aprovação da direção visual será registrada aqui com data, só quando ocorrer.

### Proposta 2 (T046), 2026-10-17 — aguardando a revisão do proprietário do produto

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
- Ainda não validado no tema Classic, em zoom de 200% nem com leitor de tela (T048); tons provisórios até a
  aprovação.
- Na gaveta estreita do bloco, o rótulo do volume ocupa duas linhas.
- A sala com 200 atividades não foi capturada; o caso de 14 atividades mostra a mesma lista com "Mostrar todas".
- A aprovação da direção visual será registrada aqui com data, só quando ocorrer.

## Avaliação com professores (T049–T051)

Pendente.

## Runs de CI (T054)

Pendente.

## Constitution Check reavaliado (T055)

Pendente.
