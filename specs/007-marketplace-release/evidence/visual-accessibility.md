# Auditoria visual e de acessibilidade (Feature 007, T014)

Verificações automáticas executadas pelo agente em 2026-10-18 com `tools/dev/demo/checks.py` e
`screenshots.py` (Chrome do Selenium de cada pilha), código 0.7.0, nos sites de demonstração com dados
fictícios. Gaveta de blocos **aberta** para o professor de demonstração (onde o Moodle 5.3 aplica o cinza
que derrubou o contraste no CI). Registros em `logs/t014/<combinação>/`, capturas em
`screenshots/t014/<combinação>/`. **Não substitui** o teste humano com leitor de tela (grupo D,
`human-validations.md`).

## Jornadas

Bloco na página da sala (resultado com sugestão; sala ainda não analisada) e página "Your course in
numbers" nos estados: completo (distribuição, materiais por atividade, arquivos grandes, sugestão),
potencial das tarefas, histórico de um ano, sem resultado, desatualizado e com falha.

## Resultados

| Verificação | 4.5 Boost | 4.5 Classic | 5.3 Boost |
| --- | --- | --- | --- |
| axe-core (WCAG 2.0/2.1/2.2 A e AA), 8 páginas | 0 violação | 0 violação | 0 violação do plugin; 1 do núcleo (abaixo) |
| Teclado, página completa | 16 paradas, todas com indicador de foco; `<summary>` abre com Enter | idem | idem |
| Teclado, histórico | 5 paradas, todas com indicador | idem | idem |
| Teclado, bloco | 1 parada (o link), com indicador | idem | idem (depois da correção do ambiente, abaixo) |
| Zoom 200% (640 px) e 320 px: rolagem horizontal | nenhuma (página, potencial, histórico, bloco) | nenhuma | nenhuma |
| Árvore de acessibilidade | registrada (`tree-*.json`) | registrada | registrada |
| Capturas desktop e celular | `screenshots/t014/45-boost` | desktop; celular não capturado (falha de login da ferramenta no Classic; o layout de 320 px foi verificado acima) | `screenshots/t014/53-boost` |

Itens "incomplete" do axe (não decididos automaticamente): no plugin, só o texto de ajuda da legenda
(`.block_coursecare-overview-help`), porque o fundo se sobrepõe à amostra de cor. Medido no navegador:
`#1d2125` sobre branco, 14 px, contraste 16:1, nas duas versões. Os demais estão em elementos do núcleo
(menus da barra superior, índice do curso).

Violação do núcleo no 5.3 (`axe-page-potential`): contraste de um link de atividade oculta no índice do
curso (`#course-index-cm-35`), fora do plugin.

## Correções desta feature

- Contraste no 5.3 (achado do CI, T006): o Moodle 5.3 pinta de cinza (`gray-600`, `#6a737b`) o texto dos
  blocos na gaveta; sobre o painel verde do bloco (`#eef6ef`) dava 4,38:1. O painel passou a fixar a cor
  de texto do Boost (`#1d2125`, a mesma que o 4.5 já exibia; ≈15:1). Confirmado no CI (Behat com axe) e
  nesta auditoria com a gaveta aberta.

## Ambiente

No 5.3, o tour de usuário "curso, professor" do núcleo estava ativo para o professor de demonstração e
deixava a página `aria-hidden` (nenhuma parada de teclado no bloco). Os tours foram marcados como
concluídos, como no 4.5, e as verificações da página da sala foram refeitas. Não é defeito do plugin.

## Pendências humanas

Teste com leitor de tela (NVDA ou VoiceOver) e aprovação das capturas pelo proprietário do produto
(grupos B e D).
