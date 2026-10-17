# Contract: bloco compacto (nível 1)

Estende [o contrato da Feature 002](../../002-course-storage-diagnostics/contracts/block-ui.md), que
estende [o da Feature 001](../../001-plugin-foundation/contracts/block-ui.md). Continua valendo:
página principal apenas, `block/coursecare:view` no contexto do bloco, ícone decorativo
(`{{#pix}} sprout, block_coursecare {{/pix}}`), frase de identidade, WCAG 2.2 AA, sem dependência de
tema, uma leitura do diagnóstico por renderização e nenhuma consulta a `files`.

## Conteúdo, em ordem

1. Ícone decorativo (broto) e frase de identidade (`tagline`), como na Feature 001.
2. **Sem** a mensagem `intro` ("orientações em breve"): a chave é removida de `lang/en` e de
   `translations/pt_br` (FR-005).
3. A linha do diagnóstico da Feature 002 (mesmas chaves e estados; um número no máximo).
4. Quando há resultado (D, E, F com resultado, G, H): link `overview:link` para
   `blocks/coursecare/overview.php?id=<id da instância>`. Sem resultado: nenhum link.

| Estado | Linha (Feature 002) | Link |
| --- | --- | --- |
| A, F sem resultado | `diagnostic:notavailable` | Não |
| B, C sem resultado | `diagnostic:preparing` | Não |
| D, H com arquivos | `overview:files` (rótulo) + volume em destaque + `diagnostic:calculatedon` | Sim |
| E | D + `diagnostic:mayoutdated` | Sim |
| F com resultado | D + `diagnostic:updatefailed` | Sim |
| G, H sem arquivos | `diagnostic:nofiles` + `diagnostic:calculatedon` | Sim |

- O link é um `<a>` comum, com texto completo do catálogo (sem "clique aqui" e sem ícone sozinho),
  foco visível do tema, alvo de toque com pelo menos 24 × 24 px.
- Nada de categorias, atividades, gráficos, badges, contadores ou cores de alerta (FR-004).
- Visual: pode usar os elementos das diretrizes aprovadas (verdes suaves, cantos arredondados) dentro
  do escopo `.block_coursecare`, sem alterar o cabeçalho nativo do bloco nem as cores do tema.

## Strings

| Chave | en (proposta) | pt_br (proposta) |
| --- | --- | --- |
| `overview:link` | Learn more about the files in your course | Conheça melhor os arquivos da sua sala |

O texto final passa pela revisão de tom e rótulos (SC-008). `intro` é removida das duas línguas.

## Desempenho (SC-004)

O link não exige consulta: a URL usa o id da instância já carregada. O teste da Feature 002
(`test_render_reads_no_files`) continua valendo, com o mesmo número de leituras em todos os estados.
