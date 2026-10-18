# Feature Specification: Usage History

**Feature Branch**: `006-usage-history` (criada a partir de `main` em `c019a00`, que já integra 003, 004
e 005 pelos PRs #7, #8 e #9; as validações humanas pendentes dessas features continuam registradas nos
respectivos `tasks.md` e obrigatórias para o seu encerramento)

**Created**: 2026-10-18

**Status**: Clarified (decisões do proprietário do produto de 2026-10-18: Q1 A, Q2 A, Q3 A; padrões listados confirmados)

**Input**: User description: "Feature 006 — Usage History: permitir que o professor acompanhe a
evolução do armazenamento registrado na sua sala, reaproveitando as análises da Feature 002, sem nova
varredura de arquivos e sem cálculo pesado na exibição; mesmos indicadores e definições da 002; seção
'Evolução dos arquivos da sua sala' na página da 003, com gráfico simples e alternativa textual;
linguagem neutra; nenhum ponto fictício; retenção limitada; privacidade e desempenho para milhares de
salas."

**Constituição de referência**: 1.2.0

**Categorias de indicador (Princípio IV)**: o histórico só acompanha **Armazenamento lógico** (e as suas
categorias) e **Conteúdo único**, exatamente como a Feature 002 os define. Não introduz Consumo físico
atribuível, Economia observada nem Potencial teórico; o potencial da Feature 005 nunca entra no
histórico.

**Dados (Princípio VII)**: só totais agregados da sala, já calculados pela análise da 002, com a data da
análise. Nenhum dado por estudante, por arquivo ou por atividade; nenhum nome.

## Clarifications

### Session 2026-10-18

- Q: Granularidade e várias análises no mesmo dia? → A: um ponto por sala e por dia, com a última
  análise bem-sucedida do dia (FR-002).
- Q: Retenção? → A: 365 dias por padrão, ajustável pela administração do site entre 90 dias e 5 anos;
  pontos mais antigos removidos em lotes (FR-007, FR-009).
- Q: Histórico ao remover a última instância do bloco? → A: apagado junto com o resultado atual, pela
  mesma regra de elegibilidade da Feature 002 (FR-008).
- Padrões confirmados pelo proprietário do produto: indicadores guardados e exibidos (FR-010, FR-021,
  FR-022); primeiro ponto a partir do resultado atual na atualização (FR-006); período exibido igual à
  retenção (FR-026); posição da seção (FR-020); bloco inalterado (FR-027).

### Session 2026-10-19 (avaliação do proprietário do produto)

- Linguagem (T031): frases de variação com "o volume dos arquivos registrados" (preserva a diferença entre
  quantidade de arquivos e volume de armazenamento); a limitação sobre o início do histórico se refere ao
  início efetivo da coleta, não a uma versão do plugin (a versão 0.6.0 fica na documentação técnica).
- Visualização (T032): o gráfico mostra no máximo os últimos 90 dias até a última medição; o histórico
  completo retido continua na tabela; dias sem análise continuam como lacunas; sem filtros nem nova
  biblioteca (FR-026).
- Desempenho (SC-003): registrar uma verificação simples de carregamento real no navegador, em condições
  normais e com histórico extenso.

## Dependências e estado das features anteriores

| Feature | Situação | O que a 006 usa |
| --- | --- | --- |
| 002 Diagnóstico | Integrada | A análise em segundo plano, o resultado atual de cada sala e as suas regras de elegibilidade e de limpeza |
| 003 Experiência do professor | Integrada; validações humanas pendentes | A página "Sua sala em números", a direção visual, a divulgação progressiva |
| 004 Recomendações | Integrada; validações humanas pendentes | Nada muda; o histórico não gera sugestão |
| 005 Potencial das tarefas | Integrada; aprovações de tom e visual pendentes | Nada muda; o potencial fica fora do histórico |

## Como o diagnóstico funciona hoje (Feature 002)

- Cada sala elegível tem **um** resultado atual. Uma análise bem-sucedida o substitui inteiro, de uma
  vez; enquanto ela não termina, o resultado anterior continua valendo.
- Uma análise que falha não muda o resultado: o anterior é mantido e a falha é registrada.
- As salas são reanalisadas, por padrão, uma vez por dia (o intervalo pode ser reduzido até uma hora
  pela administração); a primeira análise acontece quando o bloco é adicionado; o administrador pode
  pedir ou rodar análises pela linha de comando; uma mudança no método de cálculo provoca nova análise.
- Um resultado com arquivos de áreas não reconhecidas é marcado como **cobertura parcial**.
- Quando a sala deixa de ser elegível (sala excluída ou última instância do bloco removida), o resultado
  é apagado pela tarefa periódica.
- Por isso, **hoje nenhuma medição passada é guardada**: o histórico precisa começar a ser registrado.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Ver como os arquivos registrados da sala evoluíram (Priority: P1)

Na página "Sua sala em números", o professor encontra a seção "Evolução dos arquivos da sua sala": uma
frase que diz, com dados reais, como o total mudou desde a análise anterior, um gráfico simples do
armazenamento lógico ao longo das medições e as datas de cada uma.

**Why this priority**: é o objetivo da feature: acrescentar a dimensão temporal ao diagnóstico.

**Independent Test**: numa sala com várias análises em dias diferentes, a seção mostra um ponto por dia
medido, com o valor igual ao resultado de cada análise, e a frase da variação correta.

**Acceptance Scenarios**:

1. **Given** uma sala analisada em três dias diferentes, com 120 MB, 150 MB e 140 MB, **When** o
   professor abre a página, **Then** vê três medições com as suas datas e a frase "Os arquivos
   registrados nesta sala diminuíram 10,0 MB desde a análise de [data] (de 150,0 MB para 140,0 MB)".
2. **Given** a mesma sala, **When** o professor lê a seção, **Then** encontra, sem abrir detalhes, que os
   valores são medições feitas em datas específicas e que aumento ou redução, por si só, não indicam
   problema nem melhoria.
3. **Given** a seção, **When** o professor usa leitor de tela ou não vê o gráfico, **Then** encontra os
   mesmos dados em texto (tabela com data e valores).

---

### User Story 2 - Ver a evolução por categoria e o conteúdo único (Priority: P2)

O professor pode ver como evoluíram os materiais da sala, os arquivos enviados pelos estudantes, os
outros arquivos e os não classificados, e o conteúdo único, sem que isso torne a página um painel.

**Why this priority**: responde "o volume de materiais aumentou?" e "como evoluíram os envios?", mas a
leitura principal (US1) já é útil sozinha.

**Independent Test**: com análises cujas categorias mudam, a tabela de dados mostra, por medição, cada
categoria e o conteúdo único iguais aos do resultado daquela análise; os envios aparecem só como total
da sala.

**Acceptance Scenarios**:

1. **Given** uma sala cujos materiais cresceram e cujos envios ficaram iguais, **When** o professor abre
   os dados da evolução, **Then** vê, por data, o valor de cada categoria, com o nome da categoria em
   texto (não só por cor).
2. **Given** a mesma tabela, **When** o professor lê o conteúdo único, **Then** ele aparece rotulado como
   tal, nunca somado ao armazenamento lógico.

---

### User Story 3 - Entender um histórico curto, com lacunas ou com mudanças de método (Priority: P1)

Quando o histórico ainda não permite comparar, tem dias sem análise, análises que falharam, cobertura
parcial ou mudança no modo de medir, a página explica isso em vez de inventar pontos.

**Why this priority**: honestidade dos indicadores (Princípio IV); sem ela, o histórico engana.

**Independent Test**: salas com nenhuma, uma e duas medições; com intervalo sem análise; com falha; com
cobertura parcial; com mudança de método: cada uma mostra a frase do caso e nenhum ponto inventado.

**Acceptance Scenarios**:

1. **Given** uma sala com uma única medição, **When** o professor abre a página, **Then** vê essa medição
   e a frase "A evolução aparece a partir da próxima análise", sem gráfico de tendência.
2. **Given** uma sala sem análise entre os dias 3 e 10, **When** o professor vê o gráfico e a tabela,
   **Then** nenhum valor aparece para esses dias e nada sugere que houve medição neles.
3. **Given** uma análise que falhou no dia 5, **When** o professor vê o histórico, **Then** o dia 5 não
   tem ponto; a falha aparece só como hoje, na abertura da página (Feature 003).
4. **Given** medições anteriores e posteriores a uma mudança no método de cálculo, **When** o professor vê
   a evolução, **Then** a mudança é indicada e a frase de variação não compara valores medidos de formas
   diferentes.

---

### Edge Cases

- **Sala recém-adicionada**: nenhuma medição ainda → a seção diz que a evolução aparecerá depois das
  análises (estados A e B da 002).
- **Várias análises no mesmo dia**: um ponto por dia, com a **última** análise bem-sucedida do dia
  (decisão Q1).
- **Tarefa reexecutada** ou **análise concorrente**: nunca dois pontos para a mesma sala e o mesmo dia; a
  análise já impede duas execuções simultâneas da mesma sala.
- **Falha**: nenhum ponto; o resultado anterior continua como hoje.
- **Cobertura parcial**: o ponto é registrado e marcado como parcial; a tabela indica isso.
- **Mudança de método** (nova versão do cálculo): o ponto guarda a versão; a interface não calcula
  variação entre versões diferentes e diz que o modo de medir mudou.
- **Mudança de configurações** (limiar de arquivos grandes, intervalo de análise): não afetam os
  indicadores do histórico (armazenamento lógico, categorias, conteúdo único).
- **Atualização do plugin**: o histórico começa a ser registrado na versão 0.6.0 (informação técnica; ao
  professor, a página fala do início efetivo da coleta); a medição atual de cada
  sala, já existente e datada, vira o primeiro ponto (é uma medição real, não uma reconstrução).
- **Sala sem arquivos**: medições de 0 bytes são reais e entram no histórico.
- **Dados desatualizados**: o último ponto mostra a sua data; a abertura da página continua avisando
  quando o resultado está desatualizado (003).
- **Sala excluída / bloco removido**: o histórico é apagado junto com o resultado atual, pela regra de
  limpeza da 002 (Q3); se o bloco voltar, o histórico recomeça do zero.
- **Restauração e cópia**: uma sala restaurada como nova sala ou copiada começa sem histórico; nenhum
  histórico é transferido entre salas.
- **Restauração sobre a mesma sala** ou **reinicialização**: a sala é a mesma; o histórico continua, e a
  próxima medição registra o que mudou (por exemplo, a remoção dos envios), sem inferir a causa.
- **Desativação temporária do plugin ou do bloco no site**: sem análises, sem pontos; ao voltar, os
  pontos seguintes retomam, com a lacuna visível.
- **Desinstalação**: todo o histórico é apagado com os demais dados do plugin.
- **Retenção**: pontos mais antigos que o período de retenção (365 dias por padrão) são removidos pela
  tarefa periódica; reduzir a retenção remove os excedentes na limpeza seguinte.

## Requirements *(mandatory)*

### Functional Requirements

**Registro do histórico**

- **FR-001**: Cada análise bem-sucedida MUST registrar, para a sala, um ponto do histórico com os
  indicadores daquela análise e a sua data; o registro MUST acontecer junto com a substituição do
  resultado atual (os dois acontecem ou nenhum acontece).
- **FR-002**: Por sala e por dia MUST existir no máximo um ponto; uma nova análise bem-sucedida no mesmo
  dia substitui o ponto do dia (decisão Q1). O "dia" é o do fuso horário padrão do site.
- **FR-003**: Uma análise que falha MUST NOT registrar nem alterar pontos.
- **FR-004**: Reexecuções e análises concorrentes MUST NOT produzir pontos duplicados.
- **FR-005**: O histórico MUST NOT ler arquivos, consultar o registro de arquivos nem refazer a análise:
  usa somente os valores que a análise da 002 acabou de calcular.
- **FR-006**: Na atualização para a versão desta feature, o resultado atual de cada sala, quando existir,
  MUST virar o primeiro ponto, com a data real dessa análise. Nenhum outro ponto passado é criado.
- **FR-007**: Pontos mais antigos que o período de retenção MUST ser removidos periodicamente, em lotes
  limitados (decisão Q2).
- **FR-009**: O período de retenção MUST ser uma configuração do site, com padrão de 365 dias e valores
  entre 90 dias e 5 anos; reduzi-lo faz a limpeza seguinte remover os pontos que ficaram fora do período.
- **FR-008**: O histórico de uma sala MUST ser apagado quando o resultado atual dela é apagado pela regra
  de elegibilidade da 002 (sala excluída ou sem o bloco) (decisão Q3), e MUST NOT ser transferido entre
  salas.

**Indicadores do histórico**

- **FR-010**: Cada ponto MUST guardar: data da análise; armazenamento lógico (tamanho e quantidade de
  arquivos); conteúdo único (tamanho e quantidade); armazenamento lógico das quatro categorias
  (materiais, envios, outros, não classificados); marca de cobertura parcial; versão do método.
- **FR-011**: Os valores MUST ser exatamente os do resultado da análise correspondente (mesmas definições
  da 002); o ponto mais recente MUST ser igual ao resultado atual.
- **FR-012**: O histórico MUST NOT incluir o potencial teórico (005), consumo físico atribuível,
  economia, referências externas, cópias de segurança ou lixeira, valores por atividade ou por estudante.

**Apresentação**

- **FR-020**: A página "Sua sala em números" MUST ganhar a seção "Evolução dos arquivos da sua sala",
  depois de "Onde estão os arquivos" e antes de "Materiais por atividade", sempre que a sala tiver pelo
  menos uma medição no histórico.
- **FR-021**: Nível visível (Princípio II, nível 2): a data da última medição; uma frase de variação
  entre as duas medições mais recentes, neutra e com os valores ("aumentaram/diminuíram X desde a análise
  de [data] (de A para B)" ou "não mudaram"); um gráfico simples do armazenamento lógico por medição; a
  frase do que os valores significam e não significam.
- **FR-022**: Nível recolhido (nível 3): a tabela com todas as medições exibidas (data; armazenamento
  lógico; as quatro categorias; conteúdo único; marca de cobertura parcial) e as limitações do
  histórico.
- **FR-023**: O gráfico MUST NOT ser a única forma de acesso: a tabela tem os mesmos dados; categorias
  MUST ser identificadas por texto, não só por cor; o gráfico MUST NOT sugerir valores entre medições
  (dias sem análise ficam sem valor).
- **FR-024**: A frase de variação MUST NOT comparar medições de versões de método diferentes nem afirmar
  causa, desperdício, inadequação, necessidade de reduzir, economia física, qualidade pedagógica ou
  impacto ambiental.
- **FR-025**: Com uma única medição, a seção MUST mostrar essa medição e dizer que a evolução aparece a
  partir da próxima análise; sem medição, a seção não aparece (a abertura da página já explica o estado).
- **FR-026**: A tabela MUST mostrar todo o histórico retido da sala (no máximo um ponto por dia do período
  de retenção); o gráfico MUST mostrar no máximo os últimos 90 dias até a última medição, e a seção MUST dizer
  que a tabela tem todas as medições quando houver medições anteriores a esse período (decisão do
  proprietário do produto de 2026-10-19).
- **FR-027**: O bloco MUST NOT mudar (Princípio II: um indicador).
- **FR-028**: Envios de estudantes MUST aparecer só como total da sala por medição (como na 003).

**Integração**

- **FR-030**: As features 002 a 005 MUST manter o seu comportamento; o histórico não gera sugestão nem
  altera as existentes.

**Privacidade, segurança e dados**

- **FR-040**: Mesmo acesso da página atual (capability de visualização do bloco); nenhuma capability nova.
- **FR-041**: Só totais da sala; nenhum dado pessoal; a declaração de privacidade do plugin continua
  verdadeira (nenhum dado pessoal armazenado).
- **FR-042**: A abertura da página MUST ler só o histórico da sala, limitado ao período de retenção, sem
  consultar o registro de arquivos.

**Idioma, acessibilidade e validação**

- **FR-050**: Frases completas, en e pt_br em paridade; datas e tamanhos no formato da língua do usuário.
- **FR-051**: WCAG 2.2 AA como nas features 003 a 005 (teclado, leitor de tela, contraste, 200%, 320 px,
  Boost e Classic).
- **FR-052**: Revisão de tom e validação visual em Moodle real, com aprovação do proprietário do produto.

### Key Entities

- **Ponto do histórico**: sala; dia; data e hora da análise; armazenamento lógico (bytes, arquivos);
  conteúdo único (bytes, arquivos); bytes por categoria (materiais, envios, outros, não classificados);
  cobertura parcial; versão do método. No máximo um por sala e dia.
- **Seção de evolução**: a frase de variação, o gráfico, a tabela e as explicações, a partir dos pontos
  da sala.

## Cenários de teste

| ID | Cenário | Resultado esperado |
| --- | --- | --- |
| CT-01 | Primeira análise bem-sucedida | Um ponto, com os valores do resultado |
| CT-02 | Análises em dias diferentes | Um ponto por dia |
| CT-03 | Duas análises no mesmo dia | Um ponto, com a última |
| CT-04 | Reexecução da tarefa e análises concorrentes | Nenhum ponto duplicado |
| CT-05 | Falha | Nenhum ponto novo; ponto anterior intacto |
| CT-06 | Recuperação depois da falha | Novo ponto no dia da análise bem-sucedida |
| CT-07 | Cobertura parcial | Ponto marcado como parcial |
| CT-08 | Mudança de método | Versão registrada; sem variação entre versões |
| CT-09 | Atualização do plugin com resultado existente | Um primeiro ponto, com a data real |
| CT-10 | Retenção | Pontos antigos removidos em lotes |
| CT-11 | Sala excluída / bloco removido | Histórico apagado com o resultado (Q3) |
| CT-12 | Restauração como nova sala, cópia | Sem histórico herdado |
| CT-13 | Integridade | Último ponto igual ao resultado atual; categorias iguais às do resultado |
| CT-14 | Página com 0, 1, 2 e muitas medições | Frases e gráfico de cada caso, sem pontos inventados |
| CT-15 | Acesso | Mesmas regras da página da 003 |
| CT-16 | Privacidade | Nenhum dado por estudante, arquivo ou atividade |
| CT-17 | Bancos | PostgreSQL e MariaDB |
| CT-18 | Desempenho | Metas de SC-003 e SC-004 |
| CT-19 | Acessibilidade e visual | axe sem violações; tabela equivalente; capturas aprovadas |
| CT-20 | Textos en e pt_br | Nenhuma causa, julgamento, alarme ou promessa |

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Em 100% dos cenários CT-01 a CT-13, o número de pontos e os seus valores são os esperados;
  nenhum ponto duplicado, fictício ou de análise que falhou.
- **SC-002**: O ponto mais recente de cada sala é idêntico ao resultado atual em todos os indicadores
  guardados.
- **SC-003**: A página de uma sala com histórico completo do período de retenção abre em até 2 segundos
  em condições normais no ambiente de referência; o bloco não acrescenta tempo em relação à 0.5.0.
- **SC-004**: Gravar o ponto acrescenta no máximo 5% ao tempo de análise de uma sala; numa instalação de
  referência com 5.000 salas, o volume do histórico não passa de um ponto por sala e por dia do período
  de retenção, e a limpeza mantém esse limite.
- **SC-005**: Verificação automática de acessibilidade sem violações; a tabela contém todos os valores do
  gráfico; seção utilizável só com teclado e a 320 px.
- **SC-006**: Revisão de tom e capturas aprovadas pelo proprietário do produto (en e pt_BR, desktop e
  celular, Boost e Classic).
- **SC-007**: Quality gates da constituição verdes no CI nas bases suportadas.

## Volume estimado (para as decisões Q1 e Q2)

Um ponto tem cerca de doze números e uma data. Com um ponto por sala e por dia:

| Salas | Retenção | Pontos (máximo) | Espaço aproximado, com índice |
| --- | --- | --- | --- |
| 5.000 | 365 dias | 1,8 milhão | cerca de 0,3 GB |
| 5.000 | 2 anos | 3,7 milhões | cerca de 0,6 GB |
| 5.000 | 365 dias, um ponto por análise com intervalo de 1 h | até 44 milhões | cerca de 7 GB |

Estimativas de ordem de grandeza (não medições); a medição entra em SC-004. Com o intervalo padrão de um
dia, "um ponto por análise" e "um ponto por dia" dão quase o mesmo volume; a diferença aparece quando a
administração reduz o intervalo.

## Limitações reconhecidas

- O histórico começa na atualização para esta versão; não existe passado anterior a ela.
- Dias sem análise (intervalo de reanálise, cron parado, plugin desativado) ficam sem ponto.
- Um ponto por dia: análises intermediárias do mesmo dia não ficam guardadas.
- Mudanças de método tornam medições anteriores e posteriores não comparáveis diretamente.
- A variação mostra o que mudou nos registros de arquivos, não por que mudou.

## Fora do escopo

Previsões, aprendizado de máquina, alertas, comparações entre professores ou salas, rankings, metas de
redução, carbono, promessas de economia, histórico por estudante ou por atividade, recomendações por
tendência, exportações, painéis institucionais, integrações externas, novos diagnósticos, histórico
retroativo artificial, histórico do potencial teórico (005).

## Riscos

- **Crescimento de dados**: limitado pela retenção (Q2) e pelo ponto diário (Q1); medido em SC-004.
- **Leitura de tendência como julgamento**: frase de significado sempre visível, linguagem neutra,
  revisão de tom.
- **Gráfico ilegível com muitos pontos ou em telas pequenas**: tabela equivalente; validação a 320 px.
- **Perda do histórico ao remover o bloco por engano**: consequência da decisão Q3, documentada.

## Assumptions

- Público e acesso iguais aos da Feature 003.
- O "dia" de um ponto é o do fuso horário padrão do site.
- Pontos de 0 bytes (sala sem arquivos) são medições reais.
- A configuração "intervalo de reanálise" da 002 continua a definir com que frequência há medições.
- Os recursos de gráfico do próprio Moodle são usados; nenhuma biblioteca externa (detalhe do plano).
