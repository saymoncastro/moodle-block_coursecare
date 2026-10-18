<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Strings for component 'block_coursecare', language 'pt_br' (development copy, not packaged).
 *
 * @package    block_coursecare
 * @copyright  2026 Saymon Castro
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['block:suggestion'] = 'Há uma sugestão sobre os arquivos da sua sala.';
$string['cli:alreadypending'] = 'Já há uma análise pendente para a sala {$a}.';
$string['cli:help'] = 'Diagnóstico de armazenamento do bloco AVA Consciente (administradores).

Exatamente um modo por execução:
  --queue --course=ID   Solicita a análise de uma sala que tem o bloco.
  --queue --all         Solicita a análise de todas as salas com o bloco e sem solicitação pendente.
  --run --course=ID     Analisa uma sala agora, neste processo (para verificação e benchmark).
  --show --course=ID    Mostra o resultado vigente de uma sala.

Opções:
  --json                Com --run ou --show: imprime JSON.
  -h, --help            Mostra esta ajuda.

Códigos de saída: 0 sucesso, 1 opções inválidas, 2 sala inexistente ou sem o bloco, 3 análise já em execução, 4 falha da análise (o resultado anterior foi mantido).

Exemplo:
  php blocks/coursecare/cli/diagnostics.php --show --course=42';
$string['cli:ineligible'] = 'A sala {$a} não existe ou não tem o bloco AVA Consciente.';
$string['cli:invalidoptions'] = 'Opções inválidas. Use --help para ver como usar esta ferramenta.';
$string['cli:queued'] = 'Análise solicitada para a sala {$a}.';
$string['cli:queuedall'] = 'Análises solicitadas: {$a}.';
$string['cli:runresult'] = 'Sala {$a->courseid}: {$a->outcome} em {$a->seconds} s, pico de memória {$a->memory}.';
$string['cli:showheading'] = 'Diagnóstico de armazenamento da sala {$a}';
$string['coursecare:addinstance'] = 'Adicionar um novo bloco AVA Consciente';
$string['coursecare:view'] = 'Ver o bloco AVA Consciente';
$string['diagnostic:calculatedon'] = 'Calculado em {$a}.';
$string['diagnostic:mayoutdated'] = 'Esta informação pode estar desatualizada.';
$string['diagnostic:nofiles'] = 'Não foram encontrados arquivos nos materiais e nas atividades desta sala.';
$string['diagnostic:notavailable'] = 'O panorama de arquivos desta sala ainda não está disponível.';
$string['diagnostic:preparing'] = 'O panorama de arquivos desta sala está sendo preparado.';
$string['diagnostic:updatefailed'] = 'A atualização mais recente não pôde ser concluída.';
$string['overview:activities'] = 'Materiais por atividade';
$string['overview:activities_help'] = 'Só os materiais de cada atividade, em relação a todos os materiais da sala.';
$string['overview:back'] = 'Voltar à sala';
$string['overview:categories'] = 'Onde estão os arquivos';
$string['overview:categories_help'] = 'Quanto cada tipo de arquivo representa no total da sala.';
$string['overview:category:material'] = 'Materiais da sala';
$string['overview:category:material_help'] = 'Arquivos publicados por quem edita a sala: recursos, pastas, páginas, livros e descrições.';
$string['overview:category:other'] = 'Outros arquivos associados';
$string['overview:category:other_help'] = 'Comentários de avaliação, anexos de fóruns, bancos de questões e de conteúdo.';
$string['overview:category:submission'] = 'Arquivos enviados em atividades';
$string['overview:category:submission_help'] = 'Arquivos enviados por participantes em tarefas e questionários, mostrados só para a sala inteira.';
$string['overview:category:unclassified'] = 'Não classificados';
$string['overview:category:unclassified_help'] = 'Arquivos de áreas que o panorama ainda não reconhece; eles contam no total.';
$string['overview:copies'] = 'Cópias de segurança e lixeira registradas no Moodle para esta sala';
$string['overview:copies_backup'] = 'Cópias de segurança: {$a->count} ({$a->size})';
$string['overview:copies_help'] = 'Medidas à parte, não somadas ao total; cópias guardadas fora do Moodle não entram.';
$string['overview:copies_recyclebin'] = 'Lixeira: {$a->count} ({$a->size})';
$string['overview:copies_unique'] = 'Conteúdo único das cópias: {$a}';
$string['overview:courselevel'] = 'Página da sala, seções e blocos';
$string['overview:coverage'] = 'Arquivos que não puderam ser classificados: {$a->count} ({$a->size}, {$a->share})';
$string['overview:files'] = 'Arquivos da sala registrados no Moodle';
$string['overview:intro'] = 'Veja onde estão os arquivos da sua sala e quando foram medidos.';
$string['overview:large'] = 'Arquivos de grande porte';
$string['overview:large_count'] = 'Arquivos: {$a}';
$string['overview:large_help'] = 'O limite é um valor de triagem definido pelo site, não uma regra pedagógica.';
$string['overview:large_materials'] = 'Materiais de grande porte por atividade';
$string['overview:large_none'] = 'Nenhum arquivo de {$a} ou mais nesta sala.';
$string['overview:large_summary'] = 'Arquivos de {$a->threshold} ou mais: {$a->count} ({$a->size}).';
$string['overview:large_truncated'] = 'Esta lista considera os 1.000 maiores materiais.';
$string['overview:link'] = 'Conheça melhor os arquivos da sua sala';
$string['overview:notmeaning'] = 'Estes números mostram os arquivos que o Moodle registra para esta sala. Eles não são o espaço em disco ocupado só por ela e não indicam quanto espaço seria liberado ao apagar arquivos.';
$string['overview:otheractivities'] = 'Outras atividades';
$string['overview:partial'] = 'Parte dos arquivos ({$a->size}, {$a->share}) não pôde ser classificada; ela está incluída no total.';
$string['overview:references'] = 'Ligações a arquivos guardados em outro lugar';
$string['overview:references_external'] = 'Externas: {$a->count} ({$a->size})';
$string['overview:references_help'] = 'Contadas à parte e não somadas ao total. Ligações externas podem apontar para arquivos fora do Moodle.';
$string['overview:references_internal'] = 'No Moodle: {$a->count} ({$a->size})';
$string['overview:references_undetermined'] = 'Tamanho desconhecido: {$a}';
$string['overview:removed'] = 'Atividades removidas após a última análise';
$string['overview:share'] = '{$a}%';
$string['overview:shareless'] = 'menos de 1%';
$string['overview:showall'] = 'Mostrar todas as atividades ({$a})';
$string['overview:technical'] = 'Detalhes técnicos';
$string['overview:threshold'] = 'Limite de grande porte usado: {$a}';
$string['overview:title'] = 'Sua sala em números';
$string['overview:unique'] = 'Conteúdo único: {$a}';
$string['overview:unique_help'] = 'O mesmo arquivo contado uma vez, mesmo usado em vários lugares. O mesmo conteúdo pode existir em outras salas, então isto não é espaço ocupado só por esta sala.';
$string['pluginname'] = 'AVA Consciente';
$string['potential:attempts'] = 'Até {$a} tentativas.';
$string['potential:attempts_one'] = '1 tentativa.';
$string['potential:attempts_unlimited'] = 'Tentativas ilimitadas.';
$string['potential:files_group'] = 'Até {$a->files} arquivos por grupo, de até {$a->size} cada.';
$string['potential:files_group_one'] = 'Até 1 arquivo por grupo, de até {$a->size}.';
$string['potential:files_participant'] = 'Até {$a->files} arquivos por participante, de até {$a->size} cada.';
$string['potential:files_participant_one'] = 'Até 1 arquivo por participante, de até {$a->size}.';
$string['potential:formula'] = '{$a->units} × {$a->files} × {$a->size}';
$string['potential:formula_attempts'] = '{$a->units} × {$a->attempts} tentativas × {$a->files} × {$a->size}';
$string['potential:heading'] = 'Potencial das tarefas';
$string['potential:hidden'] = 'Oculta para os estudantes: ninguém pode enviar enquanto ela estiver oculta.';
$string['potential:how'] = 'Como o potencial é calculado e seus limites';
$string['potential:how_attempts'] = 'Cada tentativa guarda os seus arquivos. Quando o estudante parte do envio anterior, os arquivos são copiados: as cópias contam nos arquivos registrados pelo Moodle, mas conteúdo idêntico é guardado uma vez só.';
$string['potential:how_formula'] = 'Participantes (ou grupos) × tentativas × arquivos por envio × limite por arquivo, com as configurações atuais.';
$string['potential:how_server'] = 'Os limites do servidor são os vigentes quando esta página foi mostrada.';
$string['potential:intro'] = 'O que as configurações atuais das tarefas permitem guardar em arquivos enviados. Calculado agora, com as configurações atuais.';
$string['potential:limit_activity'] = 'O limite por arquivo é definido na tarefa.';
$string['potential:limit_change'] = 'Participantes, grupos e configurações podem mudar; o potencial vale para agora.';
$string['potential:limit_course'] = 'O limite por arquivo vem das configurações da sala.';
$string['potential:limit_dates'] = 'Datas e restrições de acesso não são consideradas.';
$string['potential:limit_default'] = 'O limite por arquivo é o padrão do site para tarefas.';
$string['potential:limit_ignore'] = 'Quem pode ignorar os limites de tamanho de arquivo (normalmente não estudantes) não tem teto.';
$string['potential:limit_other'] = 'Arquivos inseridos no texto online e arquivos de feedback não entram.';
$string['potential:limit_server'] = 'O limite por arquivo vem das configurações do servidor.';
$string['potential:limit_site'] = 'O limite por arquivo vem das configurações do site.';
$string['potential:meaning'] = 'O potencial teórico é um teto das configurações: não é o espaço usado, uma previsão nem o volume que será enviado. Configurações diferentes atendem objetivos pedagógicos diferentes.';
$string['potential:nobody'] = 'Ninguém pode enviar nesta tarefa no momento.';
$string['potential:nofiles'] = 'Nenhum arquivo pode ser enviado com a configuração atual.';
$string['potential:none'] = 'Nenhuma tarefa desta sala aceita arquivos no momento.';
$string['potential:recorded'] = 'Nesta sala, os arquivos já enviados em atividades somam {$a->size} (análise de {$a->date}). Esse total não se compara tarefa a tarefa com o potencial.';
$string['potential:scope'] = 'Esta seção considera só as tarefas.';
$string['potential:units_groups'] = '{$a} grupos podem enviar.';
$string['potential:units_groups_one'] = '1 grupo pode enviar.';
$string['potential:units_participants'] = '{$a} participantes podem enviar.';
$string['potential:units_participants_one'] = '1 participante pode enviar.';
$string['potential:unknown'] = 'O potencial desta tarefa não pode ser calculado com as informações disponíveis.';
$string['potential:unlimited'] = 'Sem teto definido: com tentativas ilimitadas, cada tentativa pode guardar até {$a->size} ({$a->formula}).';
$string['potential:value'] = 'Potencial teórico: {$a->size} ({$a->formula}).';
$string['privacy:metadata'] = 'O bloco AVA Consciente não armazena dados pessoais. O panorama de armazenamento guarda apenas medições agregadas dos arquivos de cada sala e atividade, e ligações a arquivos grandes dos materiais da sala (identificador do arquivo e tamanho registrado).';
$string['settings:largefilethreshold'] = 'Limite de arquivo grande (MB)';
$string['settings:largefilethreshold_desc'] = 'Arquivos deste tamanho ou maiores são contados como arquivos grandes no panorama de armazenamento da sala (1 MB = 1.048.576 bytes). É um valor neutro de triagem, não um limite pedagógico nem um julgamento sobre o uso do arquivo.';
$string['settings:reanalysisinterval'] = 'Intervalo de reanálise';
$string['settings:reanalysisinterval_desc'] = 'Com que frequência cada sala que tem o bloco é analisada novamente em segundo plano.';
$string['settings:stalenessthreshold'] = 'Limite de atualidade';
$string['settings:stalenessthreshold_desc'] = 'Quando a última análise concluída é mais antiga que este limite, o bloco informa que a informação pode estar desatualizada. Use um valor maior que o intervalo de reanálise.';
$string['size:gb'] = 'GB';
$string['size:kb'] = 'KB';
$string['size:mb'] = 'MB';
$string['size:tb'] = 'TB';
$string['suggestion:large:more'] = 'e mais {$a} lugares, listados em Arquivos de grande porte.';
$string['suggestion:large:more_one'] = 'e mais 1 lugar, listado em Arquivos de grande porte.';
$string['suggestion:large:observed'] = '{$a->count} materiais da sala de {$a->threshold} ou mais, somando {$a->size}, em:';
$string['suggestion:large:observed_one'] = '1 material da sala de {$a->threshold} ou mais, com {$a->size}, em:';
$string['suggestion:large:observed_unnamed'] = '{$a->count} materiais da sala de {$a->threshold} ou mais, somando {$a->size}, em atividades listadas em Arquivos de grande porte.';
$string['suggestion:large:observed_unnamed_one'] = '1 material da sala de {$a->threshold} ou mais, com {$a->size}, numa atividade listada em Arquivos de grande porte.';
$string['suggestion:large:option1'] = 'uma versão otimizada do arquivo, que continue legível e acessível;';
$string['suggestion:large:option1_example'] = 'por exemplo, um PDF com imagens na resolução adequada para a tela ou um vídeo na resolução necessária.';
$string['suggestion:large:option2'] = 'dividir um conteúdo longo em partes menores;';
$string['suggestion:large:option3'] = 'um link para onde o conteúdo já está publicado.';
$string['suggestion:large:title'] = 'Materiais de grande porte';
$string['suggestion:large:todo'] = 'Ao revisar a atividade, avalie se alguma destas opções faz sentido para o seu material. Nem todas se aplicam a todos os casos:';
$string['suggestion:large:why'] = 'Arquivos grandes podem demorar mais para abrir, sobretudo em conexões lentas ou no celular. O tamanho, sozinho, não diz nada sobre a qualidade ou a importância do material.';
$string['suggestion:observed'] = 'O que observamos';
$string['suggestion:todo'] = 'O que você pode fazer, se quiser';
$string['suggestion:why'] = 'Por que pode merecer atenção';
$string['suggestions:basedon'] = 'Estas sugestões usam a análise de {$a}.';
$string['suggestions:heading'] = 'Sugestões para a sua sala';
$string['suggestions:none'] = 'Não há sugestões para esta sala no momento.';
$string['tagline'] = 'Pequenas escolhas, um AVA melhor para todos.';
$string['task:analysecourse'] = 'Analisar o armazenamento de arquivos de uma sala';
$string['task:queueanalyses'] = 'Enfileirar as análises de armazenamento de arquivos das salas';
