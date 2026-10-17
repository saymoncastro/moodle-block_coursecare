# Research: Teacher Experience (Feature 003)

Pesquisa da Fase 0 do [plan.md](plan.md). Fatos de código marcados **[código 4.5]** foram
verificados no Moodle 4.5.15 do ambiente de desenvolvimento.

## R1. Ponto de entrada da página e verificação de acesso

**Decision**: página `blocks/coursecare/overview.php?id=<id da instância do bloco>`.
O script carrega a instância (`block_instances`, `blockname = 'coursecare'`), exige que o contexto pai
seja um contexto de curso diferente do curso do site, carrega o curso, chama
`require_login($course, false)` e `require_capability('block/coursecare:view',
context_block::instance($id))`. A sala exibida é sempre a da instância. Qualquer instância
inexistente, de outro bloco ou fora de um curso termina com a exceção padrão do Moodle, sem dado do
diagnóstico. Página com `set_pagelayout('incourse')`, título e cabeçalho do catálogo de strings, e
trilha de navegação "sala → Sua sala em números".

**Rationale**: a capability do bloco é declarada no contexto do bloco (Feature 001,
`db/access.php`); verificar nesse contexto respeita sobreposições de permissão na instância. O
identificador da instância determina a sala, então não há sala "indicada por parâmetro" sem
verificação (FR-010; Feature 002 FR-041). `require_login` com o curso aplica inscrição, visitantes e
acesso do curso como em qualquer página da sala.

**Alternatives considered**: `?course=<id>` e procurar a instância (ambíguo e sem contexto do bloco
para a capability); página dentro do bloco por `$PAGE->url` com parâmetro (o bloco não tem página
própria na Block API); relatório do curso em `report/` (exigiria outro plugin, fora do escopo).

## R2. Camadas e origem dos dados

**Decision**:
- **Coleta** (`local\collector`): `diagnostic_store` ganha `get_large_materials_by_activity(int
  $diagid)` — agrega as ligações de materiais grandes por `cmid` (quantidade e soma de `filesize`),
  sobre `block_coursecare_large` (índice por `diagid`). Uma nova classe `course_structure` devolve,
  para a sala, cada `cmid` com nome formatado, URL (quando houver) e `uservisible`, a partir de
  `get_fast_modinfo()` (em cache pelo core).
- **Análise** (`local\analysis`, pura): `overview_builder` recebe o registro do diagnóstico, as
  linhas de categoria e de atividade, os grandes de materiais por atividade, a estrutura da sala e o
  estado já resolvido, e devolve a visão da sala pronta para exibição: proporções, regra "menos de
  1%", ordenação por volume de materiais, grupos (nível da sala, outras atividades, removidas após a
  análise), divisão "10 primeiras / restantes" e a regra FR-030 (por atividade, só materiais).
- **Apresentação** (`output`): renderable `course_overview` e templates; `diagnostic_line` passa a
  incluir o link do bloco.

**Rationale**: o Princípio IX exige coleta, análise e apresentação separadas; a regra de privacidade
FR-030 e as proporções ficam numa classe pura, testável sem Moodle populado. Nenhuma leitura de
`files` (FR-040): todos os números vêm das tabelas da Feature 002 **[código 4.5]**:
`block_coursecare_act` tem `materialbytes` por atividade, `block_coursecare_large` tem `cmid` e
`filesize` só de materiais (D10 da Feature 002), e o resumo tem `largelinkstruncated`.

**Alternatives considered**: ler a estrutura direto no script da página (mistura coleta e
apresentação); calcular proporções no template (Mustache não calcula e espalharia a regra FR-030).

## R3. O que é mostrado por atividade (FR-030 e dados persistidos)

**Decision**: por atividade, só `materialbytes` (volume dos materiais) e, dos grandes, a quantidade e
o volume das ligações de materiais daquela atividade. Atividades com `materialbytes = 0` não
aparecem. Proporção = `materialbytes` / volume da categoria Materiais da sala. Ordenação por
`materialbytes`, com desempate pelo nome. Nada mais por atividade.

**Rationale**: `filecount`, `logicalbytes`, `largecount` e `largebytes` por atividade misturam
categorias e revelariam envios por diferença; `submissionbytes`, `otherbytes` e `unclassifiedbytes`
podem conter arquivos de estudantes (envios, anexos de fórum, respostas). A soma de `materialbytes`
de todas as atividades (inclusive `cmid 0`) é igual ao volume da categoria Materiais (invariante da
Feature 002), então as proporções fecham sem dado novo. A quantidade de arquivos de materiais por
atividade não é persistida: fica fora, como limitação (spec, "Limitações reconhecidas").

**Alternatives considered**: mostrar o total da atividade (revela envios); acrescentar contagem por
categoria na Feature 002 (nova coleta, proibida por FR-041).

## R4. Seções recolhíveis e "Mostrar todas"

**Decision**: elemento nativo `<details>`/`<summary>` para os detalhes técnicos (recolhidos por padrão)
e para o restante da lista de atividades ("Mostrar todas"), com o texto do `summary` vindo do
catálogo de strings. Sem JavaScript.

**Rationale**: **[código 4.5]** o Moodle 4.5 não tem componente core de seção recolhível reutilizável
(nenhuma classe `collapsible_section` em `lib/classes/output`). `<details>` é operável por
teclado, anuncia o estado expandido aos leitores de tela, funciona sem JavaScript e é igual em Boost
e Classic. O conteúdo já vem renderizado: "Mostrar todas" não recarrega nada (US2/AC3).

**Alternatives considered**: `collapse` do Bootstrap (depende de JS e de `aria-expanded` mantido à
mão); componente próprio em AMD (mais código, Princípio XI); paginação da lista (exige nova
requisição).

## R5. Identidade visual e compatibilidade com temas

**Decision**: componentes do Moodle (classes `card` do Bootstrap 4.6, presentes em Boost e Classic,
cabeçalhos, listas e `pix_icon`) com estilos do plugin **escopados** em `.block_coursecare` e na
página (`.block_coursecare-overview`), usando propriedades CSS próprias (`--block-coursecare-*`) para
os verdes suaves e neutros, cantos arredondados e o broto. Nenhuma regra fora do escopo do plugin e
nenhuma alteração das variáveis globais do tema. Os tons exatos, a tipografia e a disposição são
definidos na reconstrução visual (FR-073) com contraste mínimo de 4,5:1 para texto e 3:1 para
elementos gráficos necessários (WCAG 2.2 AA).

**Rationale**: as diretrizes aprovadas (spec) pedem cartões discretos e verdes suaves; o Princípio X
pede reutilizar componentes do Moodle; o Princípio V proíbe depender de tema. Propriedades CSS
escopadas deixam a paleta num só lugar para a validação visual.

**Alternatives considered**: SCSS do tema (dependência de tema); cores inline nos templates (sem
ponto único e sem revisão de contraste); biblioteca de gráficos (dependência e peso sem necessidade).

## R6. Proporções e barras

**Decision**: cada proporção é texto ("38%", "menos de 1%") e, ao lado, uma barra decorativa
(`aria-hidden="true"`) cuja largura é a mesma proporção. Arredondamento ao inteiro mais próximo;
valores positivos abaixo de 0,5% viram "menos de 1%", nunca "0%"; zero só para categoria vazia de um
resultado concluído. A soma em bytes das categorias é igual ao total (dado da Feature 002); as
porcentagens arredondadas podem não somar 100, e nenhum texto afirma que somam.

**Rationale**: informação não transmitida só por forma ou cor (FR-016); a barra só reforça. Sem
JavaScript e sem biblioteca.

**Alternatives considered**: gráfico de pizza (difícil de ler e de tornar acessível); só texto
(perde a leitura rápida que as diretrizes visuais pedem).

## R7. Bloco compacto

**Decision**: o template do bloco mantém o broto, o nome (cabeçalho nativo) e a frase de identidade
(`tagline`), remove a mensagem `intro` ("orientações em breve"; a chave sai de `lang/en` e de
`translations/pt_br`) e mostra a linha do diagnóstico da Feature 002 seguida, quando há resultado, do
link "Conheça melhor os arquivos da sua sala" para `overview.php?id=<instância>`. Mesma única leitura
do diagnóstico da 0.2.0 (o link não exige consulta).

**Rationale**: FR-001 a FR-005; o link é a "ação para mais informações" do nível 1 sem acrescentar
números.

**Alternatives considered**: botão (mais peso visual, Princípio II); link em todos os estados (levaria
a uma página vazia em A, B e C).

## R8. Ambiente de validação, desempenho e avaliação com professores

**Decision**:
- `tools/dev/demo/setup.php` (fora do pacote) cria, num site descartável e limpo, salas fictícias
  com arquivos reais pela File API em cada estado (D, E, F com e sem resultado, G, H, A, B), uma sala
  com mais de 10 atividades com materiais, materiais e envios de grande porte (limite reduzido) e uma
  sala com mais de 1.000 materiais grandes simulada pelos dados do diagnóstico. Usado na validação
  visual, na avaliação com professores e no quickstart. Sem dados pessoais reais.
- `tools/dev/bench/render.php` ganha `--page` para medir a página (SC-003: 200 atividades com
  materiais, até 2 s) e continua medindo o bloco (SC-004: até +50 ms em relação à 0.2.0).
- A avaliação com cinco professores segue um roteiro em `evaluation/protocol.md` (tarefas para as
  seis dimensões de FR-071, registro anônimo por perfil, classificação crítico/menor de FR-071a), com
  os resultados em `evidence/`.

**Rationale**: a validação visual e a avaliação precisam de uma interface real e reproduzível
(FR-070, FR-071, FR-073); os ambientes da Feature 002 foram descartados de propósito.

**Alternatives considered**: reaproveitar o gerador de benchmark (cria dados sem conteúdo real,
inadequados a uma avaliação com pessoas); capturas sobre maquetes estáticas (não valem como
evidência, FR-070).
