# Roteiro operacional: avaliação humana das Features 003 e 004

Como preparar o ambiente, conduzir as sessões e registrar os resultados de duas validações humanas
obrigatórias e ainda não realizadas:

1. a avaliação com cinco professores, com o [protocolo consolidado](protocol.md);
2. o teste com leitor de tela, com o roteiro de 9 passos em
   [`specs/004-recommendations/evidence/README.md`](../../004-recommendations/evidence/README.md)
   ("Validação visual (T028)").

Nada aqui é dado como realizado: os resultados só existem depois das sessões.

## 1. Ambiente de demonstração

O site de demonstração roda em Docker neste computador (macOS), no projeto `coursecare-demo`:

- endereço: <http://localhost:8051> (só acessível neste computador);
- Moodle 4.5, tema Boost, plugin na versão 0.4.0 (Features 003 e 004);
- salas fictícias criadas por `tools/dev/demo/setup.php --threshold-mb=1 --activities=200`; nenhum dado
  real;
- conta do professor de demonstração: `demo.teacher` (nome exibido "Professora Demonstração"), com
  os tours do Moodle já concluídos.

O site usa o código desta cópia de trabalho (`.claude/worktrees/coursecare-storage-diagnostics-1d1d38`).
**Durante as sessões, ela deve estar na branch `004-recommendations`** (a 004 contém a 003).

### Ligar, verificar e desligar

```bash
source ~/Projetos/coursecare-devenv/env-demo.sh
cd ~/Projetos/coursecare-devenv/moodle-docker
bin/moodle-docker-compose start
bin/moodle-docker-compose exec -T webserver php -r 'define("CLI_SCRIPT",1); require "config.php"; echo get_config("block_coursecare","version"), "\n";'
```

O segundo comando mostra a versão do plugin instalada (`2026101800` = 0.4.0). Para desligar:
`bin/moodle-docker-compose stop`.

### Senha do professor de demonstração

A senha não fica no repositório. Antes da primeira sessão, o condutor define uma senha só dele:

```bash
bin/moodle-docker-compose exec -T webserver php admin/cli/reset_password.php --username=demo.teacher --password='ESCOLHA-UMA-SENHA' --ignore-password-policy
```

### Idioma da sessão

No menu do usuário (canto superior direito) → Idioma → Português - Brasil (pt_br) ou English (en),
conforme o participante. A tradução do projeto já está instalada no site de demonstração.

### Antes de cada sessão (2 minutos)

1. Entrar como `demo.teacher` e escolher o idioma do participante.
2. Abrir *Biologia celular* uma vez e conferir: o bloco *AVA Consciente* aparece aberto à direita, com
   "Há uma sugestão sobre os arquivos da sua sala.".
3. Deixar o navegador na página inicial do site (Painel).
4. Nenhuma tarefa altera dados; não é preciso restaurar nada entre sessões.

### Participantes a distância

O site só é acessível neste computador. Para participantes a distância, o condutor compartilha a tela
**com controle remoto** (o participante usa o mouse e o teclado), para que as tarefas de navegação
continuem válidas. Se isso não for possível, a sessão deve ser presencial; não substituir por o
condutor clicar no lugar do participante.

## 2. Avaliação com cinco professores

- **Quem conduz:** o proprietário do produto.
- **Participantes:** cinco professores, com pelo menos um de familiaridade tecnológica baixa e um
  de alta, e pelo menos três áreas diferentes.
- **Duração:** 25 a 30 minutos por pessoa; uma pessoa por vez.
- **Roteiro:** [protocol.md](protocol.md), lido exatamente como escrito (14 tarefas e 4 perguntas
  abertas).
- **Consentimento:** explicar que se avalia a interface, não a pessoa; não gravar áudio ou vídeo sem
  autorização; não registrar nome nem dado pessoal além do perfil do protocolo.

### Registro

Para cada participante, copiar [`../evidence/evaluation/TEMPLATE.md`](../evidence/evaluation/TEMPLATE.md)
para `../evidence/evaluation/P<n>.md` (P1 a P5) e preencher durante ou logo após a sessão.

Depois das cinco sessões:

1. Resumir em "Avaliação com professores" de `specs/003-teacher-experience/evidence/README.md`
   (resultado por tarefa e problemas) e, para T8, T9, T12 e T13, também em
   `specs/004-recommendations/evidence/README.md`.
2. Classificar cada problema como crítico ou menor, pelo critério do protocolo.
3. Problemas críticos voltam ao desenvolvimento (T051 da 003); os menores viram melhorias futuras.

## 3. Teste com leitor de tela

- **Quem conduz:** uma pessoa que use leitor de tela, de preferência no dia a dia; pode ser um dos
  cinco professores, se o perfil dele indicar.
- **Leitor e navegador:** VoiceOver com Safari (macOS, ativado por Cmd + F5) ou NVDA com Firefox ou
  Chrome (Windows; neste caso, só se o site for acessado neste computador por controle remoto, o que
  pode interferir no leitor; preferir VoiceOver neste computador).
- **Roteiro:** os 9 passos da tabela "Leitor de tela" em
  `specs/004-recommendations/evidence/README.md`, em pt_BR e, se possível, em en.
- **Registro:** copiar [`../evidence/evaluation/SCREENREADER-TEMPLATE.md`](../evidence/evaluation/SCREENREADER-TEMPLATE.md)
  para `../evidence/evaluation/screenreader.md` e preencher: leitor, versão, navegador, data, resultado
  de cada passo (anunciado como esperado / com dificuldade / não anunciado) e as frases ouvidas quando
  houver problema.
- Um passo não anunciado que impeça entender o número, o estado ou a sugestão, ou chegar ao
  detalhamento, é problema crítico.

## 4. O que fica registrado como concluído

Só o que tiver sido executado:

- T050/T051 da 003, CT-23/SC-011 da 003 e CT-13/SC-009 da 004: depois das cinco sessões registradas e
  sem problema crítico em aberto;
- teste com leitor de tela (T048 da 003, T028 da 004): depois do registro preenchido;
- os Constitution Checks finais (T055 da 003, T032 da 004): depois de todas as validações acima.
