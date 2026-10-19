# Feature Specification: Educational Experience

**Feature Branch**: `008-educational-experience` (criada a partir de `main` em `1b65b64`, que integra a
007 pelo PR #11; versão de partida 0.7.0 BETA, Moodle 4.5 LTS e 5.3 LTS)

**Created**: 2026-10-19

**Status**: Draft

**Input**: User description: "Feature 008 — Educational Experience (CourseCare / AVA Consciente): evoluir a
apresentação dos dados existentes para a experiência educativa aprovada (canvas de proposta
https://claude.ai/artifact/G2ZMgZoDgV6L8DxL1YCn1H, itens marcados como futuros excluídos): bloco educativo
com estados, página educativa com cabeçalho e ilustração, cartões de recomendação refinados, detalhamento
integrado em 'Sugestões para você' e 'Em detalhes'; acessível, responsivo, en e pt_BR, 4.5 e 5.3; sem novo
diagnóstico, nova recomendação, mudança de metodologia ou funcionalidade futura."

**Constituição de referência**: 1.2.0

**Natureza da feature**: apresentação. Nenhum dado novo, tabela, configuração, capability, critério de
recomendação, indicador ou mudança de fórmula. Toda informação exibida já é produzida pelas Features
002–006; a 008 muda a forma, a hierarquia e o tom, não o conteúdo medido.

**Referência visual**: o mockup original aprovado e a proposta visual de 2026-10-19 (canvas acima), com a
decisão do proprietário do produto de 2026-10-19. As imagens são referência de produto, não especificação
literal. Elementos marcados como futuros no canvas (dica educativa no bloco, aba "Boas práticas",
orientação sobre limites de tarefas, vídeos, "Principal sugestão") ficam fora desta feature.

**Categorias de indicador (Princípio IV)**: inalteradas. Armazenamento lógico, Conteúdo único e Potencial
teórico continuam com os nomes, as definições e as fórmulas das Features 002, 005 e 006.

## Tipos de texto (decisão do proprietário do produto, 2026-10-19)

Todo texto novo desta feature pertence a um destes tipos, e a especificação de cada string diz qual:

| Tipo | Uso | Exemplo |
| --- | --- | --- |
| Fato observado | O que a análise registrou | "8,0 MB registrados no Moodle" |
| Possibilidade | Convite opcional, sem dizer que algo está errado | "Há uma ideia para a sua sala" |
| Orientação prática | Como fazer algo no Moodle, se o professor quiser | Já existente na sugestão da 004 |
| Recomendação fundamentada | Só os critérios já especificados (Feature 004) | Materiais grandes |
| Julgamento sem dados | **Proibido** | "Sua sala pode ficar mais leve", "AVA mais sustentável" |

Mensagens positivas são permitidas quando limitadas ao que foi avaliado ("Tudo bem por aqui!" com "Não
identificamos sugestões nos critérios avaliados nesta análise"). Nenhuma mensagem afirma que a sala está
perfeita, promete economia, desempenho ou benefício ambiental, ou avalia o professor.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - O professor entende a situação da sala pelo bloco (Priority: P1)

Um professor que edita a sala vê, no bloco, a identidade do CourseCare (AVA Consciente em português), um
estado educativo com título acolhedor e uma mensagem essencial, o volume registrado com a data da análise e
um acesso às sugestões ou aos detalhes. O bloco continua compacto.

**Why this priority**: o bloco é o primeiro contato do professor com o produto em cada sala; é onde a
experiência educativa precisa aparecer primeiro.

**Independent Test**: em salas de demonstração com cada estado do diagnóstico, o bloco mostra o estado
correto, com título, mensagem, indicador e acesso, sem nenhuma outra parte desta feature implementada.

**Acceptance Scenarios**:

1. **Given** uma sala com resultado válido e nenhuma sugestão aplicável, **When** o professor abre a sala,
   **Then** o bloco mostra "Tudo bem por aqui!", a frase "Não identificamos sugestões nos critérios
   avaliados nesta análise", o volume registrado, a data e o acesso à página.
2. **Given** uma sala com resultado válido e a sugestão de materiais grandes aplicável, **When** o professor
   abre a sala, **Then** o bloco mostra "Há uma ideia para a sua sala", uma frase curta sobre a sugestão
   realmente disponível, o volume, a data e um acesso que abre as sugestões.
3. **Given** uma sala ainda sem análise, **When** o professor abre a sala, **Then** o bloco diz que o
   panorama ainda não está disponível, sem volume e sem acesso à página (como hoje).
4. **Given** uma sala com análise na fila ou em andamento e sem resultado anterior, **When** o professor
   abre a sala, **Then** o bloco diz que o panorama está sendo preparado.
5. **Given** um resultado mais antigo que o limite de desatualização, **When** o professor abre a sala,
   **Then** o bloco mostra o estado educativo desse resultado e uma nota discreta de que a informação pode
   estar desatualizada.
6. **Given** uma falha na última atualização com resultado anterior, **When** o professor abre a sala,
   **Then** o bloco mostra o estado educativo do resultado anterior e uma nota discreta de que a última
   atualização não foi concluída.
7. **Given** um resultado com cobertura parcial, **When** o professor abre a sala, **Then** o bloco mostra
   uma nota discreta de que parte dos arquivos não pôde ser classificada e está incluída no total.
8. **Given** uma sala analisada sem nenhum arquivo, **When** o professor abre a sala, **Then** o bloco
   mostra um estado neutro dizendo que nenhum arquivo foi encontrado, sem "Tudo bem por aqui!".

---

### User Story 2 - O professor chega a uma página acolhedora e orientada (Priority: P1)

Ao abrir a página da sala pelo bloco, o professor encontra um cabeçalho com a identidade do produto, uma
ilustração discreta e original, o indicador essencial com a data e uma faixa que diz, em tom positivo, se
há sugestões. A página organiza o conteúdo em duas áreas: "Sugestões para você" e "Em detalhes".

**Why this priority**: é a parte do mockup que mais muda a percepção do produto, de painel técnico para
assistente educativo.

**Independent Test**: com o bloco atual, a página reorganizada mostra cabeçalho, faixa e as duas áreas em
salas com e sem sugestão, em en e pt_BR, e todo o conteúdo das Features 002–006 continua acessível.

**Acceptance Scenarios**:

1. **Given** uma sala com sugestão, **When** o professor abre a página, **Then** vê o cabeçalho com nome,
   slogan e ilustração, o volume com a data, e uma faixa "Há uma ideia para a sua sala" com a nota de que
   sugestões são possibilidades e não limitam o uso do Moodle.
2. **Given** uma sala sem sugestão, **When** o professor abre a página, **Then** a faixa diz "Tudo bem por
   aqui!" com "Não identificamos sugestões nos critérios avaliados nesta análise".
3. **Given** a página em inglês, **When** carregada, **Then** a identidade é "CourseCare"; em pt_BR, "AVA
   Consciente"; nenhum texto fixo em outra língua.
4. **Given** a página aberta pelo acesso de sugestão do bloco, **When** carregada, **Then** a área
   "Sugestões para você" está visível e em foco de leitura.
5. **Given** um resultado ausente, em preparação ou sem arquivos, **When** a página é aberta, **Then** ela
   mostra o estado como hoje, sem faixa de sugestões e sem as duas áreas vazias.

---

### User Story 3 - O professor entende a sugestão e onde agir (Priority: P2)

O cartão da sugestão de materiais grandes (Feature 004) passa a ter título claro, ícone discreto e ações
compreensíveis, e responde: o que observamos, por que pode merecer atenção, o que você pode fazer, se
quiser, e onde está a atividade relacionada.

**Why this priority**: a sugestão já existe; a 008 só a torna mais clara. Depende da área "Sugestões para
você" (História 2).

**Independent Test**: numa sala com materiais grandes, o cartão mostra as quatro respostas, com links para
as atividades, sem nenhum dado ou critério novo.

**Acceptance Scenarios**:

1. **Given** materiais grandes na sala, **When** o professor lê o cartão, **Then** encontra o que foi
   observado (quantidade, limite e total, como na 004), o motivo, as opções e as atividades com link.
2. **Given** o cartão, **When** lido, **Then** não há selo "Principal sugestão", nem valores sugeridos, nem
   promessa de economia, velocidade ou sustentabilidade.

---

### User Story 4 - O professor ainda encontra todos os detalhes (Priority: P1)

Tudo o que as Features 002–006 mostram continua na página, agrupado em "Em detalhes": onde estão os
arquivos, arquivos de grande porte, evolução dos arquivos, materiais por atividade, potencial das tarefas e
detalhes técnicos (armazenamento lógico, conteúdo único e o que os números não significam).

**Why this priority**: a apresentação educativa não pode reduzir o acesso à informação técnica (decisão do
proprietário do produto); é condição para entregar as Histórias 2 e 3.

**Independent Test**: a suíte de regressão das Features 002–006 passa com a página reorganizada; cada
indicador da página atual existe na nova, com os mesmos valores.

**Acceptance Scenarios**:

1. **Given** a sala de demonstração completa, **When** o professor abre "Em detalhes", **Then** encontra
   cada seção da página atual, com os mesmos números, rótulos e explicações.
2. **Given** um leitor de tela ou só o teclado, **When** o professor alterna entre as duas áreas, **Then**
   sabe qual área está ativa e chega a qualquer seção sem o mouse.
3. **Given** a impressão da página ou uma falha de scripts, **When** a página é exibida, **Then** todo o
   conteúdo das duas áreas fica acessível.

---

### Edge Cases

- Resultado desatualizado **e** com falha da última atualização: o bloco mostra a nota de falha (mais
  específica), como hoje.
- Cobertura parcial junto com desatualização ou falha: as notas aparecem na ordem falha, desatualização,
  cobertura parcial, todas discretas; o estado educativo continua um só.
- Sala sem arquivos: estado neutro; nenhuma das duas áreas mostra seções vazias.
- Análise em andamento **com** resultado anterior: o bloco mostra o resultado anterior (comportamento atual
  da 002/003).
- Sugestão cujas atividades foram ocultadas ou apagadas depois da análise: o cartão mostra o que a 004 já
  mostra (atividades sem link quando não acessíveis); nenhum dado novo.
- Tela de 320 px, zoom de 200%: cabeçalho, ilustração e faixa se reorganizam em coluna; a ilustração pode
  reduzir-se ou sumir sem perda de informação.
- Tema Classic (4.5) e Boost (4.5 e 5.3); bloco na gaveta do 5.3: contraste mínimo de 4,5:1 em todo texto.
- Idioma sem tradução do plugin: inglês, com a identidade "CourseCare".

## Requirements *(mandatory)*

### Functional Requirements

**Bloco educativo (História 1)**

- **FR-001**: O bloco MUST mostrar, nesta ordem: identidade (nome do plugin e broto), estado educativo
  (título e uma mensagem essencial), indicador principal (volume registrado no Moodle) com a data da
  análise, e um único acesso à página.
- **FR-002**: O estado educativo MUST ser escolhido só a partir de informações já calculadas (estado do
  diagnóstico, existência de resultado, número de arquivos, aplicabilidade da sugestão da 004):

  | Situação | Título (en / pt_BR) | Mensagem essencial |
  | --- | --- | --- |
  | Resultado válido, sem sugestão | "All good here!" / "Tudo bem por aqui!" | "No suggestions were found for the criteria checked in this analysis." / "Não identificamos sugestões nos critérios avaliados nesta análise." |
  | Resultado válido, com sugestão | "An idea for your course" / "Há uma ideia para a sua sala" | Frase curta sobre a sugestão disponível (FR-003) |
  | Sem arquivos | Neutro, sem título positivo | Texto atual "Nenhum arquivo…" |
  | Sem análise | Neutro | Texto atual "ainda não está disponível" |
  | Em preparação, sem resultado | Neutro | Texto atual "está sendo preparado" |

  Os textos finais em inglês são revisados no plano; os pt_BR acima são os aprovados.
- **FR-003**: A frase do estado com sugestão MUST descrever só a sugestão realmente aplicável (hoje, a de
  materiais grandes da 004) e MUST NOT inventar oportunidades, nomear uma "principal" atividade ou
  prometer benefício. [NEEDS CLARIFICATION: a frase pode trazer a quantidade de materiais grandes ("Encontramos 2 materiais de 1,0 MB ou mais"), contrariando a decisão da 004 de não pôr números na frase do bloco, ou continua sem números ("Alguns materiais da sua sala têm 1,0 MB ou mais")?]
- **FR-004**: Desatualização, falha com resultado anterior e cobertura parcial MUST aparecer como notas
  discretas abaixo do indicador, sem trocar o estado educativo do resultado exibido; a cobertura parcial
  passa a aparecer no bloco (hoje só na página).
- **FR-005**: O acesso MUST levar à área "Sugestões para você" quando houver sugestão e à página no início
  quando não houver; sem resultado, não há acesso (como hoje).
- **FR-006**: O bloco MUST continuar compacto: largura de bloco padrão, sem rolagem horizontal, no máximo
  um indicador numérico principal (Princípio II) e nenhum estado de dica genérica.

**Página educativa (História 2)**

- **FR-010**: A página MUST ter um cabeçalho com a identidade (nome e slogan do plugin), uma ilustração
  discreta e o indicador essencial com a data.
- **FR-011**: A ilustração MUST ser original do projeto, vetorial, incluída no pacote, sem pessoas nem
  mascotes, sem carregamento externo, com licença GPL v3 ou posterior declarada no próprio arquivo, e
  decorativa (ignorada por tecnologias assistivas).
- **FR-012**: A página MUST mostrar, logo após o cabeçalho, uma faixa de contexto: com sugestão, "Há uma
  ideia para a sua sala" e a nota "Sugestões são possibilidades. Elas não indicam que algo esteja errado e
  não limitam o seu uso do Moodle"; sem sugestão, "Tudo bem por aqui!" com a mensagem do FR-002.
- **FR-013**: A identidade MUST vir das strings do plugin: "CourseCare" em inglês e "AVA Consciente" em
  pt_BR; nenhum nome fixo no código ou nos templates.
- **FR-014**: [NEEDS CLARIFICATION: o título principal da página passa a ser a identidade do produto ("CourseCare" / "AVA Consciente", com o slogan), mantendo "Your course in numbers" / "Sua sala em números" como subtítulo e nome do link, ou o título continua "Your course in numbers" com a identidade como marca acima dele?]
- **FR-015**: A página MUST ter uma mensagem de fechamento sem promessa ("Each suggestion is a
  possibility; the decision is always yours, based on what your students need." / "Cada sugestão é uma
  possibilidade. A decisão é sempre sua, a partir do que os seus estudantes precisam.").

**Cartão de recomendação (História 3)**

- **FR-020**: O cartão da sugestão de materiais grandes MUST responder, com títulos visíveis: o que
  observamos; por que pode merecer atenção; o que você pode fazer, se quiser; e onde estão as atividades
  relacionadas (links da 004).
- **FR-021**: O cartão MUST usar os critérios, números e textos de orientação da Feature 004 sem alteração
  de significado; mudanças de redação MUST passar pela revisão de tom do proprietário do produto.
- **FR-022**: O cartão MUST NOT ter selo "Principal sugestão", ranking, valores sugeridos para limites ou
  qualquer recomendação nova.

**Detalhamento integrado (História 4)**

- **FR-030**: A página MUST organizar o conteúdo em duas áreas: "Suggestions for you" / "Sugestões para
  você" (faixa e cartões de sugestão) e "Details" / "Em detalhes" (todas as seções das Features 002, 003,
  005 e 006).
- **FR-031**: Cada seção, número, rótulo, explicação, gráfico, tabela e conteúdo recolhido da página atual
  MUST continuar presente, com os mesmos valores e o mesmo texto (ou texto revisado com o mesmo
  significado). Nenhuma informação é duplicada e não há segunda página de diagnóstico.
- **FR-032**: A alternância entre as duas áreas MUST funcionar com teclado e leitor de tela, anunciar a área
  ativa e permitir chegar a qualquer seção. [NEEDS CLARIFICATION: as duas áreas são abas reais (só uma visível por vez, padrão de abas acessível, todo o conteúdo visível sem scripts e na impressão) ou duas seções sempre visíveis com uma navegação no topo que leva a cada uma?]
- **FR-033**: Não haverá aba ou área "Boas práticas" nem qualquer área vazia.
- **FR-034**: Sem resultado (sem análise, em preparação sem resultado, sem arquivos), a página MUST mostrar
  o estado como hoje, sem a navegação entre áreas.

**Acessibilidade, responsividade e compatibilidade**

- **FR-040**: Bloco e página MUST atender à WCAG 2.2 AA: contraste de 4,5:1 (inclusive no bloco na gaveta
  do Moodle 5.3), foco visível, ordem de leitura igual à visual, ícones decorativos ocultos ou com texto,
  nenhuma informação só por cor, alvos de pelo menos 24 px.
- **FR-041**: Bloco e página MUST funcionar sem rolagem horizontal da página em 320 px e em zoom de 200%,
  em desktop e celular.
- **FR-042**: Bloco e página MUST funcionar no Moodle 4.5 (Boost e Classic) e 5.3 (Boost), com estilos
  restritos ao plugin, sem redefinir cores ou variáveis do tema.
- **FR-043**: As cores MUST NOT indicar avaliação do professor: verde para identidade e estado sem
  sugestão, amarelo discreto para sugestões, neutros para os demais; falha e desatualização sem vermelho de
  alarme.

**Preservação e limites**

- **FR-050**: A 008 MUST NOT criar tabela, campo, configuração, capability, tarefa, critério, indicador ou
  consulta nova sobre arquivos; a renderização do bloco e da página MUST NOT fazer mais leituras do banco
  do que hoje.
- **FR-051**: Toda string nova MUST existir em en e pt_BR (Princípio VIII), completa, com parâmetros, e
  classificada por tipo de texto (seção "Tipos de texto").
- **FR-052**: A documentação (guia do professor en e pt_BR, README, listagem do Marketplace e capturas)
  MUST ser atualizada para a nova apresentação.
- **FR-053**: A aprovação visual MUST NOT ser declarada antes da avaliação das capturas finais pelo
  proprietário do produto.

### Key Entities

- **Estado educativo do bloco**: derivado, não guardado. Combina o estado do diagnóstico (002/003), a
  existência de resultado e de arquivos e a aplicabilidade da sugestão (004); define título, mensagem e
  notas.
- **Área da página**: "Sugestões para você" ou "Em detalhes"; agrupa seções existentes, sem dado próprio.
- **Ilustração**: ativo vetorial original do pacote, decorativo.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Em 100% dos estados do diagnóstico (sem análise, em preparação, completo com e sem sugestão,
  sem arquivos, desatualizado, falha com resultado, cobertura parcial), o bloco mostra o título, a
  mensagem e as notas previstas, em en e pt_BR, conferido por testes automatizados.
- **SC-002**: 100% das seções, números e textos da página atual continuam presentes depois da
  reorganização (regressão das Features 002–006 verde e inventário seção a seção).
- **SC-003**: Zero violações de acessibilidade automáticas do plugin em bloco e página, nas três
  combinações (4.5 Boost, 4.5 Classic, 5.3 Boost), em desktop, 320 px e zoom de 200%; todas as paradas de
  teclado com foco visível.
- **SC-004**: Renderização do bloco e da página sem aumento de leituras do banco e com tempo médio dentro de
  10% do medido na 0.7.0 na mesma sala.
- **SC-005**: Zero textos que prometam economia, desempenho, sustentabilidade ou que avaliem o professor
  (revisão de todas as strings novas por tipo de texto).
- **SC-006**: Capturas reais de bloco e página (4.5 e 5.3, en e pt_BR, desktop e celular) comparadas lado a
  lado com o mockup aprovado e aprovadas pelo proprietário do produto.

## Assumptions

- Escopo só de apresentação: os dados vêm das Features 002–006 sem alteração.
- A única sugestão existente é a de materiais grandes (004); a faixa e o estado do bloco tratam "uma
  sugestão"; o plural fica preparado nas strings para o futuro, sem criar sugestões.
- A cobertura parcial passa a ser nota também no bloco (a informação já existe no resultado).
- Estado "sem arquivos" é neutro: não usa "Tudo bem por aqui!" (não há o que avaliar).
- A ilustração é desenhada no projeto, como o broto, e entra no relatório de titularidade como os demais
  ativos; nenhuma imagem de banco de imagens, de terceiros ou gerada fora do projeto.
- Sem link "Como funciona?" para documentação externa (fora do escopo pedido; a documentação continua no
  repositório).
- Versão alvo 0.8.0, maturidade BETA; Moodle 4.5 e 5.3, como na 0.7.0.
- Validações humanas (revisão de tom, aprovação das capturas, leitor de tela) ficam registradas como
  pendentes até feitas; nenhuma é marcada sem evidência.

## Fora do escopo

Biblioteca de boas práticas, rotação de dicas, detecção de vídeos, integração com serviços de vídeo,
orientação ou recomendação nova sobre tarefas, priorização de recomendações e selo "Principal sugestão",
classificação de qualidade pedagógica, novos indicadores, estimativas de economia e promessas de
sustentabilidade. Continuam no roadmap (canvas de proposta, seção "Roadmap").
