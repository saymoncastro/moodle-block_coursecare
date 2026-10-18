# Contract: roteiros de auditoria

## Segurança (registro em `evidence/security-privacy.md`)

| Item | Onde verificar | Esperado |
| --- | --- | --- |
| Login e capability | `overview.php`, bloco (`get_content`), CLI | `require_login` com a sala; `block/coursecare:view` no contexto do bloco; CLI só administrador |
| Isolamento por sala | coletores e `overview.php` | Toda leitura pela sala do contexto verificado, nunca por parâmetro livre sem verificação |
| Parâmetros | `required_param`/`optional_param` | Tipos `PARAM_*` |
| Ações de escrita | páginas e formulários | Nenhuma ação de escrita na interface (ou `sesskey`) |
| Escape | templates, `get_string` com parâmetros | `{{ }}` para texto; `{{{ }}}` só para HTML já formatado pelo Moodle |
| SQL | coletores | Placeholders; nenhuma concatenação de entrada |
| Arquivos | coletores | Só metadados; nenhum conteúdo, nome ou caminho exposto |
| Erros e logs | tarefas, CLI | Sem dados pessoais nem caminhos em mensagens |
| Pacote | ZIP | Sem credenciais, logs, dados de demonstração |

## Privacidade

| Item | Esperado |
| --- | --- |
| Tabelas do plugin × declaração | `null_provider` correto: nenhuma coluna com dado pessoal |
| Turmas pequenas | Inferência possível (total de envios de uma sala com um estudante) avaliada e documentada |
| Ciclo de vida | Remoção com a sala, o bloco, a retenção e a desinstalação |

## Acessibilidade e visual

Consolidar as evidências de 003–006 por jornada; refazer verificações automáticas no 5.3 (Boost) e no 4.5
(Boost e Classic) para as jornadas principais; registrar o teste humano com leitor de tela como pendente.
