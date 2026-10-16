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
| CT-06 | T054 | | | | pendente |
| CT-07 | T042, T054 | | | | pendente |
| CT-08 | T054 | | | | pendente |
| CT-09 | T028 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US2); CI verde (run 38964414578) |
| CT-10 | T009 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local; CI verde (run 38962129016) |
| CT-11 | T054 | | | | pendente |
| CT-12 | T027 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US2); CI verde (run 38964414578) |
| CT-13 | T027 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US2); CI verde (run 38964414578) |
| CT-14 | T050 | | | | pendente |
| CT-15 | T049, T051 | | | | pendente |
| CT-16 | T044 | | | | pendente |
| CT-17 | T041 (benchmark) | | | | pendente |
| CT-18 | T011, T034 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (T011, T034); CI pendente |
| CT-19 | T042, T044 | | | | pendente |
| CT-20 | T043 | | | | pendente |
| CT-21 | T054 | | | | pendente |
| CT-22 | T028 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US2); CI verde (run 38964414578) |
| CT-23 | T049 | | | | pendente |
| CT-24 | T041 (benchmark) | | | | pendente |
| CT-25 | T003, T068 | | | | pendente |
| CT-26 | T043 | | | | pendente |
| CT-27 | T043 | | | | pendente |
| CT-28 | T034 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (T034); CI pendente |
| CT-29 | T029 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US2); CI verde (run 38964414578) |
| CT-30 | T029 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US2); CI verde (run 38964414578) |
| CT-31 | T029 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US2); CI verde (run 38964414578) |
| CT-32 | T028 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local (US2); CI verde (run 38964414578) |
| CT-33 | T058 | | | | pendente |
| CT-34 | T058 | | | | pendente |
| CT-35 | T058 | | | | pendente |
| SC-001 | T009, T027–T029, T054, T058 | | | | pendente |
| SC-002 | T021, T040, T041 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | parcial: leituras e ausência de `files` (T021) verdes; microbenchmark T040/T041 pendente |
| SC-003 | T041 | | | | pendente |
| SC-004 | T041 | | | | pendente |
| SC-005 | T044 | | | | pendente |
| SC-006 | T042 | | | | pendente |
| SC-007 | T020, T049, T068 | | | | pendente |
| SC-008 | T066 | | | | pendente |
| SC-009 | T069 | | | | pendente |
| SC-010 | T009 | 2026-10-17 | 4.5.15 | PostgreSQL 17 | verde local; CI verde (run 38962129016) |

## Revisão de tom e rótulos (T066)

Pendente.

## Runs de CI (T069)

Pendente.
