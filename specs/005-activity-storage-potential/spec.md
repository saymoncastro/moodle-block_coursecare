# Feature Specification: Activity Storage Potential

**Feature Branch**: `005-activity-storage-potential` (criada a partir de `004-recommendations`; cadeia
`main` → `003-teacher-experience` → `004-recommendations` → `005-activity-storage-potential`; a 005 só é
integrada depois da 004)

**Created**: 2026-10-18

**Status**: Draft (para revisão do proprietário do produto)

**Input**: User description: "Feature 005 — Activity Storage Potential: permitir que o professor
compreenda o potencial teórico de armazenamento das atividades Moodle que recebem arquivos, começando
por mod_assign; explicar as implicações das configurações atuais, não prever o uso. Distinguir o
armazenamento registrado, o potencial máximo teórico configurado e as limitações da estimativa; nunca
apresentar o potencial como consumo físico, crescimento provável, volume que será enviado, economia ou
desperdício; explicar a limitação em vez de inventar um número. Identidade visual aprovada; integração
com as sugestões da 004 só com critérios objetivos aprovados; privacidade, desempenho e validação."

**Constituição de referência**: 1.2.0

**Categorias de indicador (Princípio IV)**: a feature introduz o **Potencial teórico** ("projeção de
armazenamento futuro a partir de configurações, ex.: limites de envio"). Ele MUST ser rotulado como
tal, MUST NOT ser apresentado como consumo realizado e MUST NOT ser somado ao **Armazenamento lógico**
ou ao **Conteúdo único**. A feature não calcula Consumo físico atribuível nem Economia observada.

**Dados (Princípio VII)**: só configurações das tarefas, da sala e do site, e a contagem agregada de
quem pode enviar (participantes ou grupos). Nenhum conteúdo de arquivo, nenhum envio individual, nenhum
nome de estudante. Nenhuma configuração ou arquivo é alterado.

## Dependências e estado das features anteriores

- **Feature 002** (diagnóstico): integrada. Fornece o armazenamento registrado da sala e por categoria,
  e o volume dos materiais por atividade.
- **Feature 003** (experiência do professor): funcional, não integrada; validações humanas pendentes
  (T047, T048 leitor de tela, T050–T052, T055, T057).
- **Feature 004** (recomendações): funcional, não integrada; tom (T027) e visual (T028) aprovados em
  2026-10-18; pendentes o teste com leitor de tela, a avaliação com cinco professores (T031) e o
  Constitution Check (T032).
- As validações humanas pendentes das features 003 e 004 continuam obrigatórias para o encerramento
  delas e não bloqueiam esta especificação (decisão do proprietário do produto, 2026-10-18).

## Como o Moodle 4.5 limita os envios de arquivos numa tarefa

Investigação no código do Moodle 4.5.15 (`mod/assign`, plugin de envio `assignsubmission_file`). São
estes os fatos que a feature MUST respeitar:

| Configuração | Onde | Efeito |
| --- | --- | --- |
| Envio de arquivos ativado | Tarefa (plugin de envio "Arquivos") | Sem ele, a tarefa não recebe arquivos pelo envio de arquivos |
| Número máximo de arquivos enviados | Tarefa (1 até o máximo do site, padrão do site 20) | Arquivos por envio |
| Tamanho máximo do envio | Tarefa; 0 = usar o padrão do plugin no site | Vale **por arquivo**, não pelo total do envio |
| Limites superiores | Site (tamanho máximo de upload), sala (tamanho máximo de upload) e servidor (limites de upload e de envio do PHP) | O limite efetivo por arquivo é o menor entre a tarefa (ou o padrão) e os limites maiores que zero da sala e do site e os do servidor |
| Ignorar limites de tamanho | Capability de quem envia | Usuários com ela não têm limite de tamanho; normalmente não estudantes |
| Envio em grupo | Tarefa | Um envio por grupo (opcionalmente só os grupos de um agrupamento); estudantes sem grupo enviam num grupo padrão, a menos que isso esteja impedido |
| Tentativas | Tarefa: reabertura (nenhuma, manual, automática, até passar) e número máximo de tentativas (−1 = ilimitado) | Cada nova tentativa copia os arquivos da anterior e pode recebê-los até o limite de novo; as tentativas anteriores continuam guardadas |
| Quem pode enviar | Capability de envio na tarefa, inscrição ativa, grupos | Define quantos participantes (ou grupos) enviam |
| Datas e restrições de acesso | Tarefa | Podem impedir envios por um período; não mudam o limite configurado |

## Definições

- **Armazenamento registrado**: o que o diagnóstico da Feature 002 mediu na última análise
  (Armazenamento lógico).
- **Potencial teórico configurado de uma tarefa**: o maior volume que as configurações atuais da tarefa
  permitiriam guardar em envios de arquivos, se cada participante (ou grupo) usasse todo o limite em
  todas as tentativas permitidas. É um teto teórico das configurações, não uma previsão.
- **Unidade de envio**: quem envia na tarefa: cada participante que pode enviar; ou, no envio em grupo,
  cada grupo (mais o grupo padrão, quando estudantes sem grupo podem enviar).
- **Limite efetivo por arquivo**: o menor dos limites aplicáveis da tabela acima.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Entender o que as configurações de uma tarefa permitem (Priority: P1)

Na página "Sua sala em números", o professor vê, para cada tarefa da sala que recebe arquivos, o que
as configurações permitem: quantos arquivos por envio, de que tamanho cada um, por quantas tentativas,
para quantos participantes ou grupos, e o potencial teórico resultante, com frases simples ("Esta tarefa
permite o envio de até 3 arquivos por participante, de até 10 MB cada.").

**Why this priority**: é o objetivo da feature: tornar visíveis as implicações das configurações.

**Independent Test**: numa sala com tarefas de configurações diferentes, conferir a frase e o potencial
de cada uma contra a regra de cálculo (FR-010 a FR-016).

**Acceptance Scenarios**:

1. **Given** uma tarefa com envio de arquivos, até 3 arquivos, 10 MB por arquivo, 1 tentativa e 30
   participantes que podem enviar, **When** o professor abre a página, **Then** vê "até 3 arquivos por
   participante, de até 10 MB cada" e o potencial teórico de 900 MB (30 × 3 × 10 MB), rotulado como
   potencial teórico configurado.
2. **Given** uma tarefa cujo tamanho por arquivo é maior que o limite da sala, **When** o professor abre
   a página, **Then** o cálculo usa o limite da sala e a explicação diz qual limite vale.
3. **Given** uma tarefa com envio em grupo para 6 grupos, **When** o professor abre a página, **Then** a
   unidade é o grupo ("até 3 arquivos por grupo") e o potencial usa 6 grupos.
4. **Given** uma tarefa com tentativas ilimitadas, **When** o professor abre a página, **Then** a página
   diz que não há teto definido, mostra o potencial por tentativa e explica por quê.
5. **Given** uma tarefa sem envio de arquivos ativado, **When** o professor abre a página, **Then** ela
   não aparece na lista de potencial.

---

### User Story 2 - Distinguir potencial, armazenamento registrado e limitações (Priority: P1)

O professor entende que o potencial é um teto teórico das configurações, separado do que já está
registrado na sala, e conhece as limitações da estimativa.

**Why this priority**: sem essa distinção, um número grande vira susto, cobrança ou previsão errada
(Princípios I e IV).

**Independent Test**: inspecionar os textos: rótulo "potencial teórico", frase do que ele não
significa, armazenamento registrado nunca somado ao potencial, limitações acessíveis.

**Acceptance Scenarios**:

1. **Given** a seção de potencial, **When** o professor a lê, **Then** encontra, sem abrir detalhes
   técnicos, uma frase que diz que o potencial não é o espaço usado, nem uma previsão, nem um volume que
   será enviado, e que configurações diferentes atendem objetivos pedagógicos diferentes.
2. **Given** qualquer tarefa, **When** o professor compara números, **Then** o potencial nunca aparece
   somado ao armazenamento registrado, e os dois têm rótulos diferentes.
3. **Given** os detalhes técnicos da seção, **When** o professor os abre, **Then** encontra as
   limitações: datas e restrições de acesso não consideradas, usuários que ignoram limites, arquivos
   inseridos no texto online não contados, limites do servidor, e a regra das tentativas.

---

### User Story 3 - Saber quando o potencial não pode ser calculado (Priority: P2)

Quando os dados não permitem um número confiável, a página explica a limitação em vez de mostrar um
valor.

**Why this priority**: honestidade dos indicadores (Princípio IV).

**Independent Test**: tarefas com configurações incompletas, tentativas ilimitadas, nenhum participante
e envio em grupo sem grupos; conferir a frase de cada caso.

**Acceptance Scenarios**:

1. **Given** uma tarefa sem ninguém que possa enviar no momento, **When** o professor abre a página,
   **Then** vê "Ninguém pode enviar nesta tarefa no momento" em vez de um potencial de zero.
2. **Given** uma tarefa cuja configuração de envio não está registrada (por exemplo, criada por
   importação incompleta), **When** o professor abre a página, **Then** vê que o potencial não pode ser
   calculado e por quê.
3. **Given** uma sala sem tarefas que recebem arquivos, **When** o professor abre a página, **Then** a
   seção de potencial diz isso numa frase neutra.

---

### Edge Cases

- **Tentativas**: reabertura "nenhuma" ou máximo 1 → 1 tentativa; máximo N → N; ilimitado → sem teto
  (FR-013).
- **Envio em grupo com agrupamento**: só os grupos do agrupamento contam.
- **Envio em grupo sem grupos**: todos enviam no grupo padrão → 1 unidade, a menos que envios fora de
  grupo estejam impedidos (então 0 unidades: ninguém pode enviar).
- **Estudante em vários grupos** (envio em grupo): conta como membro de um dos grupos; a unidade é o
  grupo, não muda o número de grupos.
- **Tarefa oculta**: listada para o professor, com a indicação de que estudantes não podem enviar
  enquanto ela estiver oculta; o potencial descreve a configuração.
- **Limite por arquivo zero ou ausente** em todos os níveis: usa o limite do servidor; se nem ele for
  conhecido, o potencial não é calculado (FR-015).
- **Valores muito grandes**: o número é mostrado com a unidade de tamanho adequada (até TB), sem
  arredondar para "infinito".
- **Atividades de outros tipos que recebem arquivos** (fórum, banco de dados, oficina): fora desta
  entrega (FR-002).

## Requirements *(mandatory)*

### Functional Requirements

**Escopo**

- **FR-001**: A feature MUST calcular e apresentar o potencial teórico configurado de cada tarefa
  (`mod_assign`) da sala que tem o envio de arquivos ativado.
- **FR-002**: Outros tipos de atividade MUST NOT ser considerados nesta entrega; a página MUST dizer
  que o potencial cobre só tarefas.
- **FR-003**: A feature MUST NOT alterar configurações de tarefas, envios ou arquivos, nem oferecer
  ação que altere algo; links levam às páginas nativas do Moodle.

**Regra de cálculo**

- **FR-010**: Potencial teórico da tarefa = unidades de envio × tentativas × arquivos por envio ×
  limite efetivo por arquivo, quando todos os fatores forem conhecidos e finitos.
- **FR-011**: Arquivos por envio = o número máximo configurado na tarefa.
- **FR-012**: Limite efetivo por arquivo = o menor entre: o tamanho máximo da tarefa (ou, se zero, o
  padrão do plugin no site), o limite da sala e o do site quando maiores que zero, e os limites de upload
  do servidor. A explicação MUST dizer qual limite determinou o valor (tarefa, sala, site ou servidor).
- **FR-013**: Tentativas = 1 quando a reabertura é "nenhuma" ou o máximo é 1; o máximo configurado
  quando finito; quando ilimitado, a tarefa MUST mostrar "sem teto definido", o potencial por tentativa
  e a explicação.
- **FR-014**: Unidades de envio = participantes com inscrição ativa que podem enviar na tarefa; no
  envio em grupo, os grupos (do agrupamento, se definido) mais o grupo padrão quando estudantes sem
  grupo podem enviar.
- **FR-015**: Quando algum fator não puder ser determinado com confiança, o potencial MUST NOT ser
  mostrado; a tarefa mostra a razão em linguagem simples. Zero participantes MUST aparecer como frase
  ("Ninguém pode enviar nesta tarefa no momento"), não como "0 bytes".
- **FR-016**: Os cálculos MUST ser verificados por testes com valores conhecidos para cada combinação
  de FR-010 a FR-015.

**Apresentação**

- **FR-020**: A página "Sua sala em números" MUST ganhar uma seção "Potencial das tarefas", depois de
  "Materiais por atividade" e antes de "Detalhes técnicos", com uma linha por tarefa: nome atual com
  link, a frase das configurações ("até N arquivos por participante/grupo, de até X cada", tentativas)
  e o potencial teórico ou a razão de não haver número.
- **FR-021**: A seção MUST explicar, sem abrir detalhes, o que o potencial significa e o que não
  significa (não é espaço usado, previsão, volume que será enviado, economia ou desperdício; diferentes
  configurações atendem objetivos pedagógicos diferentes).
- **FR-022**: As limitações reconhecidas (seção "Limitações reconhecidas") MUST estar nos detalhes
  técnicos da página, com linguagem simples.
- **FR-023**: O bloco MUST NOT mostrar o potencial (mantém um indicador, Princípio II).
- **FR-024**: A apresentação MUST seguir a direção visual aprovada (Features 003 e 004), sem cores ou
  ícones de alerta, sem ranking de tarefas por potencial como julgamento; a ordem MUST ser a da sala
  (ordem das atividades na página do curso).
- **FR-025**: O armazenamento registrado MUST aparecer [NEEDS CLARIFICATION: Q1 — por tarefa (volume de
  envios já registrados na tarefa) ou só no agregado da sala, como decidido na Feature 003 (FR-030)?]
- **FR-026**: A seção MUST [NEEDS CLARIFICATION: Q2 — mostrar ou não um total do potencial da sala
  (soma das tarefas com potencial finito)?]

**Integração com as sugestões (Feature 004)**

- **FR-030**: A estrutura das sugestões da 004 (o que observamos; por que pode merecer atenção; o que
  você pode fazer, se quiser) MUST ser a usada por qualquer orientação baseada no potencial. Nesta
  entrega: [NEEDS CLARIFICATION: Q3 — nenhuma sugestão nova (só a seção informativa), ou uma sugestão
  com critério objetivo a aprovar?]
- **FR-031**: Nenhuma orientação MUST apresentar um limite de arquivos ou de tamanho como adequado para
  todas as tarefas.

**Privacidade, segurança e dados**

- **FR-040**: Só configurações e contagens agregadas (participantes ou grupos que podem enviar);
  nenhum envio individual, nome de estudante, nome de arquivo ou conteúdo de arquivo.
- **FR-041**: A página continua visível só a quem tem a capability de visualização do bloco, com as
  verificações da Feature 003; nenhuma capability nova.
- **FR-042**: A renderização MUST NOT consultar os registros de arquivos nem fazer cálculo pesado; o que
  for caro (contagem de participantes por tarefa em salas grandes) MUST ser feito fora da renderização
  ou de forma limitada e medida, com a decisão registrada no plano.

**Idioma, acessibilidade e validação**

- **FR-050**: Frases completas no catálogo, en e pt_br em paridade, singular e plural corretos.
- **FR-051**: WCAG 2.2 AA como nas features 003 e 004 (títulos, teclado, 320 px, texto para todo número).
- **FR-052**: Revisão de tom com aprovação do proprietário do produto; validação visual em Moodle real
  (en e pt_BR, desktop e celular, Boost e Classic) com aprovação.

### Key Entities

- **Potencial de tarefa**: tarefa (nome atual, link), envio de arquivos ativado, arquivos por envio,
  limite efetivo por arquivo e sua origem, tentativas (número ou ilimitado), envio em grupo, unidades de
  envio, potencial teórico (ou razão de não haver), data do cálculo.
- **Seção de potencial da sala**: a lista de potenciais das tarefas da sala, as frases de significado e
  as limitações.

## Cenários de teste

| ID | Cenário | Resultado esperado |
| --- | --- | --- |
| CT-01 | Tarefa padrão (3 arquivos, 10 MB, 1 tentativa, 30 participantes) | 900 MB; frase das configurações |
| CT-02 | Tamanho da tarefa zero (padrão do site) | Usa o padrão do plugin, limitado por sala, site e servidor |
| CT-03 | Limite da sala menor que o da tarefa | Usa o da sala e diz isso |
| CT-04 | Limite do servidor menor que todos | Usa o do servidor e diz isso |
| CT-05 | Tentativas: nenhuma, máximo 3, ilimitado | 1, 3, sem teto (potencial por tentativa) |
| CT-06 | Envio em grupo: 6 grupos; com agrupamento; sem grupos; sem grupos e envio fora de grupo impedido | 6, só os do agrupamento, 1 (grupo padrão), ninguém pode enviar |
| CT-07 | Participantes: suspensos, sem capability de envio, professor | Não contam |
| CT-08 | Envio de arquivos desativado; só texto online | Tarefa fora da lista |
| CT-09 | Configuração incompleta | Sem número, com a razão |
| CT-10 | Sala sem tarefas com arquivos | Frase neutra |
| CT-11 | Inspeção de privacidade | Só contagens agregadas; nenhum nome de estudante ou de arquivo |
| CT-12 | Renderização | Nenhuma consulta aos registros de arquivos; custo medido |
| CT-13 | Textos en e pt_br | Nenhuma previsão, cobrança, economia ou julgamento |
| CT-14 | Acessibilidade e visual | axe sem violações; capturas aprovadas |

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Em 100% dos casos de CT-01 a CT-10, o potencial (ou a razão de não haver número) é igual
  ao calculado manualmente pela regra de FR-010 a FR-015.
- **SC-002**: Nenhum texto da seção apresenta o potencial como consumo, previsão, volume que será
  enviado, economia ou desperdício (revisão de tom aprovada).
- **SC-003**: A página de uma sala com 200 atividades, das quais 50 tarefas com arquivos e 500
  participantes, abre em até 2 segundos em condições normais de operação no ambiente de referência; o
  bloco não acrescenta tempo em relação à 0.4.0.
- **SC-004**: A inspeção automatizada não encontra nome de estudante, nome de arquivo nem dado
  individual de envio na seção.
- **SC-005**: Verificação automática de acessibilidade sem violações; seção alcançável e compreensível
  só com teclado.
- **SC-006**: Capturas aprovadas pelo proprietário do produto (en e pt_BR, desktop e celular, Boost e
  Classic).
- **SC-007**: Quality gates da constituição verdes no CI nas bases suportadas.

## Limitações reconhecidas

- **Datas e restrições de acesso** não são consideradas: o potencial descreve a configuração, não o
  período em que envios são possíveis.
- **Usuários que ignoram limites de tamanho** (capability) não têm teto; normalmente não são
  estudantes.
- **Arquivos inseridos no texto online** e **arquivos de feedback** não entram no potencial.
- **Limites do servidor**: dependem da configuração do servidor web; o valor usado é o conhecido no
  momento do cálculo.
- **Participantes e grupos mudam**: o potencial vale para o momento do cálculo.
- **Tentativas copiam arquivos**: no Moodle, cada nova tentativa recebe uma cópia dos arquivos da
  anterior; o armazenamento lógico conta as cópias, e o físico pode não aumentar (conteúdo idêntico é
  guardado uma vez).

## Fora do escopo e features posteriores

- Outros tipos de atividade (fórum, banco de dados, oficina, H5P, etc.).
- Previsão de uso, crescimento ou histórico (Feature 006).
- Recomendações automáticas sem critério aprovado; limites "ideais".
- Alteração de configurações ou ações de um clique.
- Painel do administrador ou comparação entre salas.

## Riscos

- **Número grande lido como problema**: frase de significado sempre visível, rótulo "potencial
  teórico", revisão de tom.
- **Custo de contar participantes em salas grandes**: decisão de onde calcular no plano, com medição.
- **Regras de grupo complexas**: casos de teste dedicados (CT-06).

## Assumptions

- O público é o mesmo da Feature 003 (professor com edição e gerente).
- A lista segue a ordem das atividades na sala.
- A seção é parte da página existente; não há página nova.
