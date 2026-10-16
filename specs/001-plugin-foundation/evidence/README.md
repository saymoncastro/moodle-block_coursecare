# Evidências — Feature 001

Evidências da validação da Feature 001 (FR-030, CA-13). Ficam em `specs/`, fora do pacote
distribuível. Um critério só é marcado como atendido com o link da evidência. Todos os dados são
fictícios.

**Ambiente comum**:
- Moodle **4.5.15** (Build 20261005, `MOODLE_405_STABLE`), via moodle-docker com PHP 8.1 e
  PostgreSQL.
- Site de desenvolvimento em http://localhost:8045, com o plugin montado.
- Site limpo para o ZIP em http://localhost:8046, sem montagem.
- Navegador das capturas: Chromium headless shell 1234 (Playwright 1.48).
- CI: GitHub Actions com `moodle-plugin-ci` 4.5.11, MOODLE_405_STABLE e {PHP 8.1 + pgsql, PHP 8.3 + mariadb}.
- Data: 2026-10-16.

**Legenda de status**: ✅ validado com evidência · ⚠️ validado parcialmente (pendência descrita)
· ⏳ aguardando aprovação do proprietário do produto.

## Critérios de aceitação

| CA | Evidência | Tema | Idioma | Status |
| --- | --- | --- | --- | --- |
| CA-01 Instalação no Moodle 4.5 | [install](logs/install.txt) (site com o plugin montado); [instalação do ZIP em site sem o plugin, E2](logs/zip-install.txt) (7 s, sem erros); [atualização](logs/upgrade.txt); [desabilitar e reabilitar com instância, E1](logs/playwright-e1.json) + `screenshots/boost-en-1280-{disabled,reenabled}.png`; [desinstalação com instâncias](logs/uninstall-with-instances.txt); [sem instâncias](logs/uninstall-empty.txt) | Boost | en | ✅ |
| CA-02 Adição por usuário autorizado | [fluxo de adição pela interface](logs/playwright-add.json) (oferecido 1×, adicionado, não oferecido de novo); [matriz T020](display-matrix.md) (19/19, inclui seção, atividade e troca de formato); [restauração sem duplicar](logs/restore-merge.txt); Behat `block_coursecare_permissions` e `_display` no CI | Boost | en | ✅ |
| CA-03 Identidade e mensagem inicial | Behat `block_coursecare_ui`; PHPUnit `test_initial_content`; capturas `screenshots/boost-{en,pt_br}-1280-main*.png` e `classic-*` | Boost, Classic | en, pt_BR | ⏳ validado tecnicamente; aprovação visual e textual pendente (FR-014, FR-015, SC-002) |
| CA-04 Bloqueio sem permissão | [matriz T020](display-matrix.md) (professor sem edição, estudante, visitante, concessão de view, `addinstance` proibido com controle); [RK2](logs/playwright-visual.json); PHPUnit (arquétipos, visitante, overrides); Behat de permissões | Boost | en | ✅ |
| CA-05 Funciona em inglês | `screenshots/boost-en-*`, `classic-en-*`; [visual](logs/playwright-visual.json) | Boost, Classic | en | ✅ |
| CA-06 Funciona em pt_BR (tradução de desenvolvimento) | [importação com tool_customlang](logs/customlang-import.txt); `screenshots/boost-pt_br-*`, `classic-pt_br-*`; PHPUnit `translation_test` (CI) | Boost, Classic | pt_BR | ✅ |
| CA-07 Fallback para inglês | `screenshots/boost-pt_br-1280-fallback-{intro,none}*.png`, `boost-es-1280-no-translation*.png`; [visual](logs/playwright-visual.json); PHPUnit `translation_test` | Boost | pt_BR, es | ✅ |
| CA-08 Paridade detecta inconsistências | 28/28 testes das ferramentas e paridade com 0 erros em [CI](logs/ci-run-38914763604.txt) e localmente (PHP 8.1) | — | — | ✅ |
| CA-09 Pacote sem traduções adicionais | [verificação do ZIP real](logs/package-check.txt) (0 erros, 17 arquivos, só `lang/en`; controle com cópia ingênua: 195 erros); [ZIP instalado sem `translations/`](logs/zip-install.txt); job `i18n-package` | — | — | ✅ |
| CA-10 Responsivo e acessível | [acessibilidade](accessibility.md) (axe no CI, contraste de 16,2:1 e 15,4:1, 320 px, 200%, teclado, árvore de acessibilidade, ícone que não carrega) | Boost, Classic | en, pt_BR | ⚠️ falta leitor de tela real e alto contraste real do sistema operacional |
| CA-11 Sem modificação do core | [git status do core](logs/core-git-status.txt) (vazio) | — | — | ✅ |
| CA-12 Independente do tema | Behat Classic com axe; `screenshots/classic-*` | Boost, Classic | en, pt_BR | ✅ |
| CA-13 Evidências documentadas | este diretório | — | — | ⏳ completa após as aprovações e o fechamento de CA-10 |

## Execuções de CI

| Execução | Commit | Resultado |
| --- | --- | --- |
| [38913588080](https://github.com/saymoncastro/moodle-block_coursecare/actions/runs/38913588080) | 987b5ae | ❌ phpcs (formatação de chamadas multilinha) e Behat (faltava a tag `@accessibility`); PHPUnit 18/18; `i18n-package` ✅. Confirmou que `IGNORE_PATHS` excluiu `tools/` e `translations/` (phpcs analisou 8/8 arquivos do plugin). |
| [38914763604](https://github.com/saymoncastro/moodle-block_coursecare/actions/runs/38914763604) | 49d0484 | ✅ todos os jobs: phpcs 8/8, PHPUnit 18/18, Behat 16/16 (com acessibilidade), `i18n-package` (php -l, 28/28, paridade, ZIP). [Resumo](logs/ci-run-38914763604.txt) |
| [38922204004](https://github.com/saymoncastro/moodle-block_coursecare/actions/runs/38922204004) | 02d41a5 | ✅ todos os jobs |

## Aprovações

Preenchida somente pelo proprietário do produto, ou por solicitação dele com data, depois da
apresentação das capturas reais (FR-014, FR-015, SC-002). **Nenhuma aprovação registrada até o
momento.**

| Item | Decisão | Data | Observações |
| --- | --- | --- | --- |
