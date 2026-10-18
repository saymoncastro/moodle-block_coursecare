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
| CT-17 bancos | CI (PostgreSQL e MariaDB) | ⏳ push depende de autorização |
| CT-18 desempenho | T029 abaixo | ✅ |
| CT-19 acessibilidade e visual | Behat `@accessibility`; T032 | ⏳ aprovação visual |
| CT-20 textos | `test_history_states` (palavras proibidas en e pt_br); revisão de tom T031 | ⏳ aprovação de tom |
| SC-001 | CT-01 a CT-13 | ✅ |
| SC-002 | `diagnostic_store_test`; T030 | ✅ |
| SC-003 | T029 | ✅ |
| SC-004 | T029 | ✅ |
| SC-005 | Behat `@accessibility`; T032 | ✅ (automático) |
| SC-006 | T031, T032 | ⏳ |
| SC-007 | CI | ⏳ |

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

## Revisão de tom (T031) — pendente de aprovação

Strings em `contracts/history-ui.md` (25 chaves, en e pt_br).

## Validação visual (T032) — pendente de aprovação

Site de demonstração 0.6.0 (Moodle 4.5.15, PHP 8.1, PostgreSQL 17), professor de demonstração, Boost e
Classic, en e pt_BR (`pt_br_local` atualizado com `translations/pt_br`).

- **Capturas** em `screenshots/boost/` e `screenshots/classic/` (14 por tema):
  - *Biologia celular*: seção com gráfico (3 meses, lacuna de uma semana, redução recente), fechada e com a
    tabela aberta (`page-history-*`, `pageopen-history-*`), desktop e celular;
  - *Química geral*: uma única medição, sem gráfico (`page-history-first-*`);
  - *História*: mudança de método anterior às duas últimas medições (`page-history-method-*`);
  - bloco de *Biologia celular* (`block-*`): inalterado.
- **Verificações** (`checks.py`, resultados em `logs/t032/<tema>-<língua>/`), iguais nas quatro combinações:
  axe-core sem violações nas três páginas; 16 paradas de teclado, todas com indicador, e o trecho recolhido
  abre com Enter; sem rolagem horizontal a 200% e a 320 px (a tabela rola dentro do seu contêiner).
- **Revisão do gráfico feita nas capturas** (research R9): a primeira versão, de linhas, começava o eixo
  perto dos valores (o Moodle 4.5 envia o mínimo do eixo num formato que o Chart.js 4 ignora) e ficava alta
  demais nas capturas (altura do core em função da janela). Agora: barras a partir de zero, uma por dia
  medido, nenhuma nos dias sem análise, altura fixa e discreta, verde da página. Capturas e verificações
  refeitas sobre essa versão.

Pontos para a decisão do proprietário do produto:

1. **Rótulos do eixo horizontal**: um por dia, inclinados; com muitos dias, o Chart.js mostra só parte
   deles (as datas exatas estão na tabela).
2. **Período exibido**: todo o histórico retido (até 5 anos, conforme a retenção); com a retenção padrão,
   até 365 barras finas.
3. **Leitor de tela**: não realizado (exige uma pessoa); o gráfico tem título, e a tabela recolhida tem os
   mesmos dados.

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
- phpcs e Mustache lint: rodam no CI (não disponíveis localmente); conferidos à mão comprimento de linha,
  espaços e o estilo de chamadas em várias linhas exigido pelo CI da 005.
- Pacote (T033): `tools/package/build.sh` e `tools/package/check.php build/block_coursecare-0.6.0.zip`:
  **0 erros, 84 arquivos** (a primeira verificação recusou `db/upgradelib.php`, arquivo padrão do Moodle
  exigido pela atualização; acrescentado à lista de arquivos permitidos da ferramenta).
- CI (T034): **pendente** — a branch não foi publicada; o push depende da autorização do proprietário do produto.
