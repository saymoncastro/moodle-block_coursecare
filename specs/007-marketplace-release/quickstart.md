# Quickstart: validação da Feature 007

Contratos: [compatibility-matrix.md](contracts/compatibility-matrix.md),
[marketplace-conformance.md](contracts/marketplace-conformance.md), [audits.md](contracts/audits.md),
[ownership-report.md](contracts/ownership-report.md).

## §1 CI

Push da branch: as quatro combinações de `contracts/compatibility-matrix.md` e `i18n-package` verdes.

## §2 Pilha local 5.3

Clone de `MOODLE_503_STABLE` em `coursecare-devenv/moodle-503`, `moodle-docker` com PHP 8.3 e PostgreSQL
17, plugin montado em `public/blocks/coursecare`; instalação limpa pelo ZIP com depuração de
desenvolvedor; `php public/blocks/coursecare/cli/diagnostics.php --run --course=ID` (via
`admin/cli` do container); página e bloco.

## §3 Atualização e desinstalação (4.5 e 5.3)

Instalar 0.6.0, criar dados (salas, análises, histórico), atualizar para 0.7.0, comparar as tabelas;
repetir a atualização; desinstalar e conferir que nenhuma tabela ou configuração do plugin fica.

## §4 Pacote

`tools/package/build.sh` e `php tools/package/check.php build/block_coursecare-0.7.0.zip`.

## §5 Auditorias e documentação

Roteiros de `contracts/audits.md`; leitura guiada da documentação (SC-008).
