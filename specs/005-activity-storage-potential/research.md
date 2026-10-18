# Research: Activity Storage Potential (Feature 005)

A metodologia de cálculo foi revisada no código do Moodle 4.5.15 e está na spec ("Metodologia
validada"). Aqui ficam as decisões de projeto.

## R1. Onde calcular: na exibição da página, não na análise em segundo plano

**Decisão**: o potencial é calculado quando a página "Sua sala em números" é mostrada, a partir das
configurações atuais. Nada é persistido e a análise da Feature 002 não muda.

**Motivo**:
- os limites de upload do servidor (tamanho máximo de arquivo e de requisição do PHP) só são conhecidos
  com certeza numa requisição web; a tarefa agendada roda em linha de comando, cujo `php.ini` costuma
  ter outros valores (Metodologia, item 5): calcular em segundo plano levaria a um limite errado;
- as configurações mudam a qualquer momento; calcular na exibição mostra sempre o estado atual, sem
  data de análise a explicar nem dado novo a guardar (Princípio VII, minimização);
- o custo cai só sobre quem abre a página (professor), não sobre milhares de salas a cada análise.

**Alternativas consideradas**: estender a tarefa de análise da 002 e guardar os fatores numa tabela
nova (limite do servidor errado na linha de comando, dados desatualizados, mudança no diagnóstico da
002); cache da aplicação (infraestrutura sem necessidade demonstrada).

## R2. Contar participantes sem uma consulta pesada por tarefa

**Medição** (PHPUnit no ambiente de desenvolvimento, PostgreSQL): contar participantes que podem
enviar com `count_enrolled_users()` no contexto de cada uma de 50 tarefas, com 500 estudantes, levou
7,3 s (cerca de 146 ms por tarefa). Inviável na exibição.

**Decisão**: o conjunto de quem pode enviar só difere entre tarefas da mesma sala quando o contexto da
tarefa tem uma sobreposição da capability de envio ou uma atribuição de papel local. Então:
1. uma contagem no contexto da sala (inscrição ativa e `mod/assign:submit`);
2. uma consulta que encontra, entre os contextos das tarefas listadas, os que têm sobreposição de
   `mod/assign:submit` (`role_capabilities`) ou atribuição de papel (`role_assignments`);
3. contagem exata (no contexto da tarefa) só para esses.

Resultado igual ao de contar tarefa a tarefa, porque a capability no contexto da tarefa herda do
contexto da sala quando não há nada definido na própria tarefa. Testes cobrem os dois caminhos.

**Custo no pior caso**: a contagem exata custa cerca de 146 ms por contexto com sobreposição ou papel
local, então o custo cresce com o número desses contextos (no caso típico, nenhum ou poucos). A consulta
de detecção usa os índices de `contextid` de `role_capabilities` e `role_assignments`. A medição de
SC-003 inclui o caso típico e o pior caso (todas as 50 tarefas com sobreposição). Se o pior caso
passar de 2 s, a forma de limitar o custo é decisão do proprietário do produto (interpretação do
indicador), registrada antes da entrega.

**Medição na implementação (T028, 2026-10-18)**: com `tools/dev/bench/potential.php` (200 atividades, 50
tarefas, 500 estudantes) e `render.php --page`, a página levou 17 ms sem sobreposições, 19 ms com 5 e
62 ms com as 50 (média de 20; 89 ms e 120 ms na primeira abertura depois de limpar os caches). A
contagem exata por contexto custou cerca de 1 ms, muito menos que os 146 ms da medição de viabilidade,
que chamava `count_enrolled_users()` dentro do PHPUnit logo depois de criar cada tarefa (caches de
permissão frios a cada chamada). A causa exata da diferença não foi isolada; a decisão de R2 continua
válida e o pior caso fica dentro de SC-003 sem decisão adicional.

**Alternativas consideradas**: contar tarefa a tarefa (7,3 s); usar a contagem da sala para todas
(errado com sobreposições na atividade, Metodologia, item 4).

## R3. Envio em grupo

**Decisão**: reproduzir a regra de `assign::count_teams()` com dados lidos de uma vez:
- os ids dos participantes que podem enviar (como em R2, por tarefa só quando houver sobreposição);
- uma consulta com as associações a grupos da sala (`groups_members` com `groups`) e, quando houver
  agrupamento, os grupos do agrupamento (`groupings_groups`);
- em memória (camada de análise, pura): com agrupamento, os grupos do agrupamento com pelo menos um
  participante; sem agrupamento, os grupos dos participantes que estão em exatamente um grupo; mais um
  grupo padrão quando houver participante sem grupo único e o envio fora de grupo não estiver impedido.

**Motivo**: mesma regra do Moodle, sem uma consulta por participante. Agrupamento que não existe mais:
nenhum grupo válido, só o grupo padrão quando permitido (Metodologia, item 6).

**Alternativas consideradas**: instanciar `assign` e chamar `count_teams()` por tarefa (lista completa
de usuários por tarefa e consultas de grupo por participante; custo como em R2).

## R4. Limite efetivo por arquivo

**Decisão**: na camada de análise, o menor dos valores maiores que zero entre: limite do servidor
(`get_max_upload_file_size()` sem limites de site, sala ou tarefa, isto é, o menor entre os limites do
PHP), `$CFG->maxbytes`, `$course->maxbytes` e o tamanho da tarefa (ou o padrão do plugin quando o da
tarefa for zero); a origem é o nível que determinou o mínimo (em empate: tarefa, padrão, sala, site,
servidor, nessa ordem). O coletor lê os valores; a análise decide.

**Motivo**: é o que `repository_ajax.php` aplica ao receber o arquivo (`get_user_max_upload_file_size`
com site, sala e o limite da tarefa); separar leitura e decisão mantém a regra testável.

## R5. Tentativas

**Decisão**: `maxattempts = 1`, método de reabertura `none` ou `untilpass` sem critério de aprovação → 1;
`maxattempts = -1` → ilimitado (sem teto; potencial por tentativa); outro valor → esse número. O
critério de aprovação vem do item de nota da tarefa (`grade_items`, `itemnumber = 0`): tipo por pontos ou
escala e nota para aprovação não vazia, diferente da mínima e, na escala, diferente de zero, como em
`grade_grade::is_passed()`. É uma configuração do item, não uma nota de estudante. (Revisão de código de
2026-10-18: a primeira versão ignorava o critério.)

## R6. Configurações ausentes

**Decisão** (comportamento do Moodle, Metodologia, item 6): sem registro `enabled` = 1 → envio de
arquivos desativado, tarefa fora da lista; `assignsubmission_file | disabled` no site → nenhuma tarefa com
envio de arquivos (o Moodle exige o plugin habilitado na tarefa e visível no site); `maxfilesubmissions` ausente ou ≤ 0 → "nenhum arquivo pode
ser enviado com a configuração atual", sem potencial; `maxsubmissionsizebytes` ausente → 0 (padrão do
plugin).

## R7. Quais tarefas e em que ordem

**Decisão**: tarefas da estrutura da sala (cache de módulos) que o professor pode ver, na ordem da sala;
tarefas ocultas para os estudantes ficam na lista, com a indicação. `course_structure` passa a expor o
tipo do módulo, o id da instância e a visibilidade para estudantes.

## R8. Lembrete do armazenamento registrado (Q1 = A)

**Decisão**: quando há resultado do diagnóstico, a seção diz, numa frase, o total de "Arquivos enviados
em atividades" da sala, com a data da análise, e que ele não se compara tarefa a tarefa com o potencial.
Sem resultado, a frase não aparece; o potencial independe do diagnóstico.

## R9. Seção independente do diagnóstico

**Decisão**: a seção de potencial aparece sempre que a sala tem tarefas com envio de arquivos, mesmo
sem resultado do diagnóstico (estados A a C): o potencial vem das configurações, não da análise de
arquivos. A data mostrada é a do cálculo ("calculado agora, com as configurações atuais"; Princípio IV).

## R10. Versão

**Decisão**: `release` 0.5.0; sem mudança de banco.
