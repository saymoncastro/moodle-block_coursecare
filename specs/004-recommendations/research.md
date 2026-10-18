# Research: Recomendações (Feature 004)

Nenhum item da Technical Context ficou como NEEDS CLARIFICATION. As decisões abaixo resolvem as
escolhas de projeto da spec esclarecida em 2026-10-18.

## R1. Onde a regra de S1 vive

**Decisão**: classe pura `\block_coursecare\local\recommendation\large_materials`, com um método
estático `evaluate(array $large): ?array`. A entrada é a parte `large` da visão que o
`overview_builder` da Feature 003 já monta (materiais visíveis por atividade, grupos `courselevel`,
`others` e `removed`, limite e `truncated`). A saída é `null` (S1 não se aplica) ou os dados de S1
([contracts/recommendation-rule.md](contracts/recommendation-rule.md)).

**Motivo**: o Princípio IX prevê a camada de regras de recomendação, pura (sem banco, sessão ou
saída). Partir da visão de grandes evita leitura nova na página e garante os mesmos números da seção
"Arquivos de grande porte".

**Alternativas consideradas**: lógica dentro de `course_overview` (mistura regra com apresentação;
viola a camada); interface `rule` com registro (abstração sem segundo caso, Princípio XI); ler as
tabelas de novo dentro da regra (deixa de ser pura).

## R2. Números citados por S1

**Decisão**: a quantidade e o volume de S1 são a soma das ligações a materiais de grande porte (as
linhas de `materials` mais os grupos `courselevel`, `others` e `removed`), iguais à soma da lista
"Materiais de grande porte por atividade" da seção de grandes.

**Motivo**: o resumo da sala (`largecount`, `largebytes`) inclui envios e outros arquivos; S1 fala de
materiais, que o professor publicou. Usar o resumo diria "materiais" para arquivos de estudantes. A
frase da spec "iguais aos da seção" é lida como iguais à lista por atividade da seção.

**Alternativas consideradas**: o resumo da sala (inclui envios; rejeitado pela privacidade e pela
precisão).

## R3. Condição usada pelo bloco

**Decisão**: o bloco mostra a frase de sugestão quando há resultado com arquivos e existe pelo menos
uma ligação a material de grande porte no diagnóstico (`record_exists` em `block_coursecare_large`
por `diagid`, indexado pela chave estrangeira). É a mesma condição de S1 na página, porque toda
ligação é de material e conta em S1, inclusive no nível da sala e em atividades invisíveis ou
removidas.

**Motivo**: uma leitura por índice, só quando há resultado com arquivos; nenhum dado novo.

**Alternativas consideradas**: `largecount > 0` do resumo (já lido, mas inclui envios: o bloco
anunciaria sugestão que a página não mostra); guardar um indicador no diagnóstico (dado novo, fora do
escopo); montar a visão completa no bloco (várias leituras e o cache de módulos, custo maior).

## R4. Posição da seção e âncora

**Decisão**: a seção vem logo depois da abertura e antes da grade "Onde estão os arquivos" / "Arquivos
de grande porte", com `id="block_coursecare-suggestions"`. Com sugestão, o link do bloco leva a
`overview.php?id=<instância>#block_coursecare-suggestions`; sem sugestão, o link fica como na 003.

**Motivo**: a sugestão é a primeira coisa prática da página (Princípio II, nível 2); a âncora leva o
professor direto a ela a partir da frase do bloco.

**Alternativas consideradas**: sugestão dentro da seção de grandes (fica escondida abaixo da
distribuição, e a seção deixa de ser só descritiva); seção no fim da página (pouco encontrável).

## R5. Estrutura do texto e marcação

**Decisão**:
- Seção: `h3` "Sugestões para a sua sala", frase com a data ("Estas sugestões usam a análise de
  {data}.").
- Cartão de S1: `h4` com o título da sugestão ("Materiais de grande porte"), e as três partes numa
  lista de definições (`dl`): cada rótulo em `dt` e o conteúdo em `dd`.
- "O que observamos": uma frase completa com parâmetros (quantidade, limite, volume), seguida da lista
  dos lugares como links (`ul`), e, quando houver, a frase "e mais {n} lugares na seção Arquivos de
  grande porte" e o aviso de truncamento da 003.
- "O que você pode fazer, se quiser": uma frase de introdução e as três possibilidades numa lista.

**Motivo**: frases completas no catálogo, links fora das frases (sem concatenação, Princípio VIII);
`dl` dá rótulo e conteúdo associados para leitores de tela.

**Alternativas consideradas**: frase única com os nomes embutidos (exige concatenação ou parâmetro
com HTML); títulos `h5` para cada parte (hierarquia mais pesada).

## R6. Boas práticas de S1

**Decisão**: três possibilidades curtas, cada uma uma string: (1) versão otimizada que continue
legível e acessível (por exemplo, PDF com imagens na resolução adequada para a tela, vídeo na
resolução necessária); (2) dividir um conteúdo longo em partes menores; (3) link para onde o conteúdo
já está publicado. Precedidas de "avalie se alguma destas opções faz sentido para o seu material —
nem todas se aplicam a todos os casos".

**Motivo**: decisão do proprietário do produto (texto curto e útil na própria sugestão, sem
biblioteca nem mecanismo à parte, fatos separados de possibilidades).

**Alternativas consideradas**: página de boas práticas à parte (fora do escopo); boas práticas
diferentes por tipo de arquivo (o diagnóstico não guarda o tipo).

## R7. Validação com professores

**Decisão**: acrescentar ao protocolo da 003 (`specs/003-teacher-experience/evaluation/protocol.md`)
duas tarefas sobre a sugestão (compreensão: o que foi observado e o que é opcional; utilidade: a
sugestão ajuda?) e uma pergunta aberta. A avaliação fica pendente e não bloqueia a implementação.

**Motivo**: decisão Q2 = A do proprietário do produto.

## R8. Versão

**Decisão**: `release` 0.4.0 e `version` novo (sem mudança de banco). Sem etapa de upgrade.

**Motivo**: entrega instalável e identificável (Princípio XI); strings novas exigem só a limpeza de
cache que a atualização já faz.
