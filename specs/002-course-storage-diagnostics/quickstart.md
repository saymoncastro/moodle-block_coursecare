# Quickstart: validação da Feature 002

Roteiro para provar a feature ponta a ponta num Moodle 4.5 real. O ambiente é o mesmo da Feature 001
([quickstart da 001](../001-plugin-foundation/quickstart.md), seções 1–5: `moodle-docker`,
`MOODLE_405_STABLE`, plugin montado por `local.yml`, dados fictícios). Decisões em
[research.md](research.md); contratos em [contracts/](contracts/); benchmark em
[benchmark.md](benchmark.md). Evidências em `evidence/`.

> Todos os dados são fictícios. Não use dados reais de estudantes.

Atalho usado abaixo (dentro do diretório do `moodle-docker`):

```bash
alias mdc='bin/moodle-docker-compose exec webserver'
```

## 1. Atualização a partir da 0.1.0 (CT-25)

1. Com a 0.1.0 instalada e o bloco em duas salas de demonstração, trocar o checkout do plugin para
   a branch da Feature 002.
2. Atualizar:

```bash
mdc php admin/cli/upgrade.php --non-interactive
```

3. Esperado: sem erros; as tabelas `block_coursecare_*` existem; as duas salas aparecem no bloco como
   "ainda não disponível" (estado A) até a próxima execução da tarefa agendada.

## 2. Primeira análise ao adicionar o bloco (US1, FR-036)

1. Numa sala sem o bloco, adicionar o CourseCare (professor com edição).
2. Esperado na mesma página: "being prepared" (estado B). Nenhuma demora perceptível ao adicionar.
3. Executar as tarefas ad hoc:

```bash
mdc php admin/cli/adhoc_task.php --execute
```

4. Recarregar a sala: volume e data (estado D), ou "no files were found" numa sala vazia (estado G).

## 3. Conferir os números (US2, US6, US7)

Montar a sala de conferência com a tabela de [contracts/indicators.md](contracts/indicators.md):
três recursos com o mesmo arquivo de 10 MB, uma tarefa com dois envios de estudantes, um retorno em
arquivo, um fórum com anexo, um atalho para um arquivo privado do professor, um backup da sala e uma
atividade excluída com a lixeira ativada. Depois:

```bash
mdc php blocks/coursecare/cli/diagnostics.php --run --course=<id>
```

```bash
mdc php blocks/coursecare/cli/diagnostics.php --show --course=<id>
```

Esperado: A, B, categorias, agregado de backup e lixeira e referências iguais aos valores calculados
à mão; nenhum nome de arquivo, atividade ou usuário na saída.

## 4. Estados e atualidade (US5, CT-14, CT-15, CT-23)

- **E**: em Administração do site › Plugins › Blocos › CourseCare, definir o limite de atualidade
  como 1 hora. Envelhecer o resultado de uma sala de teste no banco de desenvolvimento (PostgreSQL do
  `moodle-docker`, prefixo `m_`):

```bash
bin/moodle-docker-compose exec db psql -U moodle -d moodle -c "UPDATE m_block_coursecare_diag SET timecompleted = timecompleted - 7200 WHERE courseid = <id>"
```

  Recarregar a sala. Esperado: valor anterior, data e aviso de possível desatualização.
- **F**: o teste PHPUnit de falha injetada cobre o caminho; manualmente, parar o banco durante um
  `--run` e verificar que o resultado anterior continua exibido com o aviso.
- **Cron parado**: não executar o cron; a página nunca dispara análise (verificar no log de tarefas
  que nada roda ao abrir a página).

## 5. Ciclo de vida (CT-20, CT-26, CT-27, CT-28)

- Remover o bloco de uma sala analisada: as linhas da sala somem de `block_coursecare_diag` e filhas.
- Desativar o CourseCare em Gerenciar blocos e reativar: o diagnóstico continua lá e é exibido com a
  data.
- Excluir uma sala com diagnóstico e com análise na fila: depois de `adhoc_task.php --execute`, sem
  erro e sem dados da sala.
- Sala sem bloco: nunca recebe registro.

## 6. Testes automatizados

```bash
mdc php admin/tool/phpunit/cli/init.php
```

```bash
mdc vendor/bin/phpunit --testsuite block_coursecare_testsuite
```

```bash
mdc php admin/tool/behat/cli/init.php
```

```bash
mdc php admin/tool/behat/cli/run.php --tags=@block_coursecare
```

Esperado: tudo verde, nas duas bases do CI (PostgreSQL e MariaDB).

## 7. Tradução e pacote

```bash
php tools/i18n/check.php
```

```bash
tools/package/build.sh && php tools/package/check.php build/block_coursecare-<release>.zip
```

Esperado: paridade en/pt_br, inclusive das novas chaves; o ZIP contém `db/install.xml`,
`db/upgrade.php`, `db/tasks.php`, `settings.php`, `cli/diagnostics.php` e `classes/**`, e nenhum
item de `tools/` (o gerador de benchmark inclusive).

## 8. Benchmark

Seguir [benchmark.md](benchmark.md) e registrar `evidence/benchmark.md`.
