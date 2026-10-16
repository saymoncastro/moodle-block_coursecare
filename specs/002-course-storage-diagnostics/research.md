# Research: Course Storage Diagnostics (Feature 002)

**Data**: 2026-10-16 · **Constituição**: 1.2.0 · **Spec**: [spec.md](spec.md)

Fonte primária: código-fonte do Moodle **4.5.15** (`MOODLE_405_STABLE`, `version.php` 2024100715),
lido diretamente no ambiente de desenvolvimento. Caminhos relativos à raiz do Moodle. Todo
comportamento marcado **[código 4.5]** foi verificado no código; o que é inferência está marcado
como **[inferência]** e tem verificação prevista nos testes ou no benchmark.

---

## R1. Escopo de contextos da sala

**Decision**: o escopo é o contexto do curso mais todos os descendentes, selecionados por
`(ctx.id = :coursectxid OR ctx.path LIKE :coursepath || '/%')`, com junção `files.contextid =
ctx.id`.

**Rationale**:
- `context.path` é `<caminho do pai>/<id>` (`lib/classes/context.php:677-697`); descendentes são
  selecionados pelo core com `path LIKE` (`context.php:813-816`), e o padrão "curso + descendentes"
  `(ctx.id = :contextid OR ctx.path LIKE :path)` já existe no core (`course/lib.php:3535`)
  **[código 4.5]**.
- Índices disponíveis **[código 4.5]**: `context.path` com dica `varchar_pattern_ops`
  (`lib/db/install.xml:1213`), que permite busca por prefixo no PostgreSQL; `files.contextid` tem
  índice próprio, porque o XMLDB cria índice para toda chave estrangeira
  (`lib/ddl/sql_generator.php:364-391`; chave em `install.xml:2649`).
- Blocos têm contexto filho do contexto onde foram inseridos (curso ou atividade)
  (`lib/classes/context/block.php:169-172`), então ficam no escopo automaticamente.
- A junção com `context` descarta registros cujo contexto já foi apagado e que o
  `file_storage::cron()` ainda não limpou (`lib/filestorage/file_storage.php:2321-2333`).

**Alternatives considered**:
- `get_child_contexts()` + `IN (...)`: rejeitado. A lista pode ter milhares de ids, e alguns bancos
  limitam a quantidade de parâmetros. A junção por `path` é uma consulta única e indexada.
- `file_storage::get_area_files()`: rejeitado. Carrega um array de `stored_file` por área
  (`file_storage.php:668-671`), e os getters de `stored_file` podem sincronizar referências
  externas (R4).

## R2. Acesso aos metadados: só DML, sem File API de leitura

**Decision**: a coleta lê `files`, `files_reference`, `repository_instances`, `repository`,
`context` e `block_instances` por `$DB` (DML), com consultas agregadas portáveis. Ela não instancia
`stored_file`, não chama métodos de `file_storage` nem de `file_system` e não lê conteúdo.

**Rationale**:
- `stored_file::get_filesize()`, `get_contenthash()` e `get_timemodified()` chamam
  `sync_external_file()` (`lib/filestorage/stored_file.php:746-748, 785-786, 824-826`), que pode
  acionar `repository::sync_reference()` com acesso de rede (por exemplo, Dropbox,
  `repository/dropbox/lib.php:612`). O construtor de `stored_file` ainda instancia o repositório de
  cada referência (`stored_file.php:80`) **[código 4.5]**. Ler a coluna `files.filesize` por DML não
  sincroniza nada: atende FR-019g.
- Só metadados: compatível com qualquer sistema de armazenamento de arquivos do Moodle
  (`file_system_filedir` ou alternativos), sem acesso ao sistema de arquivos (spec, Assumptions).
- O Princípio V permite `$DB` com consultas portáveis. A File API é a fonte dos dados; o plugin só
  os lê na tabela que ela mantém.

**Alternatives considered**: File API de alto nível. Foi rejeitada pelos efeitos colaterais
(sincronização) e pelo custo de memória.

## R3. Conteúdo e deduplicação (contenthash)

**Decision**: o Conteúdo único soma, uma vez por `contenthash`, o tamanho do conteúdo, apenas entre
registros que **não** são referências. O tamanho usado é `MAX(filesize)` do grupo.

**Rationale** **[código 4.5]**:
- `contenthash = sha1(conteúdo)` (`file_storage.php:2472-2484`); o conteúdo é gravado uma vez por
  hash (`file_system_filedir.php:346-358, 461-466`).
- O conteúdo só sai do repositório quando nenhum registro o usa (`file_system::is_file_removable`,
  `lib/filestorage/file_system.php:227-243`), e vai para a lixeira do `filedir`, esvaziada pelo cron
  (`file_system_filedir.php:287-333`). Isso fundamenta a limitação "remoção não é economia".
- **Exceção comprovada**: referências externas podem ter o hash do conteúdo vazio com `filesize >
  0` (`repository/lib.php:2739`, `repository/filesystem/lib.php:586-592`, `dropbox/lib.php:653`).
  Como as referências ficam fora de A e B (decisão da spec), a regra "mesmo hash ⇒ mesmo tamanho"
  vale para os registros contados. `MAX()` é defensivo e o teste CT-22/CT-32 verifica o invariante.
- Diretórios usam o hash vazio e `filesize = 0` (`file_storage.php:1028-1044`). Arquivos reais de 0
  byte também existem e usam o hash vazio; somam 0 em qualquer caso.

**Alternatives considered**: deduplicar por `(contenthash, filename)`, como faz
`file_get_user_used_space()` (`lib/filelib.php:680-692`). Foi rejeitado: o nome do arquivo não
muda o armazenamento, e a spec pede deduplicação por conteúdo.

## R4. Referências: internas, externas e indeterminadas

**Decision**: um registro é referência quando `referencefileid IS NOT NULL`. O tipo vem de
`files_reference.repositoryid → repository_instances.typeid → repository.type`:
- **interna**: tipo em {`user`, `local`, `coursefiles`, `contentbank`, `recent`};
- **externa**: qualquer outro tipo conhecido;
- **indeterminada**: `status = 666` (origem ausente), instância ou tipo de repositório inexistente
  (junção nula), ou referência externa nunca sincronizada (`files_reference.lastsync IS NULL`).

O tamanho registrado é somado apenas para internas e externas sincronizadas.

**Rationale** **[código 4.5]**:
- Os repositórios cujo `has_moodle_files()` retorna verdadeiro são `contentbank` (`lib.php:97`),
  `coursefiles` (209), `local` (125), `recent` (239) e `user` (149). Aliases internos recebem o
  hash e o tamanho do arquivo de origem (`repository/repository_ajax.php:236-239`).
- `update_references()` grava `status = 666` quando a origem some (`file_storage.php:2444-2463`;
  `stored_file::set_missingsource()`, `stored_file.php:1046-1048`).
- **[inferência]** a lista de tipos internos vem dos `has_moodle_files()` do core. Um tipo de
  terceiros desconhecido é tratado como externo, nunca como interno, e o teste CT-09 cobre os dois
  lados. A lista fica como constante documentada no coletor.

**Alternatives considered**: perguntar ao repositório (`repository::get_repository_by_id`). Foi
rejeitado: instancia plugins de repositório e pode acionar rede.

## R5. Matriz técnica de áreas de arquivos

**Decision**: a classificação é uma **tabela de regras explícita** (componente, área exata ou
prefixo, níveis de contexto permitidos → categoria ou exclusão), com uma linha por contrato
verificado. Tudo o que não casar com uma regra é **Não classificado**. A tabela completa, com a
evidência de cada linha, está em [contracts/coverage-matrix.md](contracts/coverage-matrix.md).

Resumo das decisões que vão além do texto da matriz da spec (permitidas pela FR-025, sem alterar as
regras de cálculo):
- `mod_*/intro` de **qualquer** módulo → Materiais (M7). A área `intro` é gravada pelo fluxo padrão
  de criação e edição de todo módulo (`course/modlib.php:174, 657, 805`, componente
  `'mod_'.$modulename`, área `'intro'`); é sempre conteúdo de quem configura a atividade.
- `block_html/content` → Materiais (M15). Único bloco core com arquivos (`blocks/html/block_html.php:137`).
- `question/response_*` (respostas de estudantes a questões, por exemplo a redação com anexo no
  Questionário) → **Envios em atividades**. Ficam no contexto do módulo
  (`mod/quiz/locallib.php:2050`; `question/engine/questionattemptstep.php:274-276`;
  `question/type/essay/questiontype.php:43-44`). Sem a regra, quizzes com redação inflariam o "Não
  classificado". **Aceito pelo proprietário em 2026-10-16** (plan.md, D11).
- Áreas de definição de questões (`question/*` e `qtype_*`, exceto `response_*`) → Outros (M14).
- `grade/feedback` e `grade/historyfeedback` (contexto do módulo, `lib/grade/grade_grade.php:1094-1099`)
  → Outros, como o retorno de tarefas (M9).
- `assignfeedback_comments/feedback` (arquivos no comentário de retorno) → Outros (M9).
- Áreas transitórias **excluídas** (M4): `mod_folder/temp`, `mod_book/importhtmltemp`,
  `assignfeedback_file/feedback_files_batch`, `assignfeedback_file/feedback_files_import`,
  `assignfeedback_editpdf/tmp_jpg_to_pdf`, `tmp_rotated_jpg` e `importhtml`.
- Áreas regeneráveis da anotação de PDF (`combined`, `partial`, `pages`) → Outros (M10), não
  excluídas: existem enquanto o Moodle as mantém.
- `course/legacy`, grupos, calendário, emblemas, campos personalizados, `mod_feedback/template`,
  Lição, Oficina, Glossário, Base de dados, Wiki, SCORM, Atividade H5P, IMS CP, Questionário
  (`feedback`), Avaliação avançada e Big Blue Button → **Não classificado** nesta feature (autoria
  mista ou contrato não verificado o bastante). Contam em A e B e marcam cobertura parcial.

**Alternatives considered**: classificar por `userid` ou mimetype. Proibido pela FR-022.
Classificar todos os módulos conhecidos: rejeitado (Princípio XI e risco de classificação falsa,
FR-023).

## R6. Cópias de segurança e lixeira

**Decision**: agregado à parte com duas subcategorias:
- **backup**: componente `backup`, áreas `course`, `section`, `automated` (contexto do curso) e
  `activity` (contexto do módulo);
- **lixeira**: `tool_recyclebin/recyclebin_course` (contexto do curso).

**Rationale** **[código 4.5]**: `store_backup_file()` grava nessas áreas e contextos
(`backup/util/helper/backup_helper.class.php:262-334`). Backups sem dados de usuário ou anonimizados
vão para `user/backup` no contexto do usuário (`:321-326`), fora do escopo (M12a). Com
`backup_auto_storage = 1`, o backup automático sai do repositório para um diretório
(`:291-317`) (M19). A lixeira copia o backup com `create_file_from_storedfile()` e apaga o
original (`admin/tool/recyclebin/classes/course_bin.php:176-200`): **mesmo contenthash**, o que
fundamenta o conteúdo único do conjunto (CT-30). A lixeira de categoria fica no contexto da
categoria (`category_bin.php:161-174`) (M13a).

## R7. Elegibilidade e ciclo de vida do bloco

**Decision**:
- **Seleção**: salas com ao menos uma linha `block_instances` com `blockname = 'coursecare'` e
  `parentcontextid` = contexto de curso, exceto o curso do site. A consulta usa o índice
  `blocknameindex` (`install.xml:2787-2791`).
- **Bloco adicionado**: `instance_create()` grava ou reabre o diagnóstico com solicitação pendente e
  enfileira a análise (operação leve, sem análise).
- **Bloco removido**: `instance_delete()` verifica se resta outra instância no curso, excluindo a
  própria (a linha ainda existe durante a chamada); se não restar, apaga o diagnóstico e as linhas
  filhas.
- **Restauração e importação**: não chamam `instance_create()`; a tarefa periódica enfileira as
  salas elegíveis sem diagnóstico (FR-036).
- **Tipo de bloco desativado**: nada muda (instâncias e dados ficam; seleção não filtra `block.visible`).
- **Curso excluído**: coberto por `instance_delete()`; a tarefa periódica ainda remove diagnósticos
  órfãos como rede de segurança.

**Rationale** **[código 4.5]**:
- `instance_create()` é chamado em `block_manager::add_block()` depois do `insert_record`
  (`lib/blocklib.php:856-863`), inclusive para blocos padrão de cursos novos
  (`blocks_add_default_course_blocks`, `blocklib.php:2763-2767`; `course/lib.php:1965`). A
  restauração insere direto (`backup/moodle2/restore_stepslib.php:4578`).
- `instance_delete()` só é chamado por `blocks_delete_instance()` (`blocklib.php:2548-2563`), antes
  de apagar a linha. `delete_course()` → `remove_course_contents()` chama
  `blocks_delete_all_for_context()` para os contextos filhos e o do curso (`lib/moodlelib.php:4805-4812`),
  e a desinstalação chama `blocks_delete_instance()` para cada instância
  (`lib/classes/plugininfo/block.php:185-188`), antes de remover as tabelas (`lib/adminlib.php:238-242`).
- Desativar o tipo é só `block.visible = 0` (`plugininfo/block.php:59-66`); nenhum callback de
  exclusão é chamado.
- Não há eventos `block_created`/`block_deleted` em `lib/classes/event/`; a fila de tarefas não é
  limpa por curso na exclusão (sem `task_adhoc` em `moodlelib.php`), então a tarefa tolera curso
  inexistente.

**Alternatives considered**: hook `before_course_deleted` ou `pre_course_delete`. Desnecessários,
porque `instance_delete()` já é chamado; ficariam como segunda via de limpeza sem caso concreto
(Princípio XI). Observador de `course_restored`: a próxima execução horária basta (FR-036).

## R8. Processamento: tarefa agendada que distribui tarefas ad hoc por sala

**Decision**:
- **Tarefa agendada** `\block_coursecare\task\queue_analyses`, de hora em hora (minuto aleatório):
  limpa órfãos, recupera solicitações presas e enfileira até **500** salas devidas por execução.
- **Tarefa ad hoc** `\block_coursecare\task\analyse_course`, uma por sala, com `customdata =
  {"courseid": N}` (sem dado pessoal; o core grava `customdata` no log da tarefa, `lib/classes/cron.php:472`).
- **Concorrência da classe ad hoc**: `get_default_concurrency_limit()` retorna **2**. O site pode
  alterar por `$CFG->task_concurrency_limit`.

**Rationale** **[código 4.5]**:
- Tarefas ad hoc dão isolamento por sala, reexecução, registro em log e paralelismo entre
  processadores de cron, sem fila própria (pedido do proprietário). O limite por classe usa
  `get_default_concurrency_limit()` (`lib/classes/task/adhoc_task.php:135-158`) e locks
  `concurrent_run_N` (`manager.php:1509-1520`); o padrão do site é 3 processadores ad hoc
  (`admin/settings/server.php:385-390`). Com 2, sobra capacidade para outros componentes.
- A distribuição por partilha justa entre classes (`manager.php:731`) evita que 500 tarefas do
  CourseCare monopolizem a fila.
- Tarefa agendada roda uma por vez sob lock da classe (`manager.php:1007`) e não "recupera"
  execuções perdidas: quando o cron atrasa, roda uma vez na volta (`manager.php:1317-1320`). O
  critério de "devida" é baseado em datas, então um atraso só adia a fila, sem duplicar nada.
- **Capacidade**: 500 por hora = 12.000 salas por dia na fila. Com 2 análises simultâneas de
  cerca de 1 s (meta a medir), a capacidade de execução passa de 100.000 salas por dia
  **[projeção a confirmar no benchmark, R13]**.

**Alternatives considered**:
- Só tarefa agendada processando salas em laço com limite de tempo: rejeitado. Uma sala grande
  ocupa a execução inteira, não há paralelismo e as falhas se misturam.
- Fila própria em tabela: rejeitado (o pedido proíbe fila própria quando os mecanismos nativos
  bastam).
- Uma tarefa ad hoc para todas as salas: rejeitado pelo mesmo motivo do laço.

## R9. Duplicidade e concorrência

**Decision**: três camadas.
1. **Registro do diagnóstico** (`block_coursecare_diag.requeststatus`): só se enfileira uma sala com
   status "nenhum". Enfileirar = atualizar o registro para "na fila" e chamar
   `queue_adhoc_task($task)` **sem** `checkforexisting`.
2. **Lock por sala**: `lock_config::get_lock_factory('block_coursecare')`, recurso
   `analyse_course_<id>`, espera 0 e validade de 2 h. Se outro processo tem o lock, a tarefa sai sem
   analisar.
3. **Frescor**: ao iniciar, se o diagnóstico concluiu depois da criação da tarefa
   (`timecompleted >= task timecreated`), a tarefa sai.

**Rationale** **[código 4.5]**:
- `checkforexisting` compara `classname`, `component` e `customdata` com uma consulta separada do
  insert, sem índice único (`manager.php:197-209, 244-274`; `task_adhoc` só tem índices em
  `nextruntime` e `timestarted`). Isso não protege contra corrida e varre `task_adhoc` a cada
  chamada; com 500 enfileiramentos por hora, o custo cresce com a fila do site.
- Os locks de PostgreSQL (advisory) e MySQL (`GET_LOCK`) são liberados quando o processo termina;
  o `db_record_lock_factory` expira por `maxlifetime` (`lib/classes/lock/db_record_lock_factory.php:111,131`).
  Daí a validade de 2 h.

**Alternatives considered**: índice único próprio de solicitações (fila própria, rejeitada);
`checkforexisting` sozinho (sem proteção contra corrida).

## R10. Falhas, interrupções e preservação de resultados

**Decision**:
- A análise calcula tudo em memória (agregados pequenos) e grava o resultado **em uma única
  transação delegada**: apaga as linhas filhas do diagnóstico, insere as novas e atualiza o
  registro. Antes da transação, nada do resultado anterior é tocado.
- Em exceção, a tarefa registra a falha (`lastattemptfailed = 1`, `failurecount + 1`,
  `failurereason` = código curto da classe da exceção, sem mensagem) e **termina normalmente**, sem
  relançar. O resultado anterior permanece.
- **Nova tentativa limitada**: a tarefa agendada reenfileira uma sala com falha após
  `min(2^(falhas − 1), 24)` horas. A partir da 5ª falha consecutiva, só no intervalo normal de
  reanálise.
- **Interrupção** (processo morto): a tarefa agendada marca como falha "interrupted" a solicitação
  "em execução" há mais de 2 h, e libera para nova fila a solicitação "na fila" há mais de 24 h
  (tarefa perdida ou apagada).

**Rationale** **[código 4.5]**: transações delegadas aninhadas viram uma só, e qualquer rollback
desfaz tudo (`lib/dml/moodle_database.php:2683-2814`); o cron força rollback de transação aberta e
recusa tarefa que termina com transação aberta (`cron.php:520-547`). Relançar faria o core repetir
até 12 vezes, com atraso dobrando até 24 h (`manager.php:1087-1134`, `adhoc_task.php:50`), sem
coordenação com o estado do diagnóstico. O core já marca como falha a tarefa ad hoc iniciada há
mais de 1 h cujo lock está livre (`manager.php:1381-1447`), então 2 h é conservador.

## R11. Consistência do retrato

**Decision**: cada indicador vem de uma consulta agregada única (uma instrução SQL). Nenhum registro
é contado duas vezes dentro de uma consulta. Entre consultas (alguns segundos), alterações
concorrentes podem gerar diferenças pequenas; a análise verifica os invariantes (B ≤ A; soma das
categorias = A; únicos por categoria ≤ lógico da categoria) e, se algum falhar, refaz as consultas
uma vez. Se falhar de novo, registra falha "inconsistent", preservando o resultado anterior.

**Rationale**: o PostgreSQL usa `READ COMMITTED` por instrução; o MySQL/MariaDB usa leitura
consistente por instrução (e por transação em `REPEATABLE READ`). A spec aceita "retrato consistente
o bastante para não contar o mesmo registro duas vezes" com correção na próxima análise.

## R12. Algoritmo de agregação e memória

**Decision**: a análise de uma sala faz **5 consultas**, todas com resultado pequeno:
- Q0 contextos da sala (id, nível, instância, caminho);
- Q1 agregados por (contexto, componente, área, tipo de referência);
- Q2 conteúdo único por categoria;
- Q3 conteúdo único do escopo principal e do agregado de backup e lixeira;
- Q4 arquivos de grande porte (só os que passam do limite).

A categoria em Q2 e Q3 é uma expressão `CASE` gerada a partir da mesma tabela de regras usada em PHP
(fonte única). Detalhes em [contracts/indicators.md](contracts/indicators.md).

**Rationale** **[código 4.5]**:
- No MySQL/MariaDB, os recordsets são carregados inteiros no cliente (`MYSQLI_STORE_RESULT`,
  `lib/dml/mysqli_native_moodle_database.php:1301-1302`). Percorrer cada registro de arquivo em PHP
  faria a memória crescer com o tamanho da sala. Com agregação no banco, a memória depende do número
  de contextos, áreas e arquivos grandes, não do total de arquivos (SC-003).
- `SUM`/`COUNT(DISTINCT)` são usados no core em consultas portáveis (por exemplo,
  `lib/classes/check/access/riskxss_result.php:78`); os valores voltam como string e são convertidos
  explicitamente (`pgsql_native_moodle_recordset.php:110`).
- Agrupar pela expressão `CASE` via subconsulta (e não por apelido) mantém a portabilidade.

**Alternatives considered**: percorrer todos os registros ordenados por hash, com memória
constante. Foi rejeitado no MariaDB pelo buffer do cliente e no PostgreSQL pela ordenação repetida
em paginação por chave.

## R13. Benchmark reproduzível

**Decision**: gerador de dados sintéticos em `tools/dev/bench/` (fora do pacote), usando a File API
real em áreas reais de módulos reais, mais um roteiro de medição. Detalhes em [benchmark.md](benchmark.md).

## R14. Persistência, cache e privacidade

**Decision**:
- Quatro tabelas próprias (registro do diagnóstico, categorias, atividades e arquivos grandes de
  material) ([data-model.md](data-model.md)). Só o resultado vigente, sem histórico (Feature 006).
- **Sem cache MUC**: o bloco lê um registro por índice único. A Cache API não garante dados
  (`cache/classes/definition.php:42-43`), e invalidar exigiria código adicional sem ganho medido.
  Se o benchmark de SC-002 mostrar necessidade, a cache entra como ajuste.
- **Privacidade**: nenhuma coluna de usuário; ligações a arquivos só para arquivos grandes da
  categoria Materiais (decisão mais restritiva que a FR-016, que permite também Outros e Não
  classificado: Outros inclui fóruns e retornos, que podem ser de estudantes). O provider continua
  `null_provider`, com a razão atualizada: "armazena apenas medições agregadas de armazenamento por
  sala e por atividade, sem dados pessoais". Registro LGPD/GDPR no plan.md.

**Alternatives considered**: provider completo. Seria exigido se alguma coluna ligasse dados a
usuários; nenhuma liga.
