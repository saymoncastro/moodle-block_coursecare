# Feature Specification: CourseCare Plugin Foundation

**Feature Branch**: `001-plugin-foundation` (a criar antes do plano; integração à `main` só após os quality gates)

**Created**: 2026-10-16

**Status**: Draft

**Input**: User description: "Feature 001 — CourseCare Plugin Foundation: primeira versão funcional e
instalável do bloco CourseCare (AVA Consciente) para Moodle 4.5, com estado inicial educativo,
internacionalização en/pt_BR conforme a constituição 1.1.0 e verificação automatizada de traduções
e do pacote distribuível. Sem análise de arquivos ou de armazenamento."

**Constituição de referência**: 1.2.0

## Clarifications

### Session 2026-10-16

- Q: Quem vê o bloco nesta feature? → A: Apenas quem edita a sala (professor com permissão de edição
  e gerente), por padrão (FR-009).
- Q: Há ativo visual aprovado? → A: Não. Esta feature cria um ícone ou ilustração simples e
  discreto, sujeito a aprovação e a verificações de contraste e de compatibilidade com temas
  (FR-015, FR-015a).

### Session 2026-10-16 (revisão do plano)

- Decisão do proprietário: a avaliação com cinco docentes sai da Feature 001 e vai para a Feature
  003. Aqui, a clareza dos textos é verificada pelo proprietário, com evidências reais, e
  opcionalmente por um ou dois docentes, sem bloqueio (SC-002).
- Decisão do proprietário: o agente propõe e implementa o ícone, a apresentação visual e os textos
  en/pt_BR. A aprovação final vem só depois das evidências visuais reais e nunca é registrada
  antecipadamente (FR-014, FR-015).
- Exibição fora da página principal: o bloco não aparece, nem com cabeçalho vazio, inclusive em
  modo de edição (FR-006).
- Unicidade: a garantia vale para os fluxos de usuário, e suas limitações estão documentadas
  (FR-010).
- Acessibilidade: testes automatizados mais verificação manual proporcional, sem declarar
  conformidade integral com a WCAG (FR-017, SC-007).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Instalação pelo administrador (Priority: P1)

O administrador do Moodle instala o CourseCare pelos mecanismos convencionais da plataforma
(instalação de plugin e atualização do site), sem alterar arquivos do core. Ele pode habilitar ou
desabilitar o bloco no gerenciamento de blocos e desinstalá-lo pelo fluxo oficial.

**Why this priority**: sem instalação não existe produto. É também a base de toda feature futura.

**Independent Test**: em um Moodle 4.5 limpo, instalar o plugin, confirmar que ele aparece como
bloco instalado e habilitado, desabilitá-lo, reabilitá-lo e desinstalá-lo, sempre pelos fluxos
nativos.

**Acceptance Scenarios**:

1. **Given** um Moodle 4.5 sem o plugin, **When** o administrador instala o pacote pelo fluxo nativo,
   **Then** a instalação conclui sem erros nem avisos, e o bloco aparece na lista de blocos com o
   nome "CourseCare".
2. **Given** o plugin instalado, **When** o administrador desabilita o bloco no gerenciamento de
   blocos, **Then** ele deixa de ser oferecido para adição e as instâncias existentes deixam de ser
   exibidas. Ao reabilitar, elas voltam.
3. **Given** o plugin instalado e com instâncias em salas, **When** o administrador o desinstala
   pelo fluxo nativo, **Then** a desinstalação conclui sem erros, e não restam instâncias nem dados
   do plugin.
4. **Given** a instalação concluída, **When** se compara o core antes e depois, **Then** nenhum
   arquivo do core foi alterado.

---

### User Story 2 - Adição do bloco a uma sala (Priority: P1)

Um usuário autorizado adiciona o bloco CourseCare à página de uma sala. A possibilidade de
adicionar, configurar e visualizar respeita as permissões nativas do Moodle.

**Why this priority**: o bloco só entrega valor quando está presente na sala.

**Independent Test**: com usuários de papéis diferentes na mesma sala, verificar quem consegue
adicionar, configurar e visualizar o bloco.

**Acceptance Scenarios**:

1. **Given** um professor com permissão de edição na sala e o modo de edição ativo, **When** ele
   escolhe adicionar um bloco, **Then** o CourseCare está entre as opções, e o bloco é adicionado.
2. **Given** a sala já contém um CourseCare, **When** o professor tenta adicionar outro, **Then** a
   plataforma não oferece uma segunda instância.
3. **Given** um usuário sem permissão de edição na sala, **When** ele acessa a sala, **Then** não
   consegue adicionar, mover, configurar nem remover o bloco.
4. **Given** um administrador que removeu a permissão de adicionar o bloco de um papel, **When** um
   usuário com esse papel tenta adicioná-lo, **Then** o CourseCare não é oferecido.
5. **Given** um usuário sem permissão para visualizar o bloco, **When** ele acessa uma sala que
   contém o CourseCare, **Then** o bloco não é exibido para ele.

---

### User Story 3 - Experiência inicial (Priority: P2)

O professor encontra na sala um bloco acolhedor e discreto, que comunica de imediato a finalidade
do CourseCare e deixa claro que as orientações ainda virão. O bloco não parece um painel
administrativo e não sugere que uma análise já tenha sido feita.

**Why this priority**: a primeira impressão define a confiança do professor no produto. A
constituição (Princípios I, II e X) exige tom educativo e não fiscalizador desde o início.

**Independent Test**: com o bloco renderizado em um Moodle real, o proprietário do produto revisa as
evidências visuais (en e pt_BR) e confirma que a finalidade está clara e que nada sugere análise já
realizada. Opcionalmente, um ou dois docentes fazem uma revisão inicial, que não bloqueia a
entrega. A avaliação estruturada com cinco docentes pertence à Feature 003.

**Acceptance Scenarios**:

1. **Given** o bloco adicionado a uma sala, **When** o professor visualiza a sala, **Then** o bloco
   mostra o nome do produto, uma frase de identidade e uma mensagem inicial curta, sem números,
   gráficos, indicadores, alertas ou status.
2. **Given** o idioma pt_BR ativo, **When** o professor visualiza o bloco, **Then** o conteúdo
   corresponde à referência: título "AVA Consciente", frase "Pequenas escolhas, um AVA melhor para
   todos." e mensagem "Em breve, você encontrará aqui orientações para utilizar os recursos da sua
   sala de forma mais consciente."
3. **Given** o bloco em uma tela de celular, **When** o professor o visualiza, **Then** todo o
   conteúdo é legível sem rolagem horizontal e sem perda de informação.
4. **Given** o bloco exibido, **When** o professor o percorre apenas com o teclado ou com leitor de
   tela, **Then** todo o conteúdo é alcançável e anunciado corretamente, e elementos visuais
   decorativos não geram ruído.
5. **Given** o bloco exibido, **When** se procura um link ou ação, **Then** não há links para páginas
   ou funcionalidades inexistentes.
6. **Given** o bloco exibido com o ativo visual do CourseCare, **When** o ativo não carrega, é
   ocultado ou é lido por leitor de tela, **Then** a mensagem continua completa e compreensível, e o
   leitor de tela não anuncia o ativo.
7. **Given** o bloco presente na sala, **When** um editor, em modo de edição, abre uma página de
   seção ou uma página de atividade em que o bloco seria carregado, **Then** nem o bloco nem seu
   cabeçalho aparecem.

---

### User Story 4 - Idioma do professor e fallback (Priority: P2)

O professor vê o CourseCare no idioma do seu ambiente Moodle. Quando a tradução não está disponível,
o bloco aparece em inglês, completo e compreensível.

**Why this priority**: a constituição (Princípio VIII) exige pt_BR validado desde a primeira feature
com interface, e o produto é internacional por desenho.

**Independent Test**: alternar o idioma do usuário entre inglês, português brasileiro (com e sem a
tradução de desenvolvimento instalada) e um terceiro idioma sem tradução, e conferir o texto do bloco
em cada caso.

**Acceptance Scenarios**:

1. **Given** o usuário com idioma inglês, **When** ele visualiza o bloco, **Then** todo o conteúdo
   aparece em inglês.
2. **Given** o pacote de idioma pt_br instalado e a tradução de desenvolvimento instalada pelo
   mecanismo nativo de personalização de idioma, **When** o usuário com idioma pt_BR visualiza o
   bloco, **Then** todo o conteúdo aparece em português brasileiro.
3. **Given** o pacote de idioma pt_br instalado sem a tradução de desenvolvimento, **When** o usuário
   com idioma pt_BR visualiza o bloco, **Then** o conteúdo do bloco aparece em inglês, completo.
4. **Given** uma tradução de desenvolvimento sem uma das mensagens, **When** o usuário pt_BR
   visualiza o bloco, **Then** apenas a mensagem ausente aparece em inglês, inteira, e nenhuma frase
   mistura os dois idiomas.
5. **Given** o usuário com um idioma sem tradução do CourseCare (ex.: espanhol), **When** ele
   visualiza o bloco, **Then** o conteúdo do bloco aparece em inglês.

---

### User Story 5 - Verificação automatizada pelo mantenedor (Priority: P3)

O mantenedor executa, localmente ou em integração contínua, uma verificação que confirma a
integridade das traduções em relação ao inglês e a conformidade do pacote distribuível com a
estratégia de distribuição por AMOS.

**Why this priority**: protege o Quality Gate 4 em todas as features seguintes. Ele não é percebido
pelo professor, mas impede regressões.

**Independent Test**: executar a verificação sobre conjuntos de exemplo, um válido e outros com
inconsistências conhecidas, e sobre um pacote gerado, conferindo o resultado de cada um.

**Acceptance Scenarios**:

1. **Given** traduções consistentes com o inglês, **When** a verificação é executada, **Then** ela
   termina com sucesso e informa que não há problemas.
2. **Given** uma tradução sem uma chave existente no inglês, **When** a verificação é executada,
   **Then** ela falha e identifica o idioma, o arquivo e a chave ausente.
3. **Given** uma tradução com uma chave inexistente no inglês, **When** a verificação é executada,
   **Then** ela falha e identifica a chave excedente.
4. **Given** uma tradução cujos parâmetros dinâmicos diferem dos parâmetros do inglês, **When** a
   verificação é executada, **Then** ela falha e identifica a chave e os parâmetros divergentes.
5. **Given** uma tradução com valor vazio, ou um arquivo de strings que não seja apenas dados no
   formato da plataforma, **When** a verificação é executada, **Then** ela falha e identifica o
   problema.
6. **Given** um pacote distribuível gerado, **When** a verificação de pacote é executada, **Then**
   ela falha se o pacote contiver `translations/` ou qualquer idioma além do inglês, e passa caso
   contrário.
7. **Given** a verificação em integração contínua, **When** ela falha, **Then** a integração é
   bloqueada e a mensagem permite corrigir o problema sem investigação adicional.

---

### Edge Cases

- O administrador desabilita o bloco no site enquanto existem instâncias: elas ficam ocultas e
  reaparecem ao reabilitá-lo, sem perda.
- A sala com o bloco é copiada, restaurada de backup ou reiniciada: o bloco acompanha a sala pelo
  comportamento nativo da plataforma, sem dados próprios a migrar.
- O bloco é exibido em uma área lateral recolhível, em tela estreita ou com zoom de 200%: o conteúdo
  continua legível e completo.
- O tema do site é diferente do tema padrão: o bloco mantém legibilidade, contraste e estrutura.
- A tradução contém a mesma frase do inglês (ex.: o nome "CourseCare"): a verificação não trata isso
  como erro, mas pode sinalizá-lo como aviso.
- `translations/` contém outro idioma além de pt_br: a verificação de paridade cobre todos os
  idiomas presentes.
- Um usuário convidado ou não autenticado acessa uma sala aberta a visitantes: o bloco não é exibido
  (FR-009).
- Um professor sem permissão de edição acessa a sala: o bloco não é exibido, salvo se o administrador
  tiver concedido a permissão de visualização a esse papel.
- O administrador concede a visualização a estudantes: o bloco aparece com o mesmo conteúdo, pois
  esta feature não tem conteúdo específico por papel.
- O bloco adicionado na página principal também é carregado nas páginas de seção (padrão nativo de
  exibição), ou em atividades quando o editor amplia "Exibir nos tipos de página": o bloco não
  aparece nessas páginas, nem com cabeçalho vazio, nem em modo de edição (FR-006).
- O formato do curso é alterado (ex.: tópicos para semanal) com o bloco presente: a instância
  continua exibida na página principal, e uma segunda instância não é oferecida (FR-010).
- Um backup da sala é restaurado sobre uma sala que já tem o bloco: a restauração não cria uma
  segunda instância (FR-010).
- Um editor sem permissão de visualização, por ajuste institucional, ativa o modo de edição na
  página principal: ele vê o cabeçalho e os controles nativos do bloco, para poder gerenciá-lo, mas
  não vê a mensagem. É uma limitação aceita da plataforma.

## Requirements *(mandatory)*

### Functional Requirements

**Instalação e ciclo de vida**

- **FR-001**: O plugin MUST ser instalável como bloco com identificador `block_coursecare`, pelos
  mecanismos oficiais de instalação e atualização do Moodle, sem alterar o core.
- **FR-002**: O plugin MUST ser desinstalável pelo mecanismo oficial, removendo todas as instâncias
  e todos os dados próprios.
- **FR-003**: O plugin MUST declarar compatibilidade apenas com as versões efetivamente validadas.
  Nesta feature, somente o Moodle 4.5.
- **FR-004**: O plugin MUST funcionar sem serviços externos, sem acesso à internet e sem dependências
  proprietárias.
- **FR-005**: O plugin MUST declarar formalmente, pelo mecanismo de privacidade da plataforma, que
  não armazena dados pessoais.

**Disponibilidade e permissões**

- **FR-006**: O bloco MUST estar disponível para adição na página principal das salas (cursos).
  Ele MUST NOT ser oferecido no Painel, na página inicial do site, em páginas de seção, em páginas de
  atividades ou em outros contextos fora da sala. Fora da página principal, o bloco MUST NOT
  aparecer, nem com cabeçalho vazio, inclusive em modo de edição, mesmo que suas configurações
  nativas de exibição o carreguem ali.
- **FR-007**: Adicionar o bloco MUST exigir uma permissão própria, concedida por padrão aos papéis
  que já podem editar a sala (professor com permissão de edição e gerente), e ajustável pelo
  administrador pelo sistema nativo de papéis.
- **FR-008**: Configurar, mover, ocultar e remover o bloco MUST seguir as permissões nativas de
  edição de blocos da sala.
- **FR-009**: A visualização do bloco MUST exigir uma permissão própria, concedida por padrão apenas
  aos papéis que editam a sala (professor com permissão de edição e gerente). Professores sem
  permissão de edição, estudantes, visitantes e usuários não autenticados MUST NOT ver o bloco por
  padrão. O administrador pode ajustar essa concessão pelo sistema nativo de papéis.
- **FR-010**: Cada sala MUST admitir no máximo uma instância do CourseCare pelos fluxos de usuário
  da plataforma: adição pela interface, mudança de formato do curso e restauração de backup.
  A plataforma garante unicidade por página, não por curso. Por isso, a garantia depende de a adição
  só ser possível na página principal e de a instância permanecer carregada nela. Instâncias criadas
  por configuração administrativa de blocos padrão ou por código fora da interface estão fora desta
  garantia e são documentadas como limitação.
- **FR-011**: O bloco MUST NOT oferecer configurações próprias nesta feature, além das opções nativas
  que a plataforma oferece a qualquer bloco.

**Experiência inicial**

- **FR-012**: O bloco MUST exibir, em ordem: o nome do produto (CourseCare / AVA Consciente), uma
  frase de identidade e uma mensagem inicial informando que as orientações estarão disponíveis em
  breve.
- **FR-013**: O estado inicial MUST NOT apresentar números, indicadores, gráficos, alertas, status,
  pontuações ou qualquer elemento que sugira análise já realizada.
- **FR-014**: Os textos MUST ser curtos, acolhedores, não técnicos e não punitivos. O conteúdo em
  pt_BR MUST corresponder à referência de produto (título "AVA Consciente"; frase "Pequenas escolhas,
  um AVA melhor para todos."; mensagem "Em breve, você encontrará aqui orientações para utilizar os
  recursos da sua sala de forma mais consciente."). Os textos em inglês e em pt_BR podem ser
  propostos e implementados nesta feature. A aprovação final MUST ser dada pelo proprietário do
  produto após a apresentação de evidências visuais da implementação real.
- **FR-015**: A apresentação visual MUST seguir a direção aprovada (acolhedora, discreta,
  profissional, não infantilizada, sem aparência de painel administrativo) e MUST incluir um ativo
  visual próprio do CourseCare (ícone ou ilustração simples), criado nesta feature, que:
  - seja original e distribuído sob licença compatível com a do projeto;
  - seja decorativo: a finalidade do bloco é compreendida sem ele, e ele é ignorado por tecnologias
    assistivas;
  - mantenha contraste e nitidez nos temas padrão, em telas pequenas, com zoom de 200% e em modo de
    alto contraste do sistema (ou desapareça sem perda de informação);
  - não use personagens infantis, emojis, pontuações nem elementos de alerta;
  - seja aprovado pelo proprietário do produto antes da integração à `main`, com base em
    evidências visuais da implementação real. A aprovação MUST NOT ser registrada antes de o ativo
    existir e ser avaliado.
- **FR-015a**: Cores próprias do ativo MUST NOT ser o único meio de transmitir informação e MUST NOT
  alterar a aparência dos demais elementos da página ou do tema.
- **FR-016**: O bloco MUST NOT conter links ou ações para páginas ou funcionalidades inexistentes.
- **FR-017**: O bloco MUST atender aos critérios aplicáveis da WCAG 2.2 AA: contraste suficiente,
  navegação completa por teclado, foco visível, estrutura semântica de títulos, alternativa textual
  para elementos informativos, elementos decorativos ignorados por tecnologias assistivas e
  legibilidade a 320 px de largura sem rolagem horizontal e com zoom de 200%. A verificação combina
  testes automatizados (axe via Behat) e verificações manuais proporcionais ao escopo (teclado,
  leitor de tela, zoom e reflow). O resultado é registrado como verificação dos critérios aplicáveis
  ao bloco e MUST NOT ser apresentado como declaração de conformidade integral com a WCAG.
- **FR-018**: O bloco MUST manter aparência e funcionamento corretos nos temas padrão da plataforma,
  sem depender de tema específico ou institucional.

**Internacionalização**

- **FR-019**: Todo texto visível MUST vir do catálogo de strings do plugin. Nenhum texto de interface
  pode estar fixo no código ou nos modelos de apresentação.
- **FR-020**: O catálogo canônico MUST estar em inglês e conter todas as chaves.
- **FR-021**: A tradução pt_BR MUST ser versionada em `translations/pt_br/`, fora da estrutura de
  idiomas distribuível, conforme o Princípio VIII.
- **FR-022**: A documentação do projeto MUST descrever como instalar a tradução de desenvolvimento
  pela personalização de idioma nativa, deixando explícito que é exclusiva de desenvolvimento e
  homologação.
- **FR-023**: Quando a tradução não estiver instalada, ou quando faltar uma chave, o bloco MUST
  exibir a string em inglês correspondente, inteira. Cada mensagem MUST ser uma string completa, e
  frases MUST NOT ser montadas por concatenação.
- **FR-024**: A tradução pt_BR MUST usar terminologia genérica do português brasileiro, sem termos ou
  referências de qualquer instituição.

**Verificação automatizada**

- **FR-025**: O projeto MUST oferecer uma verificação automatizada de traduções que compare cada
  idioma de `translations/` com o inglês e falhe ao detectar: chave ausente; chave excedente;
  divergência de parâmetros dinâmicos; valor vazio; arquivo que não seja apenas dados no formato da
  plataforma. Valores idênticos ao inglês geram aviso, não falha.
- **FR-026**: O projeto MUST oferecer uma verificação do pacote distribuível que falhe quando ele
  contiver `translations/`, idiomas além do inglês na estrutura de idiomas ou arquivos de
  desenvolvimento excluídos por regra do projeto.
- **FR-027**: As verificações MUST ser executáveis localmente e em integração contínua, sem
  interação. Elas MUST retornar sucesso ou falha de forma automatizável e relatar cada problema com
  idioma, arquivo e chave.
- **FR-028**: As verificações MUST ser cobertas por casos de teste com exemplos válidos e com cada
  tipo de inconsistência previsto em FR-025 e FR-026.

**Conformidade constitucional e evidências**

- **FR-029**: Esta feature MUST NOT exibir nenhuma categoria de indicador do Princípio IV, e MUST
  NOT ler nem armazenar dados de usuários (Princípio VII).
- **FR-030**: Cada critério de aceitação (CA-01 a CA-13) MUST ter evidência registrada, com resultado
  das verificações automatizadas, testes executados e registros de verificação manual
  (capturas em en e pt_BR, desktop, tablet e celular), indicando a versão do Moodle e o tema usados.
  As evidências MUST ficar organizadas no diretório desta feature, fora do pacote distribuível.
  Nenhum requisito pode ser declarado atendido sem a evidência correspondente.

### Rastreabilidade dos critérios de aceitação

| Critério | Requisitos | Cenários |
| --- | --- | --- |
| CA-01 Instalação no Moodle 4.5 | FR-001, FR-003 | US1-1 |
| CA-02 Adição por usuário autorizado | FR-006, FR-007, FR-010 | US2-1, US2-2, US3-7, edge cases de formato e restauração |
| CA-03 Identidade e mensagem inicial | FR-012 a FR-016, SC-002 | US3-1, US3-2, US3-5, US3-6 |
| CA-04 Bloqueio sem permissão | FR-007 a FR-009 | US2-3, US2-4, US2-5 |
| CA-05 Funciona em inglês | FR-019, FR-020 | US4-1 |
| CA-06 Funciona em pt_BR (tradução de desenvolvimento) | FR-021, FR-022 | US4-2 |
| CA-07 Fallback para inglês | FR-023 | US4-3, US4-4, US4-5 |
| CA-08 Paridade detecta inconsistências | FR-025, FR-027, FR-028 | US5-1 a US5-5, US5-7 |
| CA-09 Pacote sem traduções adicionais | FR-026 | US5-6 |
| CA-10 Responsivo e acessível | FR-017, SC-007, SC-008 | US3-3, US3-4 |
| CA-11 Sem modificação do core | FR-001 | US1-4 |
| CA-12 Independente do tema | FR-018 | Edge case de tema |
| CA-13 Evidências documentadas | FR-030 | — |

### Key Entities

- **Instância do bloco**: a presença do CourseCare em uma sala. Há no máximo uma por sala, e ela não
  guarda dados próprios nesta feature.
- **Catálogo de strings (inglês)**: o conjunto canônico de chaves e textos do plugin, incluído no
  pacote distribuível.
- **Tradução de desenvolvimento**: a cópia versionada de um idioma (pt_BR) em `translations/`, com
  as mesmas chaves do catálogo. Ela fica fora do pacote distribuível.
- **Pacote distribuível**: o artefato publicado do plugin, contendo apenas o inglês como idioma.
- **Ativo visual do CourseCare**: o ícone ou ilustração decorativa da identidade do produto. É
  original e tem licença compatível. Só é integrado à `main` após aprovação do proprietário do
  produto, com base em evidência visual real.
- **Relatório de verificação**: o resultado das verificações de traduções e de pacote, com a lista de
  problemas por idioma, arquivo e chave.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Um administrador instala o CourseCare em um Moodle 4.5 limpo em até 5 minutos, sem
  editar nenhum arquivo fora do diretório do plugin e sem erros ou avisos.
- **SC-002**: Verificação inicial da clareza dos textos. O proprietário do produto revisa as
  evidências visuais reais do bloco (en e pt_BR) e confirma, com registro, que a finalidade está
  clara e que nada sugere análise já realizada. A revisão opcional por um ou dois docentes é
  registrada quando houver, mas não bloqueia esta feature. A avaliação estruturada com cinco docentes
  (diferentes níveis de familiaridade tecnológica e, preferencialmente, áreas de ensino) foi
  transferida para a Feature 003 — Experiência visual.
- **SC-003**: 100% dos textos visíveis do bloco vêm do catálogo de strings, e 0 textos fixos são
  encontrados na revisão.
- **SC-004**: Nos três cenários de idioma (inglês; pt_BR com tradução; pt_BR sem tradução ou idioma
  sem tradução), 100% das mensagens do bloco aparecem inteiras no idioma esperado, sem frases
  mistas.
- **SC-005**: A verificação de traduções detecta 100% das inconsistências semeadas nos exemplos de
  teste (cada tipo de FR-025), não acusa falha no conjunto válido e conclui em menos de 1 minuto.
- **SC-006**: O pacote distribuível gerado contém 0 arquivos de `translations/` e 0 idiomas além do
  inglês.
- **SC-007**: A verificação automática de acessibilidade da plataforma reporta 0 violações no bloco,
  em inglês e em pt_BR. A verificação manual registrada confirma: todo o conteúdo percorrível só com
  teclado e com foco visível; leitura correta por leitor de tela, sem anúncio do ativo; legibilidade
  com zoom de 200% e a 320 px. O resultado não é declarado como conformidade integral com a WCAG.
- **SC-008**: O bloco é exibido corretamente em pelo menos dois temas padrão da plataforma, em
  desktop e em largura de celular, sem rolagem horizontal.
- **SC-009**: A presença do bloco não causa atraso perceptível no carregamento da sala, pois o bloco
  não realiza nenhum processamento de dados de armazenamento.
- **SC-010**: 13 de 13 critérios de aceitação têm evidência registrada.

## Assumptions

- "Sala" é um curso do Moodle, conforme a constituição. Nesta feature, o bloco fica restrito à página
  principal do curso. Outros contextos poderão ser avaliados em features futuras.
- O texto-base em inglês será redigido a partir da referência em pt_BR e revisado. Proposta inicial:
  título "CourseCare"; frase "Small choices, a better learning space for everyone."; mensagem
  "Soon you will find guidance here on using your course resources more mindfully."
- Por padrão, a permissão de adicionar o bloco segue o padrão da plataforma para blocos de curso:
  professor com permissão de edição e gerente.
- O título do bloco não é personalizável nesta feature.
- O agente de implementação está autorizado a propor e implementar o ícone SVG, a apresentação
  visual inicial, os textos em inglês e a tradução pt_BR. Não existe ativo prévio. A paleta própria
  limita-se ao ativo, sem redefinir cores do tema. A aprovação final é do proprietário do produto,
  após as evidências visuais da implementação real.
- A avaliação estruturada com cinco docentes está fora do escopo desta feature e pertence à
  Feature 003 — Experiência visual.
- O aplicativo móvel oficial da plataforma está fora do escopo. "Dispositivos móveis" significa o
  navegador em telas pequenas.
- Os "temas padrão" usados na validação são os temas distribuídos com o Moodle 4.5. A validação em
  temas institucionais não é necessária, pois basta não depender deles.
- O pacote de idioma pt_br oficial está instalado nos ambientes de desenvolvimento e homologação.
  Esse é um pré-requisito do regime de desenvolvimento do Princípio VIII.
- As evidências (CA-13) são registradas no diretório desta feature.
- A estratégia de geração do pacote distribuível e a exclusão de `translations/` seguem o Princípio
  VIII. Sua forma técnica é decisão do plano.
- Esta feature não antecipa a Feature 002: não há página de orientações, coleta, tarefas agendadas,
  tabelas de dados nem cache.
