# Data Model: Usage History (Feature 006)

## Tabela nova: `block_coursecare_hist` (ponto do histórico)

| Campo | Tipo | Regra |
| --- | --- | --- |
| id | int(10) | chave primária |
| courseid | int(10), not null | sala |
| timeday | int(10), not null | meia-noite do dia da análise no fuso padrão do site |
| timecompleted | int(10), not null | data e hora da análise (igual ao resultado atual quando é o ponto mais recente) |
| methodversion | int(4), not null | versão do método de cálculo da 002 |
| partial | int(1), not null | 1 quando a análise teve cobertura parcial |
| filecount | int(10), not null | arquivos registrados (armazenamento lógico) |
| logicalbytes | int(20), not null | armazenamento lógico |
| uniquecount | int(10), not null | conteúdo único (arquivos) |
| uniquebytes | int(20), not null | conteúdo único |
| materialbytes | int(20), not null | categoria materiais |
| submissionbytes | int(20), not null | categoria envios (total da sala) |
| otherbytes | int(20), not null | categoria outros |
| unclassifiedbytes | int(20), not null | categoria não classificados |

Índice único (`courseid`, `timeday`): no máximo um ponto por sala e dia (FR-002, FR-004); serve também à
consulta da página (por sala e período) e à limpeza por sala. Índice (`timeday`) para a limpeza da
retenção. Nenhum dado pessoal; nenhum id de usuário, arquivo ou atividade.

Origem dos valores: o `diagnostic_result` salvo na mesma transação (`summary` e `categories`); categoria
ausente vale 0.

## Ciclo de vida de um ponto

1. Criado (ou atualizado no mesmo dia) quando uma análise bem-sucedida salva o resultado.
2. Removido quando: fica mais antigo que a retenção; a sala deixa de ser elegível (`delete_for_course`);
   o plugin é desinstalado.

## Configuração nova

`block_coursecare/historyretention`: duração; padrão 365 dias; mínimo 90 dias; máximo 5 anos.

## Visão da seção (saída de `history_view`, em memória)

| Campo | Regra |
| --- | --- |
| latest | ponto mais recente (data, valores) |
| change | `increased`, `decreased`, `unchanged`, `first` ou `methodchanged` |
| previous | ponto anterior (quando há) |
| delta | diferença absoluta do armazenamento lógico (só quando mesma versão de método) |
| series | um valor por dia do primeiro ao último ponto; nulo nos dias sem medição |
| points | todos os pontos, do mais recente ao mais antigo, para a tabela |
