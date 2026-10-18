# Data Model: Activity Storage Potential (Feature 005)

Nenhuma tabela, campo ou configuração nova; nada é persistido. Estruturas em memória, montadas a cada
exibição da página. Formatos detalhados em [contracts/potential-calculation.md](contracts/potential-calculation.md).

## Fontes (somente leitura)

| Fonte | Campos usados |
| --- | --- |
| Estrutura da sala (`get_fast_modinfo`) | cmid, tipo do módulo, instância, nome atual, link, visível para o professor, visível para estudantes, ordem |
| `assign` | id, maxattempts, attemptreopenmethod, teamsubmission, teamsubmissiongroupingid, preventsubmissionnotingroup |
| `grade_items` (`itemnumber = 0` da tarefa) | gradetype, gradepass, grademin (configuração do item, para "até passar") |
| `assign_plugin_config` (plugin `file`, subtipo `assignsubmission`) | enabled, maxfilesubmissions, maxsubmissionsizebytes |
| Configurações | `$CFG->maxbytes`, `course.maxbytes`, `assignsubmission_file/maxbytes`, `assignsubmission_file/disabled`, limites do PHP da requisição |
| Inscrições e capability | ids de quem pode enviar (inscrição ativa, `mod/assign:submit`) |
| `role_capabilities`, `role_assignments` | contextos de tarefa com sobreposição de `mod/assign:submit` ou papel local |
| `groups`, `groups_members`, `groupings_groups` | associações a grupos da sala e grupos dos agrupamentos usados |
| Diagnóstico da 002 | total da categoria "Arquivos enviados em atividades" e data da análise (só para a frase de FR-025) |

## Potencial de tarefa (saída da análise)

| Campo | Regra |
| --- | --- |
| status | `potential`, `unlimited`, `nofiles`, `nobody` ou `unknown` |
| unit | `participant` ou `group` |
| units | ≥ 0 |
| files | arquivos por envio (≥ 1 quando há potencial) |
| filebytes, limitsource | limite efetivo por arquivo e o nível que o define |
| attempts | ≥ 1, ou nulo quando ilimitado |
| perattempt | units × files × filebytes |
| potential | perattempt × attempts; nulo quando ilimitado ou sem número |

Validações: nenhum campo com dado de envio, nome de estudante ou arquivo; nenhuma soma entre tarefas.
