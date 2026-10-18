# Contract: seção "Potencial das tarefas"

Estende o contrato da página da Feature 003 e mantém a direção visual aprovada (003 e 004).

## Posição e presença

- Depois de "Materiais por atividade" e antes de "Detalhes técnicos": `<section id="block_coursecare-potential">`.
- Presente sempre que a sala tem tarefas visíveis ao professor (com ou sem resultado do diagnóstico;
  research R9). Sem tarefas com envio de arquivos: título e `potential:none`.

## Conteúdo

1. `h3` `potential:heading`; `potential:intro` (o que é, calculado agora); `potential:meaning` (o que
   não é); `potential:scope` (só tarefas).
2. Com resultado do diagnóstico e "Arquivos enviados em atividades" maior que zero: `potential:recorded`
   (total da categoria na sala e a data da análise; não se compara tarefa a tarefa). Sem envios
   registrados (0 bytes) ou sem resultado, a frase não aparece.
3. Lista (`ul`) na ordem da sala; por tarefa (`li`):
   - nome atual com link (já formatado, sem escape duplo); `potential:hidden` quando oculta para estudantes;
   - frase das configurações: `potential:files_participant[_one]` ou `potential:files_group[_one]`;
   - origem do limite: `potential:limit_activity|default|course|site|server`;
   - tentativas: `potential:attempts_one|attempts|attempts_unlimited`;
   - quem pode enviar: `potential:units_participants[_one]` ou `potential:units_groups[_one]`;
   - resultado: `potential:value` (com `potential:formula` ou `potential:formula_attempts`),
     `potential:unlimited`, `potential:nobody`, `potential:nofiles` ou `potential:unknown`.
4. `<details>` recolhido, `potential:how` (título), com a fórmula e as limitações
   (`potential:how_formula`, `potential:how_attempts`, `potential:limit_dates`, `potential:limit_ignore`,
   `potential:limit_other`, `potential:how_server`, `potential:limit_change`).

Nenhuma cor de alerta, nenhum total, nenhuma ordenação por potencial, nenhum número de envios por tarefa.

## Strings (propostas; revisão de tom)

| Chave | en | pt_br |
| --- | --- | --- |
| `potential:heading` | Potential of assignments | Potencial das tarefas |
| `potential:intro` | What the current settings of the assignments allow to be stored in submitted files. Calculated now, with the current settings. | O que as configurações atuais das tarefas permitem guardar em arquivos enviados. Calculado agora, com as configurações atuais. |
| `potential:meaning` | The theoretical potential is a ceiling set by the settings: it is not the space used, a forecast or the amount that will be submitted. Different settings serve different teaching goals. | O potencial teórico é um teto das configurações: não é o espaço usado, uma previsão nem o volume que será enviado. Configurações diferentes atendem objetivos pedagógicos diferentes. |
| `potential:scope` | This section covers assignments only. | Esta seção considera só as tarefas. |
| `potential:recorded` | In this course, files already submitted in activities add up to {$a->size} (analysis of {$a->date}). This total is not compared assignment by assignment with the potential. | Nesta sala, os arquivos já enviados em atividades somam {$a->size} (análise de {$a->date}). Esse total não se compara tarefa a tarefa com o potencial. |
| `potential:none` | No assignment in this course accepts files at the moment. | Nenhuma tarefa desta sala aceita arquivos no momento. |
| `potential:hidden` | Hidden from students: nobody can submit while it is hidden. | Oculta para os estudantes: ninguém pode enviar enquanto ela estiver oculta. |
| `potential:files_participant` | Up to {$a->files} files per participant, of up to {$a->size} each. | Até {$a->files} arquivos por participante, de até {$a->size} cada. |
| `potential:files_participant_one` | Up to 1 file per participant, of up to {$a->size}. | Até 1 arquivo por participante, de até {$a->size}. |
| `potential:files_group` | Up to {$a->files} files per group, of up to {$a->size} each. | Até {$a->files} arquivos por grupo, de até {$a->size} cada. |
| `potential:files_group_one` | Up to 1 file per group, of up to {$a->size}. | Até 1 arquivo por grupo, de até {$a->size}. |
| `potential:limit_activity` | The size limit per file is set in the assignment. | O limite por arquivo é definido na tarefa. |
| `potential:limit_default` | The size limit per file is the site default for assignments. | O limite por arquivo é o padrão do site para tarefas. |
| `potential:limit_course` | The size limit per file comes from the course settings. | O limite por arquivo vem das configurações da sala. |
| `potential:limit_site` | The size limit per file comes from the site settings. | O limite por arquivo vem das configurações do site. |
| `potential:limit_server` | The size limit per file comes from the server settings. | O limite por arquivo vem das configurações do servidor. |
| `potential:attempts_one` | 1 attempt. | 1 tentativa. |
| `potential:attempts` | Up to {$a} attempts. | Até {$a} tentativas. |
| `potential:attempts_unlimited` | Unlimited attempts. | Tentativas ilimitadas. |
| `potential:units_participants` | {$a} participants can submit. | {$a} participantes podem enviar. |
| `potential:units_participants_one` | 1 participant can submit. | 1 participante pode enviar. |
| `potential:units_groups` | {$a} groups can submit. | {$a} grupos podem enviar. |
| `potential:units_groups_one` | 1 group can submit. | 1 grupo pode enviar. |
| `potential:value` | Theoretical potential: {$a->size} ({$a->formula}). | Potencial teórico: {$a->size} ({$a->formula}). |
| `potential:formula` | {$a->units} × {$a->files} × {$a->size} | {$a->units} × {$a->files} × {$a->size} |
| `potential:formula_attempts` | {$a->units} × {$a->attempts} attempts × {$a->files} × {$a->size} | {$a->units} × {$a->attempts} tentativas × {$a->files} × {$a->size} |
| `potential:unlimited` | No defined ceiling: with unlimited attempts, each attempt can hold up to {$a->size} ({$a->formula}). | Sem teto definido: com tentativas ilimitadas, cada tentativa pode guardar até {$a->size} ({$a->formula}). |
| `potential:nobody` | Nobody can submit in this assignment at the moment. | Ninguém pode enviar nesta tarefa no momento. |
| `potential:nofiles` | No file can be submitted with the current settings. | Nenhum arquivo pode ser enviado com a configuração atual. |
| `potential:unknown` | The potential of this assignment cannot be calculated with the available information. | O potencial desta tarefa não pode ser calculado com as informações disponíveis. |
| `potential:how` | How the potential is calculated, and its limits | Como o potencial é calculado e seus limites |
| `potential:how_formula` | Participants (or groups) × attempts × files per submission × size limit per file, with the current settings. | Participantes (ou grupos) × tentativas × arquivos por envio × limite por arquivo, com as configurações atuais. |
| `potential:how_attempts` | Each attempt keeps its own files. When a student starts from the previous submission, the files are copied: the copies count in the files recorded by Moodle, but identical content is stored only once. | Cada tentativa guarda os seus arquivos. Quando o estudante parte do envio anterior, os arquivos são copiados: as cópias contam nos arquivos registrados pelo Moodle, mas conteúdo idêntico é guardado uma vez só. |
| `potential:limit_dates` | Due dates and access restrictions are not considered. | Datas e restrições de acesso não são consideradas. |
| `potential:limit_ignore` | People allowed to ignore file size limits (usually not students) have no ceiling. | Quem pode ignorar os limites de tamanho de arquivo (normalmente não estudantes) não tem teto. |
| `potential:limit_other` | Files inserted in online text and feedback files are not included. | Arquivos inseridos no texto online e arquivos de feedback não entram. |
| `potential:how_server` | Server limits are the ones in force when this page was shown. | Os limites do servidor são os vigentes quando esta página foi mostrada. |
| `potential:limit_change` | Participants, groups and settings can change; the potential is valid for now. | Participantes, grupos e configurações podem mudar; o potencial vale para agora. |

## Acessibilidade

`h3` da seção, lista com um item por tarefa, links com nome completo, `<details>` nativo; números sempre
em texto; 320 px sem rolagem horizontal.
