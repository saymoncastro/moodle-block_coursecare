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
$string['diagnostic:volume'] = 'Arquivos desta sala, conforme registrados no Moodle: {$a}.';
$string['intro'] = 'Em breve, você encontrará aqui orientações para utilizar os recursos da sua sala de forma mais consciente.';
$string['pluginname'] = 'AVA Consciente';
$string['privacy:metadata'] = 'O bloco AVA Consciente não armazena dados pessoais. O panorama de armazenamento guarda apenas medições agregadas dos arquivos de cada sala e atividade, e ligações a arquivos grandes dos materiais da sala (identificador do arquivo e tamanho registrado).';
$string['settings:largefilethreshold'] = 'Limite de arquivo grande (MB)';
$string['settings:largefilethreshold_desc'] = 'Arquivos deste tamanho ou maiores são contados como arquivos grandes no panorama de armazenamento da sala (1 MB = 1.048.576 bytes). É um valor neutro de triagem, não um limite pedagógico nem um julgamento sobre o uso do arquivo.';
$string['settings:reanalysisinterval'] = 'Intervalo de reanálise';
$string['settings:reanalysisinterval_desc'] = 'Com que frequência cada sala que tem o bloco é analisada novamente em segundo plano.';
$string['settings:stalenessthreshold'] = 'Limite de atualidade';
$string['settings:stalenessthreshold_desc'] = 'Quando a última análise concluída é mais antiga que este limite, o bloco informa que a informação pode estar desatualizada. Use um valor maior que o intervalo de reanálise.';
$string['tagline'] = 'Pequenas escolhas, um AVA melhor para todos.';
$string['task:analysecourse'] = 'Analisar o armazenamento de arquivos de uma sala';
$string['task:queueanalyses'] = 'Enfileirar as análises de armazenamento de arquivos das salas';
