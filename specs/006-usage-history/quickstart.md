# Quickstart: validação da Feature 006

Contratos: [history-store.md](contracts/history-store.md), [history-view.md](contracts/history-view.md),
[history-ui.md](contracts/history-ui.md).

## §1 Atualização

Atualizar o site de demonstração para a 0.6.0 (`admin/cli/upgrade.php --non-interactive`): cada sala com
resultado ganha um ponto com a data real da análise; salas sem resultado, nenhum.

## §2 Pontos

Rodar `cli/diagnostics.php --run --course=ID` duas vezes no mesmo dia: um ponto, com a última. Simular
dias anteriores com o gerador de testes (só em PHPUnit/Behat) ou com análises em dias diferentes no site
de demonstração; forçar uma falha (fixture da 002): nenhum ponto novo.

## §3 Página

Conferir a seção nas salas com 0, 1, 2 e muitas medições, com lacuna, com mudança de método e com
cobertura parcial: frases, gráfico, tabela e limitações (CT-14).

## §4 Retenção e ciclo de vida

Reduzir a retenção para 90 dias com pontos mais antigos e rodar `queue_analyses`: pontos antigos
removidos; remover o bloco de uma sala: resultado e histórico removidos na próxima execução.

## §5 Desempenho

`tools/dev/bench/render.php --page` numa sala com 365 pontos; tempo de análise com e sem gravação do
ponto; volume da tabela para 5.000 salas × 365 dias (SC-003, SC-004).

## §6 Acessibilidade, visual, testes e pacote

`tools/dev/demo/checks.py` e `screenshots.py` (en e pt_BR, Boost e Classic, desktop e celular);
PHPUnit, Behat, `tools/i18n/check.php`, pacote e CI, como nas features anteriores.
