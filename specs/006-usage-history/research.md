# Research: Usage History (Feature 006)

Decisões de projeto a partir do código da Feature 002 (`diagnostic_store`, `course_analysis`,
`queue_analyses`) e dos recursos do Moodle 4.5.

## R1. Momento do registro: na mesma transação que substitui o resultado

**Decisão**: `diagnostic_store::save_result()` já substitui o resultado da sala numa transação. O ponto
do histórico é gravado dentro dessa transação, com os valores do resultado que está sendo salvo.

**Motivo**: o ponto e o resultado atual nunca divergem (FR-001, SC-002): os dois são confirmados juntos
ou nenhum é. Falha da análise não chama `save_result()` (chama `save_failure()`), então não há ponto
(FR-003). Nada é lido de novo: os valores vêm do `diagnostic_result` em memória (FR-005).

**Alternativas consideradas**: observar um evento depois da análise (sem garantia de atomicidade);
tarefa separada que copia o resultado atual uma vez por dia (perde análises e duplica o trabalho de
agendamento).

## R2. Um ponto por sala e dia, sem duplicação

**Decisão**: tabela nova com chave única (`courseid`, `timeday`); `timeday` é a meia-noite do dia da
análise no fuso horário padrão do site (`usergetmidnight($now, core_date::get_server_timezone())`).
Dentro da transação: se já existe ponto para (sala, dia), ele é atualizado; senão, inserido (Q1: a
última análise do dia vale).

**Motivo**: a análise de uma sala já roda sob um lock por sala (`analyse_course_<id>`), então duas
análises da mesma sala não gravam ao mesmo tempo; o índice único é a segunda garantia contra duplicação
(FR-004). Reexecução no mesmo dia atualiza o mesmo ponto.

**Alternativas consideradas**: guardar o dia como texto `AAAAMMDD` (mesma garantia, menos natural para
intervalos de datas); dia em UTC (o "dia" mostrado ao professor não bateria com a data do site).

## R3. Uma linha larga por ponto

**Decisão**: cada ponto é uma linha com os totais e as quatro categorias em colunas (sem tabela filha).

**Motivo**: 1 linha por ponto em vez de 5; consulta da página sem junção; o conjunto de categorias da
002 é fixo (material, submission, other, unclassified). Uma categoria nova exigiria uma mudança de
método, que já é registrada (`methodversion`).

## R4. Primeiro ponto na atualização

**Decisão**: o passo de atualização da versão 0.6.0 cria a tabela e, para cada resultado atual com
`timecompleted`, insere um ponto com os valores e a data reais dessa análise (FR-006), lendo o resultado
e as categorias em lotes.

**Motivo**: é uma medição real e datada; sem ela, o histórico de todas as salas começaria vazio por um
dia. Nada além do resultado atual existe, então nenhum passado é inventado.

## R5. Retenção

**Decisão**: configuração `block_coursecare/historyretention` (`admin_setting_configduration`), padrão
365 dias, mínimo 90 dias, máximo 5 anos (Q2). A tarefa horária `queue_analyses`, que já remove
diagnósticos órfãos, remove também até 10.000 pontos com `timeday` anterior ao limite por execução
(seleção de ids com limite e remoção por lista).

**Motivo**: sem tarefa nova; com o intervalo padrão, 5.000 salas geram cerca de 5.000 pontos por dia,
bem abaixo de 10.000 por hora; uma redução grande da retenção é absorvida em poucas horas.

**Volume**: um ponto tem 14 números (cerca de 150 bytes com o índice). 5.000 salas × 365 dias = 1,8
milhão de linhas, cerca de 0,3 GB; medido em SC-004.

## R6. Ciclo de vida

**Decisão**:
- `delete_for_course()` (sala excluída ou sem o bloco, Q3) apaga também o histórico da sala.
- Restauração como sala nova e cópia: `courseid` novo, nenhum histórico (o plugin não faz backup de
  dados do diagnóstico).
- Restauração sobre a mesma sala e reinicialização: mesmo `courseid`, o histórico continua.
- Desinstalação: a tabela está no `install.xml` e é removida com o plugin.

## R7. Leitura na página

**Decisão**: uma consulta por sala, `courseid` e `timeday >= limite da retenção`, ordenada por
`timeday`, com no máximo um ponto por dia (≤ 1.826 linhas com 5 anos). Coletor
`diagnostic_store::get_history()`; nenhuma leitura de `files`.

## R8. Variação e mudanças de método (regra pura)

**Decisão**: classe pura `local\analysis\history_view` que recebe os pontos e devolve: última medição;
variação entre as duas mais recentes (`increased`, `decreased`, `unchanged`) com os valores, ou `first`
quando há uma só; `methodchanged` (data) quando as duas mais recentes têm versões de método diferentes,
sem variação; a série diária do gráfico com valor nulo nos dias sem medição; marca de cobertura parcial
por ponto.

## R9. Gráfico

**Decisão**: `core\chart_line` do Moodle (Chart.js do core, sem biblioteca externa), renderizado sem a
tabela automática (`render_chart($chart, false)`); a tabela com todos os dados é a do próprio plugin,
no trecho recolhido (FR-022), com datas e tamanhos formatados. Eixo horizontal: um rótulo por dia do
período, do primeiro ponto à última medição; dias sem medição têm valor nulo, e o Chart.js deixa a
linha interrompida (sem interpolação, FR-023). Eixo vertical na unidade do maior valor (KB, MB ou GB).
Série única (armazenamento lógico total); categorias só na tabela (evita gráfico de painel e cor como
único identificador).

**Alternativas consideradas**: gráfico de barras (uma barra por medição: sem eixo de tempo
proporcional, lacunas invisíveis); categorias empilhadas no gráfico (mais leitura de cores, contra o
Princípio II); biblioteca externa (desnecessária).

**Revisão na implementação (2026-10-19)**: as capturas mostraram que o Moodle 4.5 envia o mínimo do eixo
ao Chart.js 4.4.2 no formato antigo (`ticks.min`), que é ignorado: num gráfico de linhas o eixo começava
perto dos valores e aumentava visualmente mudanças pequenas. Decisão revista: `core\chart_bar` (uma barra
por dia medido, nenhuma barra nos dias sem medição, eixo a partir de zero, padrão das barras no Chart.js
4), na mesma escala diária de rótulos; altura fixa e discreta (16rem) em vez da altura que o core dá a
partir da janela; cor verde dos materiais da página.

**Decisão do proprietário do produto (2026-10-19)**: gráfico com no máximo 90 dias até a última medição
(até 1.826 barras eram possíveis com 5 anos de retenção); a tabela continua com o histórico completo. Menor
mudança: a regra pura escolhe a janela (`history_view::CHART_DAYS`), a saída só desenha essa janela e uma
frase avisa que a tabela tem todas as medições.

## R10. Versão

**Decisão**: `release` 0.6.0; uma tabela nova; um passo de atualização; uma configuração nova.
