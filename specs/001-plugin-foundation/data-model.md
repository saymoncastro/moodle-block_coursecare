# Data Model: CourseCare Plugin Foundation

**Resumo**: esta feature **não cria tabelas, campos nem dados persistidos próprios** (sem
`db/install.xml`, Princípio XI; FR-029). As entidades da spec correspondem a estruturas que o core
do Moodle já mantém ou a artefatos de arquivo do repositório. Este documento existe para registrar
esse mapeamento e as regras de validação, não para propor um esquema.

## Entidades persistidas pelo core (sem dados próprios)

### Instância do bloco
- **Armazenamento**: tabela core `block_instances` (`blockname = 'coursecare'`), com contexto
  próprio (CONTEXT_BLOCK) filho do contexto do curso.
- **Campos relevantes (core)**: `parentcontextid` (curso), `pagetypepattern`, `defaultregion`,
  `defaultweight`, `configdata` (vazio: o bloco não tem configuração própria).
- **Regras**:
  - Só pode ser criada na página principal do curso (`applicable_formats`).
  - No máximo uma por página (`instance_allow_multiple = false`), o que equivale a uma por sala.
  - O conteúdo só é gerado em páginas `course-view-*` que não sejam `course-view-section-*`.
- **Ciclo de vida**: criada (adição) → visível/oculta (controle nativo) → removida (remoção ou
  desinstalação). Backup e restauração são feitos pelo core.

### Capabilities
- **Armazenamento**: tabelas core `capabilities` e `role_capabilities`, a partir de `db/access.php`.
- **Itens**: `block/coursecare:addinstance` e `block/coursecare:view`. Ver
  [contracts/capabilities.md](contracts/capabilities.md).
- **Regra**: as permissões padrão são aplicadas na instalação. Alterações institucionais ficam no
  sistema de papéis do core e são preservadas em atualizações do plugin.

## Artefatos de arquivo (não persistidos no banco)

### Catálogo de strings (inglês)
- **Arquivo**: `lang/en/block_coursecare.php`. Formato de dados puro (`$string['chave'] = 'texto';`).
- **Chaves**: `pluginname`, `tagline`, `intro`, `coursecare:addinstance`, `coursecare:view`,
  `privacy:metadata`.
- **Regras**: conjunto canônico. Toda chave usada no código existe aqui. Sem title case.

### Tradução de desenvolvimento
- **Arquivo**: `translations/<idioma>/block_coursecare.php` (nesta feature, `pt_br`).
- **Regras** (validadas por `tools/i18n/check.php`): mesmas chaves do inglês; mesmos parâmetros
  `{$a}`/`{$a->x}`; nenhum valor vazio; só sintaxe de dados; sem chaves duplicadas.
- **Estados**:

  | Estado | Onde está | Quem usa |
  | --- | --- | --- |
  | versionada | repositório | mantenedor |
  | importada | `<dataroot>/lang/pt_br_local/`, via `tool_customlang` | desenvolvimento e homologação |
  | contribuída | AMOS, após a publicação | sites, pelo pacote pt_br oficial |

### Pacote distribuível
- **Arquivo**: `build/block_coursecare-<release>.zip`, com raiz `coursecare/`.
- **Regras**: só os arquivos da allowlist (ver [plan.md](plan.md), "Estratégia de distribuição");
  nenhum idioma além de `en`; nenhum item de `translations/`, `tools/`, `specs/` ou de Spec Kit/CI.

### Relatório de verificação
- **Forma**: saída das CLIs (texto, uma linha por problema) e código de saída. Ver
  [contracts/dev-tools-cli.md](contracts/dev-tools-cli.md).
- **Campos por problema**: severidade, regra, idioma, arquivo, chave e detalhe.

### Ativo visual
- **Arquivo**: `pix/sprout.svg`. Original, GPL v3+, sem texto, decorativo.
- **Regra**: integrado somente após aprovação registrada em `evidence/`.

## Dados pessoais

Nenhum. O plugin não lê nem grava dados de usuários. O `null_provider` declara isso pela Privacy API
(FR-005; Princípio VII).
