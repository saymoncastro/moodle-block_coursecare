# Feature Specification: Teacher Experience

**Feature Branch**: `003-teacher-experience` (integração à `main` só após os quality gates e a
validação visual)

**Created**: 2026-10-17

**Status**: Clarified (decisões do proprietário do produto de 2026-10-17; pronta para `/speckit-plan`)

**Input**: User description: "Feature 003 — Teacher Experience: transformar os diagnósticos produzidos
pela Feature 002 em uma experiência visual simples, acolhedora e compreensível para o professor, em
dois níveis (bloco compacto e página de detalhamento), com a direção visual aprovada do AVA
Consciente, linguagem simples, todos os estados do diagnóstico, segurança e privacidade com as
capabilities existentes, responsividade, acessibilidade, internacionalização e validação visual em
um Moodle real. Sem recomendações (Feature 004), sem novos mecanismos de análise."

**Constituição de referência**: 1.2.0

**Categorias de indicador exibidas (Princípio IV)**: esta feature exibe ao professor o
**Armazenamento lógico** (no bloco e na página) e o **Conteúdo único** (só nos detalhes técnicos da
página, com explicação). Ela MUST NOT calcular nem exibir Consumo físico atribuível, Potencial
teórico ou Economia observada, e MUST NOT somar categorias diferentes.

**Dados lidos e exibidos (Princípio VII)**: a feature lê apenas o resultado já calculado pela
Feature 002 (pelo seu ponto de acesso único) e a estrutura da sala que o Moodle já mantém (nomes e
visibilidade das atividades). Ela MUST NOT ler os registros de arquivos, MUST NOT armazenar dados
novos e MUST NOT exibir nomes de estudantes, nomes de arquivos ou qualquer dado individual de
estudante. Envios de estudantes aparecem **somente no agregado da sala**; por atividade, só o volume
dos materiais da sala (FR-030).

## Dependências e estado das features anteriores

- **Feature 001 (0.1.0)**: bloco instalável só na página principal da sala, identidade CourseCare /
  AVA Consciente, ativo `sprout` (broto de duas folhas em verdes suaves), frase de identidade
  ("Pequenas escolhas, um AVA melhor para todos.") e capability `block/coursecare:view` (professor
  com edição e gerente). Decisões visuais registradas na 001: a paleta própria limita-se ao ativo
  (restrição substituída nesta feature pelas diretrizes visuais aprovadas), sem redefinir cores do
  tema; contraste herdado do tema; destaque moderado sem cor própria; nenhuma
  borda extra, sombra, cartão aninhado ou badge. A avaliação estruturada com cinco docentes foi
  transferida para esta feature.
- **Feature 002 (0.2.0)**: diagnóstico por sala calculado em segundo plano, com indicadores A a F,
  referências e agregado de cópias de segurança e lixeira; estados A a H; uma linha no bloco com o
  Armazenamento lógico e a data. Ela deixou para esta feature: a apresentação de categorias,
  atividades e arquivos de grande porte; a regra de exibição de agregados de envios por atividade
  (risco RK7); e a avaliação de uma atualização pela interface.
- **Protótipo visual**: o protótipo original aprovado (a segunda proposta visual) não está no
  repositório nem nos artefatos publicados. As diretrizes aprovadas estão registradas abaixo e a
  interface é reconstruída e validada a partir delas (FR-070, FR-073); a ausência do arquivo não
  bloqueia esta especificação.

## Diretrizes visuais aprovadas

Registradas por decisão do proprietário do produto em 2026-10-17 (segunda proposta visual do
CourseCare / AVA Consciente). Elas substituem, para esta feature, a restrição da Feature 001 de
limitar a paleta própria ao ativo.

- Experiência acolhedora, inspirada na natureza: **verdes suaves e cores neutras**, **cantos
  arredondados** e o pequeno símbolo de broto/folha (o ativo `sprout` da Feature 001).
- Deve lembrar um **assistente educativo de boas práticas**, não um painel administrativo.
- **Cartões discretos, hierarquia visual e elementos gráficos** são permitidos quando agregarem
  clareza.
- **Sem mascotes nem elementos infantis.**
- **Compatível com os temas nativos do Moodle** (Boost, Classic e derivados), reutilizando seus
  componentes, e atendendo à WCAG 2.2 AA (contraste, foco, informação não transmitida só por cor).
- Não definidos e, portanto, **não inventados nesta especificação**: tons exatos, tipografia,
  espaçamentos, ilustrações além do ativo e disposição detalhada dos cartões. Eles são propostos na
  etapa de reconstrução visual e só valem depois da aprovação do proprietário do produto (FR-073).

## Definições

- **Bloco compacto (nível 1)**: o bloco na página principal da sala.
- **Página "Sua sala em números" (nível 2)**: página aberta a partir do bloco, com a visão da sala.
  Nesta feature ela mostra situações concretas medidas (onde estão os arquivos), sem recomendações;
  as orientações práticas do nível 2 chegam com a Feature 004.
- **Detalhes técnicos (nível 3)**: seções da mesma página, recolhidas por padrão, para quem quiser
  aprofundar (Princípio II).
- **Resultado vigente**: o último diagnóstico concluído da sala, com sua data, lido do ponto de
  acesso único da Feature 002.
- **Nível da sala**: arquivos da página inicial, das seções e dos blocos da sala, que não pertencem
  a uma atividade (atividade `0` na Feature 002).
- Os termos Armazenamento lógico, Conteúdo único, categorias, arquivos de grande porte, referências e
  cópias de segurança e lixeira têm o significado da Feature 002.

## Clarifications

### Session 2026-10-17

- Q: Qual referência visual seguir, já que o protótipo aprovado não está no repositório? → A: A
  segunda proposta visual aprovada, registrada como diretrizes (verdes suaves e neutros, cantos
  arredondados, broto/folha, cartões discretos e elementos gráficos quando agregarem clareza, sem
  mascotes, compatível com os temas do Moodle), sem inventar detalhes não definidos, com etapa de
  reconstrução visual e aprovação do proprietário do produto.
- Q: Como exibir o volume de envios de estudantes? → A: Somente no agregado da sala; por atividade,
  apenas o volume dos materiais, sem nada que revele envios por atividade.
- Q: A avaliação estruturada com cinco professores faz parte desta feature? → A: Sim, como critério
  de aceitação, com a interface funcionando em um Moodle real; problemas críticos são corrigidos
  antes do encerramento.
- Confirmado: remover "orientações em breve" e manter a frase de identidade; não mostrar nomes de
  arquivos; sem botão de atualização; acesso só aos perfis autorizados; lista inicial de dez
  atividades; detalhes técnicos recolhidos; sem antecipar a Feature 004.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Entender a sala de relance no bloco (Priority: P1)

Como professor, ao entrar na página da minha sala, quero ver em uma frase a situação do panorama de
arquivos, um único número principal e a data, e saber onde ver mais, sem precisar entender
infraestrutura.

**Why this priority**: o bloco é o ponto de contato diário. Ele precisa ser leve e confiável antes de
qualquer detalhamento.

**Independent Test**: em uma sala com diagnóstico concluído, o bloco mostra o volume, a data e um
link "Conheça melhor os arquivos da sua sala"; em cada outro estado, mostra a mensagem do estado.

**Acceptance Scenarios**:

1. **Given** uma sala com resultado vigente, **When** o professor abre a página principal da sala,
   **Then** o bloco mostra a identidade, uma frase com o Armazenamento lógico "como registrado pelo
   Moodle", a data da análise e um link para a página de detalhamento.
2. **Given** uma sala ainda não analisada, aguardando ou em processamento sem resultado anterior,
   **When** o professor abre a sala, **Then** o bloco mostra só a mensagem do estado, sem número e sem
   link para a página.
3. **Given** um resultado desatualizado ou uma falha com resultado anterior, **When** o professor abre
   a sala, **Then** o bloco mostra o volume anterior, a data e o aviso do estado, com o link.
4. **Given** qualquer estado, **When** o bloco é exibido, **Then** ele não mostra categorias,
   atividades, gráficos, avaliação da sala nem comparação com outras salas.

---

### User Story 2 - Conhecer onde estão os arquivos da sala (Priority: P1)

Como professor, quero abrir uma página que explique, em linguagem simples, como o volume da minha
sala se divide entre materiais que publiquei, arquivos enviados em atividades e outros arquivos, e
em quais atividades estão concentrados os materiais.

**Why this priority**: é o valor novo desta feature: transformar o diagnóstico em compreensão.

**Independent Test**: em uma sala com materiais, uma tarefa com envios e um fórum com anexos, a
página mostra o total e a divisão por categoria da sala, e a lista de atividades com o volume dos
materiais de cada uma, com valores iguais ao diagnóstico e sem volume de envios por atividade.

**Acceptance Scenarios**:

1. **Given** um resultado vigente, **When** o professor segue o link do bloco, **Then** a página
   "Sua sala em números" mostra o Armazenamento lógico com a data, a divisão por categoria da sala
   (volume e proporção do total) e as atividades com materiais, ordenadas pelo volume dos materiais,
   cada uma com seu nome e link.
2. **Given** a divisão por categoria, **When** a página a exibe, **Then** cada proporção tem um
   equivalente em texto (não só cor ou forma) e a soma das categorias é igual ao total exibido.
3. **Given** uma sala com mais atividades do que o limite inicial da lista, **When** a página é
   aberta, **Then** ela mostra as primeiras e oferece "Mostrar todas", sem recarregar o diagnóstico.
4. **Given** materiais da página inicial, das seções ou de blocos, **When** a lista é exibida,
   **Then** eles aparecem agrupados como nível da sala, nunca atribuídos a uma atividade.
5. **Given** uma tarefa com materiais e com envios, **When** a lista é exibida, **Then** a tarefa
   mostra só o volume dos seus materiais; o volume dos envios não aparece por atividade, nem direta
   nem indiretamente (total da atividade, ordenação ou gráfico).
6. **Given** uma atividade que só tem envios ou outros arquivos, sem materiais, **When** a lista é
   exibida, **Then** ela não aparece na lista.

---

### User Story 3 - Confiar nos números e entender seus limites (Priority: P1)

Como professor, quero saber quando os números foram calculados, se estão atualizados, se a análise
cobriu tudo e o que os números não significam, para não tirar conclusões erradas.

**Why this priority**: honestidade dos indicadores é princípio inegociável (IV); sem ela a página
induz decisões erradas.

**Independent Test**: para cada estado A a H, a página mostra a mensagem própria; nos detalhes
técnicos, explica Conteúdo único, referências, cópias de segurança e lixeira e a cobertura.

**Acceptance Scenarios**:

1. **Given** um resultado desatualizado, **When** a página é aberta, **Then** ela mostra os números
   anteriores, a data e um aviso de possível desatualização, sem tom alarmista.
2. **Given** uma falha com resultado anterior, **When** a página é aberta, **Then** ela mostra o
   resultado anterior, a data e a informação de que a última atualização não foi concluída, sem
   detalhes técnicos da falha.
3. **Given** uma análise com cobertura parcial, **When** a página é aberta, **Then** ela informa, em
   linguagem simples, que parte dos arquivos não pôde ser classificada e quanto isso representa.
4. **Given** os detalhes técnicos recolhidos, **When** o professor os abre, **Then** vê o Conteúdo
   único com a explicação de que o mesmo conteúdo pode existir em outras salas, as referências
   (contadas à parte, sem somar ao total) e as cópias registradas no Moodle para esta sala, também
   à parte.
5. **Given** qualquer número exibido, **When** a página o apresenta, **Then** ele nunca é descrito
   como espaço físico ocupado só pela sala nem como espaço que seria liberado.

---

### User Story 4 - Proteger estudantes e restringir o acesso (Priority: P1)

Como instituição, quero que só quem já pode ver o bloco veja a página, e que nenhuma informação
individual de estudante apareça.

**Why this priority**: segurança e privacidade são inegociáveis (VII) e a página expõe mais detalhe
que o bloco.

**Independent Test**: um estudante, um professor sem edição e um professor de outra sala não acessam
a página; o professor da sala não vê nomes de estudantes nem nomes de arquivos de envios.

**Acceptance Scenarios**:

1. **Given** um usuário sem a capability de visualização do bloco na sala, **When** ele abre o
   endereço da página, **Then** recebe a negação padrão do Moodle e nenhum dado do diagnóstico.
2. **Given** um professor de outra sala, **When** ele altera o identificador no endereço, **Then** a
   página verifica a permissão no contexto daquela sala e nega o acesso.
3. **Given** uma tarefa com envios, **When** a página é exibida, **Then** o volume de envios aparece
   só no agregado da sala (FR-030), sem nomes de estudantes nem nomes de arquivos enviados.
4. **Given** uma atividade que o professor não pode ver segundo as regras do Moodle, **When** a lista
   é exibida, **Then** o nome dela não aparece.

---

### User Story 5 - Usar a experiência em qualquer tela e com tecnologia assistiva (Priority: P2)

Como professor, quero usar o bloco e a página no computador e no celular, com teclado ou leitor de
tela, em inglês ou em português.

**Why this priority**: o público docente é diverso (II, X) e o produto é internacional (VIII).

**Independent Test**: a jornada bloco → página → detalhes técnicos → volta é concluída só com
teclado, a 320 px de largura e com leitor de tela, em en e pt_BR, sem violações automáticas de
acessibilidade.

**Acceptance Scenarios**:

1. **Given** uma tela de 320 px, **When** a página é aberta, **Then** o conteúdo se reorganiza em uma
   coluna, sem rolagem horizontal, e as listas continuam legíveis.
2. **Given** navegação só por teclado, **When** o professor percorre a página, **Then** todos os
   controles (link do bloco, "Mostrar todas", seções recolhíveis, links de atividade) recebem foco
   visível e funcionam com teclado.
3. **Given** um leitor de tela, **When** a página é lida, **Then** os títulos formam uma hierarquia
   coerente, o estado das seções recolhíveis é anunciado e as proporções têm texto equivalente.
4. **Given** o idioma pt_BR sem a tradução instalada, **When** a página é aberta, **Then** todas as
   frases aparecem em inglês, completas, sem mistura de idiomas na mesma frase.

---

### User Story 6 - Ver os arquivos de grande porte da sala (Priority: P3)

Como professor, quero saber quantos arquivos de grande porte minha sala tem e em quais atividades
estão, apresentados como informação, nunca como problema.

**Why this priority**: útil, mas secundário à compreensão do total e da distribuição.

**Independent Test**: em uma sala com um material de 60 MB e um envio de 60 MB (limite padrão de
50 MB), a página mostra dois arquivos de grande porte na sala, o limite usado, e a atividade do
material; o envio entra só no total da sala.

**Acceptance Scenarios**:

1. **Given** arquivos de grande porte, **When** a página é aberta, **Then** ela mostra a quantidade e
   o volume da sala, o limite usado no cálculo e a explicação de que o limite é um valor de triagem,
   não uma regra pedagógica.
2. **Given** arquivos de grande porte em materiais, **When** a página os lista, **Then** mostra, por
   atividade, a quantidade e o volume desses materiais, com link para a atividade, sem nome de
   arquivo (FR-033).
3. **Given** arquivos de grande porte em envios, **When** a página é exibida, **Then** eles entram só
   na quantidade e no volume da sala, nunca por atividade (FR-030).
4. **Given** uma sala com mais materiais de grande porte do que o limite de ligações guardado pela
   Feature 002, **When** a página os lista por atividade, **Then** informa que a lista considera só
   os maiores, até esse limite.

---

### Edge Cases

- **Atividade removida depois da análise**: aparece agregada como "atividade removida após a última
  análise", sem nome, até a próxima análise.
- **Atividade renomeada ou ocultada depois da análise**: o nome e a visibilidade vêm do estado atual
  da sala, não do diagnóstico.
- **Sala sem arquivos no escopo principal (G) com referências ou cópias**: o resumo diz que não há
  arquivos nos materiais e atividades; referências e cópias aparecem só nos detalhes técnicos.
- **Diagnóstico ausente ao seguir um link antigo**: a página mostra o estado A, sem números.
- **Bloco removido da sala**: a página nega o acesso (a sala deixou de ser elegível e o diagnóstico é
  removido pela Feature 002).
- **Valores muito pequenos ou muito grandes**: tamanhos no formato de tamanho do Moodle; proporções
  menores que 1% aparecem como "menos de 1%", nunca como 0%.
- **Muitas atividades** (centenas): a lista inicial é limitada e o restante fica em "Mostrar todas".
- **Atividade com envios e sem materiais**: não aparece na lista de atividades.
- **Mais de 1.000 materiais de grande porte**: a lista por atividade considera os 1.000 maiores
  guardados pela Feature 002 e diz isso; os totais da sala continuam completos.
- **Tradução parcial ou ausente**: fallback inglês por string completa.
- **Temas**: Boost, Classic e temas derivados, sem depender de cores próprias do tema.
- **Rótulo de tamanho do pacote de idioma do core**: em pt_BR o core escreve "Mb"; é limitação do
  pacote oficial, fora do plugin.

## Requirements *(mandatory)*

### Functional Requirements

**Bloco compacto (nível 1)**

- **FR-001**: O bloco MUST continuar só na página principal da sala, só para quem tem a capability de
  visualização existente, com a identidade da Feature 001.
- **FR-002**: O bloco MUST mostrar no máximo um indicador, o Armazenamento lógico, com rótulo que não
  sugira espaço físico ocupado, sempre acompanhado da data da análise (Princípios II e IV).
- **FR-003**: Quando existir resultado vigente (estados D, E, F com resultado, G e H), o bloco MUST
  oferecer um link para a página de detalhamento. Sem resultado (A, B, C, F sem resultado), o bloco
  MUST mostrar só a mensagem do estado, sem link.
- **FR-004**: O bloco MUST NOT exibir categorias, atividades, gráficos, avaliação da sala, comparação
  com outras salas, alertas ou contadores chamativos.
- **FR-005**: O conteúdo textual do bloco além da linha do diagnóstico MUST ser revisto para não
  prometer o que a feature não entrega (a mensagem atual anuncia "orientações em breve"), mantendo a
  identidade e a frase de identidade aprovadas.

**Página "Sua sala em números" (níveis 2 e 3)**

- **FR-010**: A página MUST ser aberta a partir do bloco e mostrar sempre a sala do bloco de origem,
  verificando login, contexto e capability de visualização naquele contexto (Princípio VII).
- **FR-011**: A página MUST apresentar, nesta ordem: (1) resumo da sala — Armazenamento lógico, data,
  estado e cobertura; (2) onde estão os arquivos — divisão por categoria da sala; (3) materiais por atividade;
  (4) arquivos de grande porte; (5) detalhes técnicos recolhidos por padrão.
- **FR-012**: A divisão por categoria MUST mostrar, para Materiais da sala, Envios em atividades,
  Outros arquivos associados e Não classificado, o volume lógico e a proporção do total, com uma
  frase simples que explique cada categoria. A soma das categorias MUST ser igual ao total exibido.
- **FR-013**: A lista de atividades MUST mostrar, por atividade com materiais, o nome atual (como o
  professor o vê no Moodle) com link para a atividade, o volume dos materiais e a proporção do total
  de materiais da sala, ordenada do maior para o menor volume de materiais, mais o nível da sala
  como item à parte. Ela MUST NOT mostrar, por atividade, o volume total, o de envios, o de outros
  arquivos ou o de não classificados, nem a quantidade de arquivos.
- **FR-014**: A lista de atividades MUST mostrar inicialmente no máximo 10 itens e oferecer "Mostrar
  todas" para o restante.
- **FR-015**: Atividades que o professor não pode ver segundo as regras do Moodle MUST aparecer sem
  nome, agregadas como "outras atividades". Atividades removidas depois da análise MUST aparecer
  agregadas como "removidas após a última análise". Atividades sem materiais MUST NOT aparecer.
- **FR-016**: Toda proporção representada visualmente MUST ter equivalente textual, e nenhuma
  informação MUST depender só de cor (Princípio X).
- **FR-017**: Os detalhes técnicos MUST conter, cada um com explicação em linguagem simples:
  Conteúdo único; referências (internas, externas e indeterminadas, à parte do total); cópias de
  segurança e lixeira registradas no Moodle para esta sala (à parte do total); cobertura da
  classificação (registros não classificados); limite de grande porte usado no cálculo; e o que os
  números não significam.
- **FR-018**: A página MUST NOT somar referências, cópias de segurança ou lixeira ao Armazenamento
  lógico ou ao Conteúdo único, nem apresentar referências externas como arquivos guardados no
  Moodle.
- **FR-019**: Cada nível MUST ser compreensível sem a leitura do seguinte: o resumo faz sentido
  sozinho, e os detalhes técnicos não são necessários para entender o resumo e a distribuição.

**Estados**

- **FR-020**: A página MUST ter comportamento próprio para cada estado da Feature 002:

  | Estado | Bloco | Página |
  | --- | --- | --- |
  | A. Ainda não analisada | Mensagem do estado, sem número, sem link | Mensagem do estado, sem números |
  | B. Aguardando, sem resultado | "Em preparação", sem número, sem link | Mensagem de preparação, sem números |
  | C. Em processamento, sem resultado | Igual a B | Igual a B |
  | D. Concluída | Volume, data, link | Todas as seções |
  | E. Desatualizada | Volume anterior, data, aviso, link | Todas as seções, com aviso de possível desatualização no resumo |
  | F. Falha com resultado | Volume anterior, data, aviso, link | Todas as seções do resultado anterior, com a informação de que a atualização não foi concluída |
  | F. Falha sem resultado | Igual a A | Igual a A |
  | G. Sem arquivos | Frase de sala sem arquivos, data, link | Resumo sem arquivos e data; seções 2 a 4 omitidas; detalhes técnicos disponíveis |
  | H. Cobertura parcial | Igual a D ou G | Igual a D ou G, com a cobertura explicada no resumo |

- **FR-021**: Quando uma nova análise estiver pendente e existir resultado, bloco e página MUST
  continuar mostrando o resultado vigente com sua data.
- **FR-022**: A ausência de dados MUST NOT aparecer como zero; zero só aparece no estado G e em
  categorias realmente vazias de um resultado concluído.
- **FR-023**: Nenhum estado MUST emitir avaliação positiva ou negativa da sala ou do professor.

**Linguagem**

- **FR-024**: Os textos MUST usar linguagem simples, sem termos de infraestrutura ("contenthash",
  "filearea", "contexto", "deduplicação", "blob") e sem apresentar números como espaço físico
  exclusivo, economia ou mau uso. Conceitos técnicos necessários MUST ser explicados em uma frase.
- **FR-025**: Os textos MUST ser construtivos e não alarmistas, sem culpa, pressão ou comparação
  entre pessoas, verificado na revisão de tom e rótulos com aprovação do proprietário do produto
  (SC-008).

**Privacidade e segurança**

- **FR-030**: O volume de envios de estudantes MUST aparecer somente no agregado da sala (categoria
  Envios em atividades e totais da sala). Nenhum elemento da página MUST revelar, direta ou
  indiretamente, o volume de envios de uma atividade: nem o total da atividade, nem a ordenação, nem
  gráficos, nem diferenças entre valores exibidos. A mesma regra vale para "Outros arquivos
  associados" e "Não classificado", que podem conter arquivos de estudantes (anexos de fórum,
  respostas): por atividade, só os materiais.
- **FR-031**: A página MUST NOT exibir nomes de estudantes, nomes ou identificadores de arquivos de
  envios, nem dados individuais de estudantes (Princípio VII). A FR-033 estende a proibição de nomes
  a todos os arquivos.
- **FR-032**: A feature MUST reutilizar a capability de visualização existente e MUST NOT criar
  capabilities novas para o professor.
- **FR-033**: A página MUST NOT exibir nomes de arquivos. Arquivos de grande porte de materiais são
  apresentados por atividade (quantidade e volume, a partir das ligações a materiais guardadas pela
  Feature 002), com link para a atividade; os de envios e de outras categorias entram só nos totais
  da sala.
- **FR-034**: A capability de visualização MUST NOT autorizar o acesso a arquivos, conteúdos ou
  dados individuais de outros componentes.

**Desempenho e integração com a Feature 002**

- **FR-040**: Bloco e página MUST ler só o resultado pré-calculado pelo ponto de acesso único da
  Feature 002 e a estrutura da sala mantida pelo Moodle; MUST NOT consultar os registros de arquivos
  nem calcular indicadores.
- **FR-041**: A feature MUST NOT criar novos mecanismos de análise, novos dados persistidos nem
  alterar o agendamento da Feature 002. Quando um valor não puder ser apresentado com os dados já
  persistidos sem violar FR-030 ou sem induzir leitura errada, ele MUST ser omitido e a limitação
  documentada, em vez de aproximado.
- **FR-042**: A feature MUST NOT oferecer ao professor um botão de atualização do diagnóstico
  (mantém FR-039d da Feature 002).

**Acessibilidade, responsividade e temas**

- **FR-050**: Bloco e página MUST atender aos critérios aplicáveis da WCAG 2.2 AA: contraste, teclado,
  foco visível, rótulos acessíveis, hierarquia de títulos, estado anunciado das seções recolhíveis,
  reorganização a 320 px sem rolagem horizontal e informação não transmitida só por cor.
- **FR-051**: A apresentação MUST seguir as diretrizes visuais aprovadas (seção acima): verdes suaves
  e neutros, cantos arredondados, o símbolo de broto/folha, cartões discretos e elementos gráficos
  quando agregarem clareza, sem aspecto de painel administrativo e sem mascotes ou elementos
  infantis. Ela MUST reutilizar componentes do Moodle, funcionar nos temas Boost e Classic e em
  temas derivados, e MUST NOT redefinir as cores globais do tema.

**Internacionalização**

- **FR-060**: Toda frase visível MUST vir do catálogo de strings, com frases completas e parâmetros,
  em `en` e com tradução `pt_br` em paridade de chaves e parâmetros. Nenhum texto fixo em templates.
- **FR-061**: O nome exibido MUST ser CourseCare em inglês e AVA Consciente em pt_BR, com terminologia
  genérica ("sala" na tradução, não nas regras).

**Validação**

- **FR-070**: Antes da conclusão, bloco e página MUST ser validados visualmente em um Moodle real, com
  capturas em desktop e celular, em en e pt_BR, nos temas Boost e Classic, nos estados D, E, F, G e
  sem resultado, com aprovação registrada do proprietário do produto.
- **FR-071**: Uma avaliação estruturada com **cinco professores** de diferentes níveis de
  familiaridade tecnológica (e, de preferência, de áreas diferentes) MUST ser realizada com a
  interface funcionando em um Moodle real, e é critério de aceitação desta feature. Ela avalia:
  compreensão do bloco compacto; interpretação dos indicadores; navegação até o detalhamento;
  compreensão dos arquivos de grande porte; clareza dos estados e mensagens; e facilidade geral de
  uso. Evidências e dificuldades MUST ser registradas, sem dados pessoais dos participantes além do
  perfil (familiaridade e área).
- **FR-071a**: Problemas críticos identificados na avaliação MUST ser corrigidos antes do encerramento
  da feature. É crítico o problema que impede entender o número principal ou o estado do
  diagnóstico, chegar ao detalhamento, ou usar a interface com teclado ou leitor de tela, e o que
  leva a ler os números como espaço físico, economia ou avaliação do professor. Ajustes menores
  podem ser registrados como melhorias futuras.
- **FR-072**: A documentação (README) MUST descrever o bloco, a página, quem pode vê-los e o que os
  números não significam.
- **FR-073**: A interface MUST passar por uma etapa de reconstrução visual a partir das diretrizes
  aprovadas, com proposta apresentada ao proprietário do produto em capturas reais, antes da
  avaliação com professores. A feature MUST NOT ser considerada concluída sem a aprovação visual
  registrada do proprietário do produto.

### Key Entities

- **Visão da sala**: o que a página apresenta a partir do resultado vigente — total, data, estado,
  cobertura, categorias, atividades, arquivos de grande porte e detalhes técnicos. Não é persistida:
  é montada a cada exibição a partir do diagnóstico e da estrutura da sala.
- **Item de atividade**: uma atividade com materiais no resultado, com nome e visibilidade atuais,
  volume dos materiais, proporção do total de materiais e, se houver, quantidade e volume de
  materiais de grande porte; ou um grupo (nível da sala, outras atividades, removidas após a
  análise).

## Cenários de teste

| ID | Cenário | Resultado esperado |
| --- | --- | --- |
| CT-01 | Bloco no estado D | Volume, data e link; nenhum outro número |
| CT-02 | Bloco nos estados A, B, C e F sem resultado | Só a mensagem do estado, sem link |
| CT-03 | Bloco nos estados E e F com resultado | Volume anterior, data, aviso e link |
| CT-04 | Página no estado D com materiais, envios e outros | Total e categorias da sala iguais ao diagnóstico (soma das categorias = total); atividades com o volume dos materiais |
| CT-05 | Página nos estados A, B, C, E, F, G e H | Comportamento da tabela de FR-020 |
| CT-06 | Navegação bloco → página → volta | Link do bloco abre a página da mesma sala; a página tem caminho de volta à sala |
| CT-07 | Mais de 10 atividades com materiais | 10 itens iniciais e "Mostrar todas" com o restante |
| CT-08 | Atividade oculta, restrita, renomeada e removida após a análise | Nomes atuais; sem nome para a que o professor não vê; agregado das removidas |
| CT-09 | Materiais do curso, de seções e de blocos | Agrupados como nível da sala |
| CT-10 | Arquivos de grande porte em material e em envio | Quantidade, volume e limite da sala; material por atividade com link; envio só no total da sala |
| CT-11 | Detalhes técnicos | Recolhidos por padrão; Conteúdo único, referências, cópias e cobertura explicados e fora do total |
| CT-12 | Estudante, professor sem edição, professor de outra sala e visitante | Acesso negado, nenhum dado |
| CT-13 | Identificador de outra sala no endereço | Permissão verificada no contexto daquela sala |
| CT-14 | Tarefa com materiais e envios; tarefa só com envios; fórum com anexos | Por atividade, só materiais; a tarefa só com envios e o fórum sem materiais não aparecem; nenhum valor permite deduzir envios por atividade |
| CT-15 | Inspeção da página e do bloco | Nenhum nome de estudante, nome de arquivo ou termo de infraestrutura |
| CT-16 | Teclado, leitor de tela e 320 px | Jornada completa; foco visível; estado das seções anunciado; sem rolagem horizontal |
| CT-17 | Verificação automática de acessibilidade | Nenhuma violação no bloco e na página |
| CT-18 | pt_BR com e sem tradução instalada | Textos em pt_BR; sem a tradução, fallback inglês por frase completa |
| CT-19 | Temas Boost e Classic | Mesma estrutura e legibilidade, sem cores próprias além do ativo |
| CT-20 | Renderização do bloco e da página | Nenhuma consulta aos registros de arquivos |
| CT-21 | Proporção menor que 1% | "menos de 1%", nunca "0%" |
| CT-22 | Mais de 1.000 materiais de grande porte | Lista por atividade com o aviso de limite; totais da sala completos |
| CT-23 | Avaliação com cinco professores (manual) | Registro das seis dimensões de FR-071; nenhum problema crítico em aberto |
| CT-24 | Validação visual (manual) | Capturas de FR-070 aprovadas pelo proprietário do produto |

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Em 100% dos estados A a H, bloco e página mostram a mensagem e as seções da tabela de
  FR-020 (cenários automatizados).
- **SC-002**: Em 100% dos cenários com resultado, os valores exibidos (total, categorias, materiais
  por atividade, arquivos de grande porte, Conteúdo único, referências e cópias) são iguais aos do
  diagnóstico armazenado, e a soma das categorias é igual ao total exibido.
- **SC-003**: Abrir a página de uma sala com 200 atividades com materiais leva até 2 segundos no
  ambiente de referência, e nem o bloco nem a página consultam os registros de arquivos.
- **SC-004**: O bloco não acrescenta mais que 50 ms à página da sala em relação à 0.2.0, no ambiente
  de referência.
- **SC-005**: Em 100% das tentativas de acesso não autorizado (CT-12, CT-13), nenhum dado do
  diagnóstico é exibido.
- **SC-006**: A inspeção do bloco e da página em salas com envios não encontra nome de estudante,
  nome ou identificador de arquivo, nome de atividade que o professor não pode ver, nem qualquer
  valor por atividade que inclua envios (CT-14).
- **SC-007**: A verificação automática de acessibilidade não encontra violações no bloco e na página,
  e a jornada bloco → página → detalhes técnicos → volta é concluída só com teclado.
- **SC-008**: 100% das frases visíveis vêm do catálogo de strings, com paridade en/pt_br; a revisão
  de tom e rótulos não encontra termo de infraestrutura nem leitura de consumo físico, economia ou
  mau uso, com aprovação registrada do proprietário do produto.
- **SC-009**: A validação visual (FR-070) é aprovada pelo proprietário do produto, com capturas
  registradas de todos os estados exigidos, em desktop e celular, en e pt_BR, Boost e Classic.
- **SC-010**: Todos os quality gates da constituição passam (moodle-plugin-ci, PHPUnit, Behat com
  acessibilidade, paridade de strings, verificação do pacote) nas bases suportadas.
- **SC-011**: A avaliação com cinco professores (FR-071) é concluída e registrada: cada um dos cinco
  realiza as tarefas das seis dimensões avaliadas, e nenhum problema crítico (FR-071a) permanece em
  aberto no encerramento.

## Limitações reconhecidas

- **Por atividade, só o volume dos materiais**: a Feature 002 guarda, por atividade, o volume por
  categoria, mas não a quantidade de arquivos por categoria; por isso a lista de atividades mostra o
  volume dos materiais, sem quantidade de arquivos.
- **Grandes por atividade, só materiais e até 1.000**: a quantidade e o volume de grandes por
  atividade guardados pela Feature 002 misturam categorias e não são usados; os materiais de grande
  porte por atividade vêm das ligações a materiais, limitadas aos 1.000 maiores por sala.
- **Nomes e visibilidade atuais**: os nomes de atividades vêm do estado atual da sala, que pode ter
  mudado desde a análise.

## Fora do escopo

- Recomendações e orientações práticas (Feature 004); potencial de armazenamento de atividades
  (Feature 005); histórico e evolução no tempo (Feature 006).
- Rankings, comparações entre salas ou professores, pontuação, indicadores de carbono ou ambientais,
  estimativas de economia.
- Exclusão, movimentação ou edição de arquivos; ações executadas pelo plugin.
- Novos mecanismos de análise, novos dados persistidos, mudanças no agendamento ou na capacidade de
  processamento da Feature 002 (inclusive a capacidade configurável por execução, registrada como
  melhoria futura).
- Botão de atualização manual pelo professor.
- Notificações, e-mails ou mensagens.
- Painel do administrador do site ou relatórios entre salas.
- O aplicativo móvel oficial do Moodle ("celular" significa o navegador em tela pequena).

## Riscos

- **Leitura errada do número como espaço do servidor**: mitigação por rótulo, explicação curta e
  detalhes técnicos; revisão de tom.
- **Inferência do volume enviado por um estudante** em atividades com poucos envios (RK7 da Feature
  002): mitigação pela regra de FR-030 (envios só no agregado da sala). Risco residual aceito: numa
  sala em que uma única atividade recebe envios, ou com um único estudante, o agregado da sala
  coincide com o daquela atividade ou daquele estudante; quem vê a página (professor com edição e
  gerente) já tem acesso aos envios pelo próprio Moodle.
- **Página percebida como painel administrativo**: mitigação por pouca informação por vez, seções
  recolhidas, linguagem simples e validação visual com aprovação.
- **Variação entre temas**: mitigação por componentes do Moodle e validação em Boost e Classic.

## Assumptions

- O público da página é o mesmo do bloco: professor com edição e gerente (capability existente).
- A página é única por sala e é aberta pelo link do bloco; não há item de menu nem atalho adicional.
- As orientações práticas do nível 2 (Princípio II) chegam com a Feature 004; nesta feature o nível
  2 apresenta situações medidas, sem recomendações.
- O limite inicial da lista de atividades é 10 itens (confirmado pelo proprietário do produto).
- Nomes de arquivos não são exibidos: a Feature 002 não os armazena e a renderização não consulta os
  registros de arquivos; materiais grandes são localizados pela atividade.
- Os tamanhos usam o formato de tamanho do Moodle; proporções usam porcentagens inteiras, com
  "menos de 1%" para valores positivos menores que 1%.
- A frase de identidade e o ativo da Feature 001 são mantidos; a mensagem que anuncia orientações "em
  breve" é removida e substituída por um convite à página (FR-005), conforme decisão do proprietário
  do produto.
- O recrutamento dos cinco professores e a condução da avaliação (FR-071) são organizados pelo
  proprietário do produto; a implementação prepara o roteiro, o ambiente de avaliação com dados
  fictícios e o registro das evidências.
- Ambiente de referência: o mesmo da Feature 002 (Moodle 4.5, PostgreSQL e MariaDB).
