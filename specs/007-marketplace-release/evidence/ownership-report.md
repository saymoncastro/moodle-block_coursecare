# Relatório de titularidade e licenciamento — CourseCare (block_coursecare)

**Data**: 2026-10-19. **Estado**: TITULARIDADE PENDENTE DE ESCLARECIMENTO (decisão Q3 do proprietário do
produto). Este relatório reúne fatos verificáveis e perguntas; **não é parecer jurídico** e não conclui
titularidade individual, institucional ou conjunta. Nenhum cabeçalho de copyright foi alterado.

## 1. Fatos verificáveis do repositório

| Fato | Valor | Como foi verificado |
| --- | --- | --- |
| Repositório | `github.com/saymoncastro/moodle-block_coursecare`, público, na conta pessoal `saymoncastro` | `gh repo view` |
| Criação | 2026-10-16 (primeiro commit 2026-10-16 09:52 −03:00, "Initialize project with Spec Kit 1.2.0") | `git log --reverse`, API do GitHub |
| Licença declarada | `LICENSE` com o texto da GNU GPL v3 (674 linhas; igual ao `COPYING.txt` do Moodle, exceto o protocolo `https` do endereço da FSF); a API de licenças do GitHub identifica `GPL-3.0` | `diff`, `gh api .../license` |
| Autoria dos commits | 107 commits de "Saymon Castro <saymoncastro@gmail.com>" (e-mail pessoal); 7 commits de merge do GitHub (PRs) | `git log` |
| Assistência de IA | 108 commits declaram "Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>" (assistente de IA usado no desenvolvimento) | mensagens dos commits |
| Linha de copyright | `@copyright 2026 Saymon Castro` em 70 arquivos PHP e Mustache; licença "GNU GPL v3 or later" nos cabeçalhos | `git grep @copyright` |
| Menções institucionais no código | Nenhuma (a busca por "ifes" encontrou só a palavra "manifest") | `git grep -i` |
| Constituição do projeto | Declara o produto "independente de qualquer instituição" e software livre | `.specify/memory/constitution.md` |

## 2. Origem dos arquivos

| Conjunto | Origem | No pacote? |
| --- | --- | --- |
| Código PHP, templates, CSS, strings en (`classes/`, `templates/`, `lang/en`, `db/`, `cli/`, raiz) | Escritos no projeto, a partir das especificações em `specs/`, com assistência de IA declarada nos commits | Sim |
| Tradução pt_br (`translations/pt_br`) | Escrita no projeto | Não (vai ao AMOS depois da aprovação) |
| Testes (`tests/`) | Escritos no projeto | Sim (padrão Moodle) |
| Ferramentas de desenvolvimento (`tools/`) | Escritas no projeto | Não |
| Spec Kit (`.specify/`, `.claude/skills/`) | Ferramenta de terceiros (GitHub Spec Kit 1.2.0), licença a confirmar no projeto de origem (MIT, segundo o repositório público do Spec Kit) | Não |
| Código de terceiros no pacote | **Nenhum** (sem `thirdpartylibs.xml`, sem bibliotecas; o gráfico usa a API de gráficos do próprio Moodle) | — |
| Ambiente (`moodle-docker`, `moodle-plugin-ci`) | Ferramentas de terceiros usadas fora do repositório (GPL) | Não |

## 3. Ativos visuais

| Ativo | Situação |
| --- | --- |
| `pix/sprout.svg` (broto) | Comentário no arquivo: "Original artwork for block_coursecare. GNU GPL v3 or later." Desenhado no projeto (SVG escrito em código). A originalidade em relação a terceiros não pode ser comprovada só pelo repositório |
| Cores e estilos (`styles.css`) | Escritos no projeto |
| Fontes | Nenhuma fonte própria (usa as do tema do Moodle) |
| Capturas de tela | Geradas de sites de demonstração com dados fictícios; não entram no pacote |
| Nome "CourseCare" e "AVA Consciente" | Não verificado em bases de marcas; sem "Moodle" no nome (política de marcas do Moodle) |

## 4. Contexto de desenvolvimento (não verificável pelo repositório)

Perguntas para o proprietário do produto responder com documentos, quando houver:

1. O plugin foi desenvolvido como parte de atribuições do cargo, de projeto, programa, edital ou plano de
   trabalho de alguma instituição (por exemplo, o Ifes)?
2. Foram usados recursos institucionais (equipamentos, servidores, contas, horário de trabalho, bolsas,
   financiamento)?
3. Algum código, especificação, protótipo ou material visual veio de projeto institucional anterior
   (por exemplo, o protótipo "Trabalho Final" citado na Feature 005) ou de terceiros?
4. Há contrato, termo de cessão, norma interna de propriedade intelectual ou acordo com coautores que se
   aplique?
5. O protótipo e a identidade visual tiveram participação de outras pessoas?

## 5. Perguntas às instâncias competentes (por exemplo, NIT/Agifes e assessoria jurídica)

1. Quem é titular dos direitos patrimoniais sobre o código e os ativos do CourseCare, dados os fatos das
   seções 1 a 4?
2. Quais normas institucionais se aplicam a software desenvolvido nessas condições, e é necessária
   autorização formal para distribuição pública?
3. A distribuição pública sob GNU GPL v3 ou posterior (exigência do ecossistema do Moodle) é autorizada?
   Por quem deve ser feita?
4. Qual é a forma correta da linha `@copyright` em cada arquivo (titular individual, institucional ou
   ambos)?
5. Como tratar a assistência de IA declarada nos commits quanto a autoria e direitos?
6. Os nomes "CourseCare" e "AVA Consciente" exigem alguma verificação de marca antes da publicação?

## 6. Bloqueio

**A publicação externa (submissão ao Marketplace, tag e release oficiais) fica bloqueada até as respostas
às perguntas 1 a 4 da seção 5** (e às da seção 4 que se apliquem). Engenharia, documentação e testes
continuam. Se a resposta indicar outro titular, a linha `@copyright` será ajustada nos arquivos por
decisão expressa, numa mudança própria.
