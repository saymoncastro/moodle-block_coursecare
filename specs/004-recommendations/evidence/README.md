# Evidências da Feature 004 — Recomendações

Registro das evidências de cada cenário (CT) e critério de sucesso (SC) da [spec](../spec.md).
Nenhum item é marcado como atendido sem evidência: teste automatizado verde (com o run de CI) ou
registro manual com data, versão do Moodle e banco de dados. Os arquivos ficam fora do pacote.

Ambiente local dos testes: Moodle 4.5.15, PHP 8.1, PostgreSQL 17 (stack de desenvolvimento).

| Item | Teste ou evidência | Data | Moodle | Banco | Status |
| --- | --- | --- | --- | --- | --- |
| CT-01 | `course_overview_test::test_suggestion_large_materials`; `large_materials_test` | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde (CI [39130298683](#runs-de-ci-t030)) |
| CT-02 | `course_overview_test::test_suggestion_large_materials` (5 e 4 lugares) | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde (CI [39130298683](#runs-de-ci-t030)) |
| CT-03 | `course_overview_test::test_suggestion_large_materials` (oculta ao professor, removida) | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde (CI [39130298683](#runs-de-ci-t030)) |
| CT-04 | `large_materials_test::test_no_suggestion`; `course_overview_test::test_suggestion_privacy`; Behat | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde (CI [39130298683](#runs-de-ci-t030)) |
| CT-05 | `course_overview_test::test_suggestion_states`; `block_coursecare_test::test_block_suggestion`; Behat | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde (CI [39130298683](#runs-de-ci-t030)) |
| CT-06 | `course_overview_test::test_suggestion_large_materials` (truncado); `large_materials_test::test_truncated` | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde (CI [39130298683](#runs-de-ci-t030)) |
| CT-07 | `block_coursecare_test::test_block_suggestion`; Behat | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde (CI [39130298683](#runs-de-ci-t030)) |
| CT-08 | `course_overview_test::test_suggestion_privacy` (en e pt_br); revisão de tom (T027) | 2026-10-18 | 4.5.15 | PostgreSQL 17 | atendido: testes verdes e revisão aprovada pelo proprietário do produto |
| CT-09 | `block_coursecare_test::test_render_reads_no_files` (+1 leitura por índice só com resultado e arquivos) | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde (CI [39130298683](#runs-de-ci-t030)) |
| CT-10 | Behat `@accessibility` (desktop e celular); verificações de T028 (axe, teclado, zoom, 320 px) | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde (CI [39130298683](#runs-de-ci-t030)); leitor de tela pendente |
| CT-11 | Capturas de T028 | | | | pendente (aprovação) |
| CT-12 | `course_overview_test::test_suggestion_large_materials`; revisão de tom (T027) | 2026-10-18 | 4.5.15 | PostgreSQL 17 | atendido: testes verdes e revisão aprovada pelo proprietário do produto |
| CT-13 | Avaliação com professores, junto da 003 (T031) | | | | pendente |
| SC-001 | `test_suggestion_states`, `test_block_suggestion`, Behat | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde (CI [39130298683](#runs-de-ci-t030)) |
| SC-002 | `large_materials_test`, `test_suggestion_large_materials` | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde (CI [39130298683](#runs-de-ci-t030)) |
| SC-003 | `test_suggestion_privacy`, `test_suggestion_large_materials` | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde (CI [39130298683](#runs-de-ci-t030)) |
| SC-004 | Revisão de tom (T027) | 2026-10-18 | — | — | atendido: aprovada pelo proprietário do produto |
| SC-005 | Medições (T026) e investigação | 2026-10-18 | 4.5.15 | PostgreSQL 17 | atendido em condições normais de operação (decisão do proprietário do produto de 2026-10-18): bloco +0,06 ms; página com mediana de 0,69 s. A primeira abertura após limpar todos os caches (> 2 s, também na 0.3.0 e no core) não está aprovada: limitação documentada |
| SC-006 | Behat `@accessibility`; verificações de T028 | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde (CI [39130298683](#runs-de-ci-t030)) |
| SC-007 | Capturas de T028 | | | | pendente (aprovação) |
| SC-008 | CI da branch (T030) | 2026-10-18 | 4.5 | PostgreSQL e MariaDB | atendido: `moodle-plugin-ci`, PHPUnit e Behat em 8.1/pgsql e 8.3/mariadb, `i18n-package` verdes ([run](#runs-de-ci-t030)) |
| SC-009 | Avaliação com professores (T031) | | | | pendente |

## Medições (T026)

Stack descartável `coursecare-b003` da Feature 003 (Moodle 4.5.15, PHP 8.1, PostgreSQL 17.11, modo de
desenvolvedor), com as salas de `tools/dev/demo/setup.php --threshold-mb=1 --activities=200`. Mesmo
método da 003:

1. medição do bloco na 0.3.0;
2. atualização do mesmo site para a 0.4.0 (`e9e4e5d`) com `admin/cli/upgrade.php`;
3. nova medição do bloco e medição da página.

Ferramenta: `tools/dev/bench/render.php`, como administrador. Bloco na sala DEMO-COMPLETE (id 2, com
2 materiais de grande porte, isto é, com a sugestão); página na sala de 200 atividades (id 9). Saídas
em `logs/t026/`.

| Medição | Execução 1 | Execução 2 | Execução 3 | Leituras por chamada |
| --- | --- | --- | --- | --- |
| Bloco 0.3.0 (1.000 renderizações), média ms | 0,820 | 0,880 | 0,810 | 4 |
| Bloco 0.4.0 (1.000 renderizações), média ms | 0,921 | 0,873 | 0,872 | 5 |
| Página 0.4.0 (`--page`, 100 montagens), média ms | 6,618 | 5,946 | 5,997 | 10,13 / 4,05 / 4,05 |

- **Bloco:** cerca de +0,06 ms por renderização (de −0,01 a +0,10 entre execuções), com a leitura a mais por índice
  (`has_large_materials`); limite: +50 ms. **Atendido.**
- **Página no servidor:** cerca de 6 ms, como na 0.3.0 (a seção de sugestões não acrescenta leitura).
- **Página no navegador** (site de demonstração, Chrome do stack, Navigation Timing; 11 aberturas por
  execução, a primeira logo após `purge_caches`; `logs/t026/page-navigation-timing*.json`):

  | Execução | Primeira abertura (cache vazio) | Mediana das 10 seguintes | Máximo das 10 seguintes |
  | --- | --- | --- | --- |
  | 1 | 2,27 s | 0,73 s | 1,11 s |
  | 2 | 2,36 s | 0,88 s | 1,32 s |
  | 3 | 2,73 s | 0,77 s | 1,02 s |

  Referência no mesmo momento: a página do curso do core com as mesmas 200 atividades abriu em
  1,77 s com cache vazio e 0,70 s de mediana (`logs/t026/course-page-navigation-timing.json`). Na
  Feature 003, a mesma página abria em 1,40 s com cache vazio e 0,56 s de mediana.
- **SC-005 da página: não atendido nesta medição.** As aberturas normais ficam bem abaixo de 2 s, mas
  a primeira abertura depois de limpar todos os caches passou de 2 s nas três execuções. A montagem
  no servidor não mudou (cerca de 6 ms) e o ambiente estava mais lento que na 003 (a página do curso
  do core também), mas a diferença de 0,5 a 1 s entre a página e a do curso com cache vazio não foi
  explicada por estas medições. Fica pendente de investigação antes do encerramento.

### Investigação do SC-005 (primeira abertura após limpar os caches), 2026-10-18

**Método** (instrumentação existente, sem ferramenta nova):
- o mesmo script de Navigation Timing, lendo também o rodapé de desempenho do Moodle (`perfdebug`:
  tempo no servidor e leituras no banco) e as requisições de recurso com mais de 150 ms;
- no stack de medição (mesmo site e mesmos dados), três rodadas alternando o código da 0.3.0
  (`c053e3d`) e da 0.4.0 (`d35ddb7`). O número de versão do código foi igualado ao instalado, só no
  stack descartável, para o Moodle executar as duas versões sem atualizar o banco (o esquema é igual);
- em cada medição: `purge_caches`, depois 11 aberturas da página "Sua sala em números" da sala de 200
  atividades; e o mesmo para a página do curso do core dessa sala. Saídas em `logs/sc005/`.

**Resultados** (ms; "servidor" é o tempo medido pelo próprio Moodle):

| Versão | Página | Primeira abertura: total | TTFB | Servidor | Leituras no banco | Aberturas seguintes (mediana): total / TTFB / servidor |
| --- | --- | --- | --- | --- | --- | --- |
| 0.3.0 | Sua sala em números | 2016 / 1518 / 1619 | 916 / 760 / 727 | 266 / 211 / 203 | 659 | 616 / 595 / 104 |
| 0.4.0 | Sua sala em números | 1930 / 2252 / 1439 | 923 / 1096 / 713 | 209 / 344 / 182 | 659 | 687 / 652 / 106 |
| 0.3.0 | Curso (core) | 2061 / 2056 / 1742 | 1031 / 1070 / 821 | 385 / 379 / 335 | 457 | 823 / 775 / 225 |
| 0.4.0 | Curso (core) | 1762 / 2034 / 1792 | 889 / 1039 / 927 | 349 / 359 / 362 | 459 | 760 / 719 / 219 |

**Leitura:**
1. **Sem regressão da 004.** Na mesma máquina e no mesmo site, a 0.4.0 fica na mesma faixa da 0.3.0 na
   primeira abertura (1,44–2,25 s contra 1,52–2,02 s) e nas seguintes. O tempo no servidor e as leituras
   no banco são iguais (659 leituras nas duas versões).
2. **A primeira abertura passa de 2 s também na 0.3.0 e na página do curso do core** (até 2,06 s),
   nas mesmas condições.
3. **O tempo do Moodle é a menor parte.** Na primeira abertura, o servidor mede 0,18–0,34 s, mas o
   primeiro byte chega em 0,71–1,10 s; a diferença (0,5–0,8 s) acontece antes do cronômetro do Moodle
   (inicialização do PHP e leitura dos arquivos num volume montado do Docker Desktop no macOS).
4. **Depois do HTML, o navegador espera por requisições do core que reconstroem caches**: chamadas a
   `lib/ajax/service.php` e `service-nologin.php` (strings e templates do JavaScript do core) e o ícone
   do bloco servido por `theme/image.php`, cada uma com cerca de 0,8–1,3 s na primeira abertura, nas duas
   versões e na página do curso.
5. As medições anteriores de 2,27–2,73 s foram feitas no site de demonstração, com mais containers ativos
   na mesma máquina.

**Classificação: não foi demonstrada regressão da Feature 004 (A).** Os indícios apontam para C
(inicialização geral do Moodle após a limpeza de caches), agravada por B (sobrecarga do ambiente de
desenvolvimento); a causa exata não foi comprovada.

**Menor ação recomendada:** nenhuma mudança de código ou de consultas, porque não há gargalo do plugin
em condições normais.

**Decisão do proprietário do produto (2026-10-18):** nesta entrega, o limite de 2 segundos de SC-005
é avaliado pelas aberturas em condições normais de operação (atendido: mediana de 0,69 s no A/B e de
0,73–0,88 s no site de demonstração; máximo das aberturas normais 1,32 s). A primeira abertura após a
limpeza integral dos caches **não está aprovada**: fica registrada como limitação na spec, com a
causa exata não comprovada. Nenhuma otimização sem evidência de gargalo em condições normais.

## Revisão de tom (T027)

**Aprovada pelo proprietário do produto em 2026-10-18.** Ajustes editoriais pequenos continuam
permitidos, desde que preservem os princípios aprovados (sugestões opcionais, linguagem educativa,
nenhum julgamento, nenhuma promessa de economia, arquivos grandes não tratados como problema, fatos
separados das possibilidades). Ajuste editorial posterior à aprovação, no refinamento do cartão
(2026-10-18): o exemplo da primeira possibilidade virou uma frase própria
(`suggestion:large:option1_example`), exibida abaixo dela, sem mudança de sentido; com isso são 20
strings. As 19 strings novas,
em en e pt_BR, conferidas contra FR-005 e os Princípios I a IV:

- a ação é sempre opcional ("se quiser", "avalie se alguma destas opções faz sentido", "nem todas se
  aplicam"); nenhuma frase usa "deve", "é preciso", prazos ou cobrança;
- a sugestão diz que o tamanho, sozinho, não diz nada sobre a qualidade ou a importância do material;
- nenhuma frase sugere excluir, promete economia de espaço, ganho de desempenho medido ou benefício
  ambiental, nem usa desperdício, excesso ou mau uso (verificado também por
  `course_overview_test::test_suggestion_privacy`, em en e pt_br);
- o fato observado ("O que observamos") traz só números, lugares e data; as possibilidades ficam na
  terceira parte, como opções;
- "podem demorar mais para abrir" é uma explicação geral, registrada como limitação na spec;
- a frase neutra não elogia nem cobra; a frase do bloco não traz número.

| Chave | en | pt_br |
| --- | --- | --- |
| `block:suggestion` | There is a suggestion about the files of your course. | Há uma sugestão sobre os arquivos da sua sala. |
| `suggestion:large:more` | and {$a} more places, listed under Large files. | e mais {$a} lugares, listados em Arquivos de grande porte. |
| `suggestion:large:more_one` | and 1 more place, listed under Large files. | e mais 1 lugar, listado em Arquivos de grande porte. |
| `suggestion:large:observed` | {$a->count} course materials of {$a->threshold} or more, {$a->size} in total, in: | {$a->count} materiais da sala de {$a->threshold} ou mais, somando {$a->size}, em: |
| `suggestion:large:observed_one` | 1 course material of {$a->threshold} or more, {$a->size}, in: | 1 material da sala de {$a->threshold} ou mais, com {$a->size}, em: |
| `suggestion:large:observed_unnamed` | {$a->count} course materials of {$a->threshold} or more, {$a->size} in total, in activities listed under Large files. | {$a->count} materiais da sala de {$a->threshold} ou mais, somando {$a->size}, em atividades listadas em Arquivos de grande porte. |
| `suggestion:large:observed_unnamed_one` | 1 course material of {$a->threshold} or more, {$a->size}, in an activity listed under Large files. | 1 material da sala de {$a->threshold} ou mais, com {$a->size}, numa atividade listada em Arquivos de grande porte. |
| `suggestion:large:option1` | an optimised version of the file that stays readable and accessible; | uma versão otimizada do arquivo, que continue legível e acessível; |
| `suggestion:large:option1_example` | for example, a PDF with images at a resolution suited to the screen, or a video at the resolution it needs. | por exemplo, um PDF com imagens na resolução adequada para a tela ou um vídeo na resolução necessária. |
| `suggestion:large:option2` | splitting long content into shorter parts; | dividir um conteúdo longo em partes menores; |
| `suggestion:large:option3` | a link to where the content is already published. | um link para onde o conteúdo já está publicado. |
| `suggestion:large:title` | Large materials | Materiais de grande porte |
| `suggestion:large:todo` | When you review the activity, consider whether any of these options suits your material. Not all of them apply to every case: | Ao revisar a atividade, avalie se alguma destas opções faz sentido para o seu material. Nem todas se aplicam a todos os casos: |
| `suggestion:large:why` | Large files can take longer to open, especially on slow connections or on a phone. Size alone says nothing about the quality or the importance of a material. | Arquivos grandes podem demorar mais para abrir, sobretudo em conexões lentas ou no celular. O tamanho, sozinho, não diz nada sobre a qualidade ou a importância do material. |
| `suggestion:observed` | What we noticed | O que observamos |
| `suggestion:todo` | What you can do, if you wish | O que você pode fazer, se quiser |
| `suggestion:why` | Why it may deserve attention | Por que pode merecer atenção |
| `suggestions:basedon` | These suggestions use the analysis of {$a}. | Estas sugestões usam a análise de {$a}. |
| `suggestions:heading` | Suggestions for your course | Sugestões para a sua sala |
| `suggestions:none` | There are no suggestions for this course at the moment. | Não há sugestões para esta sala no momento. |

## Validação visual (T028)

**Direção visual aprovada pelo proprietário do produto em 2026-10-18**, com um refinamento pontual do
cartão de sugestão, feito no mesmo dia e com capturas finais **aguardando a confirmação dele**:

- as três partes mantidas; em telas largas, os fatos ("O que observamos") num painel tingido à
  esquerda e o motivo e as possibilidades à direita; em telas pequenas, empilhados, fatos primeiro;
- a quantidade e o volume em destaque (negrito); os lugares numa lista com o tamanho de cada um à
  direita (os mesmos valores da lista "Materiais de grande porte por atividade");
- as possibilidades com espaçamento, e o exemplo da primeira numa linha menor abaixo dela;
- sem funcionalidade, indicador, alerta ou dado novo.

Capturas e verificações refeitas depois do refinamento (as de abaixo):
Site de demonstração atualizado para a 0.4.0 (Moodle 4.5.15, PHP 8.1, PostgreSQL 17), professor de
demonstração, temas Boost e Classic, en e pt_BR.

- **Capturas** em `screenshots/boost/` e `screenshots/classic/` (18 por tema):
  - sala com sugestão (*Biologia celular*: página da sala com o bloco, bloco, página);
  - bloco e página sem sugestão com resultado (*Química geral*);
  - página de resultado antigo sem materiais grandes (*Física*: frase neutra e aviso de desatualização);
  - em celular (375 px): bloco com sugestão, página com e sem sugestão.
- **Verificações** com `tools/dev/demo/checks.py`, resultados em `logs/t028/<tema>-<língua>/`:

| Verificação | Páginas | Boost en | Boost pt_BR | Classic en | Classic pt_BR |
| --- | --- | --- | --- | --- | --- |
| axe-core 4.10, WCAG 2.0/2.1/2.2 A e AA | página com e sem sugestão; sala com sugestão | 0 violações | 0 violações | 0 violações | 0 violações |
| Zoom de 200% e 320 px | página com sugestão | sem rolagem horizontal | idem | idem | idem |
| Teclado | página com sugestão | 12 paradas, todas com indicador; os links da sugestão vêm logo depois de "Voltar à sala" | idem | idem | idem |
| Teclado | sala (bloco) | o link do bloco é alcançado, com indicador | idem | idem | idem |
| Árvore de acessibilidade | página com sugestão | `h3` da seção, `h4` da sugestão, lista de definições com os três termos, links com nome, listas | idem | idem | idem |

- A árvore registrada mostra a estrutura (títulos, termos, links e listas), mas não o texto dos
  parágrafos: a ferramenta só associa nós de elemento, não nós de texto.
- **Leitor de tela: não realizado.** Exige uma pessoa com VoiceOver (macOS/iOS) ou NVDA (Windows);
  não pode ser substituído pelas verificações automáticas. Roteiro único para as Features 003 e 004
  (cerca de 15 minutos, site de demonstração, professor de demonstração, pt_BR e en), a registrar
  nesta seção com o leitor, o navegador, a data e o resultado de cada passo:

  | Passo | Ação | Esperado |
  | --- | --- | --- |
  | 1 | Abrir *Biologia celular*; ir ao bloco pelos marcos ou pelo atalho "Pular AVA Consciente" | O leitor anuncia o título do bloco e a frase de identidade |
  | 2 | Ler o conteúdo do bloco | Rótulo, volume ("8,0 MB"), data e **a frase "Há uma sugestão sobre os arquivos da sua sala."**, sem número nem alerta |
  | 3 | Ativar o link "Conheça melhor os arquivos da sua sala" | Abre a página na seção de sugestões; o foco ou a leitura começa ali ou o título "Sugestões para a sua sala" é alcançável pelo menu de títulos |
  | 4 | Navegar por títulos (h2 → h3 → h4) | Ordem: "Sua sala em números", "Sugestões para a sua sala", "Materiais de grande porte", "Onde estão os arquivos", "Arquivos de grande porte", "Materiais por atividade", "Detalhes técnicos" |
  | 5 | Ler o cartão da sugestão | A lista de definições é anunciada com os três termos ("O que observamos", "Por que pode merecer atenção", "O que você pode fazer, se quiser"), cada um seguido do seu conteúdo |
  | 6 | Percorrer os links da sugestão | Cada link tem o nome completo da atividade; sair e voltar pela lista de links do leitor mostra nomes compreensíveis fora de contexto |
  | 7 | Percorrer com Tab a partir de "Voltar à sala" | Os links da sugestão vêm antes dos da distribuição e das atividades, na ordem visual |
  | 8 | Abrir uma sala sem sugestão (*Química geral*) | O bloco não anuncia sugestão; na página, a frase neutra é lida sob o título da seção |
  | 9 | Itens da 003 (T048) | Estado recolhido/expandido de "Mostrar todas" e "Detalhes técnicos" anunciado; proporções lidas em texto; volta à sala |
- **Pendente para fechar T028:** aprovação das capturas pelo proprietário do produto e o teste com
  leitor de tela.

## Runs de CI (T030)

| Commit | Run | Data | Jobs | Resultado |
| --- | --- | --- | --- | --- |
| `d35ddb7` (US1–US3, Phase 6, correção da revisão de código) | [39130298683](https://github.com/saymoncastro/moodle-block_coursecare/actions/runs/39130298683) | 2026-10-18 | `moodle-plugin-ci` completo, PHPUnit e Behat em PHP 8.1/PostgreSQL e PHP 8.3/MariaDB; `i18n-package` | verde |

Qualquer commit de código posterior precisa de um novo run registrado aqui.

## Avaliação com professores (T031)

Uma única avaliação para as Features 003 e 004, com o protocolo consolidado em
[`specs/003-teacher-experience/evaluation/protocol.md`](../../003-teacher-experience/evaluation/protocol.md):
compreensão dos indicadores e das recomendações, utilidade (T12), ausência de leitura fiscalizatória
(T13), clareza sobre ações opcionais (T9) e navegação (T2, T14). Ambiente pronto: site de demonstração
na 0.4.0, com as salas do roteiro e os tours do Moodle concluídos para o professor de demonstração.
**Não realizada**; nenhum participante ou resultado registrado. Falta recrutar cinco professores,
agendar e conduzir as sessões. Não bloqueia a implementação nem o teste local.

## Constitution Check reavaliado (T032)

Pendente.
