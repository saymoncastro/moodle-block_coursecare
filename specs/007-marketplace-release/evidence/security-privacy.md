# Auditorias de segurança e privacidade (Feature 007, T016, T017)

Auditoria estática do código do pacote (sem `tools/`, `tests/`, `translations/`), feita pelo agente em
2026-10-18 no commit `98bd125`, segundo `contracts/audits.md`. Não substitui revisão humana de segurança
nem a revisão manual do Marketplace.

## Segurança

| Item | Verificado | Resultado |
| --- | --- | --- |
| Login e capability | `overview.php` → `overview_access::require_access()`: instância do bloco existente e do tipo `coursecare`, contexto pai de sala (nunca a página inicial do site), `require_login($course)`, `require_capability('block/coursecare:view', contexto do bloco)`. Bloco: `get_content()` só na página principal da sala e com `has_capability('block/coursecare:view')`. CLI: `define('CLI_SCRIPT', true)` (só no servidor) | Atendido |
| Isolamento por sala | A página lê só a sala derivada da instância verificada; o parâmetro é o id da instância, não o da sala. Coletores recebem o `courseid` da elegibilidade ou da tarefa, nunca de entrada do usuário | Atendido |
| Parâmetros | Um único parâmetro em todo o pacote: `required_param('id', PARAM_INT)` em `overview.php`; nenhum `$_GET`/`$_POST`/`$_REQUEST`/`$_COOKIE` | Atendido |
| Ações de escrita | Nenhuma na interface (sem formulário, sem botão de ação; a página e o bloco só leem). Escritas só nas tarefas e no CLI | Atendido (sem `sesskey` necessário) |
| Escape | `{{ }}` para texto. `{{{ }}}` em 6 lugares: nomes de atividades (`cm_info::get_formatted_name()`, já formatados pelo Moodle), o gráfico (HTML do renderizador de gráficos do núcleo) e a frase "observado" (montada com `s()` e `html_writer::tag('strong')` dentro de `get_string`) | Atendido |
| SQL | Placeholders nomeados e `get_in_or_equal`; os fragmentos SQL montados vêm de constantes de classificação, nunca de entrada | Atendido |
| Arquivos | Só metadados (contexto, componente, área, contenthash, tamanho registrado) durante a análise; nenhum conteúdo aberto; nenhum nome, caminho ou contenthash guardado nem exibido; arquivos grandes guardados só pelo id do arquivo, o `cmid` e o tamanho | Atendido |
| Erros e logs | Motivo de falha guardado como código curto (`inconsistent`, `interrupted` ou o nome curto da classe da exceção), sem mensagem nem caminho. Logs do cron: contagens e id da sala, sem dados pessoais | Atendido |
| Pacote | 84 arquivos; sem credenciais, logs, dados de demonstração, capturas, ferramentas ou especificações (`tools/package/check.php`: 0 erro; `install.md`) | Atendido |
| Rede | Nenhuma requisição externa, nenhuma biblioteca de terceiros | Atendido |

Nenhum achado exigiu correção de código. A política de reporte privado de vulnerabilidades está em
`SECURITY.md`; o recurso "Private vulnerability reporting" do GitHub está **desativado** no repositório e
precisa ser ativado pelo proprietário (Settings > Security) antes da publicação.

## Privacidade

| Item | Resultado |
| --- | --- |
| Tabelas × declaração | As 5 tabelas não têm coluna de usuário (nenhum `userid`, nome, e-mail, nome de arquivo, caminho ou contenthash; colunas conferidas em `db/install.xml`). `null_provider` correto. `privacy:metadata` atualizado para mencionar o histórico (en e pt_br) |
| Turmas pequenas | Avaliado e documentado em `docs/privacy.md`: numa sala com um ou dois estudantes, o total de envios da sala (e sua evolução no histórico) pode corresponder aos envios dessas pessoas; os mesmos professores já veem mais detalhe nas páginas nativas; o plugin nunca nomeia ninguém; o site pode restringir `block/coursecare:view` |
| Totais por atividade | `block_coursecare_act` guarda o total de envios por atividade (usado na análise); a página mostra envios só como total da sala; o CLI de administração (`--show`) exibe os totais por atividade a administradores. Documentado em `docs/privacy.md` nesta feature |
| Ciclo de vida | Sala excluída ou sem bloco: diagnóstico e histórico removidos pela tarefa horária (`delete_for_course`, inclusive o histórico). Retenção: pontos mais antigos que `historyretention` removidos em lotes. Desinstalação: tabelas, configurações, tarefa e capabilities removidas; instâncias removidas (no 5.3 por tarefa ad hoc do núcleo) — verificado em `install.md` |
| Rede e terceiros | Nada é enviado para fora do site |
