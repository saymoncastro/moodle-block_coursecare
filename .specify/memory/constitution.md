# CourseCare (block_coursecare) Constitution

## Contexto do Produto

CourseCare é um plugin open source do tipo *block* para Moodle 4.5+, identificado tecnicamente
como `block_coursecare`. Nome internacional: **CourseCare**. Nome em português brasileiro:
**AVA Consciente**.

É um assistente educativo que promove o uso consciente dos recursos de armazenamento das salas
virtuais: ajuda professores a compreender o impacto de arquivos e configurações de atividades,
identificar oportunidades de otimização e adotar boas práticas digitais. Nasceu da necessidade de
lidar com o crescimento do armazenamento em ambientes Moodle de grande escala, mas é desenvolvido
como solução internacional, independente de qualquer instituição.

Nesta constituição, "sala" significa um curso do Moodle (contexto de curso e seus subcontextos).

## Core Principles

### I. Educação, não fiscalização (NON-NEGOTIABLE)

O plugin orienta, conscientiza e apoia decisões docentes. Ele não fiscaliza, constrange nem pune.

- Recomendações MUST ser apresentadas como sugestões opcionais, nunca como obrigações, e o
  professor MUST poder ignorá-las sem consequência.
- O plugin MUST NOT alterar atividades ou configurações, remover ou mover arquivos, bloquear
  ações pedagógicas ou impor limites de armazenamento — nem automaticamente, nem por ação de
  um clique executada pelo próprio plugin. A ação recomendada é realizada pelo professor na
  interface nativa do Moodle (o plugin pode oferecer o link para ela).
- O plugin MUST NOT produzir rankings, comparações nominais ou listas de professores, nem
  métricas individuais de desempenho docente.
- O plugin MUST NOT enviar e-mails, mensagens ou notificações não solicitadas a professores.
  Qualquer comunicação proativa exige opt-in explícito e feature própria.

**Rationale**: a autonomia pedagógica é inegociável. Um instrumento percebido como fiscalização
perde a confiança docente e, com ela, todo o seu efeito educativo.

### II. Simplicidade e acessibilidade

O público inclui professores de diferentes áreas, formações e níveis de familiaridade
tecnológica. O professor não precisa compreender infraestrutura para utilizar o plugin.

- A interface principal MUST usar linguagem simples, orientações contextualizadas e ações
  compreensíveis, sem jargões de infraestrutura (ex.: "contenthash", "filedir", "blob").
- A informação MUST seguir divulgação progressiva em três níveis:
  1. **Bloco** — uma mensagem essencial e, no máximo, um indicador principal.
  2. **Página de orientações** — situações concretas e ações práticas.
  3. **Detalhes técnicos** — opcionais, recolhidos por padrão, para quem quiser aprofundar.
- Cada nível MUST ser compreensível sem a leitura do nível seguinte.

**Rationale**: excesso de informação gera abandono; a profundidade fica disponível sem ser imposta.

### III. Recomendações contextualizadas

- Toda recomendação MUST conter três elementos: (a) a situação concreta identificada na sala;
  (b) por que ela é relevante; (c) uma ação possível ao alcance do professor.
- Recomendações MUST respeitar necessidades pedagógicas legítimas. Arquivos grandes, por si só,
  MUST NOT ser tratados como mau uso.
- Avaliações negativas MUST NOT ser emitidas sem critério objetivo, documentado e testável.
  Todo critério de recomendação MUST estar descrito na especificação da feature que o introduz.
- Limiares e critérios MUST ser configuráveis pela administração do site, com valores padrão
  neutros e documentados.

**Rationale**: orientação genérica não muda prática; orientação injusta destrói confiança.

### IV. Honestidade e rigor dos indicadores (NON-NEGOTIABLE)

Todo indicador MUST pertencer explicitamente a uma das categorias abaixo, e a interface MUST
rotulá-lo de acordo. Categorias diferentes MUST NOT ser somadas ou apresentadas como equivalentes.

| Categoria | Significado |
| --- | --- |
| **Armazenamento lógico** | Soma dos tamanhos dos arquivos associados aos contextos da sala, como o Moodle os registra. |
| **Conteúdo único** | O mesmo, contando cada conteúdo idêntico uma única vez (deduplicação do File API). |
| **Consumo físico atribuível** | Bytes efetivamente armazenados que só existem por causa da sala, apenas quando mensurável. |
| **Potencial teórico** | Projeção de armazenamento futuro a partir de configurações (ex.: limites de envio). |
| **Economia observada** | Redução efetivamente medida entre duas medições reais. |

- Projeções MUST NOT ser apresentadas como consumo realizado.
- Redução de limites configurados MUST NOT ser apresentada como economia física obtida.
- "Economia observada" MUST ser descrita como redução medida, sem afirmar causalidade que o
  sistema não comprova.
- O plugin MUST NOT inferir emissões de carbono ou impacto ambiental sem metodologia científica
  apropriada, referenciada e aprovada por emenda a esta constituição.
- Quando um valor não puder ser calculado com confiabilidade, a interface MUST reconhecer a
  limitação em vez de exibir uma estimativa sem rótulo.
- Todo indicador exibido MUST informar quando foi calculado.

**Rationale**: indicadores imprecisos apresentados como fatos comprometem a credibilidade do
produto e induzem decisões erradas por parte de docentes e gestores.

### V. Integração nativa com Moodle

- O plugin MUST usar APIs, mecanismos de extensão e convenções oficiais do Moodle: Block API,
  File API, Task API, Cache API (MUC), Access API, Privacy API, String API, Output API
  (renderers e templates Mustache), módulos AMD/ES e XMLDB.
- O plugin MUST NOT modificar o core nem exigir patches no core.
- O plugin MUST NOT depender de temas específicos, serviços externos ou recursos exclusivos de
  uma instituição. Ele MUST funcionar no tema padrão (Boost) e em temas derivados.
- Acesso direto ao banco de dados MUST passar por `$DB` (DML API), com consultas portáveis entre
  os bancos suportados pelo Moodle.

**Rationale**: aderência às APIs oficiais é o que permite a evolução entre versões e a aceitação
no diretório oficial de plugins.

### VI. Desempenho e escalabilidade

O plugin MUST ser adequado a instalações com milhares de salas e grandes volumes de arquivos, e
MUST NOT comprometer a experiência de uso ou a disponibilidade do ambiente.

- A renderização do bloco e das páginas MUST NOT executar varreduras na tabela de arquivos nem
  análises custosas. Ela lê dados pré-calculados (tabela própria e/ou cache).
- Cálculos pesados MUST ocorrer em tarefas agendadas ou ad hoc (Task API), processadas em lotes,
  com escopo por sala e limite de tempo/volume por execução.
- Falhas no processamento de uma sala MUST ser isoladas: elas não interrompem o processamento das
  demais, e o bloco exibe um estado degradado compreensível em vez de um erro.
- Operações globais (todo o site) MUST ser evitadas, salvo quando justificadas na especificação
  e executadas de forma assíncrona.
- Consultas novas sobre tabelas volumosas MUST ter custo avaliado no plano da feature
  (índices utilizados, volume esperado).

**Rationale**: um assistente de boas práticas não pode, ele próprio, degradar o ambiente.

### VII. Segurança e privacidade (NON-NEGOTIABLE)

- Toda página, endpoint e serviço MUST verificar login, contexto e capability (`require_login`,
  `require_capability` ou equivalente) segundo o princípio do menor privilégio.
- Capabilities próprias MUST ser declaradas em `db/access.php` com arquétipos mínimos necessários.
- O plugin MUST NOT expor arquivos, conteúdos, nomes de arquivos ou informações individuais de
  estudantes. Indicadores sobre envios de estudantes MUST ser agregados por atividade.
- Dados armazenados MUST ser minimizados e, por padrão, agregados por sala ou atividade.
- O plugin MUST implementar a Privacy API: `null_provider` enquanto não armazenar dados pessoais;
  provider completo (metadados, exportação e exclusão) assim que armazenar qualquer dado ligado a
  usuários.
- Decisões de tratamento de dados MUST considerar LGPD e GDPR (finalidade, necessidade,
  minimização) e ser registradas no plano da feature.
- Entradas MUST ser validadas (`required_param`/`optional_param` com tipos `PARAM_*`), ações
  que alteram estado MUST usar `sesskey`, e saídas MUST ser escapadas.

**Rationale**: o plugin lê metadados de todo o armazenamento da sala; essa visibilidade não pode
se transformar em exposição de estudantes nem em vigilância.

### VIII. Internacionalização desde a origem

- O idioma-base do código, comentários, identificadores e strings é o inglês. O arquivo canônico
  de strings é `lang/en/block_coursecare.php`. Toda chave de string MUST existir nele.
- O português brasileiro (`pt_br`) é a tradução oficialmente mantida pelo projeto. O nome exibido
  em pt_BR é "AVA Consciente".
- Textos de interface MUST NOT ser inseridos diretamente no código ou nos templates. Toda string
  visível passa pela String API (`get_string`, `{{#str}}`, `core/str`).
- Mensagens MUST ser strings completas, com parâmetros `{$a}`. Frases MUST NOT ser montadas por
  concatenação de strings, pois uma tradução parcial misturaria idiomas na mesma frase.
- Novas traduções MUST ser possíveis sem alterar regras de negócio.
- Strings MUST NOT presumir terminologia, infraestrutura ou políticas de uma instituição
  específica. Termos como "sala", "AVA" ou "disciplina" pertencem à tradução, não às regras.
- A tradução pt_BR MUST usar terminologia genérica do português brasileiro e MUST NOT depender de
  customizações de qualquer instituição. Adaptações institucionais são feitas por cada site com a
  personalização de idioma do Moodle, fora do projeto.

**Disponibilidade, distribuição e fallback da tradução** — três regimes distintos:

1. **Durante o desenvolvimento.** A tradução pt_BR é desenvolvida e validada desde a primeira
   feature com interface. A cópia versionada fica em `translations/pt_br/block_coursecare.php`,
   fora de `lang/`, com o mesmo formato de arquivo de dados e o mesmo cabeçalho de licença.
   Em ambientes de desenvolvimento e homologação, ela é instalada como personalização local de
   idioma, com a ferramenta nativa `tool_customlang` (interface "Personalização de idioma" ou
   `admin/tool/customlang/cli/import.php --lang=pt_br --source=<repo>/translations/pt_br
   --checkin`), com o pacote de idioma pt_br instalado e sem alterar arquivos do core.
2. **Distribuição pública.** A distribuição pública de traduções segue o AMOS. O pacote
   distribuível do plugin (ZIP de release e envio ao Marketplace) MUST conter apenas `lang/en`, e
   `translations/` MUST ser excluído dele (ex.: `export-ignore` em `.gitattributes`). Após a
   publicação no Marketplace, a tradução pt_BR MUST ser contribuída ao AMOS. A partir daí, os sites
   a recebem pelo pacote oficial de idioma pt_br (`tool_langimport`). Até a publicação, o
   repositório é a fonte da tradução. Depois dela, o AMOS passa a ser a fonte canônica, e
   `translations/pt_br/` guarda apenas as strings novas ou alteradas ainda não contribuídas,
   reconciliadas a cada release.
3. **Fallback.** Quando a tradução não está instalada, ou quando falta uma chave específica, vale o
   fallback nativo do Moodle: a string em inglês de `lang/en`. A interface MUST permanecer correta
   e compreensível com tradução parcial ou ausente.

A personalização local de idioma tem prioridade sobre o pacote oficial do AMOS. Por isso, a
instalação via `tool_customlang` MUST ficar restrita a ambientes de desenvolvimento e homologação,
e a documentação MUST NOT orientar sites de produção a usá-la para obter a tradução do projeto.

**Rationale**: o produto é internacional por desenho, não por adaptação posterior. Seguir o fluxo
oficial do Moodle (inglês no pacote, traduções no AMOS) garante distribuição a todos os sites sem
conflitos de precedência, sem atrasar a validação da tradução brasileira.

### IX. Qualidade, testabilidade e manutenibilidade

- O código MUST manter quatro camadas separadas, em namespaces distintos sob `\block_coursecare\`:
  1. **Coleta** — leitura de dados do Moodle (único ponto de acesso ao banco/File API).
  2. **Análise** — cálculo dos indicadores definidos no Princípio IV.
  3. **Regras de recomendação** — decisões puras sobre dados já analisados, sem acesso a banco,
     sessão ou saída.
  4. **Apresentação** — renderers, templates Mustache e JavaScript.

  Cada camada passa a existir na primeira feature que tiver a responsabilidade correspondente.
  Camadas sem responsabilidade concreta MUST NOT ser criadas vazias ou antecipadamente. Quando uma
  responsabilidade existir, ela MUST ficar na sua camada, nunca misturada a outra.
- Toda regra de negócio MUST ter testes PHPUnit. Todo fluxo de interface MUST ter cenário Behat
  quando a feature introduzir interface.
- Toda funcionalidade MUST possuir critérios de aceitação objetivos e verificáveis na especificação.
- O código MUST seguir o Moodle Coding Style e passar nas verificações do `moodle-plugin-ci`
  (ver "Fluxo de Desenvolvimento e Quality Gates").
- Classes e funções públicas MUST ter PHPDoc conforme as convenções do Moodle.

**Rationale**: separar coleta, análise e regras permite testar critérios de recomendação sem um
Moodle populado e revisar cada decisão de forma isolada.

### X. Experiência visual consistente

- A identidade visual MUST ser acolhedora, profissional e adequada a diferentes públicos docentes:
  hierarquia clara, textos curtos, mensagens construtivas, ícones compreensíveis e leitura
  adequada em telas pequenas.
- A ludicidade MUST ser discreta e não infantilizada. Elementos lúdicos MUST NOT comparar pessoas
  nem gerar pressão (sem pontos competitivos, rankings ou mensagens de culpa).
- A interface MUST atender aos critérios aplicáveis da WCAG 2.2 nível AA: contraste, navegação por
  teclado, foco visível, rótulos acessíveis, informação não transmitida apenas por cor, e ícones
  com alternativa textual.
- Componentes MUST reutilizar padrões visuais do Moodle (templates e componentes core, ícones via
  `pix_icon`) antes de criar padrões próprios.

**Rationale**: a forma comunica a intenção; um visual punitivo contradiz o Princípio I.

### XI. Evolução incremental

- O desenvolvimento MUST ser organizado em especificações pequenas, com escopo e fora-de-escopo
  explícitos.
- Cada entrega MUST ser funcional, verificável, testável e instalável isoladamente.
- Funcionalidades futuras MUST NOT ser antecipadas sem necessidade concreta na feature atual.
- Abstrações de extensibilidade (ex.: registro plugável de regras, subplugins) MUST ser
  justificadas por pelo menos dois casos concretos ou registradas na seção de complexidade do plano.

**Rationale**: entregas pequenas mantêm o produto sempre utilizável e as decisões reversíveis.

### XII. Software livre e contribuição comunitária

- O projeto MUST ser licenciado sob a GNU GPL v3 ou posterior, compatível com o Moodle e com o
  diretório oficial de plugins. Todo arquivo PHP MUST conter o cabeçalho de licença padrão do Moodle.
- Dependências proprietárias obrigatórias são proibidas. Bibliotecas de terceiros MUST ter licença
  compatível com a GPL e ser declaradas em `thirdpartylibs.xml`.
- O repositório MUST documentar instalação, configuração, permissões, limitações conhecidas,
  compatibilidade e forma de contribuição (README, CHANGES e guia de contribuição).
- O projeto MUST estar preparado para publicação no diretório de plugins do Moodle: nomenclatura
  *frankenstyle*, `version.php` completo e requisitos de revisão do diretório.

**Rationale**: um produto independente de instituição só se sustenta como bem comum.

## Restrições Técnicas e Compatibilidade

- **Tipo e componente**: plugin *block*, componente `block_coursecare`.
- **Moodle**: mínimo 4.5 (LTS), `$plugin->requires = 2024100700`. As versões suportadas MUST ser
  declaradas em `$plugin->supported` e cobertas pela matriz de CI.
- **PHP**: as versões suportadas pela versão mínima do Moodle (a partir de PHP 8.1). Recursos de
  linguagem MUST ser compatíveis com a menor versão suportada.
- **Bancos de dados**: todos os suportados pelo Moodle, por meio da DML API e do XMLDB. Mudanças
  de esquema MUST vir com `db/upgrade.php` e savepoints.
- **JavaScript**: apenas módulos AMD/ES do próprio plugin, compilados com o Grunt do Moodle. Sem
  CDN nem chamadas a serviços externos.
- **Idiomas**: `lang/` contém apenas `en`. A tradução mantida fica em `translations/pt_br/`,
  excluída do pacote distribuível (Princípio VIII).
- **Rede**: o plugin MUST NOT enviar dados para fora do site Moodle (sem telemetria).
- **Armazenamento próprio**: apenas dados agregados (ex.: medições periódicas por sala), com
  retenção definida na especificação que os introduzir.

## Fluxo de Desenvolvimento e Quality Gates

- Todo trabalho segue o Spec Kit: `/speckit-specify` → (`/speckit-clarify`) → `/speckit-plan` →
  `/speckit-tasks` → `/speckit-implement` → `/speckit-converge`.
- O plano de cada feature MUST conter uma "Constitution Check" que avalie os doze princípios. Uma
  violação MUST ser corrigida ou justificada na seção de complexidade do plano. Violações dos
  princípios I, IV e VII não admitem justificativa, apenas correção ou emenda constitucional.
- Cada especificação MUST declarar quais categorias de indicador (Princípio IV) ela exibe e quais
  dados de usuário (Princípio VII) ela lê ou armazena.
- Gates obrigatórios antes de integrar à `main`:
  1. `moodle-plugin-ci`: phplint, phpcs (moodle-cs), phpdoc, validate, savepoints, mustache e
     grunt sem erros.
  2. PHPUnit verde, com testes para toda regra de negócio nova ou alterada.
  3. Behat verde para fluxos de interface, incluindo a verificação de acessibilidade do Moodle
     (`the page should meet accessibility standards`) nas telas novas.
  4. Strings novas ou alteradas presentes em `lang/en` e em `translations/pt_br`, com paridade de
     chaves e de parâmetros `{$a}`, e revisadas no idioma. O pacote distribuível não contém
     `translations/` nem idiomas além de `en` em `lang/`.
  5. Matriz de CI cobrindo ao menos a versão mínima e a versão estável mais recente suportada.
- Revisões MUST verificar explicitamente o tom das mensagens (Princípios I e III) e os rótulos
  dos indicadores (Princípio IV), além do código.

## Conflitos entre Princípios e Resolução

Os conflitos identificados na ratificação e a resolução vinculante de cada um:

- **C1 — Simplicidade (II) × Rigor dos indicadores (IV).** IV exige distinguir cinco categorias;
  II exige pouca informação. **Resolução**: a simplificação pode omitir, nunca distorcer. O nível
  1 mostra no máximo um indicador, com rótulo correto em linguagem simples. As distinções completas
  ficam nos níveis 2 e 3. Nenhum nível pode usar um rótulo que confunda categorias.
- **C2 — Recomendações contextualizadas (III) × Privacidade (VII).** Uma situação concreta pode
  envolver envios de estudantes. **Resolução**: a recomendação refere-se à atividade e à sua
  configuração, com dados agregados, nunca a um estudante ou arquivo de estudante. O plugin não
  amplia o que as capabilities nativas do Moodle já permitem ao professor ver.
- **C3 — Ação possível (III) × Sem alteração automática (I).** **Resolução**: a "ação possível" é
  sempre executada pelo professor na interface nativa do Moodle. O plugin pode oferecer o link
  direto para ela. Qualquer alteração executada pelo plugin, mesmo confirmada pelo professor,
  exige emenda constitucional prévia.
- **C4 — Desempenho (VI) × Atualidade e rigor (IV).** Cálculo em tempo real é custoso.
  **Resolução**: a interface exibe dados pré-calculados com a data da medição. Atualidade é
  comunicada, nunca simulada. Na ausência de medição, o plugin informa que os dados ainda não
  estão disponíveis.
- **C5 — Necessidades de gestão × Não fiscalização (I).** Administradores podem querer visões do
  site. **Resolução**: visões administrativas, se especificadas, são agregadas por sala ou por
  categoria, protegidas por capability própria, em linguagem não punitiva e sem identificar ou
  ordenar professores. Cada visão exige especificação própria e Constitution Check específica.
- **C6 — Evolução incremental (XI) × Extensibilidade e separação de camadas (IX, V).**
  **Resolução** (esclarecida em 1.1.1): a separação das quatro camadas de IX é obrigatória desde a
  primeira feature que contenha cada responsabilidade. A camada é então criada com implementação
  mínima (classes simples), e nenhuma camada é criada vazia para cumprir a estrutura. Mecanismos
  genéricos de extensão só entram quando houver ao menos dois casos concretos (Princípio XI).
- **C7 — pt_BR obrigatório (VIII) × Política do diretório de plugins (XII).** O diretório do
  Moodle orienta que o plugin traga apenas as strings em inglês e que as traduções sejam
  contribuídas ao AMOS. **Resolução** (emenda 1.1.0): os regimes de desenvolvimento, distribuição
  e fallback definidos no Princípio VIII. A tradução pt_BR é critério de aceitação de toda feature
  com interface. Ela é mantida em `translations/pt_br/`, instalada em desenvolvimento por
  personalização local de idioma e contribuída ao AMOS após a publicação. O pacote distribuível
  contém apenas `lang/en`. Assim, a tradução é oficialmente mantida sem violar o fluxo oficial de
  distribuição.
- **C8 — Ludicidade (X) × Educação, não fiscalização (I).** **Resolução**: elementos lúdicos são
  apenas de reforço positivo e individual, sem comparação entre salas ou pessoas, sem pontuação
  competitiva e sem mensagens de culpa.

**Precedência para conflitos não previstos**: (1) VII Segurança e privacidade; (2) I Autonomia
pedagógica; (3) VI Disponibilidade do ambiente; (4) IV Honestidade dos indicadores; (5) II
Simplicidade; demais princípios em pé de igualdade. Todo conflito novo MUST ser registrado no plano
da feature e, se recorrente, incorporado a esta seção por emenda.

## Governance

- Esta constituição prevalece sobre qualquer outra prática, especificação ou plano do projeto.
  Uma feature em conflito com ela MUST ser ajustada; a constituição não é alterada implicitamente
  por features.
- **Emendas**: propostas por pull request que altera apenas este arquivo, com justificativa,
  impacto nas features existentes e plano de migração, quando aplicável. Emendas aos Princípios I,
  IV e VII exigem justificativa explícita do motivo pelo qual a regra anterior se tornou inadequada.
- **Versionamento** (SemVer):
  - MAJOR — remoção ou redefinição incompatível de princípio ou regra de governança.
  - MINOR — novo princípio, nova seção ou ampliação material de orientação.
  - PATCH — esclarecimentos, redação e correções sem efeito semântico.
- **Conformidade**: todo plano passa pela Constitution Check. Toda revisão de pull request verifica
  conformidade. `/speckit-analyze` e `/speckit-converge` tratam violações constitucionais como
  bloqueantes.
- **Revisão periódica**: a constituição MUST ser revisada a cada nova versão LTS do Moodle e
  antes da primeira publicação no diretório de plugins.

### Histórico de emendas

- **1.1.1 (2026-10-16)** — Esclarece o conflito C6 e o Princípio IX: cada camada (coleta, análise,
  regras, apresentação) é obrigatória a partir da primeira feature que tenha a responsabilidade
  correspondente, e nenhuma camada é criada vazia. **Justificativa**: o texto anterior ("desde a
  primeira feature") podia ser lido como exigência de criar camadas sem conteúdo. Essa leitura
  contradiz o Princípio XI e a própria resolução do C6, que pede implementação mínima. O problema
  surgiu no plano da Feature 001, que só tem apresentação. **Versão PATCH**: a emenda não cria
  obrigação nova nem remove alguma; ela fixa a interpretação compatível entre IX e XI. A exigência
  de separação continua intacta para todo código que exista.
- **1.1.0 (2026-10-16)** — Resolve o TODO(PT_BR_DISTRIBUTION) e o conflito C7. Define três regimes
  para a tradução pt_BR (desenvolvimento, distribuição pública e fallback) no Princípio VIII,
  além de restrição de idiomas e gate de paridade de strings. **Justificativa**: a decisão não
  podia aguardar a feature de empacotamento, pois a tradução precisa ser validada desde a primeira
  feature com interface. Ela segue a orientação oficial do Moodle (apenas inglês no pacote do
  plugin, traduções via AMOS) e a ordem nativa de precedência de strings (`lang/xx` do plugin <
  pacote de idioma < personalização local). **Versão MINOR, não PATCH**: a emenda cria obrigações
  novas (estrutura `translations/`, exclusão do pacote, gate de paridade), o que constitui
  ampliação material de orientação. Nenhum princípio foi removido ou redefinido.
- **1.0.0 (2026-10-16)** — Ratificação inicial.

**Version**: 1.1.1 | **Ratified**: 2026-10-16 | **Last Amended**: 2026-10-16
