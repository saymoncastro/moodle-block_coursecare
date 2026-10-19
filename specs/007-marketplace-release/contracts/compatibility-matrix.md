# Contract: matriz de compatibilidade

| Moodle | PHP | Banco | Onde | Verificações |
| --- | --- | --- | --- | --- |
| 4.5 LTS | 8.1 | PostgreSQL 17 | CI | moodle-plugin-ci completo, PHPUnit, Behat (inclui Classic e axe) |
| 4.5 LTS | 8.3 | MariaDB 11.4 | CI | idem |
| 5.3 LTS | 8.3 | PostgreSQL 17 | CI | idem (Classic pulado) |
| 5.3 LTS | 8.4 | MariaDB 11.4 | CI | idem (Classic pulado) |
| 4.5 LTS | 8.1 | PostgreSQL 17 | local | instalação pelo ZIP, atualização 0.6.0 → 0.7.0 com dados, desinstalação, CLI, auditoria visual (Boost e Classic) |
| 5.3 LTS | 8.3 | PostgreSQL 17 | local | instalação pelo ZIP, atualização 0.6.0 → 0.7.0 com dados, desinstalação, CLI, auditoria visual (Boost) |

`supported` = [405, 503] só quando todas as linhas de 5.3 estiverem verdes; a matriz final registra o run
de CI e a data de cada linha.
