# Contract: matriz de conformidade com o Moodle Marketplace

Tipos: **O** obrigatório (fonte oficial pública), **R** recomendado, **A** apresentação, **P** procedimento
da plataforma, **H** etapa humana. Fonte: as URLs datadas da spec. "Conferência humana pendente" quando o
requisito está nas diretrizes que exigem login.

| Requisito | Tipo | Fonte | Como o plugin atende |
| --- | --- | --- | --- |
| ZIP com nome Frankenstyle único (`block_coursecare`, pasta `coursecare/`) | O | Plugins FAQ | `tools/package/build.sh`, `check.php` |
| Verificações automáticas (validação do arquivo e plugin CI: phplint, phpcs, phpdoc, savepoint, mustache, thirdparty, grunt) | O | Plugins FAQ; Code pre-checks | CI do repositório com os mesmos passos |
| Revisão manual (chamado no Jira Service Management), correções e reenvio | P | Plugins FAQ | Instruções de submissão |
| Publicação manual pelo mantenedor depois da revisão | P | Plugins FAQ | Instruções de submissão |
| Novas versões enviadas manualmente pelo painel (API limitada no lançamento) | P | Plugins FAQ; Marketplace API | Instruções de versão |
| Conta de provedor (entrar com o e-mail da conta moodle.org quando existir) | H | Plugins FAQ | Etapa humana |
| Descrição, documentação, instruções de instalação e capturas na submissão | A | Plugins FAQ | `docs/marketplace/listing.md`, `docs/` |
| Só strings em inglês no pacote; tradução pelo AMOS depois da aprovação | R (legado) | Plugin contribution | pt_br fora do ZIP; contribuição pt_BR preparada |
| Licença GPL v3 ou posterior e boilerplate + `@copyright` em cada arquivo | R (legado) | Checklist; prática relatada | Cabeçalhos em todos os arquivos; LICENSE |
| Direitos sobre tudo o que é submetido | R (legado) | Checklist | **Bloqueio**: titularidade pendente (Q3) |
| Repositório público e rastreador público | R (legado) | Plugin contribution; checklist | GitHub `saymoncastro/moodle-block_coursecare`, GitHub Issues |
| Funcionar em MySQL e PostgreSQL | R (legado) | Checklist | CI PostgreSQL e MariaDB |
| Sem avisos com depuração de desenvolvedor | R (legado) | Checklist | Instalação local com depuração |
| Privacy API implementada | R (prática relatada) | Fonte secundária | `null_provider`, auditado |
| Política de marcas registradas do Moodle | O | Rodapé do Marketplace; checklist | Nome "CourseCare" sem "Moodle"; texto sem sugerir produto oficial |
| Diretrizes oficiais de submissão e guia de listagem (MMPD) | H | Exigem login | **Conferência humana pendente** |
