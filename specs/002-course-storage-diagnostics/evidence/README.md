# Evidências da Feature 002 — Course Storage Diagnostics

Registro das evidências de cada cenário (CT) e critério de sucesso (SC) da [spec](../spec.md).
Nenhum item é marcado como atendido sem evidência: teste automatizado verde (com o run de CI) ou
registro manual com data, versão do Moodle e banco de dados. Os arquivos ficam fora do pacote.

| Item | Teste ou evidência | Data | Moodle | Banco | Status |
| --- | --- | --- | --- | --- | --- |
| CT-01 | T009, T022 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local; CI verde (run 38962129016) |
| CT-02 | T009 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local; CI verde (run 38962129016) |
| CT-03 | T009, T058 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | parcial: total da sala (T009) verde; por atividade (T058) pendente |
| CT-04 | T027 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US2); CI verde (run 38964414578) |
| CT-05 | T027 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US2); CI verde (run 38964414578) |
| CT-06 | T054 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US6); CI verde (run 38968677593) |
| CT-07 | T042, T054 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | privacidade (T042) com CI verde (run 38966922553); classificação (T054) com CI verde (run 38968677593) |
| CT-08 | T054 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US6); CI verde (run 38968677593) |
| CT-09 | T028 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US2); CI verde (run 38964414578) |
| CT-10 | T009 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local; CI verde (run 38962129016) |
| CT-11 | T054 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US6); CI verde (run 38968677593) |
| CT-12 | T027 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US2); CI verde (run 38964414578) |
| CT-13 | T027 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US2); CI verde (run 38964414578) |
| CT-14 | T050 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US5); CI verde (run 38967792551); reforçado na revisão com o executor real do cron, CI verde (run 38980720482) |
| CT-15 | T049, T051 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US5); CI verde (run 38967792551) |
| CT-16 | T044 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US4); CI verde (run 38966922553) |
| CT-17 | T041 (benchmark) | 2026-10-16 | 4.5.15 | PostgreSQL 17, MariaDB 11.4 | verde: 50.000 registros em ≤ 0,57 s, memória 1,02 × a de 5.000, valores = manifesto ([benchmark.md](benchmark.md)) |
| CT-18 | T011, T034 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (T011, T034); CI verde (run 38966216241) |
| CT-19 | T042, T044 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US4); CI verde (run 38966922553) |
| CT-20 | T043 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US4); CI verde (run 38966922553) |
| CT-21 | T054 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US6); CI verde (run 38968677593) |
| CT-22 | T028 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US2); CI verde (run 38964414578) |
| CT-23 | T049 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US5); CI verde (run 38967792551) |
| CT-24 | T041 (benchmark) | | | | pendente |
| CT-25 | T003, T068 | | | | pendente |
| CT-26 | T043 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US4); CI verde (run 38966922553) |
| CT-27 | T043 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US4); CI verde (run 38966922553) |
| CT-28 | T034 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (T034); CI verde (run 38966216241) |
| CT-29 | T029 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US2); CI verde (run 38964414578) |
| CT-30 | T029 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US2); CI verde (run 38964414578) |
| CT-31 | T029 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US2); CI verde (run 38964414578) |
| CT-32 | T028 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US2); CI verde (run 38964414578) |
| CT-33 | T058 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US7); CI verde (run 38971119869) |
| CT-34 | T058 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US7); CI verde (run 38971119869) |
| CT-35 | T058 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US7); CI verde (run 38971119869) |
| SC-001 | T009, T027–T029, T054, T058 | 2026-10-17 | 4.5.15 | PostgreSQL 17, MariaDB 11 (CI) | todos os cenários verdes local e no CI (último run 38980720482) |
| SC-002 | T021, T040, T041 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | atendido: nenhuma consulta a `files` em A–H (PHPUnit); +0,8 ms por renderização em relação à 0.1.0 (limite 50 ms) ([benchmark.md](benchmark.md)) |
| SC-003 | T041 | 2026-10-16 | 4.5.15 | PostgreSQL 17, MariaDB 11.4 | atendido nas duas bases ([benchmark.md](benchmark.md)) |
| SC-004 | T041 | | | | pendente |
| SC-005 | T044 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US4); CI verde (run 38966922553) |
| SC-006 | T042 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US4); CI verde (run 38966922553) |
| SC-007 | T020, T049, T068 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US5); CI verde (run 38967792551) |
| SC-008 | T066 | | | | pendente |
| SC-009 | T069 | | | | pendente |
| SC-010 | T009 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local; CI verde (run 38962129016) |

## Revisão de tom e rótulos (T066)

Revisão feita em 2026-10-16 sobre as 24 strings novas ou alteradas da Feature 002, em en e pt_br,
contra as restrições de [contracts/block-ui.md](../contracts/block-ui.md) e os Princípios I, II e IV.
Revisão do agente de implementação; a aprovação do proprietário do produto, como na Feature 001,
fica registrada à parte quando acontecer.

| Grupo | Chaves | Resultado |
| --- | --- | --- |
| Linha do bloco | `diagnostic:volume`, `calculatedon`, `nofiles`, `notavailable`, `preparing`, `mayoutdated`, `updatefailed` | Sem palavra de avaliação ("excesso", "alto", "bom"), sem termo de infraestrutura, sem "espaço ocupado", "consumo" ou "economia". O volume é rotulado como "as recorded by Moodle" / "conforme registrados no Moodle" (Princípio IV) e sempre vem com a data. Avisos de E e F são informativos, sem alarme nem pedido de ação. Uma frase completa por chave, um parâmetro no máximo. |
| Configurações | `settings:*` e `_desc` | Unidades explícitas (MB = 1.048.576 bytes); o limite de grande porte é descrito como valor neutro de triagem, não limite pedagógico nem julgamento de uso. |
| Tarefas | `task:analysecourse`, `task:queueanalyses` | Descrevem a ação, sem avaliação. |
| CLI | `cli:*` | Texto técnico para administradores; nenhuma frase de avaliação; nenhum nome de arquivo, atividade ou usuário nos parâmetros. |
| Privacidade | `privacy:metadata` | Corrigida nesta revisão: além das medições agregadas, menciona as ligações a arquivos grandes dos materiais (identificador do arquivo e tamanho registrado), que a versão anterior omitia. |

Terminologia em pt_br: "sala" para curso e "panorama de arquivos" para o diagnóstico, como na
Feature 001. Observação fora do plugin: em pt_BR o tamanho aparece como "4.0 Mb" porque o rótulo e o
separador decimal de `display_size()` vêm do pacote pt_br do core (`logs/quickstart-002.md`).

## Runs de CI (T069)

Workflow `moodle-plugin-ci` (PHP 8.1 com PostgreSQL e PHP 8.3 com MariaDB, `MOODLE_405_STABLE`) e
job `i18n-package`, na branch `002-course-storage-diagnostics`. Todos verdes:

| Run | Commit | Conteúdo |
| --- | --- | --- |
| 38980720482 | f59412b | Revisão de código (testes fortalecidos), polimento e evidência do SC-003 |
| 38971119869 | d1fef5e | US7: atividades e arquivos grandes |
| 38968677593 | 531d8f3 | US6: volume por categoria |
| 38967792551 | 17b0a97 | US5: estados desatualizado e falha |
| 38966922553 | 1f90ae8 | US4: privacidade e ciclo de vida |
| 38966216241 | 482830d | US3: agendamento, CLI e ferramentas de benchmark |
| 38964414578 | 93be1c4 | US2: conteúdo único, referências, cópias de segurança |
| 38962129016 | 84daca5 | MVP (T001–T026) |

O run final da branch, depois do SC-004, será acrescentado aqui.
