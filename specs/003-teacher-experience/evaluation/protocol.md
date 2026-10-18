# Protocolo consolidado: avaliação com cinco professores (Features 003 e 004)

Uma única avaliação cobre a experiência do professor (Feature 003: T049–T051; FR-071, FR-071a, CT-23,
SC-011) e as sugestões (Feature 004: FR-052, CT-13, SC-009). Não há avaliação separada para a 004.
Roteiro curto e reproduzível, conduzido pelo proprietário do produto. Duração prevista: 25 a 30
minutos por participante, um participante por vez.

## O que a avaliação verifica

| Critério | Tarefas |
| --- | --- |
| Compreensão dos indicadores | T1, T3, T4, T5, T6, T7, T10, T11 |
| Compreensão das recomendações (fatos × possibilidades) | T8, T9 |
| Percepção de utilidade | T12 e pergunta aberta 4 |
| Ausência de interpretação fiscalizatória | T7, T9, T13 |
| Clareza sobre ações opcionais | T9 |
| Facilidade de navegação | T2, T14 |

## Preparação

- **Site:** site de demonstração limpo (`coursecare-demo`, Moodle 4.5, tema Boost) na versão 0.4.0,
  montado por `tools/dev/demo/setup.php --threshold-mb=1 --activities=200`, com a tradução pt_BR do
  projeto instalada. O limite de grande porte de 1 MB é um parâmetro da demonstração; explicar isso ao
  participante só se ele perguntar.
- **Conta:** a conta de professor de demonstração, aberta pelo condutor antes da sessão, com os tours
  de usuário do Moodle já concluídos. Nenhum dado pessoal do participante entra no site.
- **Idioma:** o do participante (pt_BR ou en), escolhido antes da sessão e anotado no registro.
- **Salas usadas:** *Biologia celular* (resultado completo, com sugestão), *Física* (resultado
  antigo, sem sugestão), *Matemática* (atualização que falhou, sem sugestão). Nenhuma tarefa altera
  dados: não é preciso restaurar o site entre sessões.
- **Antes de cada sessão:** deixar a gaveta de blocos aberta, como o professor a encontraria, e o
  navegador na página inicial do site.
- **Condução:** ler cada tarefa em voz alta exatamente como escrita; não explicar a interface nem
  indicar onde clicar; pedir que o participante pense em voz alta. Se ele travar por 2 minutos, anotar
  "não concluiu" e seguir para a próxima tarefa.

## Perfil (sem dados pessoais)

| Campo | Opções |
| --- | --- |
| Familiaridade com tecnologia (autodeclarada) | Baixa / Média / Alta |
| Uso do Moodle | Menos de 1 ano / 1 a 3 anos / Mais de 3 anos |
| Área | Humanas / Exatas / Biológicas e Saúde / Linguagens / Outra |
| Tecnologia assistiva usada no dia a dia | Nenhuma / Leitor de tela / Ampliação / Teclado / Outra |

Composição desejada: pelo menos um participante de familiaridade baixa e um de alta, e pelo menos três
áreas diferentes.

## Tarefas

Cada tarefa tem um critério objetivo de sucesso. Registrar: **Sucesso** (sem ajuda), **Parcial**
(com hesitação ou resposta incompleta, sem ajuda) ou **Não concluiu**; o tempo aproximado; e as
frases do participante que mostrem dúvida ou interpretação errada.

### Bloco e navegação

**T1.** "Abra a sala *Biologia celular*. Sem clicar em nada, conte o que o quadro *AVA Consciente*
informa sobre a sua sala."
Sucesso: cita o total de arquivos da sala (8,0 MB) e que ele foi calculado numa data. Notar se o
participante menciona espontaneamente que há uma sugestão (não é exigido). Erro crítico se entender o
número como espaço em disco que a sala ocupa sozinha, economia possível ou avaliação do professor.

**T2.** "Encontre mais detalhes sobre os arquivos desta sala." Depois: "Agora volte para a sala."
Sucesso: chega à página "Sua sala em números" pelo link do quadro e volta à sala pelo link "Voltar à
sala" ou pela navegação do Moodle, sem ajuda. Erro crítico se não conseguir chegar à página.

### Indicadores

Na página "Sua sala em números" da *Biologia celular*, **sem abrir os Detalhes técnicos**:

**T3.** "Qual é o total de arquivos da sala e de quando é essa informação?"
Sucesso: 8,0 MB; data do cálculo.

**T4.** "Que tipo de arquivo é a maior parte desse total?"
Sucesso: Materiais da sala (74%).

**T5.** "Qual atividade tem mais materiais? O que quer dizer o percentual dela?"
Sucesso: *Aula 1 — A célula*; os 39% são em relação aos materiais da sala (não ao total da sala).
Parcial se identificar a atividade mas não a base do percentual.

**T6.** "Quantos arquivos de grande porte há na sala? Em quais atividades há materiais de grande porte?"
Sucesso: 3 arquivos na sala; materiais grandes em *Aula 1 — A célula* e *Vídeo de laboratório*.

**T7.** "O que você entende por 'grande porte' aqui? Isso diz algo sobre a qualidade dos materiais?"
Sucesso: é um limite de triagem definido pelo site, não uma regra pedagógica nem um julgamento.
Erro crítico se entender como avaliação do professor ou do material.

### Sugestão

Na mesma página, na seção "Sugestões para a sua sala":

**T8.** "O que esta sugestão diz que foi observado na sua sala?"
Sucesso: cita os materiais de grande porte (2 materiais da sala, 3,9 MB) e as atividades *Aula 1 — A
célula* e *Vídeo de laboratório*, como fatos medidos.

**T9.** "Do que está escrito aí, o que você é obrigado a fazer?"
Sucesso: nada; as opções são possibilidades que podem não se aplicar. Erro crítico se a sugestão for
lida como ordem de excluir ou de corrigir o material, ou como avaliação do professor.

### Estados

**T10.** Abrir a sala *Física*. "O que o quadro diz? Os números ainda valem?"
Sucesso: são números de uma data anterior e podem estar desatualizados.

**T11.** Abrir a sala *Matemática*. "E aqui, o que aconteceu?"
Sucesso: a atualização mais recente não foi concluída; os números mostrados são do cálculo anterior.
Erro crítico se não entender o estado do diagnóstico em T10 ou T11.

### Percepção geral

**T12.** "De 1 (nada útil) a 7 (muito útil), quão útil seria a sugestão sobre materiais grandes para
você?" Registrar a nota e o porquê.

**T13.** "Em algum momento você sentiu que o AVA Consciente estava avaliando, fiscalizando ou
cobrando você? Onde?"
Sucesso: não. Erro crítico se a resposta apontar uma frase ou um elemento da interface.

**T14.** "De 1 (muito difícil) a 7 (muito fácil), quão fácil foi entender as informações e encontrar o
que procurava?"

Perguntas abertas, ao final:

1. O que foi mais fácil de entender? E o mais difícil?
2. Algum termo pareceu técnico demais ou confuso?
3. Você mudaria algo no que aparece no quadro da sala ou na sugestão?
4. Ao ver esses números e a sugestão, o que você pensou em fazer? (Só registrar.)

## Classificação dos problemas (FR-071a da 003; FR-052 da 004)

**Crítico**, a corrigir antes do encerramento das features:

- impede entender o número principal ou o estado do diagnóstico;
- impede chegar ao detalhamento;
- impede usar a interface com teclado ou leitor de tela (se o participante usar tecnologia assistiva);
- leva a ler os números como espaço físico, economia ou avaliação do professor;
- leva a ler a sugestão como obrigação, ordem de excluir ou de corrigir, ou como fiscalização.

**Menor**: qualquer outra dificuldade; registrada como melhoria futura em `CHANGES.md` e nas evidências
da feature correspondente.

Um mesmo problema é crítico se ocorrer com pelo menos um participante e se encaixar num dos itens acima.

## Formulário de registro

Um arquivo por participante em `specs/003-teacher-experience/evidence/evaluation/P<n>.md`
(P1 a P5), com:

```markdown
# Participante P<n>

- Data da sessão: AAAA-MM-DD
- Idioma: pt_BR | en
- Familiaridade: Baixa | Média | Alta
- Uso do Moodle: < 1 ano | 1–3 anos | > 3 anos
- Área: …
- Tecnologia assistiva: …

| Tarefa | Critério | Resultado | Tempo | Observações (frases do participante) |
| --- | --- | --- | --- | --- |
| T1 | Indicadores | | | |
| T2 | Navegação | | | |
| T3 | Indicadores | | | |
| T4 | Indicadores | | | |
| T5 | Indicadores | | | |
| T6 | Indicadores | | | |
| T7 | Indicadores; fiscalização | | | |
| T8 | Recomendações | | | |
| T9 | Recomendações; ações opcionais; fiscalização | | | |
| T10 | Indicadores (estado) | | | |
| T11 | Indicadores (estado) | | | |
| T12 | Utilidade | nota 1–7 | | |
| T13 | Fiscalização | | | |
| T14 | Navegação | nota 1–7 | | |

## Respostas abertas

1. …

## Problemas

| Problema | Tarefa | Feature (003 ou 004) | Crítico ou menor | Por quê |
| --- | --- | --- | --- | --- |
```

O resumo das cinco sessões (resultado por tarefa, problemas críticos e menores, correções feitas)
vai para "Avaliação com professores" em `specs/003-teacher-experience/evidence/README.md` e, para as
tarefas T8, T9, T12 e T13, também em `specs/004-recommendations/evidence/README.md`.
