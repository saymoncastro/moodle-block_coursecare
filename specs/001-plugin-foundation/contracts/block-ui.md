# Contract: Interface do bloco (estado inicial)

## Estrutura

```text
[cabeçalho nativo do bloco]  pluginname  → "CourseCare" | "AVA Consciente"
[conteúdo — template block_coursecare/content]
  ┌──────┐  tagline  → "Small choices, a better learning space for everyone."
  │ 🌱   │            | "Pequenas escolhas, um AVA melhor para todos."
  │ SVG  │  intro    → "Soon you will find guidance here on using your course resources more mindfully."
  └──────┘            | "Em breve, você encontrará aqui orientações para utilizar os recursos da sua
                         sala de forma mais consciente."
```

## Contexto do template

```json
{ "imageurl": "https://<site>/theme/image.php/<tema>/block_coursecare/<rev>/sprout" }
```

`imageurl` vem de `$OUTPUT->image_url('sprout', 'block_coursecare')`. Os textos usam `{{#str}}`
(`tagline`, `intro`; componente `block_coursecare`). O contexto não contém texto pronto.

## Onde o bloco aparece

- **Página principal da sala** (`course-view-<formato>`): cabeçalho e conteúdo para quem tem
  `:view`. Em modo de edição, também os controles nativos para quem pode editar.
- **Qualquer outra página**, incluindo seção (`course-view-section-*`) e atividades, mesmo que o
  padrão de exibição carregue a instância: nada é renderizado (nem bloco, nem cabeçalho, nem
  controles), em qualquer modo. Ver research R3 e contracts/capabilities.md (invariante 5).

## Regras de marcação e apresentação

1. Um único contêiner com a classe `block_coursecare`, já fornecida pelo core no bloco. Todo o CSS
   próprio fica sob esse seletor.
2. Ativo: `<img src="{{imageurl}}" alt="" width="40" height="40">`. É decorativo, com dimensões
   fixas para evitar mudança de layout.
3. Tagline e intro são parágrafos (`<p>`), não títulos. O título acessível do bloco é o cabeçalho
   nativo.
4. Sem links, botões, formulários, números, porcentagens, badges, barras, gráficos, ícones de alerta
   ou cores de status (FR-013, FR-016).
5. Em larguras de 320 px ou menos, ou no bloco estreito, o ativo e o texto não causam rolagem
   horizontal. O texto quebra normalmente, e o ativo pode ficar acima do texto.
6. Contraste do texto herdado do tema. Nenhuma cor própria é aplicada a texto (FR-015a).
7. Utilitários Bootstrap apenas se forem comuns ao BS4 e ao BS5. Sem `!important` e sem seletores
   fora de `.block_coursecare`.
8. O template traz um bloco de documentação com "Example context (json)" para o `mustache lint`.

## Critérios verificáveis

| Critério | Verificação |
| --- | --- |
| Textos corretos por idioma | Behat (en), PHPUnit R7 (pt_br/fallback), manual com pacote real (pt_br) |
| Ativo decorativo e ignorado por leitor de tela | axe (Behat) + teste manual com leitor de tela |
| WCAG 2.2 AA, parte automatizável | `the "CourseCare" "block" should meet accessibility standards` (axe) |
| WCAG 2.2 AA, parte manual | Teclado (ordem e foco visível), leitor de tela, zoom de 200% e reflow a 320 px, registrados em `evidence/`. O resultado é declarado como verificação dos critérios aplicáveis, não como conformidade integral |
| Ausência fora da página principal | Behat: em modo de edição, na página de seção e em uma atividade, `"CourseCare" "block" should not exist` |
| Mobile e tablet | Behat com `I change viewport size to "mobile"` e `"tablet"` + capturas manuais |
| Temas | Behat em Boost (padrão) e em Classic + capturas manuais |
| Sem indicadores | PHPUnit: o texto visível do HTML gerado (sem marcação) não contém dígitos, e não há `a`, `button`, `form` nem `input` |
