# Feature Specification: Assignment Guidance

**Feature Branch**: `009-assignment-guidance` (criada a partir de `main` em `b38963c`, que integra a 008
pelo PR #12; versão de partida 0.8.0 BETA, Moodle 4.5 LTS e 5.3 LTS)

**Created**: 2026-10-19

**Status**: Clarified (decisões do proprietário do produto de 2026-10-19: Q1 A, Q2 B com condições, Q3 A com condições); arquitetura B2 aprovada em 2026-10-19 (research R4)

**Input**: User description: "Feature 009 — Assignment Guidance: transformar as informações da Feature 005
sobre as configurações de envio das tarefas (mod_assign) em orientações práticas, contextualizadas e
compreensíveis, aproximando o produto do exemplo 'Trabalho Final' do mockup aprovado; reutilizar dados e
cálculos da 005 sem novas consultas; explicar os limites efetivos; implicações sem presumir inadequação;
como localizar e revisar no Moodle 4.5 e 5.3; autonomia pedagógica; nenhum valor universal; nunca
confundir potencial teórico com armazenamento usado; identidade e componentes da 008; distinguir
orientação informativa de recomendação contextualizada; nenhuma regra nova sem critério aprovado."

**Constituição de referência**: 1.2.0

**Categorias de indicador (Princípio IV)**: nenhuma nova. A feature usa o **Potencial teórico** da 005,
sempre rotulado como teto das configurações, nunca como armazenamento usado, previsão ou economia, e
nunca somado ao Armazenamento lógico ou ao Conteúdo único.

**Dados (Princípio VII)**: os mesmos da 005 (configurações da tarefa, da sala e do site; contagem
agregada de quem pode enviar). Nenhum envio, arquivo, nota ou nome de estudante; nenhum volume real por
tarefa (decisão de privacidade da 003, mantida na 005).

## Clarifications

### Session 2026-10-19

- Q: Onde aparece a orientação de cada tarefa? → A: recolhida dentro de cada tarefa da seção "Potencial
  das tarefas", na aba "Em detalhes", reutilizando os componentes existentes; nenhuma página nova por
  tarefa (FR-008).
- Q: Que critério gera uma recomendação contextualizada? → A: um só nesta feature: **tarefa com envio de
  arquivos que efetivamente admite novas tentativas sem limite máximo**, conforme o comportamento real do
  Moodle (envio de arquivos habilitado, método de reabertura, limite efetivo de tentativas, participantes
  elegíveis e demais condições), nunca pelo valor isolado do campo de tentativas. Tentativas ilimitadas
  não são um problema: a sugestão explica e convida a avaliar; não recomenda impor limite, não sugere
  valores, não fala em desperdício, aumento efetivo de armazenamento ou desempenho. O limite por arquivo
  herdado do site continua só informativo (FR-010, FR-010a, FR-011).
- Q: As sugestões de tarefas mudam o bloco? → A: sim; o bloco reflete qualquer sugestão contextualizada
  aplicável (004 ou 009), sem números novos e compacto, sem incoerência com a página; com os dois tipos,
  mensagem genérica. Sem recalcular o potencial de todas as tarefas a cada renderização, sem varrer
  arquivos, sem consultas pesadas; mudança de contrato, persistência ou processamento é apresentada com
  alternativas e custos antes da implementação (FR-013, FR-014).

## Ponto de partida (Feature 005, main em `b38963c`)

Para cada tarefa com envio de arquivos, a 005 já calcula e mostra na aba "Em detalhes", seção "Potencial
das tarefas": arquivos por envio; limite efetivo por arquivo e a sua origem (tarefa, padrão do site, sala,
site ou servidor); tentativas (com o método de reabertura e o caso "até passar"); quem pode enviar
(participantes ou grupos); o potencial teórico ou, sem teto ou sem dados confiáveis, a explicação (estados
`potential`, `unlimited`, `nofiles`, `nobody`, `unknown`). A 005 deixou registradas, sem implementar, as
recomendações futuras e o critério que cada uma exige ("Recomendações futuras" da spec 005).

## Dois tipos de ajuda (decisão do proprietário do produto no pedido)

| Tipo | O que é | Quando aparece |
| --- | --- | --- |
| **Orientação informativa** | Explica a configuração atual, o que ela significa, implicações possíveis e como revisá-la no Moodle; nunca diz que algo está errado | Em toda tarefa que aceita arquivos e tem os fatores conhecidos (estados `potential` e `unlimited`) |
| **Recomendação contextualizada** | Um cartão em "Sugestões para você" (o que observamos, por que pode merecer atenção, o que você pode fazer se quiser, como revisar) | Só quando um critério objetivo, documentado e testável, aprovado pelo proprietário do produto, se aplica (Princípio III) |

Um potencial alto, sozinho, não gera recomendação.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - O professor entende os limites de envio de uma tarefa e sabe onde revisá-los (Priority: P1)

Na tarefa "Relatório de laboratório", o professor lê, em linguagem simples, quantos arquivos cada
participante (ou grupo) pode enviar, de que tamanho, quantas tentativas e de onde vem o limite; entende o
que isso significa (um teto, não o espaço usado), algumas implicações possíveis sem julgamento e encontra
um caminho direto para revisar a configuração no Moodle, se quiser.

**Why this priority**: é a ajuda que vale para toda tarefa com envio de arquivos e não depende de
critério de recomendação; aproxima o produto do exemplo "Trabalho Final" sem prescrever valores.

**Independent Test**: numa sala com tarefas de configurações diferentes (limite próprio, padrão do site,
tentativas ilimitadas, envio em grupo), cada tarefa mostra a sua orientação com os fatores da 005 e o
caminho de revisão, em en e pt_BR, no Moodle 4.5 e 5.3.

**Acceptance Scenarios**:

1. **Given** uma tarefa que aceita até 5 arquivos de até 200 MB em 1 tentativa, **When** o professor abre
   a orientação, **Then** vê a configuração atual (os mesmos números da 005), o significado ("um teto, não
   o espaço usado"), implicações possíveis em tom neutro e os passos para revisar no Moodle, sem nenhum
   valor sugerido.
2. **Given** uma tarefa cujo limite por arquivo vem do padrão do site, **When** o professor lê a
   orientação, **Then** ela diz que o limite não foi escolhido na tarefa e onde ele pode ser definido na
   própria tarefa, sem sugerir valor.
3. **Given** uma tarefa com tentativas ilimitadas, **When** o professor lê a orientação, **Then** ela
   explica que não há teto para o que pode ser guardado ao longo das tentativas e onde revisar as
   tentativas, sem dizer que isso está errado.
4. **Given** o professor tem permissão para editar a tarefa, **When** segue o caminho de revisão, **Then**
   chega às configurações da tarefa no Moodle; **Given** não tem, **Then** os passos aparecem sem o link
   direto.
5. **Given** o Moodle 4.5 ou o 5.3, **When** o professor segue os passos, **Then** os nomes das seções e
   dos campos citados existem na versão em uso.

---

### User Story 2 - O professor recebe uma sugestão contextualizada quando um critério aprovado se aplica (Priority: P2)

Quando uma tarefa atende a um critério aprovado, aparece em "Sugestões para você" um cartão no padrão da
008 — o que observamos, por que pode merecer atenção, o que você pode fazer se quiser e como revisar —
ligado à tarefa real.

**Why this priority**: aproxima a página do cartão "Trabalho Final" do mockup, mas depende de critérios
aprovados; sem eles, a História 1 já entrega valor.

**Independent Test**: com uma tarefa que atende ao critério e outra que não, só a primeira gera o
cartão; nenhuma tarefa gera cartão só por ter potencial alto.

**Acceptance Scenarios**:

1. **Given** uma tarefa que atendia ao critério na última análise, **When** o professor abre "Sugestões
   para você", **Then** vê o cartão da tarefa com as quatro partes, a data da análise, o link para a
   tarefa, os passos para revisar as tentativas e, quando existe, o link para a orientação.
2. **Given** uma tarefa com potencial teórico muito alto que não atende a nenhum critério, **When** o
   professor abre a página, **Then** não há cartão para ela.
3. **Given** várias sugestões (materiais grandes da 004 e tarefas), **When** a aba é aberta, **Then** elas
   aparecem numa ordem fixa e documentada, sem selo "principal" nem ranking.
4. **Given** a tarefa da sugestão passou a ter 3 tentativas depois da análise, **When** o professor abre a
   página, **Then** o cartão continua, com a nota datada de que as configurações mudaram, e o bloco continua
   indicando a sugestão até a próxima análise.
5. **Given** a tarefa da sugestão foi excluída ou ocultada, **When** o professor abre a sala ou a página,
   **Then** a sugestão não aparece em nenhum dos dois.
6. **Given** uma sala sem arquivos registrados e uma tarefa que atende ao critério, **When** o professor
   abre a sala, **Then** o bloco diz "Há uma ideia para a sua sala" e a página mostra as duas abas com o
   cartão.

---

### Edge Cases

- Tarefa sem envio de arquivos, com envio desativado no site, sem arquivos permitidos (`nofiles`), sem
  ninguém que possa enviar (`nobody`) ou com fator desconhecido (`unknown`): sem orientação de revisão de
  limites; a explicação da 005 continua.
- Tarefa oculta ou ainda indisponível: mesma marcação da 005; a orientação aparece para quem pode vê-la;
  a sugestão contextualizada não aparece enquanto a tarefa estiver oculta (FR-010a, camada 2).
- Envio de arquivos desabilitado depois da análise: a tarefa sai da seção da 005; o cartão continua com o
  aviso de divergência e sem o link para a orientação (FR-011a).
- Sala sem arquivos com tarefa que atende ao critério: abas e "Há uma ideia" (FR-013a).
- Sugestões ainda não calculadas depois da atualização: sem cartão de tarefa e sem "Tudo bem por aqui!"
  (FR-013b).
- Envio em grupo: a unidade é o grupo, como na 005; os passos citam a seção de envio em grupo só quando
  ela se aplica.
- Reabertura "até passar" sem nota para aprovação: uma tentativa, como na 005; a orientação explica.
- Limite efetivo vindo da sala, do site ou do servidor (menor que o da tarefa): a orientação diz que o
  limite da tarefa não é o efetivo e que a mudança desses limites não é feita na tarefa.
- Moodle 5.3: nomes de seções e campos do formulário da tarefa conferidos na versão real.
- Muitas tarefas na sala: a página continua legível (orientação recolhida por padrão ou equivalente).

## Requirements *(mandatory)*

### Functional Requirements

**Orientação informativa (História 1)**

- **FR-001**: Para cada tarefa nos estados `potential` e `unlimited` da 005, a página MUST oferecer uma
  orientação com: configuração atual (os fatores e números da 005, sem recálculo diferente), significado
  (teto das configurações, não espaço usado, previsão ou economia), implicações possíveis e como revisar
  no Moodle.
- **FR-002**: As implicações MUST ser apresentadas como possibilidades nos dois sentidos (limites amplos
  podem ser exatamente o que a atividade pede; limites próximos do que a entrega exige podem ajudar a
  indicar o formato esperado) e MUST NOT afirmar que a configuração está inadequada.
- **FR-003**: Quando o limite por arquivo vem do padrão do site, a orientação MUST dizê-lo e indicar onde
  a tarefa pode definir o próprio limite; quando o limite efetivo vem da sala, do site ou do servidor, MUST
  dizer que esse limite não se muda na tarefa.
- **FR-004**: Com tentativas ilimitadas, a orientação MUST explicar que não há teto ao longo das
  tentativas e onde as tentativas são configuradas; com "até passar", MUST explicar a dependência da nota
  para aprovação, como a 005.
- **FR-005**: "Como revisar no Moodle" MUST listar passos com os nomes reais da versão em uso (4.5 ou 5.3)
  das seções e campos do formulário da tarefa envolvidos (envio de arquivos, tamanho máximo, número máximo
  de arquivos, tentativas e reabertura, envio em grupo quando aplicável).
- **FR-006**: Para quem pode editar a tarefa, MUST haver um link direto para as configurações da tarefa no
  Moodle; o plugin MUST NOT alterar nada (Princípio I).
- **FR-007**: A orientação MUST incluir perguntas de reflexão sem valores ("Que tipo de arquivo a atividade
  pede?", "Quantos arquivos uma entrega completa precisa ter?") e MUST NOT sugerir valores (nenhum "10 MB",
  "20 MB", "dois arquivos" ou equivalente).
- **FR-008**: A orientação de cada tarefa MUST aparecer recolhida dentro da própria tarefa na seção
  "Potencial das tarefas" (aba "Em detalhes"), com os componentes existentes; nenhuma página nova por
  tarefa.

**Recomendação contextualizada (História 2)**

- **FR-010**: Um cartão de tarefa em "Sugestões para você" MUST aparecer só quando o critério aprovado se
  aplica: **tentativas efetivamente ilimitadas numa tarefa com envio de arquivos** (FR-010a). Nenhum outro
  critério nesta feature; o limite por arquivo herdado do site é só informativo (FR-003).
- **FR-010a**: A decisão sobre a recomendação MUST ocorrer em **três camadas distintas**:
  1. **Avaliação na análise** (data de referência = data da análise): a análise da 002 avalia, com as
     regras da 005 e o comportamento efetivo do Moodle (o de `assign::reopen_submission_if_required()` no
     4.5 e no 5.3), se a tarefa atende, ao mesmo tempo: envio de arquivos habilitado e aceitando ao menos
     um arquivo; número máximo de tentativas ilimitado; método de reabertura que abre novas tentativas
     (manual ou automática; "até passar" só com nota para aprovação definida); ao menos um participante ou
     grupo que pode enviar. Sem qualquer uma dessas condições **na análise**, a tarefa não entra nas
     sugestões guardadas.
  2. **Filtro imediato** (na exibição, no bloco e na página, iguais): uma sugestão guardada só aparece se
     a atividade ainda existe, ainda é uma tarefa e está visível para os estudantes. Exclusão e ocultação
     valem na hora; visibilidade não se confunde com participantes elegíveis, que só a análise avalia.
  3. **Aviso de divergência** (só na página): quando a configuração atual (calculada ao vivo pela 005) já
     não atende às condições da camada 1 — inclusive envio de arquivos desabilitado ou estados `nofiles`,
     `nobody` e `unknown` —, o cartão **permanece** (coerente com o bloco, que usa a mesma referência) com a
     nota datada "As configurações desta tarefa mudaram depois da análise de {data}; a sugestão será revista
     na próxima análise." A próxima análise retira a sugestão.
- **FR-010b**: Texto da recomendação (direção aprovada): título "Conheça as possibilidades de envio desta
  tarefa"; fato "Esta tarefa permite novas tentativas sem um limite máximo definido."; explicação "Essa
  configuração pode fazer sentido em atividades que permitem revisão e aperfeiçoamento contínuo."; ação
  "Se quiser, confira como as tentativas funcionam e avalie se essa configuração corresponde à proposta da
  atividade." MUST NOT recomendar impor um limite de tentativas, sugerir valores ou afirmar desperdício,
  aumento efetivo de armazenamento ou prejuízo ao desempenho.
- **FR-011**: O cartão MUST seguir o padrão da 008 (o que observamos, por que pode merecer atenção, o que
  você pode fazer se quiser, como revisar) e nomear a tarefa real, com link; MUST NOT trazer selo
  "principal", ranking, comparação entre tarefas ou professores, nem valores sugeridos.
- **FR-011a**: O cartão MUST ser autossuficiente, sem depender da orientação informativa existir: sempre
  traz o link para a tarefa, os passos curtos para revisar as tentativas (com os nomes do Moodle:
  "Submission settings" › "Allowed attempts" e "Grant attempts") e, para quem pode editar, o link para as
  configurações; o link "ver a orientação desta tarefa" (na aba "Em detalhes") só aparece quando essa
  orientação existe agora (tarefa listada na seção da 005 nos estados `potential` ou `unlimited`). Nos
  demais casos (envio de arquivos desabilitado, `nofiles`, `nobody`, `unknown`) o cartão mostra o aviso
  de divergência (FR-010a, camada 3) e mantém as outras ações.
- **FR-012**: Com mais de uma sugestão, a ordem MUST ser fixa e documentada (materiais grandes da 004
  primeiro, depois as tarefas na ordem da sala); isso não é priorização.
- **FR-013**: O estado educativo do bloco e a faixa da página (008) MUST refletir qualquer sugestão
  contextualizada aplicável (004 ou 009), sem incoerência entre bloco e página: sem sugestão, o estado da
  008 conforme a validade do diagnóstico; só materiais grandes, a mensagem da 008; só tarefas, uma
  mensagem sobre as tarefas; os dois tipos, uma mensagem genérica sobre sugestões para a sala. Nenhum
  número novo no bloco; bloco compacto.
- **FR-013a**: Uma sugestão de tarefa MUST ser refletida também numa sala **sem arquivos registrados**
  (uma tarefa nova pode atender ao critério antes de qualquer envio): no bloco, "Há uma ideia para a sua
  sala" com a mensagem sobre as tarefas, mantendo a frase "nenhum arquivo foi encontrado" no lugar do
  volume; na página, as abas aparecem quando há resultado **e** (arquivos registrados **ou** sugestão
  aplicável), com "Sugestões para você" (faixa e cartão) e "Em detalhes" (o conteúdo atual de uma sala sem
  arquivos). Isso altera, só para esse caso, a regra da 008 de abas apenas com arquivos (FR-034 da 008).
  "Tudo bem por aqui!" continua exigindo arquivos registrados (008).
- **FR-013b**: Enquanto as sugestões de tarefas da sala ainda não foram calculadas (registro anterior à
  atualização, até a próxima análise), o bloco e a página MUST NOT mostrar "Tudo bem por aqui!"; não
  calculado nunca equivale a "nenhuma sugestão".
- **FR-014**: A renderização do bloco MUST NOT recalcular o potencial de todas as tarefas, varrer
  arquivos ou fazer consultas pesadas (Princípio VI); bloco e página MUST usar a mesma fonte para decidir
  se a sugestão de tarefa se aplica. A escolha da arquitetura (persistência ou cálculo) é apresentada com
  alternativas e custos ao proprietário do produto antes da implementação. O custo na análise MUST ficar
  restrito às tarefas candidatas (tentativas ilimitadas), sem rodar o coletor da 005 para todas as tarefas
  da sala.

**Preservação, idiomas e qualidade**

- **FR-020**: A 009 MUST reutilizar os fatores e cálculos da 005, sem consultas novas para dados que a 005
  já fornece, e MUST NOT alterar a metodologia do potencial teórico.
- **FR-021**: Strings completas em en e pt_BR, classificadas por tipo de texto (008); nenhuma promessa de
  economia, desempenho ou sustentabilidade; nenhum julgamento sobre a prática pedagógica.
- **FR-022**: Acessibilidade e responsividade como na 008 (WCAG 2.2 AA; teclado; leitor de tela; 320 px;
  200%; 4.5 Boost e Classic, 5.3 Boost; en e pt_BR).
- **FR-023**: Todo o conteúdo das Features 002–008 MUST continuar presente, com os mesmos valores.

### Key Entities

- **Orientação de tarefa**: derivada dos fatores da 005 para uma tarefa (configuração atual, significado,
  implicações, passos, link), sem dado guardado.
- **Critério de recomendação de tarefa**: regra objetiva sobre os fatores da 005, aprovada e testável.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% das tarefas com envio de arquivos e fatores conhecidos mostram a orientação com os
  mesmos números da 005, em en e pt_BR.
- **SC-002**: Zero valores sugeridos e zero frases que classifiquem uma configuração como inadequada
  (revisão de todas as strings novas por tipo de texto).
- **SC-003**: Os passos de revisão citam só seções e campos que existem no formulário da tarefa da versão
  em uso, conferidos no Moodle 4.5 e no 5.3.
- **SC-004**: Nenhuma tarefa gera recomendação contextualizada sem atender a um critério aprovado
  (testes com tarefas de potencial alto que não atendem a nenhum critério).
- **SC-005**: Zero violações automáticas de acessibilidade do plugin nas três combinações; mesmas leituras
  do banco na página e tempo dentro de 10% da 0.8.0.
- **SC-006**: Capturas reais aprovadas pelo proprietário do produto.

## Assumptions

- Só mod_assign; outras atividades com envio de arquivos (fórum, questionário etc.) ficam fora.
- Os dados vêm da 005; o link para as configurações usa só o identificador da atividade, já conhecido.
- A seção "Potencial das tarefas" continua na aba "Em detalhes" (008).
- Versão alvo 0.9.0 BETA; Moodle 4.5 e 5.3.
- Validações humanas e bloqueios de publicação abertos (leitor de tela, avaliação com professores,
  titularidade, diretrizes autenticadas do Marketplace) continuam abertos; esta feature não os fecha.

## Fora do escopo

Alteração automática de tarefas, novos indicadores de armazenamento, limites ideais ou valores sugeridos,
comparação entre professores ou tarefas, julgamentos sobre práticas pedagógicas, regras sem critério
aprovado, outras atividades além de mod_assign, volume real de envios por tarefa.
