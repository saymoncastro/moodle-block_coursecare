# Auditoria do pacote (Feature 007, T018)

Verificado em 2026-10-18 pelo agente sobre `build/block_coursecare-0.7.0.zip` (`tools/package/build.sh`,
`git archive` com `export-ignore`).

| Item | Resultado |
| --- | --- |
| Estrutura | Pasta raiz `coursecare/` (nome Frankenstyle `block_coursecare`); 84 arquivos |
| Conteúdo | `block_coursecare.php`, `overview.php`, `settings.php`, `version.php`, `styles.css`, `README.md`, `CHANGES.md`, `LICENSE`, `classes/` (23), `cli/` (1), `db/` (5), `lang/en` (1), `pix/` (1), `templates/` (11), `tests/` (34) |
| Fora do pacote | `tools/`, `specs/`, `docs/`, `translations/`, `.specify/`, `.claude/`, `.github/`, `CONTRIBUTING.md`, `SECURITY.md`, `build/` (`.gitattributes`) |
| Verificador | `tools/package/check.php`: 0 erro (lista de permissão de caminhos; também no job `i18n-package` do CI). Achado nesta feature: `SECURITY.md` entrava no ZIP; corrigido (`export-ignore`) |
| Arquivos indevidos | Nenhum log, credencial, `.env`, imagem de captura, dado de demonstração, `phpunit.xml`, `composer`, `node_modules` ou `.DS_Store` |
| Idioma | Só `lang/en` (o pt_br vai ao AMOS depois da aprovação) |
| Metadados (`version.php`) | `component` `block_coursecare`; `version` 2026102000; `release` 0.7.0; `maturity` `MATURITY_BETA`; `requires` 2024100700 (4.5); `supported` [405, 405] até T009 |
| Licença | `LICENSE` com a GNU GPL v3; todos os arquivos PHP com o cabeçalho GPL v3 ou posterior e `@copyright`; templates com o cabeçalho GPL (sem `@copyright`, como no núcleo); `styles.css`, `db/install.xml` e os `.feature` sem cabeçalho, como no núcleo. Nenhum cabeçalho alterado (Q3) |
| Terceiros | Nenhuma biblioteca de terceiros (sem `thirdpartylibs.xml`) |
| Instalação | ZIP instalado e desinstalado sem aviso em 4.5 e 5.3 (`install.md`) |
