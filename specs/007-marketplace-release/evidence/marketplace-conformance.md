# Matriz de conformidade com o Moodle Marketplace (Feature 007, T020)

Preenchida em 2026-10-18 a partir de `contracts/marketplace-conformance.md` e das fontes públicas datadas
da spec ("Fontes consultadas"). Tipos: **O** obrigatório, **R** recomendado, **A** apresentação, **P**
procedimento, **H** etapa humana. As diretrizes oficiais de submissão e o guia de listagem (MMPD) exigem
login Atlassian e **não foram lidos**: nada aqui os declara atendidos.

| Requisito | Tipo | Estado | Evidência |
| --- | --- | --- | --- |
| ZIP com nome Frankenstyle (`block_coursecare`, pasta `coursecare/`) | O | Atendido | `package.md` |
| Verificações automáticas (validação do arquivo e plugin CI) | O | Atendido no CI do repositório: phplint, phpmd, phpcs e phpdoc sem aviso, validate, savepoints, mustache, grunt, PHPUnit, Behat em 4.5 e 5.3; "thirdparty": não há biblioteca de terceiros | `ci.md` |
| Revisão manual (chamado no Jira Service Management), correções e reenvio | P | Pendente (depois da submissão) | `docs/marketplace/submission.md` |
| Publicação manual pelo mantenedor | P | Pendente (depois da aprovação) | `docs/marketplace/submission.md` |
| Novas versões enviadas pelo painel | P | Documentado | `docs/marketplace/submission.md`, `CONTRIBUTING.md` |
| Conta de provedor | H | Pendente (proprietário do produto) | — |
| Descrição, documentação, instalação e capturas | A | Materiais prontos; envio pendente | `docs/marketplace/listing.md`, `docs/` |
| Só inglês no pacote; tradução pelo AMOS | R | Atendido; contribuição pt_BR preparada para depois da aprovação | `i18n.md` |
| GPL v3 ou posterior; cabeçalhos | R | Atendido (convenções do núcleo) | `package.md` |
| Direitos sobre tudo o que é submetido | R | **Bloqueio**: titularidade pendente (Q3) | `ownership-report.md` |
| Repositório e rastreador públicos | R | Atendido: GitHub público, Issues abertas | repositório |
| MySQL/MariaDB e PostgreSQL | R | Atendido no CI (PostgreSQL 17, MariaDB 11.4) | `ci.md` |
| Sem avisos com depuração de desenvolvedor | R | Atendido em instalação, atualização, tarefa e desinstalação | `install.md`, `upgrade.md` |
| Privacy API | R | Atendido (`null_provider` auditado) | `security-privacy.md` |
| Política de marcas do Moodle | O | Atendido no texto preparado: nome sem "Moodle"; nenhuma afirmação de produto oficial (conferência humana na submissão) | `docs/marketplace/listing.md` |
| Política de reporte de vulnerabilidades | R | `SECURITY.md` pronto; o reporte privado do GitHub precisa ser **ativado** pelo proprietário | `security-privacy.md` |
| Diretrizes oficiais de submissão e guia de listagem (MMPD) | H | **Conferência humana pendente** (exigem login) | — |
