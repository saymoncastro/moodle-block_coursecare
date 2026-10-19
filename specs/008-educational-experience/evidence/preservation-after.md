# Inventário de preservação — depois (0.8.0)

Mesmas salas e mesmo método de `preservation-before.md` (texto visível, com as duas áreas reveladas), em
2026-10-19, depois de reimportar a tradução pt_br na personalização de idioma do site de demonstração (as
chaves novas só chegam ao pt_br desse site por essa importação; ver CONTRIBUTING).

## Comparação linha a linha com o antes

| Idioma | Tela | Linhas antes | Linhas depois | Ausentes | Motivo |
| --- | --- | --- | --- | --- | --- |
| en | Bloco | 7 | 7 | 3: Small choices, a better learning space for everyone.; There is a suggestion about the files of your course.; Learn more about the files in your course | frase e link antigos do bloco trocados pelo estado educativo e pela ação "Ver sugestão" (FR-002, FR-005); o slogan sai do bloco quando há título educativo |
| en | Página completa | 82 | 93 | 0 | — |
| en | Página com potencial | 70 | 72 | 0 | — |
| en | Página com histórico | 37 | 45 | 1: There are no suggestions for this course at the moment. | frase neutra trocada pela faixa "Tudo bem por aqui!" (FR-033) |
| pt_br | Bloco | 7 | 7 | 3: Pequenas escolhas, um AVA melhor para todos.; Há uma sugestão sobre os arquivos da sua sala.; Conheça melhor os arquivos da sua sala | frase e link antigos do bloco trocados pelo estado educativo e pela ação "Ver sugestão" (FR-002, FR-005); o slogan sai do bloco quando há título educativo |
| pt_br | Página completa | 82 | 93 | 0 | — |
| pt_br | Página com potencial | 70 | 72 | 0 | — |
| pt_br | Página com histórico | 37 | 45 | 1: Não há sugestões para esta sala no momento. | frase neutra trocada pela faixa "Tudo bem por aqui!" (FR-033) |

Resultado: todos os números, rótulos, seções e explicações da página da 0.7.0 continuam presentes. As
únicas ausências são as trocas previstas na especificação. A página com potencial (sala sem arquivos
registrados) não tem abas, como prevê o FR-034.

## Desempenho (tools/dev/bench/render.php, sala 2, mesmo site)

| Medida | Antes (0.7.0) | Depois (0.8.0) |
| --- | --- | --- |
| Bloco, 300 iterações | 0,953 ms, 5,01 leituras | 0,925 ms, 5,02 leituras |
| Página, 100 iterações | 10,94 ms, 11,10 leituras | 11,69 ms (+6,9%), 11,41 leituras |

A diferença de leituras da página (0,3 por chamada) é variação de cache entre execuções: a mesma página da
0.7.0 mediu 11,41 leituras no site de teste do ZIP da 007 (`specs/007-marketplace-release/evidence/install.md`),
e a 008 não acrescenta nenhuma consulta (só strings em cache e um ícone). Tempo dentro dos 10% do SC-004.

Medidas brutas: `{"courseid":2,"measured":"block","iterations":300,"meanms":0.925,"stdevms":1.402,"readspercall":5.02}` e `{"courseid":2,"measured":"overviewpage","iterations":100,"meanms":11.689,"stdevms":4.213,"readspercall":11.41}`.

## Bloco (sala 2) — en

```text
CourseCare

An idea for your course

We found a suggestion about large materials.

Course files recorded in Moodle

8.0 MB

Calculated on 17/10/26, 19:21.

See the suggestion
```

## Página completa (instância 6) — en

```text
Back to the course

CourseCare

Small choices, a better learning space for everyone.

Your course in numbers

See where the files of your course are and when they were measured.

Course files recorded in Moodle

8.0 MB

Calculated on 17/10/26, 19:21.

Suggestions for you
Details
Suggestions for you

An idea for your course

We found a suggestion about large materials.

Suggestions are possibilities. They do not mean that anything is wrong, and they do not limit how you use Moodle.

Suggestions for your course

These suggestions use the analysis of 17/10/26, 19:21.

Large materials
What we noticed

2 course materials of 1.0 MB or more, 3.9 MB in total, in:

Related activities
Aula 1 — A célula
2.3 MB
Vídeo de laboratório
1.6 MB
Why it may deserve attention

Large files can take longer to open, especially on slow connections or on a phone. Size alone says nothing about the quality or the importance of a material.

What you can do, if you wish

When you review the activity, consider whether any of these options suits your material. Not all of them apply to every case:

an optimised version of the file that stays readable and accessible;
for example, a PDF with images at a resolution suited to the screen, or a video at the resolution it needs.
splitting long content into shorter parts;
a link to where the content is already published.
Details
Where the files are

How much of all the files of the course each kind of file represents.

Course materials
5.9 MB · 74%

Files you and other editors published: resources, folders, pages, books and descriptions.

Files submitted in activities
1.9 MB · 24%

Files sent by participants in assignments and quizzes, shown only for the whole course.

Other associated files
146.5 KB · 2%

Feedback, forum attachments, question and content banks.

Not classified
0 bytes · 0%

Files from areas the overview does not recognise yet; they count in the total.

Large files

Files of 1.0 MB or more: 3 (5.1 MB).

The threshold is a screening value set by the site, not a teaching rule.

Large materials by activity
Aula 1 — A célula
Files: 1 · 2.3 MB
Vídeo de laboratório
Files: 1 · 1.6 MB
How the files of your course have changed

Each value is a measurement of the files recorded in Moodle for this course, taken on the date shown.

An increase or a decrease, on its own, does not mean a problem or an improvement: courses change as teaching goes on.

Latest measurement: 17/10/26, 19:21.

The volume of the files recorded in this course increased by 182.8 KB since the analysis of 16 October 2026 (from 7.8 MB to 8.0 MB).

Measurements and categories
Materials by activity

Only the materials of each activity, as a share of all the materials of the course.

Aula 1 — A célula
2.3 MB · 39%
Vídeo de laboratório
1.6 MB · 27%
Leituras complementares
1.1 MB · 19%
Guia da disciplina
410.2 KB · 7%
Glossário ilustrado
234.4 KB · 4%
Boas-vindas à disciplina
87.9 KB · 1%
Relatório de laboratório
58.6 KB · less than 1%
Course page, sections and blocks
175.8 KB · 3%
Potential of assignments

What the current settings of the assignments allow to be stored in submitted files. Calculated now, with the current settings.

The theoretical potential is a ceiling set by the settings: it is not the space used, a forecast or the amount that will be submitted. Different settings serve different teaching goals.

This section covers assignments only.

In this course, files already submitted in activities add up to 1.9 MB (analysis of 17/10/26, 19:21). This total is not compared assignment by assignment with the potential.

Relatório de laboratório

Up to 5 files per participant, of up to 200.0 MB each.

The size limit per file comes from the server settings.

1 attempt.

3 participants can submit.

Theoretical potential: 2.9 GB (3 × 5 × 200.0 MB).

How the potential is calculated, and its limits
Technical details

Each suggestion is a possibility. The decision is always yours, based on what your students need.
```

## Bloco (sala 2) — pt_br

```text
AVA Consciente

Há uma ideia para a sua sala

Encontramos uma sugestão sobre materiais de grande porte.

Arquivos da sala registrados no Moodle

8,0 MB

Calculado em 17/10/26 19:21.

Ver sugestão
```

## Página completa (instância 6) — pt_br

```text
Voltar à sala

AVA Consciente

Pequenas escolhas, um AVA melhor para todos.

Sua sala em números

Veja onde estão os arquivos da sua sala e quando foram medidos.

Arquivos da sala registrados no Moodle

8,0 MB

Calculado em 17/10/26 19:21.

Sugestões para você
Em detalhes
Sugestões para você

Há uma ideia para a sua sala

Encontramos uma sugestão sobre materiais de grande porte.

Sugestões são possibilidades. Elas não indicam que algo esteja errado e não limitam o seu uso do Moodle.

Sugestões para a sua sala

Estas sugestões usam a análise de 17/10/26 19:21.

Materiais de grande porte
O que observamos

2 materiais da sala de 1,0 MB ou mais, somando 3,9 MB, em:

Atividades relacionadas
Aula 1 — A célula
2,3 MB
Vídeo de laboratório
1,6 MB
Por que pode merecer atenção

Arquivos grandes podem demorar mais para abrir, sobretudo em conexões lentas ou no celular. O tamanho, sozinho, não diz nada sobre a qualidade ou a importância do material.

O que você pode fazer, se quiser

Ao revisar a atividade, avalie se alguma destas opções faz sentido para o seu material. Nem todas se aplicam a todos os casos:

uma versão otimizada do arquivo, que continue legível e acessível;
por exemplo, um PDF com imagens na resolução adequada para a tela ou um vídeo na resolução necessária.
dividir um conteúdo longo em partes menores;
um link para onde o conteúdo já está publicado.
Em detalhes
Onde estão os arquivos

Quanto cada tipo de arquivo representa no total da sala.

Materiais da sala
5,9 MB · 74%

Arquivos publicados por quem edita a sala: recursos, pastas, páginas, livros e descrições.

Arquivos enviados em atividades
1,9 MB · 24%

Arquivos enviados por participantes em tarefas e questionários, mostrados só para a sala inteira.

Outros arquivos associados
146,5 KB · 2%

Comentários de avaliação, anexos de fóruns, bancos de questões e de conteúdo.

Não classificados
0 bytes · 0%

Arquivos de áreas que o panorama ainda não reconhece; eles contam no total.

Arquivos de grande porte

Arquivos de 1,0 MB ou mais: 3 (5,1 MB).

O limite é um valor de triagem definido pelo site, não uma regra pedagógica.

Materiais de grande porte por atividade
Aula 1 — A célula
Arquivos: 1 · 2,3 MB
Vídeo de laboratório
Arquivos: 1 · 1,6 MB
Evolução dos arquivos da sua sala

Cada valor é uma medição dos arquivos registrados no Moodle para esta sala, feita na data indicada.

Um aumento ou uma redução, por si só, não indica problema nem melhoria: as salas mudam conforme o ensino acontece.

Última medição: 17/10/26 19:21.

O volume dos arquivos registrados nesta sala aumentou 182,8 KB desde a análise de 16 de outubro de 2026 (de 7,8 MB para 8,0 MB).

Medições e categorias
Materiais por atividade

Só os materiais de cada atividade, em relação a todos os materiais da sala.

Aula 1 — A célula
2,3 MB · 39%
Vídeo de laboratório
1,6 MB · 27%
Leituras complementares
1,1 MB · 19%
Guia da disciplina
410,2 KB · 7%
Glossário ilustrado
234,4 KB · 4%
Boas-vindas à disciplina
87,9 KB · 1%
Relatório de laboratório
58,6 KB · menos de 1%
Página da sala, seções e blocos
175,8 KB · 3%
Potencial das tarefas

O que as configurações atuais das tarefas permitem guardar em arquivos enviados. Calculado agora, com as configurações atuais.

O potencial teórico é um teto das configurações: não é o espaço usado, uma previsão nem o volume que será enviado. Configurações diferentes atendem objetivos pedagógicos diferentes.

Esta seção considera só as tarefas.

Nesta sala, os arquivos já enviados em atividades somam 1,9 MB (análise de 17/10/26 19:21). Esse total não se compara tarefa a tarefa com o potencial.

Relatório de laboratório

Até 5 arquivos por participante, de até 200,0 MB cada.

O limite por arquivo vem das configurações do servidor.

1 tentativa.

3 participantes podem enviar.

Potencial teórico: 2,9 GB (3 × 5 × 200,0 MB).

Como o potencial é calculado e seus limites
Detalhes técnicos

Cada sugestão é uma possibilidade. A decisão é sempre sua, a partir do que os seus estudantes precisam.
```
