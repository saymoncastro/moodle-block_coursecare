# Evidências da Feature 007 — International Marketplace Release

Branch `007-marketplace-release` (base `main` em `91f1aa0`). Nenhum item é marcado sem evidência.
Versão candidata: **0.7.0 BETA**. Nenhuma tag, release, submissão ou merge foi feito.
Registrado em 2026-10-18.

## Matriz de compatibilidade

Moodle 4.5 LTS (PHP 8.1 e 8.3) e 5.3 LTS (PHP 8.3 e 8.4), PostgreSQL 17 e MariaDB 11.4: CI verde no run
39178520273 (código final `c31bb59`), mais instalação, atualização e desinstalação reais nas duas versões.
`supported` = [405, 503]. Detalhes: [ci.md](ci.md).

## Instalação, atualização e desinstalação

ZIP limpo sem avisos (4.5 e 5.3); atualização 0.6.0 → 0.7.0 com dados com as cinco tabelas idênticas
(4.5 e 5.3); atualização do Moodle 4.5 → 5.3 com dados idênticos; desinstalação sem resíduos (no 5.3 as
instâncias saem pela tarefa ad hoc do núcleo). [install.md](install.md), [upgrade.md](upgrade.md).

## Auditorias de segurança e privacidade

Nenhum achado de código; um único parâmetro (`PARAM_INT`), login e capability no contexto do bloco,
nenhuma escrita na interface, SQL com placeholders. `null_provider` correto; inferência em turmas
pequenas e totais por atividade documentados. Pendente: ativar o reporte privado de vulnerabilidades no
GitHub. [security-privacy.md](security-privacy.md).

## Acessibilidade e auditoria visual

axe sem violação do plugin em 8 páginas × 3 combinações (4.5 Boost, 4.5 Classic, 5.3 Boost), teclado,
zoom 200% e 320 px sem rolagem; contraste do painel corrigido para o 5.3. Teste com leitor de tela
pendente (grupo D). [visual-accessibility.md](visual-accessibility.md).

## Internacionalização

162 chaves em paridade, nenhum texto fixo, plurais, datas e decimais pelo idioma; pacote só em inglês,
pt_BR para o AMOS. [i18n.md](i18n.md).

## Pacote

84 arquivos, sem arquivos de desenvolvimento; `SECURITY.md` retirado do ZIP nesta feature.
[package.md](package.md).

## Matriz de conformidade com o Moodle Marketplace

Cada requisito com evidência ou bloqueio; as diretrizes autenticadas (MMPD) não foram lidas.
[marketplace-conformance.md](marketplace-conformance.md). Materiais: `docs/marketplace/listing.md`,
`docs/marketplace/submission.md`, `docs/marketplace/screenshots/` (6 capturas reais, dados fictícios).

## Validações humanas

13 tarefas de 001–005 nos grupos A–E; grupo A executado; B, C e D pendentes; nenhuma marcada sem
evidência humana. [human-validations.md](human-validations.md).

## Titularidade e licenciamento

TITULARIDADE PENDENTE DE ESCLARECIMENTO: fatos, origem dos arquivos e perguntas para o proprietário do
produto e para o NIT/Agifes e a assessoria jurídica. Nenhum cabeçalho alterado.
[ownership-report.md](ownership-report.md).

## CI

Runs, falhas encontradas e corrigidas e a matriz final: [ci.md](ci.md).

## Documentação (leitura guiada, SC-008)

Instalação, atualização e desinstalação seguidas a partir de `docs/installation.md` e `docs/upgrading.md`
nos sites descartáveis; dois ajustes feitos a partir disso: remover a pasta depois da desinstalação e o
comportamento do 5.3 com as instâncias, e colocar o plugin em `public/blocks` antes de atualizar o
Moodle. Cada seção da página "Your course in numbers" conferida no guia do professor: faltava o painel
"Large files", acrescentado em inglês e em pt_BR. Citações de rótulos dos guias conferidas com as
strings reais. A leitura por um administrador e por um professor reais continua sendo validação humana.

## Matriz de prontidão final

| Área | Estado | Evidência |
| --- | --- | --- |
| Funcionalidade (001–006) | Atendido; nada novo nesta feature | specs 001–006, CI |
| Qualidade de código | Atendido (phpcs, phpdoc, phpmd, mustache, grunt sem aviso) | [ci.md](ci.md) |
| Compatibilidade 4.5 e 5.3 | Atendido | [ci.md](ci.md) |
| Instalação, atualização, desinstalação | Atendido | [install.md](install.md), [upgrade.md](upgrade.md) |
| Segurança | Atendido no código; reporte privado do GitHub a ativar | [security-privacy.md](security-privacy.md) |
| Privacidade | Atendido | [security-privacy.md](security-privacy.md) |
| Acessibilidade automática | Atendido | [visual-accessibility.md](visual-accessibility.md) |
| Acessibilidade humana (leitor de tela) | **Pendente** (D) | [human-validations.md](human-validations.md) |
| Avaliação com professores | **Pendente** (C) | [human-validations.md](human-validations.md) |
| Aprovações de tom e visual | **Pendente** (B) | [human-validations.md](human-validations.md) |
| Internacionalização | Atendido | [i18n.md](i18n.md) |
| Documentação internacional | Atendido (leitura humana pendente) | `docs/`, README |
| Pacote e versão | Atendido (0.7.0 BETA) | [package.md](package.md) |
| Página pública | Materiais prontos; aprovação pendente | `docs/marketplace/` |
| Licença (GPL) | Atendido | [package.md](package.md) |
| Titularidade | **Bloqueio** | [ownership-report.md](ownership-report.md) |
| Diretrizes autenticadas do Marketplace | **Bloqueio** (conferência humana) | [marketplace-conformance.md](marketplace-conformance.md) |
| Publicação | Não iniciada (sem autorização) | — |

## Bloqueios formais para a publicação

1. Titularidade: respostas do proprietário do produto (seção 4) e do NIT/Agifes e da assessoria jurídica
   (seção 5, perguntas 1 a 4) do relatório de titularidade.
2. Leitura das diretrizes oficiais de submissão e do guia de listagem (MMPD, exigem login) e ajuste do que
   divergir.
3. Validações humanas C (cinco professores) e D (leitor de tela) concluídas ou formalmente adiadas pelo
   proprietário do produto, com o motivo registrado (FR-120); aprovações do grupo B.
4. Reporte privado de vulnerabilidades ativado no GitHub (prometido em `SECURITY.md`).
5. Merge da feature em `main` (a documentação e o README apontam para `main`) e autorização expressa
   para submissão, tag e release.

## Recomendação

- **READY FOR REVIEW**: sim. Os critérios técnicos têm evidência (compatibilidade, instalação,
  atualização, desinstalação, segurança, privacidade, acessibilidade automática, i18n, pacote,
  documentação).
- **READY FOR SUBMISSION**: **NOT READY**. Os bloqueios 1 a 4 acima continuam abertos.
- SUBMITTED e PUBLISHED: não se aplicam.

## Constitution Check da entrega (T029)

| Princípio | Entrega |
| --- | --- |
| I. Educação | Documentação e página pública sem promessa de redução, economia, carbono, otimização ou avaliação docente (conferido em `listing.md`) |
| II. Simplicidade | Nenhuma mudança de interface além da cor de texto do painel do bloco |
| III. Recomendações | Inalteradas |
| IV. Honestidade | Compatibilidade declarada só depois do CI verde e da instalação real; itens não lidos ou não feitos registrados como pendentes |
| V. Integração nativa | APIs do núcleo; passo nativo do Behat para o Classic; tarefas e desinstalação pelo núcleo |
| VI. Desempenho | Sem código PHP novo; amostragem igual entre 4.5 e 5.3 |
| VII. Segurança e privacidade | Auditorias sem achado de código; documentação de privacidade completada |
| VIII. Internacionalização | Pacote em inglês; pt_BR para o AMOS; guias em en e pt_BR |
| IX. Qualidade | Quality gates do CI nas quatro combinações declaradas |
| X. Experiência visual | Direção aprovada preservada; correção de contraste mantém a aparência do 4.5 |
| XI. Evolução incremental | Nenhuma funcionalidade nova |
| XII. Software livre | GPL mantida; titularidade em apuração; nenhum cabeçalho alterado |

Resultado: **PASSA**, sem violação.
