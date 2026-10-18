# Quickstart: validação da Feature 004

Guia de validação de ponta a ponta. Contratos: [recommendation-rule.md](contracts/recommendation-rule.md),
[suggestions-ui.md](contracts/suggestions-ui.md).

## §1 Ambiente

O mesmo da Feature 003 (`quickstart.md` da 003, §1): site de demonstração limpo com
`tools/dev/demo/setup.php --threshold-mb=1 --activities=200`, professor de demonstração, pacote pt_br
e tradução do projeto instalada por `tool_customlang` (só desenvolvimento).

**Esperado**: salas com materiais acima de 1 MB (Biologia celular, Química geral), sem materiais
grandes (Filosofia, Artes, Matemática) e nos estados A a H.

## §2 Página

1. Biologia celular: a seção "Sugestões para a sua sala" aparece depois da abertura, com a data e o
   cartão "Materiais de grande porte" com as três partes. Quantidade e volume iguais à soma da lista
   "Materiais de grande porte por atividade"; atividades com link.
2. Sala com materiais grandes em mais de três lugares: três citados e "e mais N lugares".
3. Sala sem materiais grandes, sala sem arquivos e salas E e F: frase neutra ou S1 conforme
   [suggestions-ui.md](contracts/suggestions-ui.md); sala sem resultado: sem seção.
4. Sala com envio grande e nenhum material grande: sem S1.

## §3 Bloco

Com S1: a frase "Há uma sugestão sobre os arquivos da sua sala." e o link que abre a página na seção.
Sem S1: bloco igual ao da 003.

## §4 Acessibilidade, temas e idiomas

`tools/dev/demo/checks.py` (axe, zoom de 200%, 320 px, teclado, árvore de acessibilidade) e
`tools/dev/demo/screenshots.py` na página de Biologia celular e de uma sala sem sugestão, en e pt_BR,
Boost e Classic, desktop e celular. Leitor de tela: passagem humana, como na T048 da 003.

## §5 Desempenho

`php blocks/coursecare/tools/dev/bench/render.php --course=<id>` na 0.3.0 e na 0.4.0 (mesma sala com
materiais grandes): diferença ≤ 50 ms. `render.php --page` na sala de 200 atividades: ≤ 2 s.

## §6 Validação humana

- Revisão de tom de todas as strings novas (FR-050), com aprovação do proprietário do produto.
- Capturas aprovadas (FR-051).
- Avaliação com cinco professores, junto da 003, com as tarefas sobre a sugestão (FR-052).

## §7 Testes automatizados e pacote

```bash
vendor/bin/phpunit --testsuite block_coursecare_testsuite
php admin/tool/behat/cli/run.php --tags=@block_coursecare
php blocks/coursecare/tools/i18n/check.php
tools/package/build.sh && php tools/package/check.php build/block_coursecare-0.4.0.zip
```

**Esperado**: tudo verde.
