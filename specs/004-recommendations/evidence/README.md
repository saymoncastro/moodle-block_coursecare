# Evidências da Feature 004 — Recomendações

Registro das evidências de cada cenário (CT) e critério de sucesso (SC) da [spec](../spec.md).
Nenhum item é marcado como atendido sem evidência: teste automatizado verde (com o run de CI) ou
registro manual com data, versão do Moodle e banco de dados. Os arquivos ficam fora do pacote.

Ambiente local dos testes: Moodle 4.5.15, PHP 8.1, PostgreSQL 17 (stack de desenvolvimento).

| Item | Teste ou evidência | Data | Moodle | Banco | Status |
| --- | --- | --- | --- | --- | --- |
| CT-01 | `course_overview_test::test_suggestion_large_materials`; `large_materials_test` | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde local; CI pendente |
| CT-02 | `course_overview_test::test_suggestion_large_materials` (5 e 4 lugares) | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde local; CI pendente |
| CT-03 | `course_overview_test::test_suggestion_large_materials` (oculta ao professor, removida) | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde local; CI pendente |
| CT-04 | `large_materials_test::test_no_suggestion`; `course_overview_test::test_suggestion_privacy`; Behat | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde local; CI pendente |
| CT-05 | `course_overview_test::test_suggestion_states`; `block_coursecare_test::test_block_suggestion`; Behat | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde local; CI pendente |
| CT-06 | `course_overview_test::test_suggestion_large_materials` (truncado); `large_materials_test::test_truncated` | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde local; CI pendente |
| CT-07 | `block_coursecare_test::test_block_suggestion`; Behat | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde local; CI pendente |
| CT-08 | `course_overview_test::test_suggestion_privacy` (en e pt_br); revisão de tom (T027) | | | | pendente (aprovação) |
| CT-09 | `block_coursecare_test::test_render_reads_no_files` (+1 leitura por índice só com resultado e arquivos) | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde local; CI pendente |
| CT-10 | Behat `@accessibility` (desktop e celular); verificações de T028 (axe, teclado, zoom, 320 px) | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde local; leitor de tela pendente |
| CT-11 | Capturas de T028 | | | | pendente (aprovação) |
| CT-12 | `course_overview_test::test_suggestion_large_materials`; revisão de tom (T027) | | | | pendente (aprovação) |
| CT-13 | Avaliação com professores, junto da 003 (T031) | | | | pendente |
| SC-001 | `test_suggestion_states`, `test_block_suggestion`, Behat | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde local; CI pendente |
| SC-002 | `large_materials_test`, `test_suggestion_large_materials` | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde local; CI pendente |
| SC-003 | `test_suggestion_privacy`, `test_suggestion_large_materials` | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde local; CI pendente |
| SC-004 | Revisão de tom (T027) | | | | pendente (aprovação) |
| SC-005 | Medições (T026) | 2026-10-18 | 4.5.15 | PostgreSQL 17 | bloco atendido (+0,06 ms); página: aberturas normais < 1,4 s, primeira abertura após limpar caches > 2 s — pendente de investigação |
| SC-006 | Behat `@accessibility`; verificações de T028 | 2026-10-18 | 4.5.15 | PostgreSQL 17 | verde local; CI pendente |
| SC-007 | Capturas de T028 | | | | pendente (aprovação) |
| SC-008 | CI da branch (T030) | | | | pendente |
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

## Revisão de tom (T027)

**Preparada em 2026-10-18; aguardando a aprovação do proprietário do produto.** As 17 strings novas,
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
| `suggestion:large:option1` | an optimised version of the file that stays readable and accessible, such as a PDF with images at a resolution suited to the screen, or a video at the resolution it needs; | uma versão otimizada do arquivo que continue legível e acessível, como um PDF com imagens na resolução adequada para a tela ou um vídeo na resolução necessária; |
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

**Capturas e verificações feitas em 2026-10-18; aguardando a aprovação do proprietário do produto.**
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
- **Leitor de tela: não realizado.** Como na T048 da 003, falta a passagem humana; incluir a seção de
  sugestões no roteiro de 10 minutos da 003 (navegar por títulos até "Sugestões para a sua sala" e
  ouvir os termos da lista de definições).
- **Pendente para fechar T028:** aprovação das capturas pelo proprietário do produto e o teste com
  leitor de tela.

## Runs de CI (T030)

Pendente.

## Avaliação com professores (T031)

Junto da avaliação da Feature 003 (tarefas T11 e T12 do protocolo). Pendente; não bloqueia a
implementação nem o teste local.

## Constitution Check reavaliado (T032)

Pendente.
