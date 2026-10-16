# Research: CourseCare Plugin Foundation (Feature 001)

**Data**: 2026-10-16 · **Constituição**: 1.2.0 · **Spec**: [spec.md](spec.md)

Fontes primárias: código-fonte do Moodle no branch `MOODLE_405_STABLE` (repositório oficial
`moodle/moodle`, versão 2024100715, 4.5.x), documentação oficial (moodledev.io, docs.moodle.org) e
repositórios oficiais `moodlehq/moodle-plugin-ci` e `moodlehq/moodle-docker`. Todo comportamento
abaixo marcado como **[código 4.5]** foi lido diretamente no código-fonte, não presumido.

---

## R1. Estrutura mínima de um plugin block instalável

**Decision**: o repositório é a raiz do plugin (instalado em `blocks/coursecare`) com os arquivos:
`version.php`, `block_coursecare.php` (classe `block_coursecare extends block_base`),
`db/access.php`, `lang/en/block_coursecare.php`, `classes/privacy/provider.php`,
`templates/content.mustache`, `pix/sprout.svg` e `styles.css`.

**Rationale**:
- O `moodle-plugin-ci validate` exige, para blocos, `version.php`, `lang/en/<componente>.php`,
  `block_coursecare.php` contendo a classe `block_coursecare` e `db/access.php` **[código
  moodle-plugin-ci, `BlockRequirements`]**.
- `version.php`: `$plugin->component = 'block_coursecare'`, `$plugin->version` (AAAAMMDDXX),
  `$plugin->requires = 2024100700` (4.5.0), `$plugin->supported = [405, 405]`,
  `$plugin->maturity = MATURITY_ALPHA` e `$plugin->release = '0.1.0'`. `supported = [405, 405]`
  atende a FR-003: só se declara o que foi validado.
- A privacidade é atendida por `\core_privacy\local\metadata\null_provider`, com a string
  `privacy:metadata` (FR-005; Princípio VII).
- Não há `db/install.xml`, `db/upgrade.php`, `settings.php`, `edit_form.php`, `amd/` nem
  `classes/output/`. Nada disso é necessário nesta feature (Princípio XI).

**Alternatives considered**:
- Plugin em subdiretório do repositório (`src/`): foi rejeitado porque quebra a convenção do
  ecossistema e o `moodle-plugin-ci install --plugin`.
- Renderer e renderable próprios (`classes/output/`): rejeitados. Sem lógica de apresentação,
  `render_from_template()` direto no bloco basta, e os temas já podem sobrescrever o template Mustache.

## R2. Permissões: adicionar, configurar e visualizar

**Decision**: duas capabilities próprias, verificadas sempre no **contexto do bloco**, sem testar
nomes de papéis:

| Capability | Tipo | Contexto | Arquétipos | Clonada de |
| --- | --- | --- | --- | --- |
| `block/coursecare:addinstance` | write | CONTEXT_BLOCK | editingteacher, manager | `moodle/site:manageblocks` |
| `block/coursecare:view` | read | CONTEXT_BLOCK | editingteacher, manager | — |

O `riskbitmask` de `addinstance` segue a convenção dos blocos core (`RISK_SPAM | RISK_XSS`, como
em `block_html`), para que administradores revisem os riscos de forma uniforme. `view` não tem risco.

**Rationale** **[código 4.5, `blocks/moodleblock.class.php`, `lib/blocklib.php`]**:
- **Adicionar**: `user_can_addto()` exige `block/<nome>:addinstance` **e** `moodle/block:edit` no
  contexto da página. Se a capability não existir, o core emite debugging e **permite** a adição.
  Por isso, defini-la é obrigatório para que FR-007 funcione.
- **`myaddinstance` não é necessária**: com `'my' => false` em `applicable_formats()`, o core
  retorna `false` antes de consultá-la ("it is ok if the myaddinstance capability is not defined").
- **Configurar, mover, ocultar e remover**: são permissões nativas. Editar blocos na página exige
  `moodle/site:manageblocks` (`moodle_page::user_can_edit_blocks()`), e cada instância exige
  `moodle/block:edit` (`block_base::user_can_edit()`). Não criamos nada próprio (FR-008).
- **Visualizar**: o core já aplica `moodle/block:view` em `get_content_for_output()` e `is_empty()`,
  mas essa capability é concedida a estudantes por padrão. A capability própria
  `block/coursecare:view` é verificada em `get_content()`: sem ela, o conteúdo fica vazio e o core
  não exibe o bloco (`is_empty()` → `get_content_for_output()` retorna `null`). O controle acontece
  no servidor: o HTML do conteúdo nunca é gerado para quem não tem a permissão, ou seja, não é só
  visual.
- **Limitação nativa aceita (apenas na página principal)**: em modo de edição, se
  `instance_can_be_edited()` for verdadeiro, `get_content_for_output()` gera os controles, e o
  bloco é renderizado com cabeçalho e controles mesmo com conteúdo vazio. Assim, um editor sem
  `:view` (por override institucional) consegue gerenciar o bloco, mas não vê a mensagem. Ocultar
  também os controles impediria esse editor de remover ou configurar o bloco. O comportamento é
  aceito e documentado. Fora da página principal, isso não ocorre (R3).
- **Administradores**: `has_capability()` retorna `true` para administradores do site, conforme o
  comportamento nativo.
- **Professor sem edição e estudante**: `:view` não é concedida aos arquétipos `teacher` e `student`.
  O resultado é *não definido*, que equivale a negado, e pode ser ajustado pelo administrador
  (FR-009).

**Alternatives considered**:
- Usar só `moodle/block:view`: rejeitada, porque concede a estudantes e não é específica do plugin.
- Verificar `moodle/course:update`: rejeitada. É um proxy de papel, não uma permissão do produto,
  e quebraria personalizações institucionais de permissões.
- `view` no contexto do curso: rejeitada. O contexto do bloco permite override por instância e
  herda do curso.

## R3. Disponível só na página principal da sala e no máximo uma instância por sala

**Decision**:
1. `applicable_formats()` retorna `['course-view' => true, 'course-view-section' => false]`. Todo o
   resto fica negado pelo fallback `all` vazio.
2. `instance_allow_multiple()` retorna `false`.
3. **Guarda de exibição**: `get_content()` só produz conteúdo quando o tipo da página atual começa
   com `course-view-` e não com `course-view-section-` (a "página principal").
4. **Sem cabeçalho vazio fora da página principal**: `instance_can_be_edited()` retorna `true` só na
   página principal.

**Rationale** **[código 4.5]**:
- `blocks_name_allowed_in_format()` escolhe a chave de formato **mais específica** (maior número de
  `-`) que casa com o tipo da página. A página principal do curso é `course-view-<formato>`
  (`course/view.php`). Desde o 4.4, a página de seção é `course-view-section-<formato>`
  (`course/section.php`), que também casaria com `course-view`. Por isso, ela é negada
  explicitamente.
- **Unicidade**: a API restringe **por página**, não por curso. `instance_allow_multiple() = false`
  faz `get_addable_blocks()` excluir o bloco quando `is_block_present()` o encontra entre os
  carregados na página. Não existe mecanismo nativo de "uma instância por curso". A garantia por
  sala vem da combinação de três fatos verificados:
  1. A adição só é possível na página principal (item 1).
  2. Ao adicionar em `course-view-*`, o core grava `pagetypepattern = 'course-view-*'`, sem o
     formato (`add_block_at_end_of_default_region`). As opções de "Exibir nos tipos de página" para
     a página do curso (`*`, `course-*`, `course-view-*`) sempre incluem a página principal. Assim,
     a instância continua carregada nela mesmo após mudar o formato do curso, e o bloco não é
     oferecido de novo.
  3. A restauração (`restore_stepslib.php`) ignora um bloco sem multiplicidade quando já existe
     um do mesmo tipo exibido no mesmo contexto e tipo de página.

  Ficam **fora** da garantia, como limitação documentada, as instâncias criadas por blocos padrão
  configurados pelo administrador (`$CFG->defaultblocks_override`) ou por código fora da interface.
- **Exibição fora da página principal**: `applicable_formats()` controla apenas a **adição**.
  - O padrão gravado (`course-view-*`) **também casa com as páginas de seção**
    (`course-view-section-*`), e "Exibir nos tipos de página" pode estender a exibição a atividades
    (`*`). `blocks_remove_inappropriate()` não está implementada no core.
  - Só com a guarda (item 3), o bloco ficaria oculto nessas páginas fora do modo de edição. Em modo
    de edição, porém, `get_content_for_output()` renderiza cabeçalho e controles quando
    `$this->page->user_is_editing() && $this->instance_can_be_edited()`, mesmo com conteúdo vazio.
  - Com `instance_can_be_edited() = false` fora da página principal (item 4), não há controles.
    Como `is_empty()` é verdadeiro, `get_content_for_output()` retorna `null`, e o core não
    renderiza nada: nem bloco, nem cabeçalho.
  - `instance_can_be_edited()` é API pública documentada de `block_base` ("If overridden and set to
    false by the block it will not be editable") e é usada no core por `block_myoverview` para o
    mesmo fim. Não é um hack.
  - A gestão do bloco continua disponível na página principal, onde os controles existem.

**Alternatives considered**:
- Restringir o campo "Exibir nos tipos de página": rejeitada, porque exigiria um formulário
  próprio (`edit_form.php`) que sobrescreve um comportamento core.
- Verificar no banco a existência de outra instância no curso: rejeitada. Seria uma consulta extra
  sem necessidade, já que a adição só é possível em uma página.
- Forçar `pagetypepattern` em `instance_create()`: rejeitada. O editor pode alterar o padrão depois,
  e o padrão nativo já é `course-view-*`.
- Ocultar por CSS: rejeitada. Seria um controle apenas visual, e o HTML continuaria sendo gerado.

## R4. Renderização e identidade visual

**Decision**:
- O título do bloco (cabeçalho nativo) vem de `get_string('pluginname', 'block_coursecare')`: em
  inglês "CourseCare" e em pt_BR "AVA Consciente".
- O conteúdo é renderizado por `$OUTPUT->render_from_template('block_coursecare/content', [])`.
  O template não recebe contexto. Ele contém o ativo decorativo, a frase de identidade e a mensagem
  inicial, com strings vindas de `{{#str}}`. Não há nenhum título interno duplicado.
- O ativo é `pix/sprout.svg` (broto de duas folhas, original). Ele é renderizado pela **Pix Icon
  API**, como exige o Princípio X ("ícones via `pix_icon`"), pelo helper Mustache
  `{{#pix}} sprout, block_coursecare {{/pix}}`, que chama `$OUTPUT->pix_icon('sprout', '',
  'block_coursecare')`. O tamanho é aumentado para cerca de 40 px por CSS restrito ao bloco
  (`.block_coursecare .icon`). Não há texto embutido.
- `styles.css` tem seletores restritos a `.block_coursecare`. Sem cores de tema, sem `!important`
  e sem seletores globais. Utilitários Bootstrap só quando existem tanto no Bootstrap 4 (Moodle 4.5)
  quanto no 5 (ex.: `d-flex`, `mb-0`, `small`); espaçamentos direcionais ficam no CSS local.

**Rationale**:
- O `styles.css` de um plugin é carregado em **todas** as páginas pelo tema. Por isso, o escopo
  por classe do bloco é obrigatório (sem sobrescritas globais; FR-015a).
- O mecanismo nativo de `pix` permite que temas substituam o ativo
  (`theme/<tema>/pix_plugins/block/coursecare/`) sem alterar o plugin (Princípio V).
- **[código 4.5]** O helper `pix` (`core\output\mustache_pix_helper`) aceita `chave, componente,
  alt`. Sem `alt`, ele chama `pix_icon()` com texto vazio, e `core\output\pix_icon` acrescenta
  `aria-hidden="true"` quando o `alt` é vazio. Assim, o ativo é decorativo para tecnologias
  assistivas sem marcação própria (FR-015, US3-6). Nos temas com o sistema de ícones Font Awesome
  (Boost e Classic), um ícone do plugin sem mapeamento cai para a imagem do `pix`
  (`icon_system_fontawesome`, `unmappedIcon`).
- Como o elemento é decorativo, ele não está sujeito ao critério de contraste de componentes
  informativos (WCAG 1.4.11), mas o desenho mantém nitidez em fundo claro e escuro.
- Boost e Classic, os temas core do 4.5, usam Bootstrap 4. Evitar utilitários que mudaram de nome
  no Bootstrap 5 (`ml-*`/`ms-*`, `gap-*`) facilita a evolução para o Moodle 5.x sem retrabalho.

**Alternatives considered**:
- `<img>` com `$OUTPUT->image_url()`: rejeitada após a análise de consistência (D1). Cria um padrão
  próprio de ícone quando o Princípio X exige `pix_icon`, e não traz vantagem, pois os temas também
  substituem ícones do `pix_icon` por `pix_plugins`.
- SVG embutido no Mustache (com `currentColor`): rejeitada. Funciona bem com alto contraste, mas
  impede a substituição pelo mecanismo nativo de `pix` dos temas e foge do `pix_icon`. Só seria
  reconsiderada com Complexity Tracking justificado, se a validação em alto contraste falhar de
  forma que afete a informação.
- Ícone Font Awesome existente do core: rejeitada, porque não atende à decisão Q2 (ativo próprio).
- Ilustração grande ou mascote: proibida pela especificação e pelo Princípio X.

## R5. Internacionalização e regimes do Princípio VIII

**Decision**:
- **Strings**: `lang/en/block_coursecare.php` contém, **em ordem alfabética** (`strcmp`),
  `coursecare:addinstance`, `coursecare:view`, `intro`, `pluginname`, `privacy:metadata` e
  `tagline`. `translations/pt_br/block_coursecare.php` tem exatamente as mesmas chaves, na mesma
  ordem, e o mesmo formato de arquivo de dados.
- **Formato dos arquivos de idioma** (padrão core, ex.: `blocks/html/lang/en/block_html.php` 4.5):
  cabeçalho GPL, docblock do arquivo, atribuições `$string[...]` sem `defined('MOODLE_INTERNAL')`
  e sem outros comentários entre as strings. O `moodle-cs` (`LangFilesOrderingSniff`) emite aviso
  para chaves fora de ordem e para comentários inesperados, e, com `--max-warnings 0`, um aviso
  reprova o Gate 1.
- **Desenvolvimento**: importação com a ferramenta nativa
  `php admin/tool/customlang/cli/import.php --lang=pt_br --source=<plugin>/translations/pt_br --checkin`,
  com o pacote oficial pt_br previamente instalado.
- **Fallback**: é nativo e não exige código.

**Rationale** **[código 4.5]**:
- `core_string_manager_standard::load_component_strings()`: carrega `lang/en` do plugin e depois
  `en_local`. Em seguida, para cada idioma da cadeia de dependências, carrega `lang/<idioma>` do
  plugin (legado), `$CFG->langotherroot/<idioma>` (pacote AMOS) e `<idioma>_local`
  (personalização). No fim, aplica `array_intersect_key($string, $enstring)`: chaves ausentes no
  inglês são descartadas, e chaves ausentes na tradução permanecem em inglês. Esse é o fallback
  string a string de FR-023.
- `tool_customlang` no 4.5: o CLI `import.php` existe e, com `--checkin`, executa
  `tool_customlang_utils::checkout()` antes de importar (popula os componentes instalados). O
  `importer` usa o parser de dados `phpparser` (não executa o arquivo), rejeita strings inexistentes
  no inglês (`notice_inexitentstring`) e grava em `<dataroot>/lang/pt_br_local/`. Nenhum arquivo do
  core é alterado.
- O 4.5 **não** tem CLI de `tool_langimport` (o diretório não possui `cli/`). O pacote pt_br oficial
  é instalado pela interface (Administração › Idioma › Pacotes de idiomas) ou extraindo o ZIP
  oficial de download.moodle.org em `<dataroot>/lang/`.
- A orientação oficial é que o plugin traga só o inglês e que as traduções sejam contribuídas ao
  AMOS (checklist de contribuição de plugins).
- Concatenação é proibida (Princípio VIII). Cada mensagem é uma string completa. Nesta feature não
  há parâmetros dinâmicos, mas o verificador já valida `{$a}` e `{$a->prop}` para as próximas features.

**Alternatives considered**:
- Copiar o arquivo manualmente para `pt_br_local`: rejeitada. Funciona, mas não passa pelo
  `tool_customlang`, cujos dados de checkout podem sobrescrever o arquivo depois.
- Incluir `lang/pt_br` no plugin em desenvolvimento: proibida pela constituição 1.1.0. Além disso,
  ela tem a precedência mais baixa e gera o aviso "Found language file".

## R6. Verificação de traduções (paridade)

**Decision**: um script PHP de linha de comando, `tools/i18n/check.php`, sem dependências e sem
bootstrap do Moodle. Ele analisa os arquivos com `token_get_all()` e aceita apenas o formato de dados
(`$string['chave'] = 'literal';`, com aspas simples ou duplas sem interpolação), além do cabeçalho
de comentários e de `defined('MOODLE_INTERNAL') || die();`. Para cada idioma em `translations/`,
compara com `lang/en`:

| Regra | Severidade |
| --- | --- |
| Chave ausente na tradução | erro |
| Chave excedente na tradução | erro |
| Conjunto de parâmetros `{$a}` / `{$a->x}` diferente do inglês | erro |
| Valor vazio | erro |
| Sintaxe que não seja só dados (concatenação, heredoc, variável, chamada) | erro |
| Chave duplicada no mesmo arquivo | erro |
| Valor idêntico ao inglês | aviso |

Os testes do verificador ficam em `tools/i18n/tests/run.php`, com fixtures (um conjunto válido e um
por regra) e asserção de código de saída e de mensagem. Rodam com PHP puro.

**Rationale**: o parser por tokens espelha o requisito do AMOS ("pure data file") e não executa
código do arquivo. PHP já é pré-requisito do ecossistema, então não há nova linguagem nem
dependência. O teste com PHP puro evita que o `phpunit` do Moodle precise de um ambiente instalado
para validar uma ferramenta de repositório.

**Alternatives considered**:
- `include` do arquivo e comparação de arrays: rejeitada, porque não detecta sintaxe proibida e
  executa código.
- Script em Python ou Node: rejeitada, porque acrescenta uma linguagem ao projeto.
- Verificador como teste PHPUnit do plugin: rejeitada. Ele precisaria de um Moodle instalado e
  teria de fazer parte do pacote, enquanto o verificador deve rodar mesmo sem Moodle.

## R7. Teste de idioma e fallback no Moodle real

**Decision**: um teste PHPUnit do plugin (`tests/translation_test.php`) cria, no `dataroot` de
testes, um idioma `pt_br` mínimo (`langconfig.php`) e aplica a tradução de
`translations/pt_br/block_coursecare.php` como personalização local (`pt_br_local`). Depois
reinicia o gerenciador de strings e verifica: (a) `get_string(..., 'pt_br')` retorna o texto em
português; (b) quando uma chave é removida, essa chave retorna o inglês; (c) um idioma sem tradução
retorna o inglês. O teste é ignorado (`markTestSkipped`) quando `translations/` não existe, como
acontece em uma instalação a partir do pacote.

**Rationale**: o Behat e o CI não têm o pacote pt_br oficial (`tool_langimport` exige download).
O teste exercita o caminho real de carregamento do core com o artefato do projeto. A validação
ponta a ponta com o pacote oficial e o `tool_customlang` é feita manualmente no ambiente de
desenvolvimento, com evidência (quickstart, CA-06).

**Risco**: o `string_manager` mantém cache e lista de idiomas. Se a manipulação no `dataroot` se
mostrar frágil no 4.5, o teste se limita a (c), e (a) e (b) passam a ser verificação manual
obrigatória. Essa limitação ficaria registrada nas evidências.

## R8. Pacote distribuível

**Decision**:
- **Geração**: `tools/package/build.sh` executa `git archive --format=zip --prefix=coursecare/`
  sobre o commit e respeita `export-ignore` do `.gitattributes`. A raiz do ZIP é `coursecare/`,
  exigida pelo instalador de plugins.
- **Verificação independente**: `tools/package/check.php <zip>` lê o conteúdo **real** do ZIP
  (`ZipArchive`) e o compara com uma **lista de permissões** (allowlist). Qualquer arquivo fora
  dela, `translations/`, `lang/<≠en>/`, `specs/`, `.specify/`, `.claude/`, `.github/`, `tools/` ou
  arquivos ocultos causa falha.
- A release pública usa **somente** o ZIP gerado e verificado pelo CI, anexado à release do GitHub.

**Rationale**: `export-ignore` só vale para `git archive`, e os ZIPs automáticos do GitHub usam essa
lógica. No entanto, esses ZIPs têm a raiz `moodle-block_coursecare-<ref>/`, inválida para o
instalador, e outros mecanismos (ZIP manual, ferramentas de terceiros) ignoram o `.gitattributes`.
A allowlist sobre o ZIP real não depende do mecanismo de geração.

**Alternatives considered**:
- Confiar apenas em `export-ignore`: rejeitada pela própria especificação.
- Script de cópia com lista de inclusão: rejeitada, porque duplica o `git archive` e permite
  empacotar arquivos não commitados.

## R9. Ambiente de desenvolvimento

**Decision**: `moodlehq/moodle-docker`, o ambiente oficial da Moodle HQ, com um clone de
`MOODLE_405_STABLE`, PostgreSQL e PHP 8.1 (a versão mínima suportada pelo 4.5; o padrão do
moodle-docker é 8.3). O plugin é montado em `blocks/coursecare` por um `local.yml`, mecanismo
documentado do moodle-docker, sem copiar arquivos. Os dados de demonstração são fictícios e vêm de
CSVs em `tools/dev/demo/`, carregados pelos CLIs nativos `admin/tool/uploadcourse/cli/uploadcourse.php`
e `admin/tool/uploaduser/cli/uploaduser.php` (usuários com `course1`/`role1` para matrícula). A
desinstalação usa `admin/cli/uninstall_plugins.php --plugins=block_coursecare --run`, e a atualização
usa `admin/cli/upgrade.php`.

**Rationale**: ele é mantido pela Moodle HQ, roda Behat (com Selenium) e PHPUnit, não depende de
infraestrutura institucional e é reproduzível por colaboradores com Docker.

**Alternatives considered**:
- MoodleBox, MAMP/XAMPP ou instalação manual: rejeitadas por serem pouco reproduzíveis.
- Imagens Bitnami: rejeitadas, porque não são oficiais do ecossistema Moodle e não executam Behat.
- `moodle-plugin-ci` local: complementar. É o mesmo usado no CI, mas não substitui um Moodle
  navegável para validação visual.

## R10. Testes e acessibilidade

**Decision**:
- **PHPUnit**: capabilities e conteúdo por permissão, guarda de tipo de página,
  `instance_can_be_edited()` por tipo de página, `applicable_formats`/`instance_allow_multiple`,
  provider de privacidade e tradução e fallback (R7).
  O gerador padrão de blocos do core é suficiente; não é preciso `tests/generator/lib.php`
  **[código 4.5, `get_default_plugin_generator`]**.
- **Behat** (`@block @block_coursecare`): adição por professor com edição; bloco não oferecido pela
  segunda vez (`the add block selector should not contain "CourseCare" block`); ausência para
  estudante, professor sem edição e visitante; ausência após remover a capability de um papel;
  conteúdo correto; bloco e cabeçalho ausentes, em modo de edição, na página de seção e em uma
  atividade com "Exibir nos tipos de página" = qualquer página (`"CourseCare" "block" should not
  exist`); mudança de formato do curso sem nova oferta do bloco; acessibilidade com
  `the "CourseCare" "block" should meet accessibility standards` (`@javascript`); tamanhos `mobile`
  (425x750) e `tablet` (768x1024) com `I change viewport size to`; um cenário no tema Classic.
- **Verificação manual proporcional**: em desktop, tablet e smartphone, nos temas Boost e Classic,
  em en e pt_BR. Inclui navegação só por teclado (ordem e foco visível), leitura da interface com
  leitor de tela (VoiceOver ou NVDA), zoom de 200% e reflow a 320 px. Os resultados e capturas são
  registrados como evidência. A restauração de backup sobre uma sala que já tem o bloco também é
  verificada manualmente, pois o Behat de restauração com mesclagem custa mais do que vale neste
  escopo.
- **Declaração de resultado**: "verificação dos critérios WCAG 2.2 AA aplicáveis ao bloco (axe +
  manual)". Nunca "conformidade integral com a WCAG".

**Rationale** **[código 4.5]**: o passo de acessibilidade usa axe-core com as tags `wcag2a`,
`wcag21a`, `wcag22a`, `wcag2aa`, `wcag21aa`, `wcag22aa`, `section508`, `cat.aria`,
`cat.sensory-and-visual-cues` e `wcag134`, o que cobre WCAG 2.2 AA verificável automaticamente.
Os tamanhos `mobile`/`tablet` existem em `behat_general`. A verificação automática não substitui a
manual (foco, leitor de tela, reflow a 320 px).

## R11. CI

**Decision**: GitHub Actions com dois jobs.
1. **`moodle`**: baseado no template oficial `gha.dist.yml` do `moodle-plugin-ci` ^4 (última versão
   4.5.11). A matriz `MOODLE_405_STABLE` × {PHP 8.1 + pgsql, PHP 8.3 + mariadb} cobre as PHPs mínima
   e máxima suportadas pelo 4.5 e dois bancos. Etapas: phplint, phpmd (informativo), phpcs
   `--max-warnings 0`, phpdoc `--max-warnings 0`, validate, savepoints, mustache, grunt, phpunit
   `--fail-on-warning` e behat `--profile chrome`. A variável `IGNORE_PATHS: 'tools,translations'`
   exclui das análises do `moodle-plugin-ci` os diretórios que não são código do plugin nem vão
   para o pacote. **[código moodle-cs]** O `MoodleInternalSniff` emite **erro** para arquivos PHP
   que alteram o estado global sem `MOODLE_INTERNAL` ou inclusão de `config.php`. Isso atinge os
   scripts de linha de comando de `tools/` (independentes do Moodle) e
   `translations/pt_br/block_coursecare.php`, que não é tratado como arquivo de idioma por não
   estar em `lang/`. As fixtures de `tools/i18n/tests/fixtures/`, inválidas de propósito, ficam
   cobertas pela mesma exclusão. A primeira execução deve confirmar nos logs que esses arquivos
   foram de fato ignorados.
2. **`i18n-package`**: sem Moodle, PHP 8.1. Roda `php -l` em todo PHP de `tools/` (exceto
   fixtures) e de `translations/`, os testes do verificador, o verificador sobre o repositório, a
   geração do ZIP e a verificação do ZIP. O ZIP verificado fica como artefato do job. A
   integridade da tradução continua garantida pelo verificador de paridade (Quality Gate 4).

**Rationale**: o template oficial já reflete as práticas atuais (Ubuntu 24.04, PostgreSQL 17 e
MariaDB 11) e a matriz recomendada para o 4.5 (PHP 8.1–8.3). Duas combinações bastam para o escopo,
e o job leve dá retorno rápido sobre o Quality Gate 4.

## R12. Pontos verificados que não exigem ação

- **Backup e restauração**: as instâncias de bloco são copiadas pelo core, e o bloco não tem dados
  próprios.
- **Desinstalação**: `core\plugininfo\block::uninstall_cleanup()` remove as instâncias e os
  contextos **[código 4.5]**.
- **Desabilitar no site**: é nativo (Administração › Plugins › Blocos).
- **Temas**: Boost e Classic são os temas core do 4.5. Não há dependência de nenhum dos dois.
- **Serviços externos**: nenhum. O ativo e o CSS são locais, e não há JavaScript nesta feature.
