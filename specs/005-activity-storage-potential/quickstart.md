# Quickstart: validação da Feature 005

Contratos: [potential-calculation.md](contracts/potential-calculation.md), [potential-ui.md](contracts/potential-ui.md).

## §1 Ambiente

Site de demonstração da 003/004, atualizado para a 0.5.0, com `tools/dev/demo/setup.php`, que passa a
criar tarefas de demonstração com configurações variadas (padrão, limite da sala menor, tentativas
ilimitadas, envio em grupo com e sem agrupamento, sem envio de arquivos, número máximo de arquivos zero).

## §2 Cálculo

Para cada tarefa de demonstração, conferir na página a frase das configurações, a origem do limite, as
tentativas, quem pode enviar e o potencial contra o cálculo à mão (tabela de casos do contrato).

## §3 Privacidade e significado

A seção não mostra envios por tarefa, nomes de estudantes ou de arquivos, nem total; a frase do que o
potencial não significa está sempre visível; o trecho recolhido explica o cálculo e as limitações.

## §4 Desempenho

`php blocks/coursecare/tools/dev/bench/render.php --page --course=<sala com 50 tarefas e 500
participantes>` e a abertura no navegador: até 2 s em condições normais.

## §5 Acessibilidade e visual

`tools/dev/demo/checks.py` e `screenshots.py` (en e pt_BR, Boost e Classic, desktop e celular).

## §6 Testes e pacote

```bash
vendor/bin/phpunit --testsuite block_coursecare_testsuite
php vendor/bin/behat --config /var/www/behatdata/behatrun/behat/behat.yml --tags=@block_coursecare --no-interaction
php blocks/coursecare/tools/i18n/check.php
tools/package/build.sh && php tools/package/check.php build/block_coursecare-0.5.0.zip
```
