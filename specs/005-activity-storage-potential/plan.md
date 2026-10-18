# Implementation Plan: Activity Storage Potential

**Branch**: `005-activity-storage-potential` | **Date**: 2026-10-18 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/005-activity-storage-potential/spec.md` (commit `bf5395d`,
esclarecida em 2026-10-18: Q1 A, Q2 A, Q3 A; metodologia validada no código do Moodle 4.5)

## Summary

A página "Sua sala em números" ganha a seção **"Potencial das tarefas"**: para cada tarefa (`mod_assign`)
com envio de arquivos, o que as configurações permitem (arquivos por envio, limite efetivo por arquivo e
sua origem, tentativas, participantes ou grupos que podem enviar) e o **potencial teórico** resultante,
ou a razão de não haver número. Sem total da sala, sem sugestão nova, sem armazenamento registrado por
tarefa.

**Como fica simples e correto:**
- calculado na exibição da página com as configurações atuais (research R1): limites do servidor web
  corretos, nenhum dado persistido, a análise da 002 inalterada;
- participantes contados uma vez na sala e, por tarefa, só quando houver sobreposição ou papel local
  (R2, medido: contar tarefa a tarefa custaria 7,3 s em 50 tarefas);
- envio em grupo pela regra do Moodle com uma consulta de associações (R3);
- coletor novo `assign_settings` (leituras), análise pura `storage_potential` (regra), apresentação no
  `course_overview` e num template novo. Sem tabela, configuração, capability, JavaScript ou tarefa.

## Technical Context

**Language/Version**: PHP 8.1–8.3 (Moodle 4.5 LTS), Mustache, CSS. Sem JavaScript.

**Primary Dependencies**: Moodle 4.5 core (Access, Enrol: `get_enrolled_sql`/`count_enrolled_users`,
Groups, `get_max_upload_file_size`, `get_fast_modinfo`, Output, String); tabelas do core `assign`,
`assign_plugin_config`, `groups`, `groups_members`, `groupings_groups`, `role_capabilities`,
`role_assignments`, só leitura. Sem bibliotecas de terceiros.

**Storage**: nenhuma tabela ou campo novo; nada persistido pela feature.

**Testing**: PHPUnit (regra pura com tabela de casos; coletor com tarefas, grupos, agrupamentos,
sobreposições e papéis locais; renderable; privacidade; custo de leituras), Behat (seção, estados,
acessibilidade com axe em desktop e celular), paridade de strings, pacote, `moodle-plugin-ci`. Medição
de desempenho com `tools/dev/bench/render.php --page`. Validação visual e de tom manuais.

**Target Platform**: Moodle 4.5, PostgreSQL e MariaDB/MySQL, Boost e Classic, desktop e celular.

**Project Type**: plugin de bloco do Moodle (`block_coursecare`), versão 0.5.0.

**Performance Goals**: página com 200 atividades, 50 tarefas com arquivos e 500 participantes em até
2 s em condições normais (SC-003); o bloco não muda (nenhuma leitura nova).

**Constraints**: nenhuma consulta a `files`; nenhum dado de envio por tarefa (Q1); nenhum total (Q2);
nenhuma classificação de configuração (Q3); nenhuma alteração de configuração ou arquivo.

**Scale/Scope**: 1 coletor novo, 1 classe de análise nova, alterações no `course_structure` e no
`course_overview`, 1 template novo, estilos, 38 strings novas.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Princípio | Avaliação | Situação |
| --- | --- | --- |
| I. Educação, não fiscalização | Seção informativa; nenhuma configuração dita adequada ou inadequada; sem ranking; ordem da sala; sem ação sobre configurações ou arquivos. | ✅ |
| II. Simplicidade | Nível 2: uma linha por tarefa com frases simples; nível 3: limitações nos detalhes técnicos; o bloco não muda. | ✅ |
| III. Recomendações | Nenhuma recomendação nova (Q3); ideias futuras registradas com os critérios necessários. | ✅ N/A |
| IV. Honestidade | Categoria "Potencial teórico" rotulada; nunca somada ao armazenamento lógico; sem total; sem número quando um fator é desconhecido; ilimitado = sem teto; data do cálculo; limitações explicadas; cópias entre tentativas explicadas (lógico × físico). | ✅ |
| V. Integração nativa | APIs de inscrição, capability, grupos e limite de upload do core, com a mesma regra que a tarefa e o upload aplicam. | ✅ |
| VI. Desempenho | Cálculo na exibição só para quem abre a página; contagem por tarefa evitada (R2, medido); nenhuma consulta a `files`; meta medida (SC-003). | ✅ (medição na implementação) |
| VII. Segurança e privacidade | Mesmo acesso da página; só configurações e contagens; nenhum envio, nome de estudante ou de arquivo; nada armazenado; envios só no agregado da sala (Q1). | ✅ |
| VIII. Internacionalização | Frases completas com singular e plural, en e pt_br. | ✅ |
| IX. Qualidade e camadas | Leituras no coletor (`local\collector\assign_settings`); regra pura em `local\analysis\storage_potential`; apresentação em `output`. | ✅ |
| X. Experiência visual | Mesma linguagem visual da página; sem cores de alerta; validação visual com aprovação. | ✅ |
| XI. Evolução incremental | Só `mod_assign`; sem estrutura para outros módulos (registrados como fora do escopo). | ✅ |
| XII. Software livre | GPL, README e CHANGES 0.5.0. | ✅ |

**Resultado do gate (pré-pesquisa e pós-design)**: **PASSA**, sem violações e sem Complexity Tracking.

### Registro de tratamento de dados (Princípio VII)

| Item | Decisão |
| --- | --- |
| Dados lidos | Configurações das tarefas, da sala e do site; limites do servidor; ids e quantidade de quem pode enviar; associações a grupos da sala. |
| Dados exibidos | Por tarefa: configurações, número de participantes ou grupos que podem enviar, potencial teórico. Da sala: o total de envios já registrado (agregado, da 002). |
| Dados armazenados | Nenhum. |
| Risco residual | O número de participantes por tarefa já é visível ao professor no próprio Moodle. Nenhum dado de envio por tarefa. |
| Privacy API | `null_provider` mantido. |

## Project Structure

### Documentation (this feature)

```text
specs/005-activity-storage-potential/
├── plan.md, research.md, data-model.md, quickstart.md
├── contracts/
│   ├── potential-calculation.md   # regra de cálculo com casos
│   └── potential-ui.md            # seção da página, strings
├── checklists/requirements.md
└── tasks.md
```

### Source Code (repository root)

```text
classes/local/collector/
├── assign_settings.php        # NOVO: tarefas, configurações do envio, limites, participantes, grupos
└── course_structure.php       # + modname, instance, visible
classes/local/analysis/
└── storage_potential.php      # NOVO: regra pura (limite efetivo, tentativas, unidades, potencial)
classes/output/
└── course_overview.php        # + dados da seção "Potencial das tarefas"
templates/
├── overview_potential.mustache  # NOVO
└── overview.mustache            # inclui a seção
styles.css, lang/en, translations/pt_br, version.php (0.5.0), README.md, CHANGES.md
tests/analysis/storage_potential_test.php      # NOVO
tests/collector/assign_settings_test.php       # NOVO
tests/output/course_overview_test.php          # + seção
tests/behat/block_coursecare_potential.feature # NOVO
tools/dev/demo/setup.php                       # + tarefas de demonstração com configurações variadas
```

## Fluxo

`course_overview::for_course()` → `course_structure` (tarefas visíveis ao professor, na ordem da sala)
→ `assign_settings::for_course()` (configurações das tarefas e do plugin, limites de site, sala,
servidor e padrão, contagem da sala, contextos com sobreposição, contagens exatas, associações a
grupos) → `storage_potential::evaluate()` por tarefa → `course_overview` monta frases → template.

## Riscos

- **Custo em salas muito grandes**: medido em SC-003; a contagem por tarefa só acontece com sobreposição
  ou papel local, e o custo cresce com o número desses contextos; o pior caso (todas as tarefas com
  sobreposição) é medido, e um resultado acima de 2 s vai para decisão do proprietário do produto (R2).
- **Diferença entre a nossa regra de grupos e a do Moodle**: testes que comparam com
  `assign::count_teams()` em cenários de grupo.
- **Limites do servidor diferentes por requisição**: a página usa os da própria requisição e diz isso.

## Complexity Tracking

Sem itens.
