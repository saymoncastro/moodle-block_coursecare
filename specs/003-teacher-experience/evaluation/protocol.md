# Protocolo: avaliação com cinco professores (T049; FR-071, FR-071a, CT-23, SC-011)

Roteiro curto e reproduzível para a avaliação conduzida pelo proprietário do produto (T050). Duração
prevista: 20 a 25 minutos por participante, um participante por vez.

## Preparação

- **Site:** site de demonstração limpo (`coursecare-demo`, Moodle 4.5, tema Boost), montado por
  `tools/dev/demo/setup.php --threshold-mb=1 --activities=200`, com a interface aprovada (T046) e
  validada (T048). O limite de grande porte de 1 MB é um parâmetro da demonstração; explicar isso ao
  participante só se ele perguntar.
- **Conta:** a conta de professor de demonstração, aberta pelo condutor antes da sessão. Nenhum dado
  pessoal do participante entra no site.
- **Idioma:** o do participante (pt_BR ou en), escolhido antes da sessão e anotado no registro.
- **Salas usadas:** Biologia celular (resultado completo), Física (resultado antigo), Matemática
  (atualização que falhou). Nenhuma tarefa altera dados: não é preciso restaurar o site entre sessões.
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

### D1. Compreensão do bloco

**T1.** "Abra a sala *Biologia celular*. Sem clicar em nada, conte o que o quadro *AVA Consciente*
informa sobre a sua sala."

Sucesso: cita o total de arquivos da sala (8,0 MB) e que ele foi calculado numa data. Erro crítico se
entender o número como espaço em disco que a sala ocupa sozinha, economia possível ou avaliação do
professor.

### D3. Navegação até o detalhamento (antes de D2, para chegar à página sem ajuda)

**T2.** "Encontre mais detalhes sobre os arquivos desta sala." Depois: "Agora volte para a sala."

Sucesso: chega à página "Sua sala em números" pelo link do quadro e volta à sala pelo link "Voltar à
sala" ou pela navegação do Moodle, sem ajuda. Erro crítico se não conseguir chegar à página.

### D2. Interpretação dos indicadores

Na página "Sua sala em números" da *Biologia celular*, **sem abrir os Detalhes técnicos**:

**T3.** "Qual é o total de arquivos da sala e de quando é essa informação?"
Sucesso: 8,0 MB; data do cálculo.

**T4.** "Que tipo de arquivo é a maior parte desse total?"
Sucesso: Materiais da sala (74%).

**T5.** "Qual atividade tem mais materiais? O que quer dizer o percentual dela?"
Sucesso: *Aula 1 — A célula*; os 39% são em relação aos materiais da sala (não ao total da sala).
Parcial se identificar a atividade mas não a base do percentual.

### D4. Arquivos de grande porte

**T6.** "Quantos arquivos de grande porte há na sala? Em quais atividades há materiais de grande porte?"
Sucesso: 3 arquivos; *Aula 1 — A célula* e *Vídeo de laboratório*.

**T7.** "O que você entende por 'grande porte' aqui? Isso diz algo sobre a qualidade dos materiais?"
Sucesso: é um limite de triagem definido pelo site, não uma regra pedagógica nem um julgamento.
Erro crítico se entender como avaliação do professor ou do material.

### D5. Clareza dos estados e mensagens

**T8.** Abrir a sala *Física*. "O que o quadro diz? Os números ainda valem?"
Sucesso: são números de uma data anterior e podem estar desatualizados.

**T9.** Abrir a sala *Matemática*. "E aqui, o que aconteceu?"
Sucesso: a atualização mais recente não foi concluída; os números mostrados são do cálculo anterior.
Erro crítico se não entender o estado do diagnóstico em T8 ou T9.

### D6. Facilidade geral de uso

**T10.** "De 1 (muito difícil) a 7 (muito fácil), quão fácil foi entender as informações sobre os
arquivos da sua sala?"

Perguntas abertas, ao final:

1. O que foi mais fácil de entender? E o mais difícil?
2. Algum termo pareceu técnico demais ou confuso?
3. Você mudaria algo no que aparece no quadro da sala?
4. Ao ver esses números, o que você pensou em fazer? (Só registrar; nenhuma recomendação faz parte
   desta versão.)

## Classificação dos problemas (FR-071a)

**Crítico**, a corrigir antes do encerramento da feature (T051):

- impede entender o número principal ou o estado do diagnóstico;
- impede chegar ao detalhamento;
- impede usar a interface com teclado ou leitor de tela (se o participante usar tecnologia assistiva);
- leva a ler os números como espaço físico, economia ou avaliação do professor.

**Menor**: qualquer outra dificuldade; registrada como melhoria futura em `CHANGES.md` e
`evidence/README.md`.

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

| Tarefa | Dimensão | Resultado | Tempo | Observações (frases do participante) |
| --- | --- | --- | --- | --- |
| T1 | D1 | | | |
| T2 | D3 | | | |
| T3 | D2 | | | |
| T4 | D2 | | | |
| T5 | D2 | | | |
| T6 | D4 | | | |
| T7 | D4 | | | |
| T8 | D5 | | | |
| T9 | D5 | | | |
| T10 | D6 | nota 1–7 | | |

## Respostas abertas

1. …

## Problemas

| Problema | Tarefa | Crítico ou menor | Por quê |
| --- | --- | --- | --- |
```

O resumo das cinco sessões (resultado por tarefa, problemas críticos e menores, correções feitas)
vai para "Avaliação com professores (T049–T051)" em `evidence/README.md`.
