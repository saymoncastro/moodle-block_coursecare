# Evidências da Feature 006 — Usage History

Branch `006-usage-history` (local, sem push; base `main` em `c019a00`). Nenhum item é marcado sem evidência;
validações humanas pendentes ficam como pendentes.

## Cenários e critérios

| Item | Evidência | Situação |
| --- | --- | --- |
| CT-01 primeira análise | `diagnostic_store_test::test_history_point_saved_with_result` | ✅ |
| CT-02 dias diferentes | idem (2 pontos em 2 dias) | ✅ |
| CT-03 mesmo dia | idem (1 ponto, com a última análise) | ✅ |
| CT-04 reexecução e concorrência | idem (mesmo dia atualiza); lock por sala da 002; índice único (sala, dia), comprovado no site de demonstração (a tentativa de inserir um segundo ponto no mesmo dia foi recusada pelo banco) | ✅ |
| CT-05 falha | idem (`save_failure` não muda pontos); `test_history_point_is_atomic` (erro na transação não muda resultado nem ponto) | ✅ |
| CT-06 recuperação | idem (nova análise bem-sucedida grava o ponto do dia) | ✅ |
| CT-07 cobertura parcial | idem (`partial` = 1); `test_history_states` (marca na tabela) | ✅ |
| CT-08 mudança de método | `history_view_test::test_method_changed`; `test_history_states` (entre as duas últimas e antes delas) | ✅ |
| CT-09 atualização | `upgradelib_test::test_seed_history`; site de demonstração: 8 resultados → 8 pontos | ✅ |
| CT-10 retenção | `queue_analyses_test::test_history_retention` (limite, lote, padrão de 365 dias) | ✅ |
| CT-11 sala excluída / bloco removido | `queue_analyses_test::test_orphans_removed`; `diagnostic_store_test` (`delete_for_course`) | ✅ |
| CT-12 restauração, cópia | pontos por `courseid`; o plugin não faz backup do histórico (nada a copiar) | ✅ (por construção) |
| CT-13 integridade | `diagnostic_store_test` (último ponto = resultado atual); conferência T030 | ✅ |
| CT-14 página com 0, 1, 2 e muitas medições | `course_overview_test::test_history_section`, `test_history_states`; Behat `block_coursecare_history.feature` | ✅ |
| CT-15 acesso | página e capability inalteradas; testes de acesso da 003 na suíte | ✅ |
| CT-16 privacidade | `test_history_categories` (nenhum nome de estudante ou atividade); tabela só com totais da sala | ✅ |
| CT-17 bancos | CI run 39159055662: PostgreSQL e MariaDB | ✅ |
| CT-18 desempenho | T029 abaixo | ✅ |
| CT-19 acessibilidade e visual | Behat `@accessibility`; T032 | ⏳ aprovação visual |
| CT-20 textos | `test_history_states` (palavras proibidas en e pt_br); revisão de tom T031 | ⏳ aprovação de tom |
| SC-001 | CT-01 a CT-13 | ✅ |
| SC-002 | `diagnostic_store_test`; T030 | ✅ |
| SC-003 | T029 | ✅ |
| SC-004 | T029 | ✅ |
| SC-005 | Behat `@accessibility`; T032 | ✅ (automático) |
| SC-006 | T031, T032 | ⏳ |
| SC-007 | CI run 39159055662 (`515eaa6`) | ✅ |

## Desempenho (T029)

Site de desenvolvimento (PostgreSQL 17, PHP 8.1). Saídas em `logs/t029/`.

| Medição | Resultado |
| --- | --- |
| Volume: 5.000 salas × 365 dias | 1.827.201 linhas; tabela 234 MB + índices 130 MB ≈ 0,36 GB (estimativa do plano: 0,3 GB) |
| Leitura do histórico de uma sala (365 pontos) | 0,8 ms |
| Limpeza de até 10.000 pontos antigos (um lote) | 67 ms |
| Gravação do ponto (consulta + inserção ou atualização) | 0,18 ms |
| Análise de uma sala (12 a 32 ms nas salas medidas) | o ponto representa de 0,6% a 1,5% (limite de SC-004: 5%) |
| `save_result()` completo com o ponto, 5.000 salas | 0,98 ms por sala |
| Atualização: primeiro ponto de 5.000 salas com resultado | 1,1 s |
| Página com 365 pontos (média de 20) | 37,7 ms, 5,5 leituras |
| Página com 1.826 pontos, retenção máxima (média de 20) | 33,5 ms, 5,5 leituras; 89 ms na primeira abertura depois de limpar os caches |
| Bloco | 0,84 ms e 4 leituras (0.5.0: 0,83 ms e 4 leituras); código do bloco inalterado |

Os tempos são do servidor (renderização); a abertura no navegador não foi cronometrada. A abertura da
página continua muito abaixo de 2 s.

## Conferência à mão (T030)

Site de demonstração atualizado para 0.6.0: a atualização criou 8 pontos (um por sala com resultado); a
ferramenta de demonstração acrescentou pontos passados fictícios em *Biologia celular* (três meses, com
uma semana sem análise e uma redução recente) e *História* (mudança de método há 5 dias).

| Sala | Último ponto × resultado atual | Frase mostrada | Conferência |
| --- | --- | --- | --- |
| Biologia celular | 8.347.152 = 8.347.152 | aumentaram 182,8 KB desde 16/10 (de 7,8 MB para 8,0 MB) | 8.347.152 − 8.160.000 = 187.152 B = 182,8 KB ✅ |
| História | 450.000 = 450.000 | aumentaram 78,1 KB (de 361,3 KB para 439,5 KB); e, depois da revisão, a frase da mudança de método | 80.000 B = 78,1 KB ✅ |
| Química geral | 5.250.000 = 5.250.000 | primeira medição guardada | 1 ponto ✅ |

A conferência mostrou uma divergência com a spec (US3, cenário 4: uma mudança de método anterior às
duas últimas medições não era indicada), corrigida em `history_view` (`methodchange`) com testes.

## Avaliação do proprietário do produto (2026-10-19) e ajustes

| Decisão | Ajuste | Verificação |
| --- | --- | --- |
| T031: "o volume dos arquivos registrados" nas frases de variação | `history:increased`, `history:decreased`, `history:unchanged` em en e pt_br | testes da página; capturas |
| T031: início do histórico sem citar a versão | `history:limit_start`: "O histórico começa quando as medições desta sala passaram a ser guardadas; não há medições anteriores." (a versão 0.6.0 fica no README, CHANGES e na spec) | paridade en/pt_br |
| T032: gráfico com até 90 dias, histórico completo na tabela | `history_view::CHART_DAYS` = 90: o gráfico vai do primeiro dia medido dos últimos 90 dias até a última medição; nova frase `history:chartperiod` quando há medições mais antigas; a tabela continua com todas | `history_view_test::test_chart_window`; `test_history_states` (81 rótulos para uma janela que começa há 80 dias; 6 linhas na tabela) |
| SC-003: carregamento real no navegador | medido abaixo | — |

Strings: 26 chaves novas (`history:chartperiod` acrescentada); paridade en/pt_br: 162 chaves em cada
catálogo, nenhuma faltando. Testes depois dos ajustes: PHPUnit 256 testes, 2563 asserções, OK; Behat 69
cenários, 769 passos; `tools/i18n/check.php` com 0 erros.

### SC-003 no navegador

Chrome do contêiner Selenium do site de demonstração (mesma máquina, condições normais, cache do navegador
ativo), 11 aberturas de cada página; saídas em `logs/sc003-browser/`.

| Página | Primeira abertura | Mediana das 10 seguintes | Servidor (rodapé de desempenho) | HTML |
| --- | --- | --- | --- | --- |
| *Biologia celular* (cerca de 90 dias) | 2.816 ms (primeira depois de limpar os caches) | 878 ms | 0,13 s | 225 KB |
| *Estatística* (1 ano, 351 medições) | 1.419 ms | 934 ms | 0,09 s | 319 KB |
| *Línguas* (5 anos, 1.808 medições) | 1.777 ms | 997 ms | 0,19 s | 901 KB (57 KB comprimido) |
| *Química geral* (1 medição, sem gráfico) | 1.034 ms | 592 ms | — | — |
| *Artes* (sem resultado) | 622 ms | 575 ms | 0,07 s | 166 KB |

Todas abaixo de 2 s em condições normais. A primeira abertura depois de limpar os caches (2,8 s) é a
limitação já documentada na Feature 004 (carga a frio). Com 5 anos, a tabela de 1.808 linhas faz o HTML
chegar a 901 KB (57 KB transferidos); o servidor leva 0,19 s. Sem regressão que justifique investigação.

## Revisão de tom (T031) — ajustes aplicados, aguardando a aprovação final

Strings em `contracts/history-ui.md` (26 chaves, en e pt_br), com os dois ajustes acima.

## Validação visual (T032) — capturas finais aguardando aprovação

Site de demonstração 0.6.0 (Moodle 4.5.15, PHP 8.1, PostgreSQL 17), professor de demonstração, Boost e
Classic, en e pt_BR. Retenção do site de demonstração: 5 anos (para mostrar o histórico completo na
tabela). Os valores passados são dados fictícios do site de demonstração; o ponto de hoje vem da análise
real (por isso, em *Línguas*, a última variação é uma redução).

Capturas em `screenshots/boost/` e `screenshots/classic/` (18 por tema, en e pt_br):

| Arquivo | Pedido |
| --- | --- |
| `01-90days-desktop-*` | desktop com cerca de 90 dias (*Biologia celular*, lacuna de uma semana) |
| `02-90days-mobile-*`, `02b-90days-mobile-open-*` | celular com cerca de 90 dias, fechado e com a tabela aberta |
| `03-365days-*` | histórico de 365 dias (*Estatística*; gráfico com os últimos 90 dias e a frase do período) |
| `04-5years-*` | retenção de cinco anos (*Línguas*; gráfico com 90 dias) |
| `05-one-measurement-*` | apenas uma medição (*Química geral*; sem gráfico) |
| `06-method-change-*` | mudança de método (*História*) |
| `07-table-open-*`, `08-table-open-5years-*` | tabela detalhada aberta (90 dias e 5 anos) |

Verificações (`logs/t032/<tema>-<língua>/`), iguais nas quatro combinações: axe-core sem violações nas cinco
páginas; 16 paradas de teclado, todas com indicador, e o trecho recolhido abre com Enter; sem rolagem
horizontal a 200% (5 anos) e a 320 px (90 dias e 5 anos; a tabela rola dentro do seu contêiner).

Leitor de tela: não realizado (exige uma pessoa); o gráfico tem título, e a tabela recolhida tem os mesmos
dados.

## Revisão de código (2026-10-19)

| Achado | Correção | Teste |
| --- | --- | --- |
| A legenda da tabela usava `visually-hidden`, classe que o Moodle 4.5 (Bootstrap 4) não tem: aparecia como um título cinza repetindo "Medições e categorias" (visível nas capturas) | `sr-only`, a classe do Moodle 4.5 | `test_history_section` (legenda `sr-only`); capturas refeitas |
| Rótulos do gráfico sem ano (`strftimedateshort`); com retenção de até 5 anos, datas repetidas | Dia e mês dentro do mesmo ano; com o ano (`strftimedatefullshort`) quando o histórico atravessa anos | `test_history_states` (rótulos nos dois casos) |
| Duas linhas em branco em `queue_analyses.php` | Removida | — |

Verificados sem problema: ponto na mesma transação do resultado, lock por sala e índice único; remoção com
a sala e com o último bloco; retenção igual na página e na limpeza; atualização sem duplicar ao rodar de
novo; datas da tabela com o ano; acesso, privacidade e leituras.

Capturas e verificações refeitas depois das correções (as desta seção de evidências).

## Testes e CI

- PHPUnit `--testsuite block_coursecare_testsuite`: **255 testes, 2549 asserções, OK** (PHP 8.1, PostgreSQL 17),
  depois da revisão de código.
- Behat `--tags=@block_coursecare`: **69 cenários, 769 passos, todos verdes** (inclui os 8 da 006, com axe em
  desktop e celular).
- `tools/i18n/check.php`: 0 erros.
- Atualização do site de desenvolvimento e do site de demonstração para 0.6.0: sem erros.
- phpcs e Mustache lint: não rodados localmente; verdes no CI (T034).
- Pacote (T033): `tools/package/build.sh` e `tools/package/check.php build/block_coursecare-0.6.0.zip`:
  **0 erros, 84 arquivos** (a primeira verificação recusou `db/upgradelib.php`, arquivo padrão do Moodle
  exigido pela atualização; acrescentado à lista de arquivos permitidos da ferramenta).
- CI (T034): run abaixo.

## Runs de CI (T034)

| Commit | Run | Data | Jobs | Resultado |
| --- | --- | --- | --- | --- |
| `515eaa6` (US1–US3, Polish, correções da revisão de código) | [39159055662](https://github.com/saymoncastro/moodle-block_coursecare/actions/runs/39159055662) | 2026-10-19 | `moodle-plugin-ci` completo (phpcs, Mustache lint, PHPUnit e Behat) em PHP 8.1/PostgreSQL e PHP 8.3/MariaDB; `i18n-package` | **verde** |

O código vigente é o de `515eaa6`; os commits seguintes, se houver, só mudam documentação.
