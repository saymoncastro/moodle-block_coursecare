# Feature Specification: Recomendações

**Feature Branch**: `004-recommendations` (criada a partir de `003-teacher-experience`, ainda não
integrada à `main`; a integração da 004 só acontece depois da 003)

**Created**: 2026-10-18

**Status**: Clarified (decisões do proprietário do produto de 2026-10-18; pronta para `/speckit-plan`)

**Input**: User description: "Feature 004 — Recomendações do CourseCare / AVA Consciente: transformar
o diagnóstico de arquivos produzido pela Feature 002 e apresentado pela 003 em orientações
educativas, contextualizadas e acionáveis para o professor. Menor entrega funcional; reaproveitar
dados e interface; cada orientação explica o que foi observado, por que pode merecer atenção e o
que o professor pode fazer; só quando os dados a sustentarem; tamanho não determina qualidade,
desperdício nem necessidade de exclusão; sem promessas de economia ou benefícios ambientais não
medidos; privacidade; visual aprovado; tom educativo em português e inglês; sem LLM, serviços
externos, novas análises, histórico, rankings, exclusão automática ou configurações
administrativas."

**Constituição de referência**: 1.2.0

**Categorias de indicador (Princípio IV)**: as sugestões citam só o **Armazenamento lógico** de
materiais de grande porte, como a Feature 003 já exibe. A feature MUST NOT calcular nem exibir
Consumo físico atribuível, Potencial teórico ou Economia observada.

**Dados (Princípio VII)**: só o resultado já calculado pela Feature 002 e a estrutura da sala mantida
pelo Moodle, os mesmos dados que a Feature 003 lê. Nenhuma análise nova, nenhum dado novo
persistido, nenhuma leitura dos registros de arquivos.

## Dependências e estado das features anteriores

- **Feature 002** (diagnóstico): concluída e integrada. Fornece, entre outros, as ligações aos
  materiais de grande porte por atividade (só materiais da sala, no máximo 1.000 por sala) e o
  limite de grande porte usado em cada resultado.
- **Feature 003** (experiência do professor): funcional e publicada na branch
  `003-teacher-experience` (CI verde em `c053e3d`), ainda não integrada. A 004 reaproveita o bloco,
  a página "Sua sala em números" e a direção visual aprovada. **Validações humanas pendentes da 003**,
  registradas aqui sem serem dadas como concluídas e sem bloquear esta especificação:
  - T047: aprovação da revisão de tom e rótulos;
  - T048: teste com leitor de tela e aprovação das capturas finais;
  - T050 e T051: avaliação com cinco professores e correção dos problemas críticos;
  - T052: execução manual do quickstart;
  - T055: reavaliação do Constitution Check;
  - T057: confirmação da formatação de tamanhos (separador decimal e KB/MB).

  Qualquer mudança que essas validações exijam na página ou no bloco vale também para a 004.

## Escopo aprovado e sugestões novas

O que já estava aprovado para a 004 (roadmap de 2026-10-17 e "Fora do escopo" das specs 002 e 003):
estados educativos do bloco; recomendações contextualizadas; boas práticas; orientações práticas;
explicações sobre oportunidades identificadas. O nível 2 da divulgação progressiva (Princípio II,
"situações concretas e ações práticas") recebe aqui suas primeiras orientações.

Como esta especificação atende a cada item, sem ampliar o escopo:

| Item aprovado | Nesta entrega |
| --- | --- |
| Estados educativos do bloco | Uma frase no bloco quando há sugestão (FR-010) |
| Recomendações contextualizadas | Sugestões calculadas a partir dos dados da sala, com os três elementos do Princípio III |
| Orientações práticas | A parte "o que você pode fazer" de cada sugestão, com link para a página nativa do Moodle |
| Boas práticas | Dentro da sugestão, ligadas ao que foi observado; não há lista genérica, porque as orientações só aparecem quando os dados as sustentam |
| Explicações sobre oportunidades identificadas | A parte "por que pode merecer atenção" de cada sugestão |

**Sugestões novas desta especificação** (não estavam no roadmap; marcadas para decisão):

- **S1** — materiais de grande porte, com boas práticas vinculadas (a única orientação desta
  entrega; aprovada em 2026-10-18).
- Seção "Sugestões para a sua sala" na página, logo depois da abertura (FR-001).
- S2 (materiais de grande porte em atividades ocultas para os estudantes) foi proposta e ficou para
  depois (ver "Orientações deixadas para depois").

**Conflito com a constituição e como foi resolvido**: o Princípio III exige que limiares e critérios
de recomendação sejam configuráveis pela administração do site. O pedido desta feature exclui novas
configurações administrativas. Para cumprir as duas coisas, a primeira entrega só usa condições que
não precisam de um novo limiar: a existência de materiais acima do **limite de grande porte já
configurável** pela administração desde a Feature 002. Toda orientação que exigiria um limiar novo
(por exemplo, "envios acima de X% da sala") fica fora e está registrada em "Orientações deixadas
para depois".

## Clarifications

### Session 2026-10-18

- Q: A primeira entrega inclui S2 (atividade oculta para os estudantes com material de grande porte)?
  → A: Não. Só S1. S2 fica para depois: uma atividade oculta pode estar em preparação, e esse sinal
  não demonstra necessidade de intervenção.
- Q: As boas práticas da 004 entram nesta entrega? → A: Sim, vinculadas à S1, como um texto curto na
  própria sugestão. Elas vão além de repetir o diagnóstico e oferecem caminhos concretos quando
  pertinentes (por exemplo, otimizar o arquivo preservando legibilidade e acessibilidade), sem
  afirmar que o material precisa de correção nem que as alternativas necessariamente se aplicam. O
  fato observado fica separado das possibilidades que o professor pode avaliar. Sem biblioteca nova,
  outro mecanismo de recomendações ou configurações.
- Q: Como validar as sugestões com professores? → A: Na mesma avaliação com cinco professores da
  Feature 003, acrescentando perguntas sobre compreensão e utilidade da sugestão. A avaliação fica
  registrada como pendente e não bloqueia a implementação nem o teste local da 004.
- O reaproveitamento do limite de grande porte existente e a exclusão das orientações sem dados
  suficientes foram confirmados.

### Session 2026-10-18 (validação)

- Q: Como avaliar o limite de 2 segundos da página em SC-005? → A: Nesta entrega, pelas aberturas em
  condições normais de operação. A primeira abertura logo após a limpeza integral dos caches fica
  documentada como limitação; ela não é apresentada como aprovada, e a causa exata não é declarada
  como comprovada. Nenhuma otimização sem evidência de gargalo em condições normais. (Decisão do
  proprietário do produto, 2026-10-18; a investigação não demonstrou regressão relevante da 004.)
- A revisão de tom das 19 strings foi aprovada (T027); ajustes editoriais pequenos continuam
  permitidos, desde que preservem os princípios aprovados.
- A direção visual foi aprovada (T028), com um refinamento pontual do cartão de sugestão para
  melhorar a leitura e a hierarquia (números, atividades identificadas e alternativas), sem
  funcionalidade nova.

## Definições

- **Sugestão**: orientação opcional mostrada ao professor, com três partes: o que foi observado na
  sala; por que pode merecer atenção; o que o professor pode fazer, se quiser. Ignorá-la não tem
  consequência.
- **Fato observado**: o que o diagnóstico mediu na sala (quantidade, volume, atividades). É dito como
  fato, com a data da análise.
- **Possibilidades**: caminhos que o professor pode avaliar (boas práticas). São ditos como
  possibilidades, nunca como correção necessária, e podem não se aplicar ao material.
- **Condição de exibição**: regra objetiva e testável, aplicada ao resultado vigente, que decide se
  a sugestão aparece.
- **Resultado vigente**, **nível da sala**, **materiais de grande porte**: como na Feature 003.
- **Material de grande porte**: arquivo de material da sala com tamanho igual ou maior que o limite
  de grande porte registrado no resultado vigente.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Entender uma sugestão sobre os materiais grandes da sala (Priority: P1)

Na página "Sua sala em números", o professor encontra uma seção de sugestões. Quando a sala tem
materiais de grande porte, há uma sugestão que diz quais atividades os têm e quanto somam, explica
por que isso pode merecer atenção sem tratar o tamanho como problema e indica o que ele pode fazer,
se quiser: algumas possibilidades concretas (boas práticas), apresentadas como opções a avaliar, e um
link para a atividade.

**Why this priority**: é a primeira orientação prática do produto e a razão de existir da feature.

**Independent Test**: numa sala com materiais acima do limite, abrir a página e conferir a sugestão
(três partes, atividades citadas, números iguais à soma da lista "Materiais de grande porte por
atividade", link para a atividade). Numa sala sem materiais acima do limite, conferir que a sugestão não aparece.

**Acceptance Scenarios**:

1. **Given** uma sala cujo resultado vigente tem materiais de grande porte em duas atividades,
   **When** o professor abre a página, **Then** a seção de sugestões mostra a sugestão S1, que cita
   as duas atividades com nome e link, a quantidade e o volume dos materiais de grande porte e o
   limite usado, com as três partes identificadas.
2. **Given** a mesma sala, **When** o professor lê a sugestão, **Then** ela diz explicitamente que
   o tamanho, sozinho, não indica problema nem qualidade do material, e nenhuma frase fala em
   excluir, economizar espaço ou benefício ambiental.
3. **Given** a mesma sala, **When** o professor lê "O que você pode fazer, se quiser", **Then**
   encontra possibilidades concretas (por exemplo, uma versão otimizada que preserve legibilidade e
   acessibilidade, ou um link para onde o conteúdo já está publicado), apresentadas como opções que
   podem não se aplicar, separadas do fato observado.
4. **Given** uma sala com materiais de grande porte em mais de três atividades, **When** o professor
   abre a página, **Then** a sugestão cita as três de maior volume e informa quantos outros lugares
   existem, remetendo à seção "Arquivos de grande porte".
5. **Given** uma sala cujos materiais de grande porte estão numa atividade que o professor não pode
   ver ou que foi removida depois da análise, **When** ele abre a página, **Then** essa atividade
   aparece só agregada, sem nome, como na Feature 003.
6. **Given** uma sala com envios de estudantes acima do limite e nenhum material acima do limite,
   **When** o professor abre a página, **Then** a sugestão S1 não aparece.

---

### User Story 2 - Saber pelo bloco que há uma sugestão (Priority: P2)

No bloco da página principal da sala, o professor vê, quando houver sugestão, uma frase curta que a
anuncia, sem número, selo ou cor de alerta. O link do bloco leva à página, na seção de sugestões.

**Why this priority**: torna a sugestão encontrável sem abrir a página por acaso, mantendo o bloco
com uma mensagem essencial e no máximo um indicador (Princípio II).

**Independent Test**: conferir o bloco numa sala com sugestão (frase presente, link leva à seção) e
numa sala sem sugestão (bloco igual ao da Feature 003).

**Acceptance Scenarios**:

1. **Given** uma sala com a sugestão S1, **When** o professor abre a sala, **Then** o bloco mostra o
   volume, a data, a frase "Há uma sugestão sobre os arquivos da sua sala" e o link, que abre a
   página na seção de sugestões.
2. **Given** uma sala sem sugestão aplicável, **When** o professor abre a sala, **Then** o bloco é
   igual ao da Feature 003, sem frase de sugestão.
3. **Given** uma sala sem resultado, **When** o professor abre a sala, **Then** o bloco é igual ao da
   Feature 003 (só a mensagem do estado, sem link).

---

### User Story 3 - Entender quando não há sugestão ou o diagnóstico não permite sugerir (Priority: P1)

O professor sempre sabe se há sugestões e em que análise elas se baseiam. Sem sugestão aplicável, a
página diz isso de forma neutra, sem elogio nem cobrança. Sem resultado, não há seção de sugestões.

**Why this priority**: uma sugestão sem base ou um silêncio ambíguo quebram a confiança (Princípios
III e IV).

**Independent Test**: percorrer os estados A a H da Feature 002 e conferir a seção e o bloco
conforme a tabela de FR-020.

**Acceptance Scenarios**:

1. **Given** uma sala com resultado e sem materiais de grande porte, **When** o professor abre a
   página, **Then** a seção de sugestões mostra "Não há sugestões para esta sala no momento." e
   nenhuma avaliação positiva ou negativa.
2. **Given** um resultado desatualizado ou cuja última atualização falhou, **When** há sugestão,
   **Then** ela aparece com a data da análise em que se baseia, além do aviso que a Feature 003 já
   mostra na abertura.
3. **Given** uma sala sem resultado, aguardando ou em processamento, **When** o professor abre a
   página, **Then** não há seção de sugestões.

---

### Edge Cases

- **Limite de 1.000 ligações**: se as ligações a materiais grandes foram truncadas, a sugestão diz
  que considera os 1.000 maiores materiais, como a seção "Arquivos de grande porte".
- **Limite alterado pela administração**: a sugestão usa o limite registrado no resultado vigente,
  não o valor atual da configuração; a mudança vale a partir da próxima análise (como na 002).
- **Atividade renomeada depois da análise**: aparece com o nome atual (como na 003).
- **Materiais grandes só no nível da sala** (página inicial, seções, blocos): a sugestão cita "Página
  da sala, seções e blocos" com link para a página da sala.
- **Cobertura parcial (H)**: a sugestão aparece normalmente; ela trata só de materiais classificados.
- **Sala sem arquivos (G)**: sem sugestão; a seção mostra a frase neutra.
- **Professor que não pode editar a atividade** (capability de visualização concedida a outro papel
  pela administração): o link leva à página da atividade, que o Moodle mostra conforme as
  permissões dele; o plugin não oferece ação de edição.

## Orientações iniciais

| ID | Condição de exibição | Dados necessários (todos já existentes) | Exemplo de mensagem (pt_BR) | Ação disponível |
| --- | --- | --- | --- | --- |
| S1 | O resultado vigente tem pelo menos uma ligação a material de grande porte (material da sala ≥ limite registrado no resultado) | Ligações a materiais grandes por atividade (Feature 002); limite do resultado; nomes, links e visibilidade atuais das atividades (estrutura da sala) | **O que observamos** (fato): 2 materiais da sala de 1,0 MB ou mais, somando 3,9 MB, em *Aula 1 — A célula* e *Vídeo de laboratório*, na análise de 17/10/26. **Por que pode merecer atenção:** arquivos grandes podem demorar mais para abrir, sobretudo em conexões lentas ou no celular. O tamanho, sozinho, não diz nada sobre a qualidade ou a importância do material. **O que você pode fazer, se quiser** (possibilidades): ao revisar a atividade, avalie se alguma destas opções faz sentido para o seu material — nem todas se aplicam a todos os casos: (1) uma versão otimizada do arquivo que continue legível e acessível, como um PDF com imagens em resolução adequada para a tela ou um vídeo na resolução necessária; (2) dividir um conteúdo longo em partes menores; (3) um link para onde o conteúdo já está publicado. | Link "Abrir a atividade" para cada atividade citada (página nativa do Moodle); o professor decide e age no Moodle |

Em en, S1: "**What we noticed:** 2 course materials of 1.0 MB or more, 3.9 MB in total, in *Lesson 1 — The
cell* and *Lab video*, in the analysis of 17/10/26. **Why it may deserve attention:** large files can
take longer to open, especially on slow connections or on a phone. Size alone says nothing about the
quality or the importance of a material. **What you can do, if you wish:** when you review the
activity, consider whether any of these options suits your material — not all of them apply to every
case: (1) an optimised version of the file that stays readable and accessible, such as a PDF with
images at a resolution suited to the screen, or a video at the resolution it needs; (2) splitting long
content into shorter parts; (3) a link to where the content is already published." O texto final
passa pela revisão de tom (SC-004).

### Orientações deixadas para depois (dependências registradas)

| Orientação candidata | Por que fica fora | Dependência |
| --- | --- | --- |
| S2 — materiais de grande porte em atividades ocultas para os estudantes | Decisão de 2026-10-18: uma atividade oculta pode estar em preparação, e esse sinal não demonstra necessidade de intervenção | Um sinal que diferencie preparação de material fora de uso (decisão de produto futura) |
| Cópias de segurança guardadas na sala | O agregado da 002 mistura backups feitos pelo professor com backups automáticos do site, que o professor em geral não pode gerenciar; a sugestão poderia apontar para algo fora do alcance dele | Separar, no diagnóstico, os backups manuais (sala, seção, atividade) dos automáticos |
| Itens na lixeira da sala | Saem sozinhos no prazo do site; não há ação útil para o professor, e incentivar a limpeza seria pressão por exclusão | Nenhuma; reavaliar só se houver necessidade observada |
| Mesmo conteúdo enviado várias vezes na sala | A 002 só guarda os totais da sala (Conteúdo único); não há como dizer onde estão as repetições, então a orientação não seria acionável. Além disso, o Moodle já guarda conteúdo idêntico uma vez só, então não há espaço a ganhar | Repetições por atividade no diagnóstico |
| Envios de estudantes e limites de envio das tarefas | Exige limiar novo (configuração) e é o tema da Feature 005 (potencial de armazenamento); por atividade, violaria a privacidade (FR-030 da 003) | Feature 005 |
| Tipos de arquivo (vídeo, imagem, PDF) | A 002 não guarda tipo nem nome de arquivo | Novo dado no diagnóstico |
| Boas práticas gerais, sem relação com a sala | As boas práticas desta entrega ficam vinculadas à S1; as orientações só aparecem quando os dados as sustentam | Decisão de produto futura |

## Requirements *(mandatory)*

### Functional Requirements

**Página "Sua sala em números" (nível 2)**

- **FR-001**: Quando existir resultado vigente, a página MUST mostrar a seção "Sugestões para a sua
  sala" logo depois da abertura e antes de "Onde estão os arquivos". Sem resultado, a seção MUST NOT
  aparecer.
- **FR-002**: Cada sugestão MUST ter as três partes do Princípio III, cada uma com seu rótulo visível
  ("O que observamos", "Por que pode merecer atenção", "O que você pode fazer, se quiser"). A primeira
  parte MUST trazer só fatos medidos na sala (números, atividades, data da análise); a terceira MUST
  trazer só possibilidades a avaliar, apresentadas como tais e separadas visualmente dos fatos.
- **FR-003**: Uma sugestão MUST aparecer somente quando sua condição de exibição (tabela
  "Orientações iniciais") for verdadeira no resultado vigente. A condição MUST ser objetiva,
  testável e descrita nesta especificação; nenhuma sugestão MUST aparecer por padrão, sorteio ou
  regra não documentada.
- **FR-004**: A sugestão S1 MUST:
  - citar a quantidade e o volume dos materiais de grande porte e o limite usado. Quantidade e volume
    MUST ser iguais à soma da lista "Materiais de grande porte por atividade" da Feature 003 (só
    materiais da sala), e não ao total da linha de abertura da seção "Arquivos de grande porte", que
    inclui envios e outros arquivos; o texto MUST dizer que conta materiais da sala;
  - citar até três atividades, pelo nome atual e com link, ordenadas do maior para o menor volume de
    materiais de grande porte (empate pelo nome);
  - informar quantos outros lugares têm materiais de grande porte, quando houver mais de três. Um
    lugar é uma atividade com nome, o nível da sala, o grupo de atividades que o professor não pode
    ver ou o grupo de atividades removidas (cada grupo conta como um lugar, porque o diagnóstico não
    diz quantas atividades ele reúne),
    remetendo à seção "Arquivos de grande porte";
  - tratar atividades que o professor não pode ver, ou removidas depois da análise, só de forma
    agregada e sem nome, e o nível da sala como "Página da sala, seções e blocos";
  - informar quando a lista de ligações foi truncada nos 1.000 maiores materiais;
  - oferecer, em "O que você pode fazer, se quiser", boas práticas concretas e curtas: uma versão
    otimizada do arquivo que preserve legibilidade e acessibilidade; dividir um conteúdo longo em
    partes; um link para onde o conteúdo já está publicado. Elas MUST ser apresentadas como opções que
    podem não se aplicar ao material, MUST NOT afirmar que o material precisa de correção e MUST
    caber num texto curto da própria sugestão (sem página, biblioteca ou mecanismo à parte).
- **FR-005**: Os textos das sugestões MUST:
  - apresentar a ação como opcional ("se quiser"), sem "deve", "é preciso" ou prazos;
  - dizer, na sugestão S1, que o tamanho sozinho não indica problema nem qualidade do material;
  - não sugerir exclusão como ação principal, não prometer economia de espaço, ganho de desempenho
    medido ou benefício ambiental, e não usar as palavras desperdício, excesso ou mau uso;
  - não comparar a sala com outras salas ou professores.
- **FR-006**: A ação de uma sugestão MUST ser um link para uma página nativa do Moodle (a página da
  atividade ou da sala). O plugin MUST NOT executar nenhuma ação, nem por um clique, e MUST NOT
  oferecer botão para dispensar, adiar ou marcar a sugestão como resolvida (ignorar não tem
  consequência e não é registrado).
- **FR-007**: Quando houver sugestão, a seção MUST informar a data da análise em que ela se baseia.
  A frase neutra de FR-008 não repete a data, que a abertura da página já mostra. Nos estados E e F
  com resultado, as sugestões MUST continuar visíveis, com o aviso que a abertura já mostra.
- **FR-008**: Com resultado e sem sugestão aplicável, a seção MUST mostrar uma frase neutra única
  ("Não há sugestões para esta sala no momento."), sem elogio, cobrança ou indicação de que a sala
  esteja "boa" ou "ruim".
- **FR-009**: As demais seções da Feature 003 MUST permanecer como estão; a sugestão complementa a
  seção "Arquivos de grande porte" e não a substitui.

**Bloco (nível 1)**

- **FR-010**: Quando existir pelo menos uma sugestão, o bloco MUST mostrar uma frase curta que a
  anuncia ("Há uma sugestão sobre os arquivos da sua sala."), sem número, selo, ícone de alerta ou
  cor de alerta, e o link existente MUST levar à seção de sugestões da página. Sem sugestão, o bloco
  MUST ser igual ao da Feature 003.
- **FR-011**: O bloco MUST continuar com no máximo um indicador (o volume) e uma mensagem essencial
  (Princípio II); a frase de sugestão não acrescenta número.

**Estados**

- **FR-020**: Bloco e seção de sugestões MUST seguir esta tabela:

  | Estado (Feature 002) | Bloco | Seção de sugestões |
  | --- | --- | --- |
  | A, B, C, F sem resultado | Igual à 003 | Ausente |
  | D, H com sugestão | 003 + frase de sugestão | Sugestões com a data da análise |
  | D, H sem sugestão | Igual à 003 | Frase neutra |
  | E, F com resultado, com sugestão | 003 (com o aviso) + frase de sugestão | Sugestões com a data da análise |
  | E, F com resultado, sem sugestão | Igual à 003 | Frase neutra |
  | G (e E ou F de um resultado sem arquivos) | Igual à 003 | Frase neutra |

**Privacidade, segurança e dados**

- **FR-030**: As sugestões MUST usar, por atividade, só materiais da sala; MUST NOT citar envios de
  estudantes por atividade, nomes de estudantes, nomes de arquivos ou atividades que o professor não
  pode ver (Princípio VII; FR-030 e FR-033 da Feature 003).
- **FR-031**: A feature MUST reutilizar a capability de visualização existente e MUST NOT criar
  capabilities.
- **FR-032**: Bloco e página MUST ler só o resultado da Feature 002 e a estrutura da sala; MUST NOT
  consultar os registros de arquivos, criar análises, persistir dados, guardar histórico, chamar
  serviços externos ou modelos de linguagem.
- **FR-033**: A feature MUST NOT criar configurações administrativas. O único limiar usado é o limite
  de grande porte já configurável desde a Feature 002, registrado em cada resultado.

**Apresentação, idioma e acessibilidade**

- **FR-040**: A seção e as sugestões MUST seguir a direção visual aprovada na Feature 003 (verdes
  suaves, neutros, cantos arredondados, broto, cartões discretos), sem cores ou ícones de alerta.
- **FR-041**: Toda frase MUST vir do catálogo de strings, completa e com parâmetros, em en e pt_br
  com paridade; nenhuma frase montada por concatenação.
- **FR-042**: Seção e bloco MUST atender aos mesmos critérios de acessibilidade da Feature 003 (WCAG
  2.2 AA, títulos em ordem, links com nome completo, teclado, 320 px sem rolagem horizontal, Boost e
  Classic).

**Validação e documentação**

- **FR-050**: Os textos das sugestões, da frase do bloco e da frase neutra MUST passar por revisão de
  tom com aprovação registrada do proprietário do produto antes da conclusão.
- **FR-051**: Bloco e seção MUST ser validados em capturas reais (en e pt_BR, desktop e celular,
  Boost e Classic, com e sem sugestão), com aprovação registrada do proprietário do produto.
- **FR-052**: A sugestão MUST ser avaliada na mesma avaliação com cinco professores da Feature 003
  (protocolo de `specs/003-teacher-experience/evaluation/protocol.md`), acrescida de perguntas sobre a
  compreensão e a utilidade da sugestão. A avaliação fica registrada como pendente; ela não bloqueia
  a implementação nem o teste local da 004, mas a feature só é considerada concluída depois dela, sem
  problema crítico em aberto (critério de FR-071a da 003).
- **FR-053**: O README MUST descrever as sugestões, a condição de cada uma, o que elas não significam
  e o que fica para depois.

### Key Entities

- **Sugestão**: identificador (S1), condição de exibição, dados observados que ela cita, texto das
  três partes e links de ação. Não é persistida: é calculada a cada exibição a partir do resultado
  vigente.
- **Lista de sugestões da sala**: as sugestões cuja condição é verdadeira no resultado vigente (nesta
  entrega, só S1), com a data da análise.

## Cenários de teste

| ID | Cenário | Resultado esperado |
| --- | --- | --- |
| CT-01 | Sala com materiais de grande porte em duas atividades e um envio grande | S1 com três partes, números iguais à soma da lista "Materiais de grande porte por atividade" (sem o envio), duas atividades com link |
| CT-02 | Mais de três lugares com materiais grandes | Três citados por volume, "e mais N lugares", remissão à seção de grandes |
| CT-03 | Atividade invisível para o professor e atividade removida com materiais grandes | Só agregadas, sem nome |
| CT-04 | Envios grandes, nenhum material grande | Sem S1; frase neutra |
| CT-05 | Estados A a H (tabela de FR-020) | Bloco e seção conforme a tabela |
| CT-06 | Ligações truncadas (mais de 1.000 materiais grandes) | Aviso de que considera os 1.000 maiores |
| CT-07 | Bloco com e sem sugestão | Frase presente só com sugestão; nenhum número novo; link leva à seção |
| CT-08 | Inspeção dos textos en e pt_br | Nenhuma obrigação, promessa de economia, termo de julgamento ou comparação (FR-005) |
| CT-09 | Renderização do bloco e da página | Nenhuma consulta aos registros de arquivos |
| CT-10 | Acessibilidade automática e teclado | Nenhuma violação; sugestão e links alcançados pelo teclado |
| CT-11 | Capturas (FR-051) | Aprovadas pelo proprietário do produto |
| CT-12 | Leitura de "O que você pode fazer, se quiser" | Possibilidades concretas, apresentadas como opções que podem não se aplicar; nenhuma afirmação de que o material precisa de correção; fatos só na primeira parte |
| CT-13 | Avaliação com professores (manual, junto da 003) | Perguntas de compreensão e utilidade da sugestão registradas; nenhum problema crítico em aberto |

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Em 100% dos estados A a H, bloco e seção de sugestões seguem a tabela de FR-020
  (cenários automatizados).
- **SC-002**: Em 100% das salas de teste em que a condição de S1 é verdadeira, a sugestão aparece com
  as três partes e números iguais à soma da lista "Materiais de grande porte por atividade"; em 100%
  das salas em que é
  falsa, ela não aparece.
- **SC-003**: Nenhuma sugestão cita envio por atividade, nome de estudante, nome de arquivo ou
  atividade que o professor não pode ver (inspeção automatizada em salas com envios grandes).
- **SC-004**: A revisão de tom não encontra obrigação, promessa de economia ou de benefício ambiental,
  termo de julgamento ou comparação, e o proprietário do produto aprova os textos.
- **SC-005**: O bloco não acrescenta mais que 50 ms em relação à 0.3.0, e a página de uma sala com 200
  atividades continua abrindo em até 2 segundos em condições normais de operação, no ambiente de
  referência da Feature 003 (decisão de 2026-10-18; a primeira abertura após a limpeza integral dos
  caches é uma limitação documentada, não coberta por este critério).
- **SC-006**: A verificação automática de acessibilidade não encontra violações no bloco e na seção,
  e a sugestão e seus links são alcançados só com o teclado.
- **SC-007**: As capturas de FR-051 são aprovadas pelo proprietário do produto.
- **SC-008**: Os quality gates da constituição passam no CI (PHPUnit, Behat, paridade de strings,
  verificação do pacote) nas bases suportadas.
- **SC-009**: Na avaliação com cinco professores (FR-052), cada um responde às perguntas sobre a
  sugestão e nenhum problema crítico permanece em aberto no encerramento (pendente; não bloqueia a
  implementação).

## Limitações reconhecidas

- A sugestão S1 depende do limite de grande porte: com um limite alto, poucas salas a verão; com um
  limite baixo, muitas. O limite é uma escolha da administração do site, já documentada.
- "Podem demorar mais para abrir" é uma explicação geral, não uma medição feita na sala.
- As ligações a materiais grandes consideram no máximo os 1.000 maiores por sala.
- **Primeira abertura após a limpeza integral dos caches**: no ambiente de desenvolvimento medido, a
  página (como a página do curso do core) pode passar de 2 segundos logo depois de `purge_caches`. Não
  foi demonstrada regressão da Feature 004; a causa exata não foi comprovada (ver evidências).

## Fora do escopo e features posteriores

- Orientações listadas em "Orientações deixadas para depois".
- **Feature 005**: potencial de armazenamento das atividades (limites de envio, projeções).
- **Feature 006**: histórico e evolução no tempo.
- Dispensar, adiar ou marcar sugestões; contagem de sugestões no bloco; notificações, e-mails ou
  mensagens.
- Configurações administrativas, limiares novos, rankings, comparações, pontuação, estimativas de
  economia, carbono ou impacto ambiental.
- Exclusão, compressão ou movimentação de arquivos pelo plugin; ações de um clique.
- Modelos de linguagem, serviços externos, novas análises, novos dados persistidos.

## Riscos

- **Sugestão lida como cobrança ou ordem de excluir**: mitigação por rótulos ("se quiser"), frase
  explícita sobre tamanho e qualidade, revisão de tom com aprovação.
- **Sugestão repetida a cada visita**: aceito nesta entrega (sem dispensar); a frase é curta e
  neutra, e o bloco não mostra contagem.
- **Uma única orientação parece pouco**: aceito; as orientações candidatas dependem de dados que
  ainda não existem (tabela de dependências).

## Assumptions

- O público é o mesmo da Feature 003: professor com edição e gerente (capability existente).
- Há uma única sugestão nesta entrega; não há ordenação nem priorização entre sugestões.
- Os textos de exemplo são propostas; o texto final sai da revisão de tom (FR-050).
- A seção usa os componentes visuais da Feature 003; um elemento visual novo, se houver, passa pela
  aprovação de FR-051.
- A camada de regras de recomendação prevista no Princípio IX passa a existir com a regra de S1, sem
  estrutura para orientações futuras.
- As boas práticas são um texto curto da própria sugestão, no catálogo de strings; não há biblioteca
  de boas práticas, outro mecanismo de recomendações ou configuração.
