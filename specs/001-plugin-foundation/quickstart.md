# Quickstart: ambiente e validação da Feature 001

Roteiro para reproduzir o ambiente Moodle 4.5 e provar a feature ponta a ponta. As decisões estão em
[research.md](research.md) (R5, R7–R11), os contratos em [contracts/](contracts/), e as evidências
são registradas em `evidence/` (CA-13).

> Todos os dados são fictícios. Não use dados reais de estudantes nem infraestrutura institucional.

## 1. Pré-requisitos

- Docker com Docker Compose, git e cerca de 6 GB livres.
- Variável com o caminho absoluto do clone deste repositório: `COURSECARE_PLUGIN_DIR`.

## 2. Subir o Moodle 4.5 (moodle-docker)

```bash
git clone https://github.com/moodlehq/moodle-docker.git
```

```bash
git clone -b MOODLE_405_STABLE --depth 1 https://github.com/moodle/moodle.git moodle
```

```bash
export MOODLE_DOCKER_WWWROOT="$PWD/moodle" MOODLE_DOCKER_DB=pgsql MOODLE_DOCKER_PHP_VERSION=8.1 MOODLE_DOCKER_BROWSER=chrome
```

```bash
cp moodle-docker/config.docker-template.php moodle/config.php
```

```bash
cp "$COURSECARE_PLUGIN_DIR/tools/dev/local.yml.dist" moodle-docker/local.yml
```

O `local.yml` monta o plugin em `/var/www/html/blocks/coursecare` (volume do serviço `webserver`).

```bash
cd moodle-docker && bin/moodle-docker-compose up -d && bin/moodle-docker-wait-for-db
```

```bash
bin/moodle-docker-compose exec webserver php admin/cli/install_database.php --agree-license --fullname="CourseCare dev" --shortname="coursecare_dev" --summary="CourseCare development site" --adminpass="test" --adminemail="admin@example.com"
```

**Esperado**: o site está em http://localhost:8000, e `block_coursecare` aparece instalado em
Administração › Plugins › Blocos, sem erros ou avisos de depuração (CA-01, CA-11).

## 3. Dados de demonstração

```bash
bin/moodle-docker-compose exec webserver php admin/tool/uploadcourse/cli/uploadcourse.php --mode=createnew --file=/var/www/html/blocks/coursecare/tools/dev/demo/courses.csv
```

```bash
bin/moodle-docker-compose exec webserver php admin/tool/uploaduser/cli/uploaduser.php --file=/var/www/html/blocks/coursecare/tools/dev/demo/users.csv
```

**Esperado**: o curso `CC-DEMO` foi criado, com quatro usuários fictícios matriculados (colunas
`course1`/`role1`): `editor` (professor com edição), `assistente` (professor sem edição), `aluno`
(estudante) e `gestor` (gerente). As senhas de teste estão no próprio CSV.

## 4. Português brasileiro (regime de desenvolvimento)

1. Instale o pacote oficial pt_br. O 4.5 não tem CLI para isso. Use Administração › Idioma ›
   Pacotes de idiomas › "Português - Brasil" › Instalar, ou extraia o ZIP oficial de
   download.moodle.org (langpack 4.5) em `/var/www/moodledata/lang/`.
2. Importe a tradução do projeto pela ferramenta nativa:

```bash
bin/moodle-docker-compose exec webserver php admin/tool/customlang/cli/import.php --lang=pt_br --source=/var/www/html/blocks/coursecare/translations/pt_br --checkin
```

```bash
bin/moodle-docker-compose exec webserver php admin/cli/purge_caches.php
```

**Esperado**: o log do importador mostra sucesso para todas as chaves de `block_coursecare` e nenhum
`notice_inexitentstring`. O arquivo `/var/www/moodledata/lang/pt_br_local/block_coursecare.php`
existe, e nenhum arquivo do core foi alterado.

> Exclusivo para desenvolvimento e homologação (constituição 1.1.0, Princípio VIII).

## 5. Validação funcional manual

| # | Passos | Esperado | CA |
| --- | --- | --- | --- |
| V1 | Como `editor`, ativar a edição em `CC-DEMO` e adicionar o bloco "CourseCare" | Bloco adicionado; título, ativo, tagline e intro corretos | CA-02, CA-03 |
| V2 | Com o bloco presente, abrir de novo "Adicionar um bloco" | CourseCare não é oferecido | CA-02 |
| V3 | Abrir uma página de seção e uma atividade do curso, em modo de edição | CourseCare não é oferecido | CA-02 |
| V4 | Entrar como `assistente`, depois `aluno`, depois visitante | Bloco não exibido | CA-04 |
| V5 | Como admin, conceder `block/coursecare:view` a "Professor" no curso; repetir como `assistente` | Bloco exibido | CA-04 |
| V6 | Como admin, remover `block/coursecare:addinstance` de "Professor com edição"; repetir V1 em outro curso | CourseCare não é oferecido | CA-04 |
| V7 | Idioma do `editor` = English | Textos em inglês | CA-05 |
| V8 | Idioma do `editor` = Português - Brasil (após a seção 4) | "AVA Consciente" e textos de referência | CA-06 |
| V9 | Remover a personalização pt_br (`tool_customlang`) e repetir V8 | Textos em inglês, completos | CA-07 |
| V10 | Remover uma única chave (ex.: `intro`) da personalização e repetir V8 | Só a intro em inglês, inteira | CA-07 |
| V11 | Desabilitar o bloco em Administração › Plugins › Blocos e reabilitar | Instâncias somem e voltam | CA-01 |

## 6. Responsividade, temas e acessibilidade

Para cada combinação de {Boost, Classic} × {en, pt_BR}:
- larguras de 1280 px (desktop), 768 px (tablet), 375 px (smartphone) e 320 px (reflow);
- zoom de 200% no desktop;
- navegação apenas por teclado (foco visível, nenhum elemento inalcançável);
- leitor de tela (VoiceOver ou NVDA): o cabeçalho e os dois parágrafos são anunciados, e o ativo não;
- modo de alto contraste do sistema: o texto continua legível, e o ativo pode sumir.

Para alternar o tema:

```bash
bin/moodle-docker-compose exec webserver php admin/cli/cfg.php --name=theme --set=classic
```

**Esperado**: sem rolagem horizontal, sem sobreposição e sem perda de texto. Registrar capturas em
`evidence/` (CA-10, CA-12).

**Compreensão (SC-002)**: mostrar o bloco por até 10 segundos a pelo menos 5 professores e perguntar
"Para que serve este bloco?" e "Alguma análise da sua sala já foi feita?". Registrar as respostas.

## 7. Testes automatizados

```bash
bin/moodle-docker-compose exec webserver php admin/tool/phpunit/cli/init.php
```

```bash
bin/moodle-docker-compose exec webserver vendor/bin/phpunit --testsuite block_coursecare_testsuite
```

```bash
bin/moodle-docker-compose exec webserver php admin/tool/behat/cli/init.php
```

```bash
bin/moodle-docker-compose exec -u www-data webserver php admin/tool/behat/cli/run.php --tags=@block_coursecare
```

Ferramentas de repositório (no host, com PHP 8.1+ e a extensão zip):

```bash
php tools/i18n/tests/run.php && php tools/i18n/check.php
```

**Esperado**: tudo verde. `check.php` retorna `0` sem erros (CA-08).

## 8. Pacote distribuível

```bash
tools/package/build.sh && php tools/package/check.php build/block_coursecare-*.zip
```

```bash
unzip -l build/block_coursecare-*.zip
```

**Esperado**: `check.php` retorna `0`. A listagem mostra só `coursecare/` com os arquivos da
allowlist, sem `translations/`, sem `lang/pt_br` e sem `tools/`, `specs/` ou arquivos ocultos
(CA-09).

**Instalação a partir do pacote**: remova o `local.yml` e recrie os containers. Extraia o ZIP em
`moodle/blocks/` e rode `admin/cli/upgrade.php --non-interactive`. O plugin deve instalar sem erros,
e `tests/translation_test.php` deve ser ignorado por falta de `translations/` (CA-01, CA-09).

## 9. Atualização e desinstalação

**Atualização**: incremente temporariamente `$plugin->version` em `version.php` e rode o comando
abaixo. Depois, desfaça a alteração com `git restore version.php`.

```bash
bin/moodle-docker-compose exec webserver php admin/cli/upgrade.php --non-interactive
```

**Esperado**: atualização sem erros, com instâncias e permissões preservadas.

**Desinstalação**:

```bash
bin/moodle-docker-compose exec webserver php admin/cli/uninstall_plugins.php --plugins=block_coursecare --run
```

**Esperado**: desinstalação concluída, com 0 registros `blockname = 'coursecare'` em
`block_instances` e as capabilities removidas. Se o diretório continuar montado, o próximo
`upgrade.php` reinstala o plugin, o que serve para reinstalar o ambiente.

## 10. Evidências (CA-13)

`evidence/README.md` contém uma tabela CA-01 a CA-13 → evidência (link para captura, log de CI ou
saída de comando), com a data, a versão exata do Moodle (`version.php` do core), o tema, o idioma e
o navegador. Registra também a aprovação do ativo visual e dos textos (FR-014, FR-015).
