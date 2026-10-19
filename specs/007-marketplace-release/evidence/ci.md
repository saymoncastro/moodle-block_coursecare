# CI e matriz de compatibilidade (Feature 007, T006, T027)

## Runs do CI (GitHub Actions, `.github/workflows/ci.yml`)

| Run | Commit | Resultado | Observação |
| --- | --- | --- | --- |
| 39175280146 | `d33c179` | falha nos dois jobs de 5.3 | PHPUnit 11: helper `result()` colidia com o método final `TestCase::result()`; Behat: 5 cenários com contraste 4,38:1 no painel do bloco na gaveta do 5.3 |
| 39176211801 | `251c2ca` | 4 jobs Moodle verdes; `i18n-package` falhou | `SECURITY.md` entrava no ZIP (corrigido com `export-ignore`) |
| 39177141603 | `aa6bcc4` | **verde** (5 jobs) | base para declarar 5.3 (T009) |
| 39178520273 | `c31bb59` | **verde** (5 jobs) | código final: `supported` = [405, 503]; depois dele só documentação e evidências |

Cada job Moodle roda `moodle-plugin-ci`: phplint, phpmd, phpcs e phpdoc (`--max-warnings 0`), validate,
savepoints, mustache, grunt (`--max-lint-warnings 0`), PHPUnit (`--fail-on-warning`) e Behat (Chrome, com
os cenários de acessibilidade axe). O job `i18n-package` roda lint das ferramentas, testes das
ferramentas, paridade de tradução, build e verificação do pacote.

## Matriz final Moodle × PHP × banco × resultado

| Moodle | PHP | Banco | Onde | Verificações | Resultado |
| --- | --- | --- | --- | --- | --- |
| 4.5 LTS | 8.1 | PostgreSQL 17 | CI 39178520273 | completo; Behat inclui o cenário Classic | ✅ |
| 4.5 LTS | 8.3 | MariaDB 11.4 | CI 39178520273 | completo; Behat inclui o cenário Classic | ✅ |
| 5.3 LTS | 8.3 | PostgreSQL 17 | CI 39178520273 | completo; cenário Classic pulado (tema ausente do núcleo) | ✅ |
| 5.3 LTS | 8.4 | MariaDB 11.4 | CI 39178520273 | completo; cenário Classic pulado | ✅ |
| 4.5.15 | 8.3 | PostgreSQL 17 | local, 2026-10-18 | ZIP: instalação, tarefa, atualização 0.6.0 → 0.7.0 com dados, desinstalação com dados | ✅ (`install.md`, `upgrade.md`) |
| 5.3+ (20261013) | 8.3 | PostgreSQL 17 | local, 2026-10-18 | ZIP: instalação, tarefa, CLI, atualização 0.6.0 → 0.7.0 com dados, desinstalação com dados; atualização do Moodle 4.5 → 5.3 com dados | ✅ (`install.md`, `upgrade.md`) |
| 4.5.15 | 8.1 | PostgreSQL 17 | local (demonstração), 2026-10-18 | auditoria visual e de acessibilidade, Boost e Classic | ✅ (`visual-accessibility.md`) |
| 5.3+ (20261013) | 8.3 | PostgreSQL 17 | local (demonstração), 2026-10-18 | auditoria visual e de acessibilidade, Boost | ✅ (`visual-accessibility.md`) |

O site local de 4.5 usado para o ZIP rodou com PHP 8.3 (o contrato previa 8.1); o PHP 8.1 com 4.5 está
coberto pelo CI e pelo site de demonstração.

PHPUnit e Behat rodaram no CI, não localmente nesta feature (o ambiente local de 4.5 precisaria
reinicializar o PHPUnit depois da mudança de versão; o de 5.3 não foi inicializado). Com isso, as suítes
completas de cada combinação declarada estão no CI acima.

## Desempenho (amostragem)

Mesma sala de demonstração e mesmo código 0.7.0 (`tools/dev/bench/render.php`): bloco 1,50 ms (4.5) e
1,46 ms (5.3) por renderização, com 5 leituras do banco; página 10,85 ms (4.5) e 11,34 ms (5.3), com 11,4
leituras. Desde a 0.6.0 só mudaram uma regra de CSS, uma string e `version.php`, sem código PHP de
execução; o desempenho medido nas features anteriores segue válido.
