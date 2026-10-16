# Implementation Plan: CourseCare Plugin Foundation

**Branch**: `001-plugin-foundation` | **Date**: 2026-10-16 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/001-plugin-foundation/spec.md`

**Constituição**: 1.2.0 · **Pesquisa**: [research.md](research.md) · **Contratos**: [contracts/](contracts/)
· **Validação**: [quickstart.md](quickstart.md)

## Summary

Entregar o primeiro `block_coursecare` instalável no Moodle 4.5. O bloco aparece somente na página
principal da sala e somente para quem tem `block/coursecare:view` (por padrão, professor com edição
e gerente). Ele exibe a identidade CourseCare / AVA Consciente com um ativo SVG decorativo próprio e
uma mensagem inicial educativa, sem nenhum indicador.

A abordagem técnica usa só APIs nativas (Block API, Access API, String API, Output API com
Mustache, Privacy API com `null_provider`, `pix`). Não há tabelas, tarefas, configurações nem
JavaScript.

Em volta do plugin ficam as ferramentas de repositório, excluídas do pacote: um verificador de
paridade de traduções, a geração e a verificação do ZIP, CI com `moodle-plugin-ci` e um ambiente
`moodle-docker` reproduzível.

## Technical Context

**Language/Version**: PHP 8.1–8.3 (faixa suportada pelo Moodle 4.5). O código não usa recursos
posteriores ao PHP 8.1. Mustache (motor do core). CSS restrito ao bloco.

**Primary Dependencies**: Moodle 4.5 (`$plugin->requires = 2024100700`, `supported = [405, 405]`).
Não há dependências de terceiros em tempo de execução. Ferramentas de desenvolvimento:
`moodlehq/moodle-plugin-ci` ^4 (4.5.11) e `moodlehq/moodle-docker`.

**Storage**: N/A. O plugin não cria tabelas. As instâncias usam a tabela core `block_instances`.

**Testing**: PHPUnit e Behat do Moodle (via `moodle-plugin-ci` e `moodle-docker`). Testes em PHP
puro para as ferramentas de repositório. Validação manual visual e de acessibilidade com evidências.

**Target Platform**: Moodle 4.5 LTS em servidor web com PostgreSQL ou MariaDB/MySQL (todos os
bancos do core via DML). Navegadores suportados pelo Moodle 4.5, em desktop, tablet e smartphone.

**Project Type**: plugin Moodle do tipo *block* (repositório = raiz do plugin).

**Performance Goals**: renderização do bloco sem acesso ao banco além do que o core já faz para
blocos. Custo de `get_content()` limitado a uma verificação de capability e à renderização de um
template (SC-009).

**Constraints**: sem modificação do core; sem serviços externos; sem dependência de tema; WCAG 2.2
AA; pacote distribuível apenas com inglês; sem concatenação de strings.

**Scale/Scope**: 1 bloco, 2 capabilities, 6 strings, 1 template, 1 SVG e nenhuma tabela.

Nenhum item está como NEEDS CLARIFICATION: todos foram resolvidos em [research.md](research.md).

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Princípio | Avaliação | Situação |
| --- | --- | --- |
| I. Educação, não fiscalização | Mensagem educativa e opcional, sem alteração de atividades, notificações ou métricas de docentes. | ✅ |
| II. Simplicidade e acessibilidade | Nível 1 apenas (bloco). Sem jargão, sem links para níveis inexistentes. | ✅ |
| III. Recomendações contextualizadas | Não há recomendações nesta feature. A mensagem não avalia a sala. | ✅ N/A |
| IV. Honestidade dos indicadores | Nenhuma categoria de indicador é exibida (FR-029). O estado inicial não sugere análise (FR-013). | ✅ |
| V. Integração nativa | Block, Access, String, Output, Privacy, `pix`, `tool_customlang`. Sem core patch, sem tema específico. | ✅ |
| VI. Desempenho | Sem consultas próprias, sem tarefas, sem varreduras. | ✅ |
| VII. Segurança e privacidade | Capability própria no contexto do bloco, verificada no servidor. `null_provider`. Não lê nem armazena dados de usuário. | ✅ |
| VIII. Internacionalização | `lang/en` canônico; `translations/pt_br` versionado; importação por `tool_customlang`; fallback nativo; pacote só com `en`; verificador de paridade. | ✅ |
| IX. Qualidade e camadas | Só a responsabilidade de **apresentação** existe nesta feature, e ela fica isolada no template, no CSS e no `get_content()`. Coleta, análise e regras não são criadas: pelo C6 (emenda 1.2.0), cada camada é obrigatória a partir da primeira feature com a responsabilidade correspondente, e camadas vazias são proibidas. A Feature 002 introduz coleta e análise. PHPUnit, Behat e `moodle-plugin-ci` completos. | ✅ |
| X. Experiência visual | Ativo decorativo discreto e aprovado; WCAG 2.2 AA verificada por axe e manualmente; sem ludicidade competitiva. | ✅ |
| XI. Evolução incremental | Sem abstrações, renderer, settings ou JS. Escopo idêntico ao da spec. | ✅ |
| XII. Software livre | GPL v3+; cabeçalho padrão em todo PHP; sem `thirdpartylibs.xml` (nenhuma biblioteca); README, CHANGES, CONTRIBUTING e LICENSE. | ✅ |

**Gates de integração** (constituição, "Fluxo de Desenvolvimento e Quality Gates"): 1)
`moodle-plugin-ci` completo; 2) PHPUnit verde; 3) Behat verde com acessibilidade; 4) paridade
en/pt_br e pacote sem `translations/`; 5) matriz com a versão mínima e a mais recente suportada.
Como `supported = [405, 405]`, as duas coincidem no 4.5.

**Resultado do gate (pré-pesquisa e pós-design, reexecutado após a revisão do plano)**: **PASSA**,
sem violações, contra a constituição 1.2.0. A observação sobre o IX feita contra a 1.1.0 foi
resolvida pela redefinição do C6 na emenda 1.2.0 (PR saymoncastro/moodle-block_coursecare#2), que
substitui a 1.1.1 (classificada incorretamente como PATCH).

## Project Structure

### Documentation (this feature)

```text
specs/001-plugin-foundation/
├── spec.md              # Especificação aprovada (com Clarifications)
├── plan.md              # Este arquivo
├── research.md          # Fase 0: decisões e fontes
├── data-model.md        # Fase 1: entidades (sem tabelas próprias)
├── quickstart.md        # Fase 1: ambiente e roteiro de validação
├── contracts/
│   ├── capabilities.md  # Permissões e comportamento de acesso
│   ├── block-ui.md      # Contrato de interface e acessibilidade do bloco
│   └── dev-tools-cli.md # CLIs de verificação de tradução e de pacote
├── checklists/
│   └── requirements.md  # Qualidade da spec
├── evidence/            # CA-13: criado na implementação (relatório e capturas)
└── tasks.md             # Fase 2 (/speckit-tasks), ainda não criado
```

### Source Code (repository root)

O repositório é a raiz do plugin, instalado em `<moodle>/blocks/coursecare`. Os itens marcados com
✗ ficam **fora do pacote distribuível**.

```text
.
├── version.php                     # Metadados: component, version, requires, supported, maturity, release
├── block_coursecare.php            # class block_coursecare extends block_base
├── db/
│   └── access.php                  # block/coursecare:addinstance, block/coursecare:view
├── classes/
│   └── privacy/
│       └── provider.php            # null_provider
├── lang/
│   └── en/
│       └── block_coursecare.php    # Catálogo canônico (único idioma no pacote)
├── templates/
│   └── content.mustache            # Ativo + tagline + intro (com contexto de exemplo para lint)
├── pix/
│   └── sprout.svg                  # Ativo decorativo original
├── styles.css                      # Apenas seletores .block_coursecare
├── tests/
│   ├── block_coursecare_test.php   # Formatos, multiplicidade, permissões, guarda de página
│   ├── translation_test.php        # Tradução e fallback no string manager real (R7)
│   ├── privacy/
│   │   └── provider_test.php       # Razão do null_provider
│   └── behat/
│       └── block_coursecare.feature
├── README.md                       # Instalação, permissões, limitações, compatibilidade
├── CHANGES.md
├── LICENSE                         # GPL v3
├── CONTRIBUTING.md               ✗ # Contribuição, tradução e AMOS
├── translations/                 ✗
│   └── pt_br/block_coursecare.php  # Tradução oficialmente mantida (Princípio VIII)
├── tools/                        ✗
│   ├── i18n/
│   │   ├── check.php               # Verificador de paridade (contracts/dev-tools-cli.md)
│   │   └── tests/
│   │       ├── run.php             # Testes em PHP puro
│   │       └── fixtures/           # Conjuntos válidos e inválidos (inválidos de propósito)
│   ├── package/
│   │   ├── build.sh                # git archive --prefix=coursecare/
│   │   └── check.php               # Verificação do ZIP real por allowlist
│   └── dev/
│       ├── local.yml.dist          # Montagem do plugin no moodle-docker
│       └── demo/                   # CSVs fictícios de cursos e usuários
├── .github/workflows/ci.yml      ✗ # Jobs moodle e i18n-package
├── .gitattributes                ✗ # export-ignore dos itens ✗
├── .gitignore                    ✗
├── .specify/ .claude/ specs/     ✗ # Spec Kit
```

**Structure Decision**: plugin único na raiz, conforme a convenção do ecossistema e do
`moodle-plugin-ci`. As camadas do Princípio IX que esta feature não usa (coleta, análise e regras)
**não** são criadas. Elas entram na Feature 002 em `classes/local/` (namespaces
`\block_coursecare\local\collector`, `...\analysis` e `...\rules`), e a apresentação evolui para
`classes/output/` quando houver dados a renderizar. A fundação não precisa ser refeita: o bloco
passará a ler dados prontos e entregá-los ao template.

## Arquitetura

```text
Página principal da sala (course-view-<formato>)
   └─ block_manager (core) ── moodle/block:view? ──┐
                                                   ▼
        is_main_page = tipo de página começa com course-view- e não com course-view-section-
        block_coursecare::get_content()
          1. !is_main_page?                                               → conteúdo vazio
          2. !has_capability('block/coursecare:view', contexto do bloco)? → conteúdo vazio
          3. render_from_template('block_coursecare/content', [])
                ├─ {{#pix}} sprout, block_coursecare {{/pix}}            ← Pix Icon API (aria-hidden)
                └─ {{#str}} tagline / intro, block_coursecare {{/str}}  ← String API
                                                    (lang/en ← pacote AMOS ← pt_br_local)
        block_coursecare::instance_can_be_edited() = is_main_page
   Conteúdo vazio e sem controles ⇒ o core não exibe o bloco, nem o cabeçalho (R3).
   Na página principal, em modo de edição, quem edita vê os controles nativos (RK2).
```

Métodos de `block_base` sobrescritos: `init()` (título = `pluginname`), `applicable_formats()`,
`instance_allow_multiple()` (false), `has_config()` (false), `get_content()` e
`instance_can_be_edited()`. Este último é API pública documentada ("If overridden and set to false
by the block it will not be editable"), usada no core por `block_myoverview`. Não são
sobrescritos: `instance_allow_config()` (o padrão false basta) e `specialization()`. O teste de
página principal é um método privado do próprio bloco, não uma abstração. Detalhes em
[contracts/capabilities.md](contracts/capabilities.md) e [contracts/block-ui.md](contracts/block-ui.md).

## APIs oficiais utilizadas

| API | Uso | Fonte verificada |
| --- | --- | --- |
| Block API (`block_base`) | Definição, formatos, multiplicidade, conteúdo | `blocks/moodleblock.class.php` 4.5 |
| Block manager | Regras de adição e exibição | `lib/blocklib.php` 4.5 |
| Access API | `db/access.php`, `has_capability()` | `blocks/html/db/access.php` 4.5 (padrão) |
| String API | `get_string()`, `{{#str}}` | `lib/classes/string_manager_standard.php` 4.5 |
| Output API | `render_from_template()`, helper Mustache `pix` → `pix_icon()` (Princípio X) | `lib/classes/output/mustache_pix_helper.php`, `pix_icon.php` 4.5 |
| Privacy API | `null_provider` | Core 4.5 |
| `tool_customlang` | Importação pt_br em desenvolvimento | `admin/tool/customlang/cli/import.php` 4.5 |
| Testing | `advanced_testcase`, gerador padrão de blocos, `behat_blocks`, `behat_accessibility`, `behat_general` | Core 4.5 |

## Dependências e versões

| Item | Versão | Uso |
| --- | --- | --- |
| Moodle | 4.5 (`MOODLE_405_STABLE`, mínimo 2024100700) | Plataforma alvo e única versão declarada |
| PHP | 8.1 e 8.3 no CI (8.1 no ambiente local) | Faixa do 4.5 |
| PostgreSQL / MariaDB | 17 / 11 (template oficial do CI) | Matriz de CI |
| moodle-plugin-ci | ^4 (4.5.11) | CI e verificações oficiais |
| moodle-docker | branch principal | Ambiente local |
| Docker e Docker Compose | atuais | Ambiente local |
| git | qualquer versão com `git archive` | Pacote |

Nenhuma dependência é distribuída no pacote.

## Estratégia de internacionalização

As decisões estão em research.md (R5–R7), e o contrato em [contracts/dev-tools-cli.md](contracts/dev-tools-cli.md).

- **Chaves** (`lang/en`, em ordem alfabética, exigida pelo `moodle-cs`): `coursecare:addinstance`
  "Add a new CourseCare block", `coursecare:view` "View the CourseCare block", `intro` "Soon you
  will find guidance here on using your course resources more mindfully.", `pluginname`
  "CourseCare", `privacy:metadata` "The CourseCare block does not store any personal data." e
  `tagline` "Small choices, a better learning space for everyone.". As strings em inglês seguem a
  convenção do Moodle (sem title case). O arquivo segue o padrão core: sem `MOODLE_INTERNAL` e sem
  comentários entre as strings (research R5).
- **pt_BR** (`translations/pt_br`): `pluginname` "AVA Consciente" e as frases de referência da
  spec. Revisão de idioma registrada nas evidências.
- **Regimes**: desenvolvimento (`tool_customlang` CLI), distribuição (só `lang/en`; AMOS após
  publicação) e fallback (nativo, string a string).
- **CI**: `tools/i18n/check.php` com código de saída diferente de zero em caso de erro.

## Estratégia de permissões

Contrato: [contracts/capabilities.md](contracts/capabilities.md).

| Ação | Verificação | Origem |
| --- | --- | --- |
| Adicionar | `block/coursecare:addinstance` + `moodle/block:edit` (página) | Core + plugin |
| Configurar, mover, ocultar, remover | `moodle/site:manageblocks` (página) + `moodle/block:edit` (bloco) | Core |
| Ver o bloco | `moodle/block:view` (core) **e** `block/coursecare:view` (plugin), ambas no contexto do bloco | Core + plugin |
| Desabilitar no site | Administração › Plugins › Blocos | Core |

## Diretrizes de apresentação

Contrato: [contracts/block-ui.md](contracts/block-ui.md).

- Ativo à esquerda (cerca de 40 px) e texto à direita em telas largas. Em telas estreitas, a
  composição empilha.
- Tagline em destaque moderado (peso ou tamanho levemente maior, sem cor própria) e mensagem em
  texto normal. Contraste herdado do tema.
- Sem bordas extras, sombras, cartões aninhados, badges, números ou botões.
- O ativo SVG é original, com duas folhas em tons de verde suaves, traço simples e sem texto. O
  agente propõe e implementa o ativo, a apresentação e os textos. O proprietário do produto aprova
  depois de ver as capturas da implementação real, antes da integração à `main` (FR-014, FR-015).
  `evidence/README.md` registra a aprovação com data, só quando ela ocorrer, e nunca como
  antecipada.

## Ambiente de desenvolvimento

Roteiro completo em [quickstart.md](quickstart.md): `moodle-docker` + `MOODLE_405_STABLE` + PHP 8.1
+ PostgreSQL, plugin montado por `local.yml`, dados fictícios por CSV com os CLIs nativos, pacote
pt_br oficial e `tool_customlang` para a tradução, Behat com Chrome no Selenium e PHPUnit no
container. Também cobre a simulação de atualização (versão temporária e `upgrade.php`) e de
desinstalação (`uninstall_plugins.php`).

## Estratégia de testes

| Camada | Ferramenta | Cobre |
| --- | --- | --- |
| Unidade e integração do plugin | PHPUnit (`advanced_testcase`) | FR-006 a FR-010, guarda de página e `instance_can_be_edited()` por tipo de página, FR-013 (sem indicadores no HTML), FR-005, FR-023 (R7) |
| Funcional | Behat | US2 (adição, segunda instância, permissões por papel e override), US3 (conteúdo), US3-7 (bloco e cabeçalho ausentes na página de seção e em atividade, em modo de edição), mudança de formato do curso sem segunda instância, CA-10 (axe, mobile, tablet), CA-12 (Classic) |
| Ferramentas | `tools/i18n/tests/run.php` | FR-025, FR-027, FR-028 (cada regra com fixture) |
| Pacote | `tools/package/check.php` no ZIP real | FR-026, CA-09 |
| Qualidade | `moodle-plugin-ci` | phplint, phpcs, phpdoc, validate, savepoints, mustache, grunt |
| Manual com evidência | quickstart | CA-01, CA-06, CA-11; restauração sobre sala com o bloco (FR-010); desktop, tablet e smartphone; Boost e Classic; en e pt_BR; teclado; leitor de tela; zoom de 200%; 320 px; revisão de clareza pelo proprietário do produto (SC-002) e, opcionalmente, por 1–2 docentes |

A feature **não** é considerada concluída só com testes automatizados (CA-13, SC-010). Para
acessibilidade, o axe cobre apenas o que é verificável automaticamente. As verificações manuais
complementam, e o resultado é registrado como verificação dos critérios aplicáveis ao bloco, sem
declarar conformidade integral com a WCAG (FR-017, SC-007).

## Estratégia de CI

Arquivo `.github/workflows/ci.yml`, acionado em `push` e `pull_request` (research R11):
- **`moodle`**: `MOODLE_405_STABLE` × {8.1/pgsql, 8.3/mariadb}. Executa todas as etapas do template
  oficial (phpmd é informativo) e define `IGNORE_PATHS: 'tools,translations'`. Esses diretórios
  não são código do plugin nem vão para o pacote, e o `MoodleInternalSniff` do `moodle-cs`
  reprovaria seus arquivos (research R11).
- **`i18n-package`**: PHP 8.1 sem Moodle. Executa `php -l` nos PHP de `tools/` (exceto fixtures)
  e de `translations/`, `php tools/i18n/tests/run.php`,
  `php tools/i18n/check.php`, `tools/package/build.sh` e `php tools/package/check.php
  build/block_coursecare-<release>.zip`, e publica o ZIP como artefato.

Qualquer falha bloqueia a integração à `main`.

## Estratégia de distribuição

- `.gitattributes` marca com `export-ignore` os itens ✗ da estrutura.
- `tools/package/build.sh` gera `build/block_coursecare-<release>.zip` com a raiz `coursecare/`.
- `tools/package/check.php` valida o **conteúdo real** do ZIP pela allowlist:
  - **Arquivos permitidos**: `version.php`, `block_coursecare.php`, `db/access.php`,
    `classes/privacy/provider.php`, `lang/en/block_coursecare.php`, `templates/*.mustache`,
    `pix/*.svg`, `styles.css`, `tests/**`, `README.md`, `CHANGES.md` e `LICENSE`.
  - **Proibidos explicitamente**: `translations/`, `lang/<≠en>/`, `tools/`, `specs/`, `.specify/`,
    `.claude/`, `.github/`, arquivos ocultos, `build/`, `vendor/` e `node_modules/`.
- A release pública usa apenas o ZIP do CI. Os ZIPs automáticos do GitHub não são canal de
  distribuição, pois a raiz deles é inválida para o instalador. A submissão ao Marketplace fica
  fora do escopo; o pacote apenas fica pronto para ela.

## Riscos e limitações

| # | Risco ou limitação | Mitigação |
| --- | --- | --- |
| RK1 | O padrão nativo `course-view-*` carrega o bloco também nas páginas de seção, e "Exibir nos tipos de página" pode carregá-lo em atividades | **Resolvido nativamente** (R3): guarda em `get_content()` mais `instance_can_be_edited()` falso fora da página principal. Sem conteúdo e sem controles, o core não renderiza o bloco nem o cabeçalho. Verificado por Behat (US3-7). |
| RK2 | Na página principal, um editor sem `:view` por override vê o cabeçalho e os controles em modo de edição, mas não a mensagem | **Limitação aceita da plataforma**: os controles nativos de gestão são renderizados para quem pode editar o bloco. Sem eles, o editor não conseguiria remover nem configurar o bloco. Documentada no README. |
| RK9 | A unicidade nativa é por página, não por curso | Garantia nos fluxos de usuário (R3): adição só na página principal; padrão `course-view-*` que mantém a instância carregada em qualquer formato; restauração que ignora duplicatas de blocos sem multiplicidade. **Fora da garantia**, como limitação documentada: blocos padrão configurados pelo administrador (`$CFG->defaultblocks_override`) e criação por código fora da interface. |
| RK3 | `translation_test.php` (R7) pode ser frágil diante do cache e da lista de idiomas do string manager | Plano B: reduzir a (c) e tornar (a) e (b) verificação manual obrigatória com evidência. |
| RK4 | O pacote pt_br oficial não pode ser instalado por CLI no 4.5 | Instalação pela interface ou extraindo o ZIP oficial no `dataroot`, documentada no quickstart. |
| RK5 | Utilitários Bootstrap divergentes entre o 4.5 (BS4) e o 5.x (BS5) | Usar só classes comuns; espaçamento no CSS local. |
| RK6 | O ativo SVG, renderizado como imagem pelo `pix_icon`, não se adapta ao modo de alto contraste | Ele é decorativo e some sem perda de informação (FR-015). Basta registrar o resultado. Qualquer alternativa fora do `pix_icon` exige Complexity Tracking justificado (R4; Princípio X). |
| RK7 | A clareza dos textos na Feature 001 é verificada só pelo proprietário do produto e, opcionalmente, por 1–2 docentes | Intencional (decisão do proprietário). A avaliação estruturada com cinco docentes, de diferentes níveis de familiaridade tecnológica e áreas, está na Feature 003. |
| RK8 | Apenas o Moodle 4.5 é declarado | Intencional (FR-003). Versões 5.x entram após validação, em feature própria ou em emenda da matriz. |

## Critérios de conclusão

1. Todos os FR-001 a FR-030 implementados, sem itens fora do escopo.
2. Jobs `moodle` e `i18n-package` verdes no último commit da branch.
3. ZIP gerado pelo CI verificado: só `lang/en` e nenhum item ✗.
4. Roteiro do [quickstart.md](quickstart.md) executado em Moodle 4.5 real: en e pt_BR; fallback;
   desktop, tablet e smartphone; Boost e Classic; teclado, leitor de tela e zoom de 200%;
   atualização e desinstalação.
5. Ativo SVG e textos (en e pt_BR) aprovados pelo proprietário do produto **após** a apresentação
   das capturas reais, com registro datado em `evidence/README.md` (SC-002, FR-014, FR-015).
6. `evidence/` com evidência para CA-01 a CA-13 (SC-010), indicando a versão do Moodle e o tema.
   Nenhum CA é marcado como atendido sem evidência, e `evidence/` fica fora do pacote (dentro de
   `specs/`).
7. Constitution Check reavaliada sem novas violações.

## Complexity Tracking

Nenhuma violação a justificar. O desvio de IX/C6 registrado contra a constituição 1.1.0 foi
resolvido pela emenda 1.2.0, que redefine o C6: cada camada só é obrigatória a partir da primeira
feature com a responsabilidade correspondente, e camadas vazias são proibidas.
