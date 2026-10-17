# Feature Specification: Course Storage Diagnostics

**Feature Branch**: `002-course-storage-diagnostics` (integração à `main` só após os quality gates)

**Created**: 2026-10-16

**Status**: Clarified (pronta para `/speckit-plan`)

**Input**: User description: "Feature 002 — Course Storage Diagnostics: primeiro mecanismo de análise
efetiva do armazenamento associado às salas. Identificar, calcular e organizar informações confiáveis
sobre os arquivos de uma sala, distinguindo categorias de uso e considerando a deduplicação do
Moodle, com processamento fora da requisição da página, resultados reutilizáveis pelas próximas
features e apresentação mínima. Sem recomendações, pontuação, histórico ou painel administrativo."

**Constituição de referência**: 1.2.0

**Categorias de indicador exibidas (Princípio IV)**: esta feature calcula **Armazenamento lógico** e
**Conteúdo único**, e exibe ao professor apenas o Armazenamento lógico. Ela MUST NOT calcular nem
exibir Consumo físico atribuível, Potencial teórico ou Economia observada.

**Dados de usuário lidos e armazenados (Princípio VII)**: a análise lê metadados de registros de
arquivos dos contextos da sala, incluindo os de envios de estudantes (tamanho, identificador de
conteúdo, componente, área e contexto). Ela MUST NOT ler o conteúdo dos arquivos nem o autor do
envio. Ela armazena apenas agregados por sala e por atividade e, para arquivos de grande porte que
não são envios, a ligação ao registro do arquivo (FR-016). Não armazena identificadores de usuários,
nomes de arquivos, nem identificadores de arquivos de envios de estudantes (FR-040 a FR-044).

## Estado da Feature 001 e dependências

Verificado em 2026-10-16 (`main` em `c6ceccf`):

- A Feature 001 está integrada à `main` (PR #3 "Feature 001: CourseCare plugin foundation" e PR #4,
  que registra a aprovação do ícone e dos textos pelo proprietário, ambos mesclados em 2026-10-16),
  versão `0.1.0` (`2026101600`), maturidade alfa, Moodle 4.5 apenas. A Feature 002 parte dessa base.
- **Disponível e reutilizado**: o bloco só pode ser adicionado na página principal da sala, uma vez
  por sala. A capability `block/coursecare:view` (contexto do bloco; por padrão professor com
  permissão de edição e gerente) controla quem vê o conteúdo, e a capability
  `block/coursecare:addinstance` controla quem adiciona o bloco. Há strings em `lang/en`, tradução em
  `translations/pt_br` com verificação de paridade, template Mustache do conteúdo, ícone decorativo,
  verificação do pacote distribuível e CI com `moodle-plugin-ci`.
- **Inexistente, a introduzir aqui**: tabelas próprias (a 001 não tem `db/install.xml` nem
  `db/upgrade.php`), configurações de administração (não há `settings.php`), tarefas, cache, e as
  camadas de **coleta** e **análise** (Princípio IX, resolução C6: introduzidas na primeira feature
  com a responsabilidade correspondente). A camada de **regras de recomendação** continua inexistente,
  porque esta feature não tem recomendações.
- **A rever**: a declaração de privacidade (`null_provider` com a frase "não armazena dados
  pessoais") passa a ser avaliada contra os dados efetivamente armazenados (FR-044). A mensagem atual
  do bloco ("em breve você encontrará orientações...") continua válida e coexiste com a apresentação
  mínima.
- **Nenhuma dependência pendente** da Feature 001 foi encontrada. A atualização a partir da `0.1.0`
  instalada MUST funcionar (FR-052).

## Definições

- **Sala**: um curso do Moodle. Os **contextos da sala** são o contexto do curso e todos os seus
  contextos descendentes (atividades e recursos, e blocos inseridos no curso ou nas atividades).
- **Registro de arquivo**: cada entrada da tabela de arquivos do Moodle. Um registro aponta para um
  **conteúdo**, identificado pelo seu hash de conteúdo. Vários registros, na mesma sala ou em salas
  diferentes, podem apontar para o mesmo conteúdo, que fica armazenado uma única vez no repositório
  de arquivos do Moodle (deduplicação).
- **Área de arquivos**: a combinação de componente (por exemplo, o módulo Recurso) e área (por
  exemplo, "conteúdo") à qual o registro pertence. Ela é o principal indício da finalidade do arquivo.
- **Referência (atalho)**: registro que aponta para um arquivo de outro lugar, em vez de guardar
  conteúdo próprio. É **interna** quando a origem é outro arquivo do próprio Moodle (por exemplo,
  arquivos privados de um usuário ou arquivos de outra sala) e **externa** quando a origem é um
  repositório fora do Moodle. É **indeterminada** quando a origem ou o tamanho não podem ser
  determinados com segurança (por exemplo, origem marcada pelo Moodle como ausente). O tamanho
  registrado reflete a última sincronização com a origem.
- **Escopo do diagnóstico**: os registros de arquivo incluídos pela matriz da seção "Matriz de
  inclusão, exclusão e classificação".
- **Escopo principal**: o escopo do diagnóstico sem as referências (M16, M17) e sem o agregado de
  cópias de segurança e lixeira (M12, M13). Corresponde aos arquivos em uso pedagógico na sala e é a
  base dos indicadores A a E.
- **Agregado de cópias de segurança e lixeira**: medição separada das cópias de segurança e da
  lixeira registradas nos contextos da sala (FR-019a a FR-019d).

## Clarifications

### Session 2026-10-16

- Q: Quais salas o CourseCare deve analisar? → A: Opção A, com ressalvas: somente as salas com ao
  menos uma instância do bloco. Adicionar o bloco torna a sala elegível. Remover definitivamente a
  última instância encerra a elegibilidade, e os diagnósticos da sala são eliminados sem processamento
  pesado na requisição. Desativar temporariamente o tipo de bloco não equivale a remover as
  instâncias. A seleção de salas fica separada do mecanismo de análise, para permitir outras políticas
  no futuro. Nenhuma análise de todas as salas do site nesta feature. As metas de desempenho se referem
  às salas elegíveis (FR-003, FR-003a a FR-003d, FR-044b, SC-004).
- Q: As cópias de segurança da sala e a lixeira devem entrar no volume total de arquivos da sala? →
  A: Opção B, com condições: o indicador principal representa os arquivos em uso pedagógico, sem
  backups e lixeira. Backups e lixeira formam um agregado separado, fora de A e B, apenas com registros
  associados à sala de forma confiável, com subtotais para backups e lixeira. Backups fora da estrutura
  analisada não são presumidos como medidos, e o agregado não é apresentado como o total de backups
  da sala nem como consumo físico adicional. Sem gerenciamento de backups ou lixeira nesta feature
  (M12, M12a, M13, M13a, FR-010, FR-011, FR-019a a FR-019d, CT-29 a CT-31).
- Q: Os atalhos (arquivos que apontam para outro arquivo do Moodle ou para um repositório externo)
  devem entrar no volume da sala? → A: Opção A, com condições: ficam fora de A e B. São registradas à
  parte as quantidades de referências internas, externas e indeterminadas, e o tamanho registrado
  quando disponível, apenas como informação, sem representar consumo físico. Referências internas a
  arquivos já contados não geram dupla contagem. O cache local de referências externas não é
  presumido a partir do tamanho registrado. Não há download nem sincronização para calcular. Origem ou
  tamanho incertos ficam como indeterminados. Caminhos, identificadores e metadados de arquivos
  privados não são expostos. A apresentação fica separada do armazenamento pedagógico principal
  (M16, M17, FR-019e a FR-019h, CT-09, CT-32).
- Q: A partir de que tamanho um arquivo deve ser considerado "de grande porte" por padrão? → A: Opção A:
  50 MB (50 × 1.048.576 bytes), configurável pelo administrador para todo o site, sem limite por sala.
  Arquivos com tamanho igual ou superior ao limite são de grande porte. A identificação é apenas
  informativa, sem avaliação negativa nem recomendação, e respeita a deduplicação. Uma mudança no
  limite vale a partir da próxima análise, sem alterar arquivos. O valor é um parâmetro inicial de
  triagem, não um limite pedagógico nem uma referência universal (FR-015, FR-015a a FR-015c, CT-33 a
  CT-35).
- Q: O professor deve poder pedir, pelo bloco, uma nova análise da sala nesta feature? → A: Opção A:
  sem botão de atualização. Ao adicionar o bloco, a primeira análise é solicitada automaticamente e
  processada em segundo plano, sem bloquear a página. A reanálise periódica é diária por padrão, e o
  administrador pode solicitar uma análise pela linha de comando. Não há pedidos duplicados nem
  análises concorrentes da mesma sala. Até a primeira análise, o bloco mostra o estado correspondente,
  sem indicadores. Resultados desatualizados são identificados explicitamente. O funcionamento não
  depende do professor, e a política de elegibilidade é respeitada. Botão manual e detecção automática
  de mudanças ficam para avaliação futura (Feature 003 ou posterior). Enfileiramento, concorrência e
  agendamento são definidos no plano, com mecanismos nativos do Moodle (FR-036, FR-036a, FR-038,
  FR-039c, FR-039d).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Conhecer o volume de arquivos da sala (Priority: P1)

O professor autorizado abre a página principal da sala e o bloco informa, em linguagem simples, o
volume de arquivos associado à sala e quando essa informação foi calculada. Ele não precisa fazer
nada para que o diagnóstico aconteça.

**Why this priority**: é a primeira informação útil do produto e a base de todas as features
seguintes. Sem ela, nenhuma orientação futura tem sustentação.

**Independent Test**: em uma sala com o bloco e arquivos conhecidos, executar o processamento em
segundo plano e verificar que o bloco mostra o volume esperado, com data, sem que a página tenha
analisado os arquivos.

**Acceptance Scenarios**:

1. **Given** uma sala com o bloco e três arquivos de 1 MB, 2 MB e 3 MB em materiais, **When** o
   processamento em segundo plano conclui, **Then** o bloco mostra 6 MB como volume de arquivos da
   sala, rotulado como volume registrado (e não como espaço ocupado no servidor), com a data e a hora
   da análise.
2. **Given** uma sala com o bloco que ainda não foi analisada, **When** o professor abre a página,
   **Then** o bloco informa que o diagnóstico ainda não está disponível, sem mostrar zero e sem
   avaliar a sala.
3. **Given** uma sala analisada que não tem nenhum arquivo no escopo principal, **When** o professor
   abre a página, **Then** o bloco informa que a análise concluída não encontrou arquivos nos materiais
   e nas atividades da sala, com a data.

---

### User Story 2 - Receber informações confiáveis (Priority: P1)

Os números representam corretamente o que o Moodle registra. Um mesmo conteúdo usado em vários
lugares não é tratado como várias cópias físicas, e nenhum número é apresentado como espaço ocupado
exclusivamente pela sala ou como economia.

**Why this priority**: o Princípio IV é inegociável. Um número errado na primeira feature compromete
a confiança em todas as seguintes.

**Independent Test**: com dados de teste controlados (o mesmo conteúdo em vários lugares da sala e
em outra sala, referências e áreas excluídas), comparar os indicadores calculados com valores
esperados calculados à mão.

**Acceptance Scenarios**:

1. **Given** um arquivo de 10 MB anexado em três recursos da mesma sala, **When** a sala é
   analisada, **Then** o armazenamento lógico é 30 MB e o conteúdo único é 10 MB.
2. **Given** o mesmo conteúdo de 10 MB presente na sala A e na sala B, **When** as duas são
   analisadas, **Then** cada uma registra 10 MB de conteúdo único, e nenhuma das duas o apresenta como
   espaço exclusivo seu.
3. **Given** uma referência a um arquivo de repositório externo, **When** a sala é analisada,
   **Then** ela é contada à parte como referência e não entra no armazenamento lógico nem no conteúdo
   único.

---

### User Story 3 - Preservar o desempenho (Priority: P1)

O administrador instala a nova versão em um site com milhares de salas. As análises acontecem em
segundo plano, em lotes limitados, sem varreduras durante o carregamento de páginas e sem
processamentos duplicados.

**Why this priority**: o Princípio VI exige que o assistente não degrade o ambiente. Uma análise
correta mas custosa não pode ser entregue.

**Independent Test**: em um ambiente com volume representativo (milhares de salas elegíveis, uma sala
com dezenas de milhares de registros), medir a renderização do bloco, a duração e a memória de cada
execução e a ausência de análises concorrentes da mesma sala.

**Acceptance Scenarios**:

1. **Given** uma sala com o bloco, **When** a página é carregada, **Then** nenhuma consulta à tabela
   de arquivos é feita durante a renderização.
2. **Given** milhares de salas elegíveis, **When** uma execução do processamento em segundo plano
   ocorre, **Then** ela analisa apenas um lote limitado de salas e respeita o limite de tempo por
   execução.
3. **Given** duas solicitações de análise da mesma sala ao mesmo tempo, **When** o processamento
   ocorre, **Then** a sala é analisada uma única vez.

---

### User Story 4 - Preservar a privacidade (Priority: P1)

O administrador confia que o diagnóstico não expõe arquivos, nomes de arquivos nem dados de
estudantes, e que ninguém consulta o diagnóstico de uma sala sem a permissão adequada.

**Why this priority**: o Princípio VII é inegociável. A análise lê metadados de todos os arquivos da
sala, inclusive envios de estudantes.

**Independent Test**: inspecionar os dados persistidos e a saída do bloco e da ferramenta
administrativa, e tentar acessar o diagnóstico de outra sala ou sem permissão.

**Acceptance Scenarios**:

1. **Given** uma sala com envios de estudantes, **When** ela é analisada, **Then** os dados
   persistidos contêm apenas totais por sala e por atividade, sem identificador de usuário e sem nome
   ou identificador de arquivo enviado por estudante.
2. **Given** um estudante ou um professor sem permissão de edição na sala, **When** ele abre a página
   da sala, **Then** ele não vê o diagnóstico.
3. **Given** um professor autorizado na sala A, **When** ele tenta obter o diagnóstico da sala B
   alterando parâmetros, **Then** nada da sala B é exibido.

---

### User Story 5 - Saber se a informação é atual (Priority: P2)

O professor sabe quando o diagnóstico foi realizado e se ele pode não refletir a situação atual
(análise antiga, falha na última atualização ou processamento parado).

**Why this priority**: a resolução C4 da constituição exige comunicar a atualidade, nunca simulá-la.
Ela depende de US1 existir.

**Independent Test**: provocar cada estado (não analisado, aguardando, em andamento, concluído,
desatualizado, falha, sem arquivos, cobertura parcial) e verificar a mensagem do bloco em cada um.

**Acceptance Scenarios**:

1. **Given** um diagnóstico concluído há mais tempo que o limite de atualidade, **When** o professor
   abre a página, **Then** o bloco mostra o valor anterior com sua data e indica que ele pode estar
   desatualizado.
2. **Given** um diagnóstico concluído e uma nova tentativa que falhou, **When** o professor abre a
   página, **Then** o bloco mantém o último resultado válido, com sua data, e indica que a
   atualização mais recente não foi concluída.

---

### User Story 6 - Entender de onde vem o volume (Priority: P2)

O diagnóstico distribui o volume da sala entre categorias confiáveis (materiais da sala, envios em
atividades, outros arquivos associados e arquivos não classificados), baseadas no componente e na
área de cada arquivo, e não no usuário que o enviou nem no nome ou extensão do arquivo.

**Why this priority**: é o insumo central das orientações da Feature 004. Nesta feature os dados
ficam disponíveis e verificáveis, mas a apresentação completa é da Feature 003.

**Independent Test**: em uma sala com arquivos em cada área da matriz, verificar a categoria e o
volume de cada uma no resultado persistido (testes automatizados e ferramenta administrativa).

**Acceptance Scenarios**:

1. **Given** um arquivo em um recurso do tipo Arquivo e um arquivo enviado por um estudante em uma
   tarefa, **When** a sala é analisada, **Then** o primeiro conta em "Materiais da sala" e o segundo em
   "Envios em atividades".
2. **Given** um arquivo em uma área de um componente de terceiros não reconhecido, **When** a sala é
   analisada, **Then** ele conta em "Não classificado", e o resultado indica cobertura parcial da
   classificação.
3. **Given** um conteúdo idêntico em um material e em um envio, **When** a sala é analisada,
   **Then** ele conta no volume das duas categorias, e o conteúdo único da sala o conta uma vez.

---

### User Story 7 - Identificar atividades e arquivos de grande porte (Priority: P3)

O diagnóstico identifica as atividades que concentram mais volume e os arquivos acima de um limite
objetivo de tamanho, sem tratar o tamanho como mau uso.

**Why this priority**: é útil para as features seguintes, mas depende de US1, US2 e US6 corretos.

**Independent Test**: em uma sala com atividades de volumes diferentes e arquivos acima e abaixo do
limite, verificar a ordenação das atividades e a contagem de arquivos de grande porte no resultado.

**Acceptance Scenarios**:

1. **Given** três atividades com 100 MB, 10 MB e 1 MB de arquivos, **When** a sala é analisada,
   **Then** o resultado lista as três com seus volumes, e a de 100 MB aparece com o maior volume.
2. **Given** um limite de grande porte de 50 MB e envios de estudantes de 60 MB e 70 MB em uma tarefa,
   **When** a sala é analisada, **Then** o resultado registra, para essa tarefa, 2 arquivos de grande
   porte com 130 MB, sem identificar os arquivos nem os estudantes.

---

### Edge Cases

- Sala sem nenhum registro de arquivo, ou só com registros fora do escopo principal (diretórios,
  rascunhos, referências, cópias de segurança e lixeira): estado "sem arquivos", valor zero, com data.
  Referências e o agregado de cópias de segurança e lixeira continuam registrados à parte.
- Arquivos de tamanho zero: entram na contagem, não alteram os volumes.
- Referência cuja origem desapareceu (registro marcado pelo Moodle como origem ausente): contada à
  parte como referência com tamanho indeterminado.
- Arquivo em contexto de bloco inserido na sala (por exemplo, bloco de texto com imagem): pertence à
  sala e é classificado pela matriz.
- Atividade oculta ou com restrição de acesso: seus arquivos entram nos totais. Nesta feature, o nome
  da atividade não é exibido a ninguém além do administrador (ferramenta administrativa).
- Atividade em processo de exclusão assíncrona, ou movida para a lixeira: os arquivos que continuam
  registrados nos contextos da sala são tratados pela matriz (a cópia na lixeira entra no agregado
  de cópias de segurança e lixeira, fora do volume principal).
- Sala excluída depois de ter diagnóstico: os dados próprios da sala são removidos, e análises
  pendentes dela são descartadas sem erro.
- Bloco removido da sala: a sala deixa de ser elegível, as análises pendentes são descartadas e o
  diagnóstico persistido é removido sem processamento pesado na requisição.
- Tipo de bloco desativado pelo administrador, ou instância ocultada na sala: a sala continua elegível
  e o diagnóstico é mantido. Ao reativar, o bloco mostra o resultado existente com sua data.
- Bloco removido e adicionado de novo antes da limpeza: a sala volta a ser elegível e nunca exibe
  dados de uma análise anterior à remoção.
- Sala restaurada ou importada: é tratada como sala nova (ainda não analisada) ou com resultado
  desatualizado, nunca com o diagnóstico de outra sala.
- Cópias de segurança automáticas guardadas fora do repositório de arquivos do Moodle (em diretório
  configurado pelo administrador): não são vistas pela análise; a limitação é documentada.
- Processamento em segundo plano desativado ou parado: o bloco continua mostrando o último resultado
  com a data e passa a indicá-lo como desatualizado quando o limite de atualidade é excedido; a página
  nunca inicia uma análise.
- Mudança de arquivos durante a análise: o resultado reflete um retrato consistente o bastante para
  não contar o mesmo registro duas vezes; a diferença é corrigida na próxima análise.
- Atualização do plugin a partir da `0.1.0`: todas as salas com bloco começam como "ainda não
  analisada".

## Matriz de inclusão, exclusão e classificação

Base: código-fonte do Moodle 4.5.15 (`MOODLE_405_STABLE`). Cada linha MUST ser reverificada no
planejamento técnico contra o código do componente responsável, e o conjunto suportado MUST ser
mantido como uma lista explícita e testada (FR-021, FR-025). Identificadores técnicos aparecem aqui
apenas para rastreabilidade; nunca na interface (Princípio II).

**Regra geral de escopo**: entram no escopo os registros de arquivo dos contextos da sala, exceto os
excluídos abaixo. Nenhum registro fora dos contextos da sala entra, mesmo que o conteúdo seja o mesmo.

| # | Situação | Onde o Moodle registra | Tratamento | Justificativa |
| --- | --- | --- | --- | --- |
| M1 | Entradas de diretório | Qualquer área; registro com nome de arquivo "." | **Excluir** | Representam pastas, não conteúdo; têm tamanho zero. |
| M2 | Rascunhos (uploads em edição) | Contexto do usuário, área de rascunho | **Excluir** (fora dos contextos da sala) | Pertencem ao usuário, são temporários e limpos pelo Moodle; não são da sala. |
| M3 | Arquivos privados e cópias de segurança do usuário | Contexto do usuário | **Excluir** (fora dos contextos da sala) | Pertencem ao usuário, não à sala, mesmo que tenham sido feitos a partir dela. |
| M4 | Áreas temporárias de componentes | Ex.: área temporária do módulo Pasta; áreas temporárias de conversão da anotação de PDF | **Excluir** | Dados transitórios do componente, sem finalidade pedagógica estável. |
| M5 | Materiais da sala no nível do curso | Curso: resumo, imagem do curso, seções | **Incluir** → Materiais da sala | Conteúdo publicado pelo professor na página da sala. |
| M6 | Recursos com arquivos | Arquivo (conteúdo), Pasta (conteúdo), Página (conteúdo), Livro (capítulos), Rótulo/área de texto e mídia (introdução) | **Incluir** → Materiais da sala | Contrato documentado de cada módulo: são materiais disponibilizados. |
| M7 | Descrição e anexos de atividades | Área de introdução de qualquer atividade suportada; anexos da descrição e da atividade na Tarefa | **Incluir** → Materiais da sala | Conteúdo de apresentação da atividade, publicado por quem a configura. |
| M8 | Envios de estudantes em tarefas | Tarefa: envio de arquivos; arquivos embutidos no texto online | **Incluir** → Envios em atividades | Contrato documentado dos subplugins de envio da Tarefa. |
| M9 | Retorno a envios | Tarefa: arquivos de retorno; PDF anotado final | **Incluir** → Outros arquivos associados | Produzidos no fluxo de avaliação; não são material nem envio do estudante. |
| M10 | Arquivos gerados para avaliação | Tarefa (anotação de PDF): PDF combinado, páginas convertidas, carimbos | **Incluir** → Outros arquivos associados | Gerados pelo Moodle a partir de envios; ocupam registro na sala, mas não são materiais. |
| M11 | Participação em fóruns | Fórum: anexos e imagens de mensagens | **Incluir** → Outros arquivos associados | Professores e estudantes publicam nas mesmas áreas; o componente não permite separar material de participação com segurança. |
| M12 | Cópias de segurança da sala | Componente de backup no contexto da sala ou de suas atividades: sala, seção, atividade, automáticas | **Agregado à parte** → Cópias de segurança e lixeira (subtotal "cópias de segurança"); fora de A, B e das categorias | Existem por causa da sala, mas não são arquivos em uso pedagógico. A associação é confiável porque o registro está em um contexto da sala. |
| M13 | Lixeira da sala | Lixeira, no contexto da sala (uma cópia de segurança por atividade excluída) | **Agregado à parte** → Cópias de segurança e lixeira (subtotal "lixeira"); fora de A, B e das categorias | Existe por causa da sala, é temporária e é limpa pelo Moodle; não é material em uso. O componente da lixeira separa com segurança esses registros dos backups. |
| M12a | Cópias de segurança feitas a partir da sala, mas guardadas no contexto de um usuário (por exemplo, sem dados de usuários ou anonimizadas) | Contexto do usuário, área de backup do usuário | **Excluir**; registrar como limitação | A ligação com a sala só poderia ser inferida pelo nome do arquivo, o que não é confiável (FR-022). |
| M13a | Lixeira de categoria (sala inteira excluída) | Contexto da categoria | **Excluir** | Não pertence a uma sala existente. |
| M14 | Banco de questões e banco de conteúdo da sala | Questões no contexto da sala; banco de conteúdo da sala | **Incluir** → Outros arquivos associados | Associados à sala, mas podem não estar em uso por nenhuma atividade. |
| M15 | Arquivos em blocos da sala | Contextos de blocos sob a sala ou suas atividades | **Incluir** → Não classificado, exceto componentes suportados na lista explícita | Componentes variados; só classifica quando o contrato for verificado. |
| M16 | Referências (atalhos) internas e externas | Qualquer área; registro que aponta para outro arquivo do Moodle ou para um repositório externo | **Contar à parte** (quantidade por tipo e tamanho registrado, quando disponível); fora de A, B, das categorias e das atividades | Não guardam conteúdo próprio; a origem pode estar na sala, em arquivos privados ou fora do Moodle, e o tamanho reflete a última sincronização. |
| M17 | Referência indeterminada | Registro de referência com origem marcada pelo Moodle como ausente, ou com origem ou tamanho que não podem ser determinados com segurança | **Contar à parte** como indeterminada, sem tamanho | O valor correto não pode ser determinado; nenhum valor artificial é produzido. |
| M18 | Componentes de terceiros ou áreas desconhecidas | Qualquer componente ou área fora da lista explícita | **Incluir** → Não classificado; marcar cobertura parcial | Ocupam registro na sala, mas a finalidade é desconhecida. Nunca são presumidos material do professor. |
| M19 | Cópias de segurança automáticas guardadas em diretório externo | Fora do repositório de arquivos do Moodle | **Fora de alcance**; registrar como limitação | Não aparecem nos registros de arquivo. |

## Requirements *(mandatory)*

### Functional Requirements

**Escopo e elegibilidade**

- **FR-001**: O diagnóstico MUST considerar apenas os registros de arquivo dos contextos da sala
  (contexto do curso e descendentes), aplicando a matriz de inclusão e exclusão.
- **FR-002**: O diagnóstico MUST NOT considerar registros de contextos de usuário, de outras salas,
  de categorias ou do site, mesmo quando compartilharem conteúdo com a sala.
- **FR-003**: São elegíveis para análise somente as salas com ao menos uma instância do bloco
  CourseCare. Adicionar o bloco torna a sala elegível. A sala deixa de ser elegível quando a última
  instância do bloco é removida definitivamente. O sistema MUST NOT analisar salas sem o bloco nesta
  feature.
- **FR-003a**: Desativar o tipo de bloco na administração do site, ou ocultar a instância na sala,
  MUST NOT ser tratado como remoção. A sala continua elegível e seus diagnósticos são mantidos.
- **FR-003b**: A elegibilidade MUST ser verificada novamente imediatamente antes de cada análise. Uma
  análise pendente de sala que deixou de ser elegível MUST ser descartada sem erro e sem gravar
  resultado.
- **FR-003c**: A regra de seleção das salas elegíveis MUST ficar separada do mecanismo de análise e
  da interface, de modo que outra política de seleção possa ser adotada no futuro sem refazer a
  análise. Nesta feature existe apenas a política "salas com o bloco".
- **FR-003d**: As metas de desempenho e escalabilidade (SC-003, SC-004) referem-se às salas elegíveis,
  não ao total de salas do site.
- **FR-004**: Quando uma área não puder ser analisada com segurança, o resultado MUST registrar a
  limitação (cobertura parcial) em vez de apresentar o total como completo.

**Indicadores**

- **FR-010 (Indicador A — Armazenamento lógico)**: O sistema MUST calcular a soma dos tamanhos
  registrados dos arquivos do escopo principal, ou seja, excluindo referências (M16, M17) e o agregado de
  cópias de segurança e lixeira (M12, M13). O valor representa o volume lógico dos arquivos em uso
  pedagógico associados à sala e MUST NOT ser apresentado como consumo físico.
- **FR-011 (Indicador B — Conteúdo único)**: O sistema MUST calcular a soma dos tamanhos dos conteúdos
  distintos do escopo principal, contando uma única vez cada conteúdo identificado pelo mecanismo de
  identificação de conteúdo do Moodle, independentemente de quantos registros, áreas ou categorias o
  referenciam dentro da sala.
- **FR-012**: O sistema MUST NOT apresentar o Conteúdo único como espaço físico ocupado exclusivamente
  pela sala, porque o mesmo conteúdo pode existir em outras salas ou áreas do site.
- **FR-013 (Indicador C — Distribuição por categoria)**: Para cada categoria (Materiais da sala,
  Envios em atividades, Outros arquivos associados, Não classificado), o sistema MUST calcular a
  quantidade de arquivos, o volume lógico e o conteúdo único da categoria.
- **FR-014**: A soma dos volumes lógicos das categorias MUST ser igual ao Armazenamento lógico da
  sala. A soma dos conteúdos únicos das categorias MAY ser maior que o Conteúdo único da sala, quando
  um conteúdo aparece em mais de uma categoria; essa diferença MUST ser explicada na documentação e
  MUST NOT ser apresentada como erro.
- **FR-015 (Indicador D — Arquivos de grande porte)**: O sistema MUST identificar como de grande
  porte os arquivos do escopo principal com tamanho igual ou superior ao limite de grande porte. Para
  cada atividade e para o nível da sala, MUST registrar a quantidade de registros e o volume lógico
  desses arquivos, e também a quantidade de conteúdos distintos e o conteúdo único deles, para que um
  mesmo conteúdo usado em vários lugares não pareça vários arquivos grandes.
- **FR-015a**: O limite de grande porte MUST ser uma configuração única do site, ajustável pelo
  administrador, com padrão de 50 MB, na convenção de tamanho do Moodle (50 × 1.048.576 =
  52.428.800 bytes). Esta feature MUST NOT oferecer limites por sala.
- **FR-015b**: Uma mudança no limite MUST valer a partir da próxima análise de cada sala, sem alterar
  arquivos e sem exigir reprocessamento imediato. Cada diagnóstico MUST registrar o limite usado no
  seu cálculo.
- **FR-015c**: A identificação de grande porte MUST ser apenas informativa: MUST NOT gerar avaliação
  negativa, alerta ou recomendação nesta feature. A documentação MUST apresentar o valor padrão, a
  unidade, a finalidade (triagem inicial) e as limitações (não é limite pedagógico nem referência
  universal de uso adequado).
- **FR-016**: Arquivos de grande porte em Envios em atividades MUST ser registrados apenas como
  quantidade e volume por atividade. Para as demais categorias, o resultado MAY guardar a ligação
  com o arquivo, para que uma feature futura o localize, mas MUST NOT guardar cópia do nome.
- **FR-017**: Nenhum texto do diagnóstico MUST apresentar o tamanho de um arquivo como uso
  inadequado.
- **FR-018 (Indicador E — Concentração por atividade)**: O sistema MUST calcular, para cada atividade
  da sala com arquivos no escopo principal, a quantidade de arquivos, o volume lógico e o volume por
  categoria. Os arquivos do contexto do curso e dos blocos da sala MUST ser agregados à parte, como
  "nível da sala", nunca atribuídos a uma atividade.
- **FR-019**: Um arquivo só é atribuído a uma atividade quando seu contexto é o da própria atividade
  ou um descendente dele.
- **FR-019a (Agregado de cópias de segurança e lixeira)**: O sistema MUST calcular, por sala e à parte
  dos indicadores A a E, a quantidade de arquivos, o volume lógico e o conteúdo único das cópias de
  segurança (M12) e da lixeira (M13), com um subtotal para cada uma e um conteúdo único do conjunto,
  que conta uma vez o conteúdo presente nas duas (a lixeira pode guardar o mesmo conteúdo de um backup).
- **FR-019b**: O agregado MUST incluir apenas registros nos contextos da sala, cuja associação com ela é
  confiável. Ele MUST ser rotulado como "cópias registradas no Moodle para esta sala" e MUST NOT ser
  apresentado como o total de backups da sala, porque não cobre backups guardados no contexto de
  usuários (M12a) nem backups automáticos em diretório externo (M19).
- **FR-019c**: O agregado MUST NOT ser somado aos indicadores A ou B, MUST NOT ser apresentado como
  consumo físico adicional e MUST NOT ser apresentado como espaço que seria liberado.
- **FR-019d**: Esta feature MUST NOT oferecer gerenciamento, exclusão ou configuração de backups ou da
  lixeira. O bloco MUST NOT exibir o agregado; ele fica disponível para as próximas features e para a
  ferramenta administrativa.
- **FR-019e (Referências)**: O sistema MUST registrar à parte, por sala, a quantidade de referências
  internas, externas e indeterminadas e, para internas e externas, a soma dos tamanhos registrados
  pelo Moodle, quando disponíveis. Referências MUST NOT entrar nos indicadores A a E nem no agregado de
  cópias de segurança e lixeira.
- **FR-019f**: O tamanho registrado de uma referência MUST ser tratado como informativo e MUST NOT ser
  apresentado como armazenamento ocupado pela sala, inclusive quando uma referência externa usar cópia
  local em cache. Uma referência interna cuja origem já é contada no escopo principal MUST NOT
  acrescentar volume a nenhum indicador.
- **FR-019g**: O sistema MUST NOT baixar, sincronizar ou atualizar referências para calcular os
  indicadores; ele usa apenas o que o Moodle já registrou. Quando a origem ou o tamanho não puderem
  ser determinados com segurança, a referência MUST ser contada como indeterminada, sem valor
  estimado.
- **FR-019h**: O resultado MUST NOT guardar nem exibir caminhos, nomes, identificadores ou metadados
  da origem de referências. As informações sobre referências MUST ficar separadas do armazenamento
  pedagógico principal e MUST NOT ser exibidas no bloco nesta feature.
- **FR-020 (Indicador F — Atualização)**: Cada diagnóstico MUST registrar o estado, a data e a hora
  da última análise concluída, a data e a hora da última tentativa e a existência de falha na última
  tentativa.

**Classificação**

- **FR-021**: A classificação MUST ser feita pela área de arquivos (componente e área) e pelo
  contexto, segundo uma lista explícita de áreas suportadas, derivada da matriz e verificada no
  código-fonte de cada componente.
- **FR-022**: A classificação MUST NOT depender do usuário registrado no arquivo, do nome do arquivo
  ou da extensão, nem ler o conteúdo dos arquivos.
- **FR-023**: Áreas fora da lista explícita MUST ser classificadas como "Não classificado" e contadas
  nos indicadores A e B, nunca presumidas como material do professor.
- **FR-024**: O resultado MUST indicar a proporção (quantidade e volume) de arquivos não
  classificados, para que a cobertura da classificação seja conhecida.
- **FR-025**: O conjunto inicial suportado é o dos componentes nativos da matriz (curso, Arquivo,
  Pasta, Página, Livro, Rótulo/área de texto e mídia, Tarefa e seus subplugins nativos de envio e
  retorno, Fórum, banco de questões e banco de conteúdo), mais backup e lixeira, que vão para o
  agregado à parte. A ampliação para outros componentes MUST ser possível acrescentando entradas à
  lista, sem alterar as regras de cálculo.

**Rigor dos cálculos**

- **FR-030**: Cada indicador MUST ter metodologia documentada: o que soma, o que exclui, o que conta
  uma única vez e o que não representa.
- **FR-031**: Todos os indicadores desta feature são medidos a partir dos registros do Moodle. O
  sistema MUST NOT produzir estimativas; quando um valor não puder ser determinado (por exemplo,
  referência com origem ausente), ele MUST ser registrado como indeterminado e nunca como zero.
- **FR-032**: O sistema MUST NOT calcular consumo físico atribuível, economia, projeções ou
  indicadores ambientais.
- **FR-033**: Nenhum indicador MUST ser apresentado como espaço que seria liberado pela remoção de
  arquivos.

**Processamento e atualização**

- **FR-034**: A renderização do bloco e de qualquer página MUST NOT consultar os registros de
  arquivos nem calcular indicadores; ela MUST apenas ler resultados pré-calculados.
- **FR-035**: As análises MUST ocorrer em processamento em segundo plano, em lotes limitados por
  execução (quantidade de salas e tempo), sem exigir a análise de todas as salas em cada execução.
- **FR-036**: Ao adicionar o bloco a uma sala, o sistema MUST solicitar automaticamente a primeira
  análise, processada em segundo plano, sem bloquear nem atrasar o carregamento da página. Uma sala
  elegível sem diagnóstico e sem solicitação pendente (por exemplo, após a atualização a partir da
  `0.1.0` ou após uma solicitação descartada) MUST ser colocada na fila na próxima execução periódica.
- **FR-036a**: O funcionamento do diagnóstico MUST NOT depender de nenhuma ação do professor.
- **FR-037**: As salas elegíveis MUST ser reanalisadas periodicamente, com intervalo configurável pelo
  administrador (padrão: uma vez por dia), priorizando as que estão há mais tempo sem análise.
- **FR-038**: Um diagnóstico concluído MUST ser considerado desatualizado quando sua idade exceder o
  limite de atualidade (padrão: 72 horas, configurável). O bloco MUST distinguir explicitamente um
  resultado disponível de um resultado atual (estados D e E). Esta feature MUST NOT implementar
  detecção automática de mudanças nos arquivos da sala; um arquivo adicionado ou removido aparece na
  próxima análise.
- **FR-039**: O sistema MUST impedir análises simultâneas da mesma sala e MUST NOT enfileirar a mesma
  sala mais de uma vez.
- **FR-039a**: Uma análise que falhar MUST preservar o último resultado válido, registrar a falha sem
  dados pessoais e não interromper a análise das demais salas. Novas tentativas MUST ser limitadas.
- **FR-039b**: Uma análise interrompida (por exemplo, por limite de tempo ou queda do processo) MUST
  poder ser retomada ou refeita sem deixar resultado parcial apresentado como completo.
- **FR-039c**: O administrador MUST poder solicitar a análise de uma sala específica ou de todas as
  salas elegíveis por ferramenta de linha de comando, que também enfileira em vez de processar tudo
  de uma vez quando o volume for grande. A solicitação respeita a elegibilidade (FR-003) e não cria
  pedido duplicado para uma sala que já tem análise pendente (FR-039).
- **FR-039d**: Esta feature MUST NOT oferecer ao professor um controle para solicitar nova análise. A
  atualização pela interface poderá ser avaliada na Feature 003.

**Estados do diagnóstico**

- **FR-045**: O diagnóstico de uma sala MUST estar em exatamente um dos estados abaixo, e o bloco MUST
  exibir uma mensagem própria para cada um:

  | Estado | Quando | O que o bloco mostra |
  | --- | --- | --- |
  | A. Ainda não analisado | Sala elegível sem resultado e sem análise na fila | "Diagnóstico ainda não disponível"; nenhum número |
  | B. Aguardando processamento | Análise na fila e nenhum resultado anterior | "Diagnóstico em preparação"; nenhum número |
  | C. Em andamento | Análise em execução e nenhum resultado anterior | Igual a B |
  | D. Concluído | Último resultado dentro do limite de atualidade | Volume de arquivos da sala e data |
  | E. Desatualizado | Último resultado além do limite de atualidade | Volume anterior, data e aviso de possível desatualização |
  | F. Falha | Última tentativa falhou | Com resultado anterior: volume anterior, data e aviso de que a atualização não foi concluída. Sem resultado anterior: "Diagnóstico ainda não disponível" |
  | G. Sem arquivos | Análise concluída sem arquivos no escopo principal (referências e o agregado de cópias de segurança e lixeira podem existir, medidos à parte) | Mensagem de que não foram encontrados arquivos nos materiais e nas atividades da sala, com data |
  | H. Parcialmente suportado | Análise concluída com arquivos não classificados ou valores indeterminados | Igual a D ou G; a limitação fica registrada no resultado (a apresentação detalhada é da Feature 003) |

- **FR-046**: Quando existir resultado anterior, os estados B e C MUST continuar exibindo o resultado
  anterior com sua data (estado D ou E), porque a nova análise ainda não terminou.
- **FR-047**: A ausência de dados MUST NOT ser apresentada como zero. Zero só pode aparecer no estado G.
- **FR-048**: Nenhum estado MUST emitir avaliação positiva ou negativa da sala.

**Apresentação mínima e integração com a Feature 001**

- **FR-049**: O bloco MUST continuar visível apenas na página principal da sala e apenas para quem
  tem a capability de visualização da Feature 001. Esta feature MUST NOT criar novas capabilities para
  o professor.
- **FR-050**: O bloco MUST exibir no máximo um indicador (o Armazenamento lógico), em linguagem simples
  e com rótulo que não sugira espaço físico ocupado, acompanhado da data da análise. Ele MUST NOT
  exibir tabelas, gráficos, categorias ou listas de atividades nesta feature.
- **FR-051**: A identidade visual, as mensagens da Feature 001 e a acessibilidade (WCAG 2.2 AA
  aplicável, verificação Behat de acessibilidade) MUST ser preservadas. Toda string nova MUST existir
  em `lang/en` e em `translations/pt_br`, com paridade de chaves e parâmetros.
- **FR-052**: A atualização a partir da versão `0.1.0` MUST funcionar pelo fluxo nativo, e a
  desinstalação MUST remover todos os dados próprios do plugin.
- **FR-053**: Os resultados MUST ficar disponíveis por um ponto de acesso interno único, para que as
  Features 003 e 004 os leiam sem refazer a análise.
- **FR-054**: A ferramenta administrativa de linha de comando MUST permitir ao administrador
  consultar o resultado completo de uma sala (todos os indicadores, categorias, atividades, agregado
  de cópias de segurança e lixeira, referências e limitações), para verificação. Ela MUST NOT exibir nomes de arquivos de envios de estudantes nem
  identificadores de usuários.

**Segurança e privacidade**

- **FR-040**: O sistema MUST NOT modificar, mover, copiar ou remover qualquer arquivo, nem ler seu
  conteúdo.
- **FR-041**: Toda exibição de diagnóstico MUST verificar login, contexto e capability. A sala exibida
  MUST ser sempre a do próprio bloco, nunca uma sala indicada por parâmetro.
- **FR-042**: Os dados persistidos MUST conter apenas agregados por sala e por atividade, as
  limitações, os estados e, para arquivos de grande porte que não são envios, a ligação ao registro
  do arquivo (FR-016). MUST NOT conter identificadores de usuários, nomes de arquivos, identificadores
  de arquivos de envios de estudantes, nem caminhos ou identificadores da origem de referências.
- **FR-043**: Os registros de falha MUST NOT conter dados pessoais nem nomes de arquivos.
- **FR-044**: A declaração da Privacy API MUST ser revisada para descrever com exatidão os dados
  armazenados. Se algum dado ligado a usuário for armazenado, o provider completo (metadados,
  exportação e exclusão) MUST ser implementado.
- **FR-044a**: A capability de visualização do CourseCare MUST NOT ser usada como autorização para
  exibir arquivos, nomes ou dados individuais de outros componentes. Nesta feature, o professor vê
  apenas o total da sala.
- **FR-044b**: Os dados de diagnóstico de uma sala MUST ser removidos quando a sala for excluída e
  quando a última instância do bloco for removida dela. A remoção MUST NOT executar processamento
  pesado durante a requisição do usuário: quando não for uma operação leve, ela é feita em segundo
  plano, e o diagnóstico deixa de ser exibido imediatamente.

**Documentação**

- **FR-055**: O README MUST explicar o escopo do diagnóstico, a metodologia de cada indicador, a
  matriz de inclusão e exclusão, os estados, as configurações, as limitações de cobertura e de
  escalabilidade e o que os números não significam (consumo físico exclusivo, economia).

### Key Entities *(include if feature involves data)*

- **Diagnóstico da sala**: o resultado vigente de uma sala. Atributos: sala, estado, data da última
  análise concluída, data da última tentativa, indicação de falha, indicadores A e B, quantidade de
  arquivos, quantidade de registros, volume lógico, quantidade de conteúdos distintos e conteúdo único
  dos arquivos de grande porte, proporção não classificada, quantidade de referências internas,
  externas e indeterminadas, tamanho registrado de referências internas e externas, quantidade de
  valores indeterminados e limite de grande porte usado. Um por sala.
- **Agregado de cópias de segurança e lixeira**: para um diagnóstico, quantidade de arquivos, volume
  lógico e conteúdo único das cópias de segurança e da lixeira, cada uma separadamente, e conteúdo
  único do conjunto. Sem nomes de arquivos e sem usuários.
- **Agregado por categoria**: para um diagnóstico e uma categoria, quantidade de arquivos, volume
  lógico e conteúdo único.
- **Agregado por atividade**: para um diagnóstico e uma atividade (ou o "nível da sala"), quantidade de
  arquivos, volume lógico, volume por categoria e, para os arquivos de grande porte, quantidade de
  registros, volume lógico, quantidade de conteúdos distintos e conteúdo único. Identifica a
  atividade, não seu nome.
- **Arquivo de grande porte de material**: ligação a um registro de arquivo de categoria diferente de
  "Envios em atividades", com tamanho e atividade. Sem nome e sem usuário.
- **Solicitação de análise**: sala, origem (automática, periódica ou administrador), momento e
  situação (na fila, em execução). No máximo uma pendente por sala.

## Cenários de teste

Cada cenário MUST ter teste automatizado, exceto quando indicado. Valores em MB usam 1 MB = 1.048.576
bytes nos dados de teste.

| ID | Cenário | Resultado esperado verificável |
| --- | --- | --- |
| CT-01 | Sala com o bloco e sem arquivos | Estado G; A = 0; B = 0; 0 arquivos; data registrada |
| CT-02 | Um arquivo de 1 MB em um recurso Arquivo | A = 1 MB; B = 1 MB; Materiais = 1 MB; 1 arquivo |
| CT-03 | Arquivos de 1, 2 e 3 MB em recursos diferentes | A = 6 MB; B = 6 MB; 3 arquivos; 3 atividades com 1, 2 e 3 MB |
| CT-04 | O mesmo conteúdo de 10 MB em três recursos da sala | A = 30 MB; B = 10 MB |
| CT-05 | O mesmo conteúdo de 10 MB nas salas X e Y | B de X = 10 MB; B de Y = 10 MB; nenhum texto afirma exclusividade |
| CT-06 | Arquivos em resumo do curso, seção, Arquivo, Pasta, Página, Livro e Rótulo | Todos em Materiais da sala, com os volumes inseridos |
| CT-07 | Envios de arquivo e texto online com imagem em uma tarefa | Volume em Envios em atividades, atribuído à tarefa; nenhum nome ou usuário persistido |
| CT-08 | Arquivos em retorno de tarefa e em fórum | Todos em Outros arquivos associados; soma das categorias = A |
| CT-09 | Referência interna a arquivo privado do professor (3 MB) e referência externa (4 MB registrados) | 1 interna e 1 externa, com 3 MB e 4 MB registrados; fora de A, B, categorias e atividades; nenhum caminho ou nome da origem persistido; nenhuma sincronização disparada |
| CT-10 | Diretórios, rascunho de usuário, arquivo privado e área temporária | Nenhum entra em A, B ou na contagem |
| CT-11 | Arquivo em componente de terceiros simulado | Em Não classificado; contado em A e B; resultado com cobertura parcial (estado H) |
| CT-12 | Arquivo de 5 MB adicionado após uma análise | O resultado anterior é mantido com sua data até a próxima análise; a próxima análise mostra A + 5 MB |
| CT-13 | Arquivo de 5 MB removido após uma análise | Idem, com A − 5 MB na próxima análise; nenhum texto chama a diferença de economia |
| CT-14 | Falha forçada durante a análise de uma sala com resultado anterior | Resultado anterior preservado; estado F com aviso; demais salas do lote analisadas |
| CT-15 | Resultado com idade acima do limite de atualidade | Estado E; valor anterior com data e aviso |
| CT-16 | Estudante, professor sem edição e professor de outra sala | Nenhum vê o diagnóstico; parâmetros manipulados não exibem outra sala |
| CT-17 | Sala com ao menos 50.000 registros de arquivo (teste de volume, com evidência registrada) | Análise concluída dentro do limite por execução; memória sem crescimento proporcional ao número de arquivos; valores corretos por amostragem conhecida |
| CT-18 | Duas solicitações simultâneas para a mesma sala | Uma única análise executada; uma única solicitação pendente |
| CT-19 | Atividade oculta e atividade com restrição de acesso, ambas com arquivos | Arquivos contados nos totais e no agregado da atividade; nenhum nome exibido no bloco |
| CT-20 | Exclusão da sala com diagnóstico e com análise pendente | Dados da sala removidos; análise pendente descartada sem erro |
| CT-21 | Conteúdo idêntico em um material e em um envio | Conta no volume das duas categorias; B da sala conta uma vez; soma dos B das categorias > B da sala, explicada |
| CT-22 | Arquivo de tamanho zero e referência com origem ausente | Arquivo zero contado sem volume; referência registrada como indeterminada, sem tamanho, nunca zero |
| CT-23 | Processamento em segundo plano parado | O bloco nunca inicia análise; passa a estado E após o limite de atualidade |
| CT-24 | Site com 5.000 salas elegíveis (teste de volume, com evidência registrada) | Cada execução processa um lote limitado; todas as salas analisadas dentro do intervalo de reanálise com a frequência padrão do processamento |
| CT-25 | Atualização a partir da `0.1.0` com blocos existentes | Atualização sem erros; salas com bloco em estado A ou B |
| CT-26 | Remoção do bloco de uma sala analisada, com análise pendente | Diagnóstico removido; análise pendente descartada sem gravar resultado; sala deixa de ser analisada |
| CT-27 | Tipo de bloco desativado e reativado pelo administrador | Diagnóstico mantido; a sala continua elegível; ao reativar, o bloco mostra o resultado com sua data |
| CT-28 | Site com salas sem o bloco | Nenhuma análise é feita para salas sem o bloco |
| CT-29 | Sala com 10 MB de materiais, um backup de sala de 40 MB e uma atividade excluída na lixeira de 5 MB | A = 10 MB; o bloco mostra 10 MB; agregado: cópias de segurança 40 MB, lixeira 5 MB; nenhum dos dois entra em A, B, categorias ou atividades |
| CT-30 | Conteúdo da lixeira idêntico ao de um backup da sala (mesmo conteúdo de 5 MB nos dois) | Subtotais: 5 MB em cada; conteúdo único do agregado = 5 MB |
| CT-31 | Backup da sala guardado no contexto do usuário que o fez | Não entra no agregado; o resultado registra a limitação de cobertura |
| CT-32 | Arquivo de 10 MB em um recurso e uma referência interna a ele em outro recurso da mesma sala | A = 10 MB; B = 10 MB; 1 referência interna com 10 MB registrados, sem alterar A ou B |
| CT-33 | Arquivos de 52.428.799 e 52.428.800 bytes com o limite padrão | Só o segundo é de grande porte |
| CT-34 | O mesmo conteúdo de 60 MB em dois recursos da sala | 2 registros de grande porte com 120 MB de volume lógico; 1 conteúdo distinto com 60 MB de conteúdo único |
| CT-35 | Limite alterado de 50 MB para 100 MB depois de uma análise | O resultado vigente mantém o limite de 50 MB registrado; a próxima análise usa 100 MB; nenhum arquivo é alterado |

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Em 100% dos cenários CT-01 a CT-13, CT-21, CT-22 e CT-29 a CT-35, os indicadores A a E,
  as contagens de referências e o agregado de cópias de segurança e lixeira coincidem exatamente, em
  bytes, com os valores esperados calculados à mão.
- **SC-002**: O carregamento da página principal de uma sala com o bloco não faz nenhuma consulta aos
  registros de arquivos e não acrescenta mais que 50 ms em relação ao bloco da Feature 001, medidos no
  ambiente de referência.
- **SC-003**: Uma sala com 50.000 registros de arquivo é analisada em uma única execução de até
  5 minutos, com pico de memória que não cresce proporcionalmente ao número de registros (evidência
  com 5.000 e 50.000 registros).
- **SC-004**: Em um site com 5.000 salas elegíveis e o processamento em segundo plano com a frequência
  padrão do Moodle, todas as salas têm diagnóstico concluído em até 24 horas, e nenhuma execução
  excede o limite de tempo configurado.
- **SC-005**: Em 100% das tentativas de acesso não autorizado (CT-16), nenhum dado de diagnóstico é
  exibido.
- **SC-006**: A inspeção dos dados persistidos após CT-07 e CT-19 não encontra identificador de
  usuário, nome de arquivo ou identificador de arquivo de envio de estudante.
- **SC-007**: Em 100% dos estados A a H, a mensagem exibida corresponde à tabela de FR-045, e nenhuma
  mostra zero fora do estado G.
- **SC-008**: Nenhum texto voltado ao usuário, em `en` e em `pt_br`, usa termos de infraestrutura
  ("contenthash", "blob", "filedir") ou apresenta os números como espaço físico exclusivo, economia
  ou mau uso; verificado na revisão de tom e rótulos exigida pela constituição.
- **SC-009**: Todos os quality gates da constituição passam no Moodle 4.5 (moodle-plugin-ci, PHPUnit,
  Behat com verificação de acessibilidade, paridade de strings e verificação do pacote), com testes
  automatizados para todos os cenários de deduplicação (CT-04, CT-05, CT-21).
- **SC-010**: Nenhum arquivo é criado, alterado ou removido pelo CourseCare: a comparação dos registros
  de arquivo antes e depois de uma análise completa de uma sala de teste é idêntica.

## Limitações reconhecidas

- **Não é consumo físico**: o Conteúdo único conta conteúdos distintos da sala, mas o mesmo conteúdo
  pode existir em outras salas, em arquivos privados ou em cópias de segurança de usuários. Esta
  feature não calcula quanto espaço seria liberado se a sala deixasse de existir.
- **Remoção não é economia**: um arquivo removido continua ocupando espaço até que o Moodle elimine o
  conteúdo sem nenhuma referência, e não ocupa espaço novo se outro registro usa o mesmo conteúdo.
- **Referências**: são contadas à parte e não entram no volume da sala. O tamanho registrado reflete a
  última sincronização com a origem, pode estar desatualizado e não indica se há cópia local em cache.
  O diagnóstico não sincroniza referências.
- **Cobertura da classificação**: apenas os componentes nativos da lista explícita são classificados.
  Componentes de terceiros aparecem como "Não classificado".
- **Fórum**: material do professor e participação dos estudantes não são distinguíveis pelo componente
  e ficam juntos em "Outros arquivos associados".
- **Cópias de segurança e lixeira**: medidas à parte, apenas quando registradas nos contextos da sala.
  Backups guardados no contexto de usuários e backups automáticos em diretório externo não são
  vistos, e o agregado não representa todos os backups da sala.
- **Atualidade**: os valores refletem o momento da última análise, nunca o instante atual.
- **Escalabilidade**: os limites efetivos (registros por sala, salas por execução) MUST ser medidos e
  documentados no plano e no README, com as evidências de CT-17 e CT-24.

## Fora do escopo

Recomendações educativas automáticas (Feature 004); apresentação completa, categorias, atividades e
gráficos para o professor (Feature 003); pontuação de sustentabilidade; avaliação qualitativa de
professores; rankings ou comparações entre salas; projeções de armazenamento futuro e potencial
teórico; simuladores de configuração; análise de limites de envio das tarefas; histórico
longitudinal (só o resultado vigente é guardado); painel administrativo institucional; consumo físico
atribuível e economia observada; exclusão, movimentação ou compressão de arquivos; integração com
serviços externos; gerenciamento, exclusão ou configuração de backups e da lixeira; botão de
atualização manual e detecção automática de mudanças nos arquivos; inteligência artificial
generativa; estimativa de emissões de carbono; alterações em formulários de atividades; classificação
de componentes de terceiros.

## Riscos

- **Custo de consulta** em salas com muitos registros e em sites com tabela de arquivos muito grande.
  Mitigação: análise por sala, em lotes, com custo avaliado no plano (Princípio VI).
- **Classificação errada** por contrato de componente mal interpretado. Mitigação: lista explícita
  verificada no código-fonte, testes por área e "Não classificado" como padrão seguro.
- **Interpretação errada pelos professores** de que o volume é espaço ocupado ou desperdício.
  Mitigação: rótulo cuidadoso, data sempre visível, nenhuma avaliação, revisão de tom.
- **Exposição indireta de envios** por agregados de atividades muito pequenas (por exemplo, uma tarefa
  com um único envio). Mitigação: nesta feature o professor vê apenas o total da sala; a regra de
  exibição de agregados por atividade será definida na Feature 003.
- **Concorrência e reentrância** das tarefas em clusters com vários processos de cron. Mitigação:
  FR-039 com testes de simultaneidade.
- **Variação entre bancos de dados** (PostgreSQL e MariaDB/MySQL) em consultas agregadas. Mitigação:
  consultas portáveis e CI com os dois bancos.

## Assumptions

- O diagnóstico se apoia exclusivamente nos metadados dos registros de arquivo e nas definições dos
  contextos do Moodle, sem acesso ao sistema de arquivos físico; por isso funciona com qualquer
  sistema de armazenamento suportado pelo Moodle.
- Intervalo de reanálise padrão de 24 horas e limite de atualidade padrão de 72 horas, ambos
  configuráveis, documentados como valores neutros.
- A apresentação de categorias, atividades e arquivos de grande porte ao professor é da Feature 003;
  aqui eles são verificáveis por testes automatizados e pela ferramenta administrativa.
- O Moodle 4.5 continua sendo a única versão declarada e validada (`$plugin->supported = [405, 405]`).
- Nenhum dado é enviado para fora do site; não há telemetria.
