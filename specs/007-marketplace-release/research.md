# Research: International Marketplace Release (Feature 007)

Fontes externas com datas na spec ("Fontes consultadas"). Leitura do código do Moodle 5.3 feita num clone
raso de `MOODLE_503_STABLE` (`344c92d5b`, "weekly release 5.3+", Build 20261013) em 2026-10-19.

## R1. Ambientes de verificação

**Decisão**: duas pilhas locais com `moodle-docker` (já com o suporte a 5.3, `mdl-5.3`): a existente de 4.5
(PHP 8.1/8.3, PostgreSQL 17, MariaDB 11.4) e uma nova de 5.3 (PHP 8.3, PostgreSQL 17) a partir do clone; no
CI, `moodle-plugin-ci ^4` com quatro combinações: 4.5 × PHP 8.1 × PostgreSQL, 4.5 × PHP 8.3 × MariaDB,
5.3 × PHP 8.3 × PostgreSQL, 5.3 × PHP 8.4 × MariaDB 11.4.

**Motivo**: os requisitos de ambiente diferem (5.3 exige PHP 8.3+, PostgreSQL 17, MariaDB 11.4); cada
versão declarada precisa de testes nela (FR-010, FR-011).

## R2. Estrutura `public/` (5.1+)

**Achado**: no 5.3, o código web fica em `public/`, e `public/config.php` é um carregador que inclui o
`config.php` da raiz. O plugin fica em `public/blocks/coursecare`; `overview.php`
(`__DIR__ . '/../../config.php'`) e `cli/diagnostics.php` (`__DIR__ . '/../../../config.php'`) chegam a
`public/config.php`, que carrega a configuração real.

**Decisão**: nenhuma mudança de código; verificação por execução real do CLI e da página no 5.3.

## R3. Bootstrap 5

**Achado**: os templates usam uma só classe só do Bootstrap 4 (`sr-only`, na legenda da tabela do
histórico). O 5.3 ainda a define em `theme/boost/scss/moodle/bs4-compat.scss` (camada de compatibilidade,
obsoleta); `visually-hidden` não existe no 4.5.

**Decisão**: manter `sr-only` (funciona nas duas versões declaradas); registrar como dívida a trocar quando
o 4.5 deixar de ser suportado. Nenhuma outra classe ou atributo do Bootstrap 4 nos templates.

## R4. Tema Classic

**Achado**: o 5.3 remove o Classic do core (MDL-88351); o passo do Behat `the :plugin plugin is installed`
existe no 4.5 e no 5.3 e pula o cenário quando o plugin não está instalado.

**Decisão**: o cenário do Classic ganha `Given the "theme_classic" plugin is installed`: roda no 4.5,
é pulado no 5.3 (como a orientação do proprietário do produto pede). Capturas e verificações visuais do
Classic só no 4.5.

## R5. APIs usadas pelo plugin no 5.3

**Achado (leitura estática)**: `core\chart_bar`, `render_chart($chart, $withtable)`, `get_enrolled_sql`,
`usergetmidnight`, `get_max_upload_file_size`, `format_time`, os campos de `assign` lidos pela 005
(`maxattempts`, `attemptreopenmethod`, `teamsubmission`, `teamsubmissiongroupingid`,
`preventsubmissionnotingroup`) e a configuração `assignsubmission_file/maxbytes` continuam iguais. O
Chart.js do 5.3 mantém o envio de `ticks.min` (motivo do gráfico de barras da 006 continua válido).

**Decisão**: confirmar pelo CI (PHPUnit e Behat completos) e por instalação real; corrigir só o que falhar.

## R6. Versão 0.7.0 BETA

**Decisão**: `version` 2026102000, `release` '0.7.0', `maturity` MATURITY_BETA, `requires` 2024100700
(4.5), `supported` [405, 503] só depois da verificação de 5.3 (FR-010). Sem mudança de banco (nenhum passo
de atualização novo).

## R7. Documentação

**Decisão**: documentação em inglês em `docs/` no repositório (link de documentação do Marketplace para a
pasta no GitHub): `installation.md`, `upgrading.md`, `configuration.md`, `teacher-guide.md`,
`indicators.md`, `privacy.md`, `troubleshooting.md`; `README.md` como porta de entrada (com limitações e
suporte), `CHANGES.md`, `CONTRIBUTING.md`, `SECURITY.md`. Português do Brasil: `docs/pt_br/guia-do-professor.md`
(identidade AVA Consciente). `docs/` fica fora do pacote (`export-ignore`), como `specs/`.

**Motivo**: o Marketplace pede documentação e instruções de instalação; o pacote deve conter só o
necessário para o plugin funcionar.

## R8. Página pública

**Decisão**: `docs/marketplace/listing.md` com nome, descrição curta, descrição longa em inglês, tipo,
versões, links (código, issues, documentação), e as capturas reais (site de demonstração 5.3 e 4.5,
dados fictícios) em `docs/marketplace/screenshots/`, com a lista de afirmações proibidas conferida.

## R9. Segurança e privacidade

**Decisão**: auditoria por roteiro (contracts/audits.md) sobre `overview.php`, o bloco, o CLI, as tarefas,
os coletores e os templates; Privacy API conferida contra as cinco tabelas; risco de inferência em turmas
pequenas avaliado e documentado em `docs/privacy.md`.

## R10. Titularidade

**Decisão**: relatório documental (contracts/ownership-report.md) com fatos verificáveis do repositório e
perguntas objetivas às instâncias competentes; sem alterar cabeçalhos nem concluir titularidade (Q3).

## R11. Validações humanas anteriores

**Decisão**: consolidar as 13 tarefas abertas em A–E (spec, Clarifications); executar agora as do grupo A
(verificações técnicas: Constitution Checks de 003, 004 e 005, registro da decisão de formatação de
tamanhos da 003, quickstart limpo automatizável).
