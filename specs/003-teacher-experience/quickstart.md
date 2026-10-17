# Quickstart: validação da Feature 003

Guia de validação de ponta a ponta, visual e com professores. Contratos:
[block-ui.md](contracts/block-ui.md), [overview-page.md](contracts/overview-page.md),
[overview-data.md](contracts/overview-data.md).

## §1 Ambiente limpo e reproduzível

1. Ambiente `moodle-docker` conforme o `CONTRIBUTING.md` (Moodle 4.5, PHP 8.1, PostgreSQL; repetir com
   MariaDB quando indicado), com `tools/dev/local.yml.dist` copiado para `local.yml` e
   `COURSECARE_PLUGIN_DIR` apontando para o checkout do branch.
2. Instalação do site e do plugin; pacote oficial pt_br e tradução do projeto instalada com
   `tool_customlang` (só desenvolvimento e homologação, Princípio VIII).
3. `php blocks/coursecare/tools/dev/demo/setup.php` cria as salas fictícias (research R8) e o usuário
   professor; depois `php admin/cli/adhoc_task.php --execute` analisa as salas. Sem dados reais.

**Esperado**: salas nos estados A, B, D, E, F com e sem resultado, G e H; uma sala com mais de 10
atividades com materiais; uma tarefa com envios; materiais e envios de grande porte.

## §2 Bloco compacto

Para cada sala de demonstração, como professor: o bloco mostra a frase de identidade, sem a mensagem
de "orientações em breve", a linha do estado e, só com resultado, o link para a página (CT-01 a
CT-03).

## §3 Página "Sua sala em números"

1. Seguir o link do bloco: abre a página da mesma sala, com o caminho de volta (CT-06).
2. Na sala completa: total, data, quatro categorias com proporção em texto, materiais por atividade
   (10 + "Mostrar todas"), nível da sala, grandes e detalhes técnicos recolhidos (CT-04, CT-07,
   CT-09 a CT-11).
3. Na tarefa com envios: só o volume dos materiais; a atividade só com envios não aparece (CT-14).
4. Estados E, F, G, H e sem resultado conforme o contrato (CT-05).
5. Valores conferidos com `php blocks/coursecare/cli/diagnostics.php --show --course=<id>` (SC-002).

## §4 Acesso

Estudante, professor sem edição, professor de outra sala e visitante abrem o endereço da página:
negação padrão, nenhum número (CT-12). Trocar o `id` por uma instância de outra sala: negação
(CT-13).

## §5 Acessibilidade, celular e temas

- Só teclado: bloco → link → página → "Mostrar todas" → detalhes técnicos → "Voltar à sala", com foco
  visível (CT-16).
- Leitor de tela (VoiceOver ou NVDA): títulos em ordem, estado das seções anunciado, proporções lidas
  em texto.
- Largura de 320 px e celular real ou emulado: uma coluna, sem rolagem horizontal.
- Temas Boost e Classic (CT-19).
- pt_BR com e sem a tradução instalada (CT-18).

## §6 Desempenho

- `php blocks/coursecare/tools/dev/bench/render.php --page --course=<sala com 200 atividades>`:
  média ≤ 2 s (SC-003).
- `render.php --course=<id>` na 0.2.0 e na 0.3.0, mesma sala: diferença ≤ 50 ms (SC-004).

## §7 Reconstrução e validação visual (FR-073, FR-070)

1. A implementação propõe a interface a partir das diretrizes aprovadas (spec) e apresenta capturas
   reais ao proprietário do produto.
2. Capturas finais em `evidence/screenshots/`: bloco e página nos estados D, E, F, G e sem resultado;
   desktop (1280 px) e celular (375 px); en e pt_BR; Boost e Classic.
3. Aprovação registrada em `evidence/README.md`, com data, só quando acontecer.

## §8 Avaliação com cinco professores (FR-071)

1. Roteiro em `evaluation/protocol.md`: tarefas para as seis dimensões (bloco, indicadores,
   navegação, grandes, estados e mensagens, facilidade geral), registro anônimo por perfil
   (familiaridade tecnológica e área).
2. Sessões no site de demonstração, com a interface aprovada na §7.
3. Resultados e dificuldades em `evidence/evaluation/`; problemas críticos (FR-071a) corrigidos e
   reavaliados antes do encerramento; menores registrados como melhorias futuras.

## §9 Testes automatizados e pacote

```bash
vendor/bin/phpunit --testsuite block_coursecare_testsuite
php admin/tool/behat/cli/run.php --tags=@block_coursecare
php blocks/coursecare/tools/i18n/check.php
tools/package/build.sh && php tools/package/check.php build/block_coursecare-0.3.0.zip
```

**Esperado**: tudo verde; o pacote não contém `tools/`, `translations/` nem `specs/`.
