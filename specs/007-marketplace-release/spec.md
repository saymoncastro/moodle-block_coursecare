# Feature Specification: International Marketplace Release

**Feature Branch**: `007-marketplace-release` (criada a partir de `main` em `91f1aa0`, que integra 001–006)

**Created**: 2026-10-19

**Status**: Draft (perguntas abertas Q1–Q3 para o proprietário do produto)

**Input**: User description: "Feature 007 — International Marketplace Release: consolidar as features
001–006 num plugin publicável e distribuível internacionalmente pelo Moodle Marketplace; auditoria de
prontidão; conformidade com os requisitos vigentes do Marketplace; compatibilidade verificada com Moodle
4.5 LTS e 5.3 LTS; instalação, atualização e desinstalação; segurança e privacidade; precisão
metodológica; acessibilidade; internacionalização; auditoria visual final; documentação internacional;
materiais da página pública; licenciamento e titularidade; pacote e versionamento; testes e CI;
governança; validações humanas pendentes. Sem novas funcionalidades, sem redesign, sem submissão,
publicação, tag ou release sem aprovação."

**Constituição de referência**: 1.2.0

**Natureza da feature**: consolidação e conformidade. Nenhuma funcionalidade nova de diagnóstico,
recomendação, potencial ou histórico. Mudanças de código só para corrigir defeitos reais,
compatibilidade verificada, segurança, privacidade, acessibilidade ou conformidade.

## Estados de prontidão

| Estado | Significado | Quem decide |
| --- | --- | --- |
| READY FOR REVIEW | Pronto para a revisão final interna: critérios técnicos com evidência | Equipe, com evidências |
| READY FOR SUBMISSION | Pronto para submeter: READY FOR REVIEW + bloqueios jurídicos e de conformidade resolvidos + validações humanas obrigatórias concluídas ou formalmente adiadas pelo proprietário do produto | Proprietário do produto |
| SUBMITTED | Submetido ao Marketplace | Só com autorização expressa |
| PUBLISHED | Aprovado pela revisão do Marketplace e publicado pelo mantenedor | Confirmação oficial do Marketplace |

CI verde não é publicação. Nenhum estado é declarado sem evidência.

## Auditoria inicial (2026-10-19)

### Estado do repositório

| Item | Situação |
| --- | --- |
| `main` | `91f1aa0`, integra 001–006 (PRs #3, #5, #7, #8, #9, #10) |
| Branch 006 | Integrada; nenhum commit fora de `main` |
| PRs abertos | Nenhum |
| Outras branches | `constitution-1.1.1` e `constitution-1.2.0`: integradas pelos PRs #1 e #2 (o commit da branch difere, o conteúdo está em `main`) |
| Versão | `release` 0.6.0, `maturity` ALPHA, `requires` 2024100700 (4.5), `supported` [405, 405] |
| CI | `moodle-plugin-ci` completo, PHPUnit e Behat em Moodle 4.5 (PHP 8.1 + PostgreSQL 17, PHP 8.3 + MariaDB 11), `i18n-package` |

### Tarefas abertas das features anteriores (não presumidas concluídas)

| Feature | Abertas | Natureza |
| --- | --- | --- |
| 001 | T045 | Validação visual, responsiva e de acessibilidade completa (humana) |
| 002 | — | Concluída |
| 003 | T047, T048, T050, T051, T052, T055, T057 | Revisão de tom, validação visual com leitor de tela, avaliação com cinco professores, correções críticas dela, quickstart limpo, Constitution Check, registro da decisão de formatação de tamanhos |
| 004 | T031, T032 | Avaliação com professores (junto da 003), Constitution Check |
| 005 | T029, T030, T033 | Revisão de tom, aprovação visual, Constitution Check |
| 006 | — | Concluída (tom e capturas aprovados; T035 feito) |

### Matriz de prontidão inicial

| Área | Situação | Evidência ou motivo |
| --- | --- | --- |
| Funcionalidade | Atendido, com pendências humanas | Features 001–006 integradas e testadas; aceitação humana de 003/004 pendente |
| Qualidade do código | Atendido para 4.5 | CI verde em `main`; phpcs, phpdoc, Mustache lint |
| Compatibilidade | **Pendente** | Só 4.5 verificado; 5.3 LTS não testado (riscos: diretório `public/` do 5.1+, tema Classic removido no 5.3, Bootstrap 5, PHP 8.3/8.4) |
| Privacidade | Pendente de auditoria | `null_provider`; dados agregados por sala; risco de inferência em turmas pequenas não avaliado |
| Segurança | Pendente de auditoria consolidada | Verificações por feature (acesso da 003, CLI da 002); sem auditoria única |
| Acessibilidade | Parcial | Verificações automáticas (axe, teclado, zoom, 320 px) em 003–006; teste humano com leitor de tela pendente |
| Internacionalização | Atendido, com verificação final | en e pt_br em paridade (162 chaves); pt_br fora do ZIP |
| Documentação | Parcial | README, CHANGES, CONTRIBUTING; faltam manual do professor, guia do administrador, solução de problemas, política de segurança, suporte |
| Instalação e atualização | Parcial | Instalação limpa e atualização 0.1→0.2 validadas na 002; atualização até 0.6 e desinstalação não revalidadas juntas |
| Licenciamento | **Bloqueante até confirmação** | LICENSE GPL v3; cabeçalhos "2026 Saymon Castro" em 70 arquivos; um único autor no Git; ícone declarado original; titularidade não documentada (Q3) |
| Publicação | **Pendente** | Diretrizes oficiais do Marketplace exigem login; conta de provedor não verificada; versão ALPHA |

## Clarifications

_(a preencher com as respostas a Q1–Q3)_

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Administrador instala o plugin num site limpo (Priority: P1)

Um administrador de qualquer país baixa o pacote, instala pelo procedimento padrão do Moodle e encontra o
bloco, as tabelas, as configurações e a tarefa agendada sem erros, sem ferramentas extras.

**Why this priority**: sem instalação confiável não há publicação.

**Independent Test**: instalar o ZIP num Moodle limpo de cada versão declarada, com depuração de
desenvolvedor ligada, e conferir componente, tabelas, configurações, tarefa, capabilities e bloco
disponível, sem avisos.

**Acceptance Scenarios**:

1. **Given** um Moodle limpo de cada versão declarada, **When** o administrador instala o ZIP pela
   interface, **Then** a instalação termina sem avisos, erros ou dependências extras.
2. **Given** o plugin instalado, **When** o administrador abre as configurações, **Then** encontra as
   quatro configurações documentadas com valores padrão.

---

### User Story 2 - Mantenedor atualiza uma instalação existente (Priority: P1)

Um site com versões anteriores do CourseCare é atualizado sem perda de diagnósticos ou histórico, e a
remoção do plugin apaga os seus dados como documentado.

**Why this priority**: instituições que já usam o plugin não podem perder dados.

**Independent Test**: atualizar de cada versão anterior suportada até a candidata, comparando os dados
antes e depois; desinstalar e conferir a remoção.

**Acceptance Scenarios**:

1. **Given** um site com a versão 0.6.0 e diagnósticos e histórico, **When** atualiza para a candidata,
   **Then** diagnósticos e pontos continuam iguais.
2. **Given** a atualização interrompida e repetida, **When** roda de novo, **Then** termina sem duplicar
   nem perder dados.
3. **Given** o plugin instalado com dados, **When** o administrador desinstala, **Then** todas as tabelas
   e configurações do plugin são removidas, como a documentação diz.

---

### User Story 3 - Professor usa o produto em inglês ou português (Priority: P1)

Um professor de qualquer país usa o bloco e a página "Your course in numbers" na sua língua, com datas,
números e tamanhos no formato dela, e entende o que cada indicador significa com o manual.

**Why this priority**: é o valor do produto para o público internacional.

**Independent Test**: percorrer as jornadas principais em en e pt_BR, desktop e celular, nas versões
declaradas; ler o manual do professor e encontrar a explicação de cada indicador.

**Acceptance Scenarios**:

1. **Given** um professor com o Moodle em inglês, **When** abre o bloco e a página, **Then** todo texto
   está em inglês, sem texto fixo em outra língua.
2. **Given** o manual do professor, **When** o professor procura o que significa "unique content",
   "theoretical potential" ou a evolução, **Then** encontra uma explicação com exemplo e o que o plugin
   não mede.

---

### User Story 4 - Administrador configura e entende os dados guardados (Priority: P2)

O administrador encontra, em inglês, o que cada configuração faz, o que o plugin guarda, por quanto tempo,
e como remover os dados.

**Independent Test**: guia do administrador e política de privacidade conferidos contra o código.

**Acceptance Scenarios**:

1. **Given** o guia do administrador, **When** o administrador ajusta a retenção, **Then** o guia diz o
   efeito na limpeza e no volume.
2. **Given** a política de privacidade, **When** comparada às tabelas do plugin, **Then** descreve
   exatamente o que é guardado.

---

### User Story 5 - Revisor do Marketplace avalia o pacote (Priority: P1)

Um revisor encontra um pacote com metadados corretos, só inglês, sem arquivos de desenvolvimento,
licença GPL, cabeçalhos de copyright, Privacy API, código no estilo do Moodle e documentação.

**Independent Test**: pacote verificado contra a matriz de conformidade do Marketplace; validação
automática do Marketplace (quando a submissão for autorizada).

**Acceptance Scenarios**:

1. **Given** o ZIP candidato, **When** inspecionado, **Then** contém só os arquivos necessários, inglês,
   GPL e copyright em cada arquivo.
2. **Given** a matriz de conformidade, **When** conferida, **Then** cada requisito obrigatório tem
   evidência ou um bloqueio registrado.

---

### User Story 6 - Desenvolvedor prepara a submissão (Priority: P2)

O desenvolvedor tem as instruções exatas de submissão, a descrição curta e longa em inglês, as capturas
reais, a versão candidata e a lista de bloqueios abertos.

**Independent Test**: seguir as instruções até o passo que exige autorização, sem executar a submissão.

**Acceptance Scenarios**:

1. **Given** as instruções de submissão, **When** seguidas, **Then** todo campo exigido tem conteúdo
   pronto e toda etapa humana está identificada.
2. **Given** a página pública preparada, **When** revisada, **Then** nenhuma afirmação não demonstrada
   aparece (redução automática, economia, carbono, otimização, avaliação docente).

### Edge Cases

- **Diretrizes oficiais inacessíveis sem login**: os requisitos são confirmados pelo proprietário do
  produto ao entrar no Marketplace; a matriz marca o que veio de fonte pública e o que falta confirmar.
- **Incompatibilidade com 5.3 que exija mudança de arquitetura**: apresentada antes de implementar.
- **Tema Classic ausente no 5.3**: testes e capturas do Classic só nas versões que o têm.
- **Turmas muito pequenas**: o total de envios da sala pode corresponder aos envios de um estudante;
  avaliar e documentar (o professor já vê esses envios no Moodle).
- **Site sem cron**: documentar que o diagnóstico não aparece sem a tarefa agendada.
- **Instalação a partir do código (Git)**: igual ao ZIP; ferramentas de desenvolvimento ignoradas.

## Requirements *(mandatory)*

### Functional Requirements

**Auditoria e prontidão**

- **FR-001**: A feature MUST manter uma matriz de prontidão (as 11 áreas acima) com cada item classificado
  como atendido com evidência, pendente, não aplicável com justificativa ou bloqueante.
- **FR-002**: A feature MUST manter uma matriz de conformidade com o Moodle Marketplace separando requisitos
  obrigatórios, recomendações, documentação e apresentação, procedimentos da plataforma e etapas humanas,
  com as fontes oficiais (URL e data de acesso) e a indicação do que não pôde ser confirmado.
- **FR-003**: Os estados READY FOR REVIEW, READY FOR SUBMISSION, SUBMITTED e PUBLISHED MUST ser
  declarados só com as condições da tabela de estados.

**Compatibilidade**

- **FR-010**: A compatibilidade declarada (`supported` e documentação) MUST incluir só versões verificadas
  por testes automatizados e instalação real (decisão Q2). [NEEDS CLARIFICATION: Q2 — quais versões
  declarar: 4.5 e 5.3 LTS; 4.5 a 5.3 (incluindo 5.0–5.2); ou só 4.5 na primeira publicação?]
- **FR-011**: Para cada versão declarada: instalação limpa, PHPUnit, Behat, verificações de código e
  verificações de acessibilidade MUST passar, com uma matriz Moodle × PHP × banco × resultado.
- **FR-012**: Defeitos de compatibilidade MUST ser corrigidos sem mudar o comportamento aprovado; mudança
  de arquitetura MUST ser apresentada antes.

**Instalação, atualização e desinstalação**

- **FR-020**: Instalação pelo ZIP e pelo código MUST funcionar sem ferramentas de desenvolvimento, sem
  avisos com depuração de desenvolvedor.
- **FR-021**: Atualização a partir de 0.6.0 (e das versões anteriores que o projeto suportar) MUST
  preservar diagnósticos e histórico, ser idempotente e respeitar os savepoints.
- **FR-022**: A desinstalação MUST remover todas as tabelas, configurações e tarefas do plugin, e a
  documentação MUST dizer isso.

**Segurança e privacidade**

- **FR-030**: Uma auditoria de segurança MUST cobrir login, capabilities, isolamento por sala,
  parâmetros, ações de escrita (sesskey), escape, SQL parametrizado, arquivos, erros, logs e conteúdo do
  pacote; achados reais MUST ser corrigidos.
- **FR-031**: A declaração da Privacy API MUST corresponder a todos os dados processados e guardados,
  incluindo o histórico; o risco de inferência em turmas pequenas MUST ser avaliado e documentado.

**Metodologia**

- **FR-040**: A documentação dos indicadores MUST corresponder ao código (armazenamento lógico, conteúdo
  único, potencial teórico, histórico) sem alterar fórmulas aprovadas; divergência só com evidência.

**Acessibilidade**

- **FR-050**: A feature MUST consolidar as evidências de acessibilidade de 003–006 por jornada, refazer só
  o que mudou ou falta, e manter os testes humanos pendentes como pendentes.

**Internacionalização**

- **FR-060**: en e pt_br MUST estar em paridade, sem texto fixo, com placeholders e plurais corretos e
  formatos de data, número e tamanho da língua do usuário.
- **FR-061**: O pacote MUST conter só a língua inglesa, conforme o processo oficial; a contribuição pt_BR
  ao AMOS MUST ficar preparada para depois da aprovação.

**Experiência**

- **FR-070**: Uma auditoria visual final das jornadas principais (bloco, página, distribuição, materiais
  grandes, sugestões, potencial, histórico, estados sem resultado, desatualizado e falha), desktop e
  celular, MUST registrar e corrigir só defeitos reais, sem redesign.

**Documentação e página pública**

- **FR-080**: A documentação em inglês MUST incluir: README público, instalação, atualização, configuração
  administrativa, manual do professor (com exemplos), explicação dos indicadores, privacidade e
  tratamento de dados, solução de problemas, limitações, histórico de versões, contribuição, suporte e
  reporte de problemas e de vulnerabilidades.
- **FR-081**: A página pública MUST ter descrição curta e longa em inglês e capturas reais com dados
  fictícios, sem dados pessoais nem afirmações não demonstradas.

**Licenciamento e titularidade**

- **FR-090**: A revisão de licenciamento MUST cobrir licença, cabeçalhos, titularidade (decisão Q3),
  terceiros e ativos (ícone, fontes, imagens), registrar bloqueios e diligências, sem parecer jurídico.
  [NEEDS CLARIFICATION: Q3 — quem é o titular do código e dos ativos: autoria pessoal de Saymon Castro,
  instituição (por exemplo, o Ifes), ou titularidade conjunta?]

**Pacote, versão e lançamento**

- **FR-100**: O pacote MUST conter só os arquivos necessários (sem ferramentas, credenciais, logs, dados
  de demonstração, capturas, especificações), com metadados corretos.
- **FR-101**: A versão de lançamento e a maturidade MUST seguir a decisão Q1; versão e changelog
  coerentes. [NEEDS CLARIFICATION: Q1 — primeira publicação como 1.0.0 estável, 1.0.0 RC antes da estável,
  ou 0.7.0 beta?]
- **FR-102**: Tag, GitHub Release e submissão MUST acontecer só com autorização expressa do proprietário
  do produto.

**Governança**

- **FR-110**: O repositório MUST ter licença, README, CONTRIBUTING, política de segurança, orientação de
  issues e pull requests e política de versões e compatibilidade, sem processos desnecessários.

**Validações humanas**

- **FR-120**: As validações humanas pendentes de 001, 003, 004 e 005 MUST ser listadas com o estado real;
  READY FOR SUBMISSION exige que estejam concluídas ou formalmente adiadas pelo proprietário do produto,
  com o motivo registrado.

**Escopo**

- **FR-130**: A feature MUST NOT acrescentar funcionalidades de diagnóstico, recomendação, potencial ou
  histórico, nem redesenhar a interface, nem acrescentar dependências externas.

### Key Entities

- **Matriz de prontidão**: área, item, classificação, evidência ou motivo.
- **Matriz de conformidade do Marketplace**: requisito, tipo (obrigatório, recomendado, apresentação,
  procedimento, humano), fonte, situação, evidência.
- **Matriz de compatibilidade**: Moodle × PHP × banco × resultado, com o run de CI ou a execução local.
- **Versão candidata**: número, maturidade, pacote, changelog, verificação.

## Cenários de teste

| ID | Cenário | Resultado esperado |
| --- | --- | --- |
| CT-01 | Instalação limpa pelo ZIP em cada versão declarada | Sem avisos; componentes registrados |
| CT-02 | Instalação a partir do código | Igual ao ZIP |
| CT-03 | Atualização 0.6.0 → candidata com dados | Dados preservados |
| CT-04 | Atualização repetida | Idempotente |
| CT-05 | Desinstalação | Nada do plugin fica |
| CT-06 | Suítes automatizadas em cada versão declarada × PHP × banco | Verdes |
| CT-07 | Auditoria de segurança | Sem achado aberto |
| CT-08 | Auditoria de privacidade | Declaração correta; risco documentado |
| CT-09 | Paridade e formatos en/pt_br | Sem diferença |
| CT-10 | Conteúdo do pacote | Só o necessário, inglês, GPL, copyright |
| CT-11 | Auditoria visual das jornadas | Defeitos reais corrigidos |
| CT-12 | Documentação × código | Sem divergência |
| CT-13 | Página pública | Sem afirmação não demonstrada |
| CT-14 | Matriz de conformidade | Cada obrigatório com evidência ou bloqueio |

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Em 100% das versões declaradas, a instalação limpa termina sem avisos e as suítes
  automatizadas passam.
- **SC-002**: A atualização a partir de 0.6.0 preserva 100% dos diagnósticos e pontos do histórico, e uma
  segunda execução não muda nada.
- **SC-003**: A desinstalação deixa zero tabelas e configurações do plugin.
- **SC-004**: Zero achados abertos de segurança e privacidade de severidade alta ou média.
- **SC-005**: 100% das chaves de idioma em paridade; zero textos fixos.
- **SC-006**: O pacote não contém nenhum arquivo de desenvolvimento, teste visual, dado de demonstração,
  credencial ou língua além do inglês.
- **SC-007**: Cada requisito obrigatório do Marketplace tem evidência ou bloqueio registrado.
- **SC-008**: Um administrador consegue instalar e configurar seguindo só a documentação em inglês, e um
  professor encontra no manual a explicação de cada indicador mostrado (verificação por leitura guiada).
- **SC-009**: Nenhum estado de prontidão é declarado sem as suas condições.

## Limitações reconhecidas

- As diretrizes oficiais do Marketplace exigem login: parte da conformidade só é confirmada por uma
  pessoa com conta de provedor.
- A revisão do Marketplace não tem prazo garantido para plugins gratuitos.
- A revisão de licenciamento não é parecer jurídico.

## Fora do escopo

Novas funcionalidades; redesign; previsões, alertas, comparações, painéis; dependências externas;
submissão, publicação, tag ou release sem autorização; suporte a versões não verificadas.

## Riscos

- **Compatibilidade com 5.3**: diretório `public/` (scripts com caminho relativo ao `config.php`), tema
  Classic removido (cenários e capturas do Classic), Bootstrap 5 (classes de utilidade), PHP 8.3/8.4.
- **Titularidade não documentada**: bloqueia a publicação até a confirmação (Q3).
- **Validações humanas pendentes**: bloqueiam READY FOR SUBMISSION até conclusão ou adiamento formal.
- **Requisitos do Marketplace mudando**: fontes datadas; confirmação humana antes da submissão.

## Assumptions

- O nome internacional é CourseCare; a identidade em português é AVA Consciente (só na tradução).
- O repositório público `saymoncastro/moodle-block_coursecare` e as GitHub Issues são o código-fonte e o
  rastreador públicos.
- Moodle 4.5 LTS e 5.3 LTS são as versões-alvo; 5.0–5.2 só se houver justificativa e verificação.

## Fontes consultadas (acesso em 2026-10-19)

- Moodle Marketplace: https://marketplace.moodle.com/
- Plugins FAQ (processo de submissão, verificações automáticas, revisão, publicação manual, novas
  versões): https://docs.moodle.org/501/en/Plugins_FAQ
- Plugin contribution (marcado como legado; inglês só no pacote, AMOS após aprovação):
  https://moodledev.io/general/community/plugincontribution
- Plugin contribution checklist (legado; requisitos de metadados, licença, privacidade, segurança):
  https://moodledev.io/general/community/plugincontribution/checklist
- Code pre-checks (phplint, phpcs, phpdoc, savepoint, mustache, thirdparty, grunt):
  https://moodledev.io/general/community/plugincontribution/codeprechecks
- Moodle Marketplace API (novas versões por API, mantenedor):
  https://moodledev.io/general/community/plugincontribution/moodlemarketplaceapi
- Moodle 5.3 release notes (5 de outubro de 2026; PHP 8.3+; PostgreSQL 17; MariaDB 11.4; Classic
  removido; atualização a partir de 4.4): https://moodledev.io/general/releases/5.3
- Diretrizes oficiais de submissão e guia de listagem (exigem login, não lidos):
  https://moodle.atlassian.net/wiki/spaces/MMPD/pages/3498180610/Plugin+submission+guidelines e
  https://moodle.atlassian.net/wiki/spaces/MMPD/pages/3976560653/Quick+guide+to+listing+a+plugin+on+Moodle+Marketplace
- Fontes secundárias (indicativas, não normativas): https://pimenko.com/en/moodle-marketplace-user-experience-feedback/
