# Data Model: International Marketplace Release (Feature 007)

Nenhuma tabela, campo, configuração ou capability nova; nenhum passo de atualização. As entidades são
artefatos de prontidão em `specs/007-marketplace-release/evidence/`.

| Entidade | Campos | Regras |
| --- | --- | --- |
| Item de prontidão | área, item, classificação (`met`, `pending`, `n/a`, `blocking`), evidência ou motivo | `met` só com evidência citável (arquivo, run de CI, captura); `n/a` só com justificativa |
| Requisito do Marketplace | requisito, tipo (obrigatório, recomendado, apresentação, procedimento, humano), fonte (URL e data), situação, evidência | Requisito de fonte autenticada sem leitura: `human check pending` |
| Linha de compatibilidade | Moodle, PHP, banco, verificação (CI run ou execução local), resultado | Versão declarada só com todas as linhas da versão verdes |
| Validação humana | feature, tarefa, grupo (A–E), estado real, evidência | Nunca `done` sem evidência humana |
| Versão candidata | versão, release, maturidade, requires, supported, pacote, verificação do pacote | 0.7.0 BETA; `supported` só versões verificadas |
