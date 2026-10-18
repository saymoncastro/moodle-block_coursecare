# Implementation Plan: International Marketplace Release

**Branch**: `007-marketplace-release` | **Date**: 2026-10-19 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/007-marketplace-release/spec.md` (commit `6789f4a`, decisões do
proprietário do produto de 2026-10-19: 0.7.0 BETA; Moodle 4.5 e 5.3 LTS; titularidade pendente)

## Summary

Consolidar o CourseCare como versão candidata **0.7.0 BETA** para Moodle 4.5 LTS e 5.3 LTS, sem novas
funcionalidades: verificar e corrigir a compatibilidade com 5.3 (CI com quatro combinações e pilha local),
validar instalação, atualização e desinstalação nas duas versões, auditar segurança e privacidade,
consolidar acessibilidade e i18n, escrever a documentação internacional e os materiais da página pública,
produzir o relatório de titularidade e as matrizes de prontidão, conformidade e compatibilidade, e
preparar as instruções de submissão. Nenhuma tag, release, submissão ou merge.

**O que a pesquisa já resolveu** (research R2–R5): o carregamento do `config.php` funciona no 5.3
(`public/config.php` é um carregador); `sr-only` ainda existe no 5.3 pela camada de compatibilidade; o
cenário do Classic passa a ser pulado no 5.3 com o passo nativo `the "theme_classic" plugin is installed`;
as APIs e campos usados existem no 5.3. A confirmação vem do CI e da instalação real.

## Technical Context

**Language/Version**: PHP 8.1–8.3 (Moodle 4.5) e 8.3–8.4 (Moodle 5.3), Mustache, CSS.

**Primary Dependencies**: Moodle 4.5 LTS e 5.3 LTS core; `moodle-plugin-ci ^4` no CI; `moodle-docker`
local. Sem bibliotecas de terceiros no pacote.

**Storage**: nenhuma mudança de banco (sem passo de atualização novo).

**Testing**: CI com 4.5 × (8.1 PostgreSQL, 8.3 MariaDB) e 5.3 × (8.3 PostgreSQL, 8.4 MariaDB 11.4);
instalação, atualização e desinstalação locais nas duas versões; verificações automáticas de
acessibilidade (axe, teclado, zoom, 320 px); auditorias por roteiro.

**Target Platform**: Moodle 4.5 e 5.3, Boost (4.5 e 5.3) e Classic (4.5), desktop e celular.

**Project Type**: plugin de bloco do Moodle; versão candidata 0.7.0 BETA.

**Performance Goals**: sem regressão em relação à 0.6.0 (página até 2 s; bloco igual).

**Constraints**: nenhuma funcionalidade nova, redesign ou dependência; nenhuma compatibilidade declarada
sem teste; nenhuma submissão, publicação, tag, release ou merge sem autorização; cabeçalhos de copyright
inalterados (Q3).

**Scale/Scope**: CI, versão, um ajuste de Behat, documentação (`docs/`), materiais da página pública,
evidências e relatórios; correções só para defeitos encontrados.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Princípio | Avaliação | Situação |
| --- | --- | --- |
| I. Educação | Documentação e página pública sem promessas (redução, economia, carbono, avaliação docente) | ✅ |
| II. Simplicidade | Sem mudança de interface; auditoria visual corrige só defeitos | ✅ |
| III. Recomendações | Inalteradas | ✅ |
| IV. Honestidade | Indicadores documentados exatamente como calculados; compatibilidade só verificada; estados de prontidão só com evidência | ✅ |
| V. Integração nativa | APIs do core em 4.5 e 5.3; passo nativo do Behat para o Classic | ✅ |
| VI. Desempenho | Sem regressão; verificação por amostragem | ✅ |
| VII. Segurança e privacidade | Auditoria consolidada; Privacy API conferida; inferência em turmas pequenas documentada | ✅ |
| VIII. Internacionalização | Inglês no pacote; pt_BR pelo AMOS depois da aprovação, conforme a estratégia da constituição | ✅ |
| IX. Qualidade | Quality gates no CI das versões declaradas | ✅ |
| X. Experiência visual | Direção aprovada preservada | ✅ |
| XI. Evolução incremental | Escopo de consolidação, sem funcionalidades | ✅ |
| XII. Software livre | GPL mantida; titularidade em apuração documental, sem alteração de cabeçalhos | ✅ (publicação bloqueada até Q3) |

**Resultado do gate (pré-pesquisa e pós-design)**: **PASSA**, sem violações e sem Complexity Tracking.

## Project Structure

### Documentation (this feature)

```text
specs/007-marketplace-release/
├── plan.md, research.md, data-model.md, quickstart.md
├── contracts/
│   ├── compatibility-matrix.md
│   ├── marketplace-conformance.md
│   ├── ownership-report.md
│   └── audits.md
├── evidence/          # matrizes finais, relatórios, logs, capturas de verificação
├── checklists/requirements.md
└── tasks.md
```

### Source Code (repository root)

```text
.github/workflows/ci.yml                        # + Moodle 5.3 (PHP 8.3 PostgreSQL, PHP 8.4 MariaDB 11.4)
.github/ISSUE_TEMPLATE/, PULL_REQUEST_TEMPLATE  # mínimos
version.php                                     # 0.7.0 BETA; supported [405, 503] depois da verificação
tests/behat/block_coursecare_ui.feature         # Classic: passo de plugin instalado
README.md, CHANGES.md, CONTRIBUTING.md, SECURITY.md
docs/                                           # NOVO, fora do pacote: guias em inglês, guia do professor pt_BR,
                                                # marketplace/listing.md e capturas
.gitattributes                                  # docs/ fora do pacote
tools/package/check.php                         # regras atualizadas, se necessário
```

## Riscos

- **Falhas no 5.3 não vistas na leitura estática**: o CI as mostra; correção sem mudar comportamento;
  mudança arquitetural apresentada antes.
- **Diretrizes autenticadas do Marketplace**: requisitos novos podem aparecer na conferência humana.
- **Titularidade**: bloqueia a publicação até as respostas formais.

## Complexity Tracking

Sem itens.
