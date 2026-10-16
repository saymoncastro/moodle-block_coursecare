# Contract: apresentação mínima no bloco

Estende [o contrato da Feature 001](../../001-plugin-foundation/contracts/block-ui.md). Tudo o que
não está aqui continua valendo: página principal apenas, `block/coursecare:view` no contexto do
bloco, ícone decorativo, tagline, WCAG 2.2 AA aplicável, sem dependência de tema.

## Conteúdo

Abaixo da tagline e da mensagem `intro` da Feature 001, o bloco acrescenta **uma** linha de estado
do diagnóstico (nível 1 do Princípio II): no máximo um número (Armazenamento lógico) e a data.
Nada de tabelas, gráficos, categorias, atividades, avisos coloridos, ícones de alerta, botões ou
links nesta feature (FR-050, FR-039d).

| Estado | Chave(s) de string (en) | Exemplo em inglês | Número |
| --- | --- | --- | --- |
| A, F sem resultado | `diagnostic:notavailable` | "The file overview for this course is not available yet." | Não |
| B, C sem resultado | `diagnostic:preparing` | "The file overview for this course is being prepared." | Não |
| D | `diagnostic:volume` + `diagnostic:calculatedon` | "Files in this course, as recorded by Moodle: {$a}" / "Calculated on {$a}." | Sim |
| E | D + `diagnostic:mayoutdated` | "This information may be out of date." | Sim |
| F com resultado | D + `diagnostic:updatefailed` | "The latest update could not be completed." | Sim |
| G | `diagnostic:nofiles` + `diagnostic:calculatedon` | "No files were found in this course's materials and activities." | Não (zero implícito na frase) |

- H não muda a mensagem nesta feature (a limitação fica no resultado; Feature 003).
- Com resultado e nova análise pendente (B ou C), mostra o estado do resultado existente (FR-046).
- O texto final das strings é proposto na implementação e revisado quanto a tom e rótulo
  (Princípios I e IV; SC-008). Restrições: nenhuma palavra de avaliação ("excesso", "alto",
  "bom"), nenhum termo de infraestrutura, nenhum "espaço ocupado", "consumo" ou "economia".
- Tamanho formatado pelo core (`display_size()`, unidades binárias, rótulos do pacote de idioma);
  data por `userdate()` com `strftimedatetimeshort` no fuso do usuário.
- Toda string nova em `lang/en` e em `translations/pt_br`, com paridade (Princípio VIII).

## Acessibilidade e estrutura

- A linha de estado é um parágrafo comum, depois da mensagem `intro`. Informação nunca só por cor.
- Os avisos de E e F são texto, sem `role="alert"` (não são eventos da sessão do usuário).
- Template: `templates/content.mustache` recebe um contexto com o estado já resolvido. As strings
  com parâmetros são montadas em PHP com `get_string()` e um único parâmetro por frase, sem
  concatenação. O exemplo de contexto do template cobre os estados para o lint de Mustache.

## Desempenho (SC-002)

`get_content()` faz, além do que a Feature 001 já fazia: uma leitura de
`block_coursecare_diag` por `courseid` e a leitura de `stalenessthreshold` pela configuração do
plugin (em cache pelo core). Nenhuma consulta a `files`, nenhuma escrita, nenhuma tarefa enfileirada.
Teste: diferença de leituras no banco ≤ 2 em relação ao bloco sem diagnóstico, e nenhuma consulta a
`{files}` (por inspeção do log de consultas do teste).
