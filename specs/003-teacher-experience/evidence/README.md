# Evidências da Feature 003 — Teacher Experience

Registro das evidências de cada cenário (CT) e critério de sucesso (SC) da [spec](../spec.md).
Nenhum item é marcado como atendido sem evidência: teste automatizado verde (com o run de CI) ou
registro manual com data, versão do Moodle e banco de dados. Os arquivos ficam fora do pacote.

| Item | Teste ou evidência | Data | Moodle | Banco | Status |
| --- | --- | --- | --- | --- | --- |
| CT-01 | T013 | | | | pendente |
| CT-02 | T013 | | | | pendente |
| CT-03 | T013, T014 | | | | pendente |
| CT-04 | T017, T019 | | | | pendente |
| CT-05 | T024, T026 | | | | pendente |
| CT-06 | T014 | | | | pendente |
| CT-07 | T018, T020 | | | | pendente |
| CT-08 | T018 | | | | pendente |
| CT-09 | T018 | | | | pendente |
| CT-10 | T038, T039 | | | | pendente |
| CT-11 | T025, T026 | | | | pendente |
| CT-12 | T030, T032 | | | | pendente |
| CT-13 | T030, T032 | | | | pendente |
| CT-14 | T018, T031 | | | | pendente |
| CT-15 | T031, T047 | | | | pendente |
| CT-16 | T034, T052 | | | | pendente |
| CT-17 | T034 | | | | pendente |
| CT-18 | T037, T052 | | | | pendente |
| CT-19 | T048 | | | | pendente |
| CT-20 | T005, T013, T019 | | | | pendente |
| CT-21 | T007 | | | | pendente |
| CT-22 | T038 | | | | pendente |
| CT-23 | T050 | | | | pendente |
| CT-24 | T046, T048 | | | | pendente |
| SC-001 | T024, T026 | | | | pendente |
| SC-002 | T019, T031 | | | | pendente |
| SC-003 | T044 | | | | pendente |
| SC-004 | T044 | | | | pendente |
| SC-005 | T030, T032 | | | | pendente |
| SC-006 | T031 | | | | pendente |
| SC-007 | T034, T052 | | | | pendente |
| SC-008 | T047 | | | | pendente |
| SC-009 | T048 | | | | pendente |
| SC-010 | T053, T054 | | | | pendente |
| SC-011 | T050, T051 | | | | pendente |

## Revisão de tom e rótulos (T047)

Pendente.

## Reconstrução e validação visual (T046, T048)

### Proposta 1 (T046), 2026-10-17 — aguardando a revisão do proprietário do produto

- Ambiente: site de demonstração limpo e descartável (`moodle-docker`, projeto `coursecare-demo`), Moodle
  4.5.15, PHP 8.1, PostgreSQL 17, tema Boost, pacote oficial pt_br e tradução do projeto instalada por
  `tool_customlang`; salas fictícias criadas por `tools/dev/demo/setup.php --threshold-mb=1
  --activities=200` (limite de grande porte de 1 MB para a demonstração).
- Capturas em `screenshots/proposal-1/`, feitas pelo Chrome do próprio stack com
  `tools/dev/demo/screenshots.py`, como o professor `demo.teacher`, em en e pt_BR:
  - bloco nos estados D e A (`block-d-*`, `block-a-*`);
  - página nos estados D (fechada e com os detalhes técnicos abertos), D com 14 atividades
    (`page-many-*`), E, F, G, H e sem resultado (`page-a-*`), em desktop (1280 px);
  - página em D e G em celular (375 px, emulação do Chrome; `*-mobile-*`).
- O rodapé de desempenho do modo de desenvolvedor (do `config.php` compartilhado pelos sites de
  desenvolvimento) foi ocultado só no momento da captura; ele não existe num site de produção.
- Observação fora do plugin: em pt_BR o rótulo de tamanho vem do pacote oficial do core ("Mb", "Kb").
- A aprovação da direção visual será registrada aqui com data, só quando ocorrer.

## Avaliação com professores (T049–T051)

Pendente.

## Runs de CI (T054)

Pendente.

## Constitution Check reavaliado (T055)

Pendente.
