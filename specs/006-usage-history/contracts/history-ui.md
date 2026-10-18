# Contract: seção "Evolução dos arquivos da sua sala"

Estende a página da Feature 003 e mantém a direção visual aprovada (003 a 005).

## Posição e presença

- Depois da grade "Onde estão os arquivos" / "Arquivos de grande porte" e antes de "Materiais por
  atividade": `<section id="block_coursecare-history">`.
- Presente quando a sala tem pelo menos um ponto (com ou sem resultado atual válido: um resultado que
  falhou depois de pontos anteriores mantém a seção). Sem pontos, ausente.

## Conteúdo

1. `h3` `history:heading`; `history:intro` (o que são os valores); `history:meaning` (o que não significam).
2. `history:latest` com a data da última medição.
3. Frase da variação: `history:increased` | `history:decreased` | `history:unchanged` | `history:first` |
   `history:methodchanged`.
4. Com dois pontos ou mais: o gráfico (`core\chart_line`, série `history:series`, sem tabela automática),
   título `history:chart`, eixo vertical `history:axis` com a unidade.
5. `<details>` recolhido, `history:data` (título): tabela com uma linha por ponto (mais recente
   primeiro): data; arquivos registrados; as quatro categorias (nomes `overview:category:*`); conteúdo
   único; `history:partial` quando parcial. Depois, `history:limits` (lista).

Nenhuma cor de alerta, nenhum ícone de tendência, nenhuma meta, nenhum valor por atividade ou estudante.

## Strings (propostas; revisão de tom)

| Chave | en | pt_br |
| --- | --- | --- |
| `history:heading` | How the files of your course have changed | Evolução dos arquivos da sua sala |
| `history:intro` | Each value is a measurement of the files recorded in Moodle for this course, taken on the date shown. | Cada valor é uma medição dos arquivos registrados no Moodle para esta sala, feita na data indicada. |
| `history:meaning` | An increase or a decrease, on its own, does not mean a problem or an improvement: courses change as teaching goes on. | Um aumento ou uma redução, por si só, não indica problema nem melhoria: as salas mudam conforme o ensino acontece. |
| `history:latest` | Latest measurement: {$a}. | Última medição: {$a}. |
| `history:increased` | The files recorded in this course increased by {$a->delta} since the analysis of {$a->date} (from {$a->from} to {$a->to}). | Os arquivos registrados nesta sala aumentaram {$a->delta} desde a análise de {$a->date} (de {$a->from} para {$a->to}). |
| `history:decreased` | The files recorded in this course decreased by {$a->delta} since the analysis of {$a->date} (from {$a->from} to {$a->to}). | Os arquivos registrados nesta sala diminuíram {$a->delta} desde a análise de {$a->date} (de {$a->from} para {$a->to}). |
| `history:unchanged` | The files recorded in this course did not change since the analysis of {$a->date} ({$a->to}). | Os arquivos registrados nesta sala não mudaram desde a análise de {$a->date} ({$a->to}). |
| `history:first` | This is the first measurement kept for this course; the change over time appears from the next analysis. | Esta é a primeira medição guardada desta sala; a evolução aparece a partir da próxima análise. |
| `history:methodchanged` | The way of measuring changed on {$a}; the measurements before and after that date are not compared. | A forma de medir mudou em {$a}; as medições de antes e de depois dessa data não são comparadas. |
| `history:chart` | Files recorded in Moodle, by measurement date | Arquivos registrados no Moodle, por data de medição |
| `history:series` | Files recorded | Arquivos registrados |
| `history:axis` | Size ({$a}) | Tamanho ({$a}) |
| `history:data` | Measurements and categories | Medições e categorias |
| `history:col_date` | Date | Data |
| `history:col_total` | Files recorded | Arquivos registrados |
| `history:col_unique` | Unique content | Conteúdo único |
| `history:partial` | Partial coverage | Cobertura parcial |
| `history:limits` | About these measurements | Sobre estas medições |
| `history:limit_start` | The history starts with version 0.6.0 of the plugin; there are no measurements from before. | O histórico começa na versão 0.6.0 do plugin; não há medições anteriores. |
| `history:limit_gaps` | Days without an analysis have no value; nothing is estimated for them. | Dias sem análise ficam sem valor; nada é estimado para eles. |
| `history:limit_day` | One value per day: the latest analysis of the day. | Um valor por dia: a última análise do dia. |
| `history:limit_cause` | The values show what changed in the recorded files, not why it changed. | Os valores mostram o que mudou nos arquivos registrados, não por que mudou. |
| `history:limit_retention` | Measurements older than {$a} are removed. | Medições com mais de {$a} são removidas. |
| `settings:historyretention` | History retention | Retenção do histórico |
| `settings:historyretention_desc` | How long the measurements of each course are kept for the "How the files of your course have changed" section (from 90 days to 5 years). | Por quanto tempo as medições de cada sala são guardadas para a seção "Evolução dos arquivos da sua sala" (de 90 dias a 5 anos). |

## Acessibilidade

`h3` da seção; frase da variação em texto; gráfico com título e sem papel de única fonte; tabela com
`<caption>` e cabeçalhos (`th scope`); `<details>` nativo; números sempre em texto; 320 px sem rolagem
horizontal da página (a tabela rola dentro do seu contêiner, com foco por teclado).
