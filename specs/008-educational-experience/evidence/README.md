# Evidências da Feature 008 — Educational Experience

Branch `008-educational-experience` (base `main` em `1b65b64`). Nenhum item é marcado sem evidência;
a aprovação visual depende da avaliação das capturas pelo proprietário do produto. Registrado em
2026-10-19.

## Inventário de preservação

[preservation-before.md](preservation-before.md) × [preservation-after.md](preservation-after.md): na
página, zero seções, números ou rótulos perdidos em en e pt_br; as únicas trocas são as previstas (frase
e link antigos do bloco; frase neutra substituída pela faixa "Tudo bem por aqui!").

## Estados do bloco

Função pura `diagnostic_line::educational()` com teste de tabela (todas as combinações de estado, arquivos,
cobertura parcial e sugestão) e Behat `block_coursecare_states.feature` (idea, all good, neutro
desatualizado, com falha, parcial, ideia desatualizada, sem arquivos, em preparação, axe em desktop e
celular). "Tudo bem por aqui!" nunca aparece com falha, desatualização ou cobertura parcial.

## Abas

`amd/src/tabs.js` (padrão de abas WAI-ARIA, melhoria progressiva) e Behat `block_coursecare_tabs.feature`:
sem JavaScript tudo aparece; com sugestão abre "Sugestões para você" e o foco vai para a seção; sem sugestão
abre "Em detalhes"; clique, setas, Home e End; `tabindex` itinerante; sem resultado não há abas; axe nas
duas abas em desktop e celular. Verificado no Chrome do Selenium (`logs/tabs/`): voltar e avançar do
navegador restauram a aba (`history.json`: abertura em sugestões → clique em detalhes → voltar restaura
sugestões → avançar restaura detalhes); na impressão emulada as duas áreas aparecem e a lista de abas some
(`print.json`). Achado e corrigido: o atributo `hidden` dos painéis era mantido na impressão pelo
`[hidden] { display: none !important }` dos temas; o painel inativo passou a usar uma classe do plugin.

## Acessibilidade e responsividade

`logs/t022/` (`checks.py`): 4.5 Boost, 4.5 Classic e 5.3 Boost, gaveta de blocos aberta. axe sem violação
do plugin em 8 páginas × 3 combinações (a única violação, no 5.3, é do índice do curso do núcleo, a mesma
da 007); todas as paradas de teclado com foco visível; zoom 200% e 320 px sem rolagem horizontal. Itens
"incomplete" do plugin: a nota da faixa (`#1d2125` sobre branco, 16:1, cores fixadas no CSS) e uma linha
de arquivos grandes da 006. Teste com leitor de tela: **pendente** (grupo D).

## Desempenho

[preservation-after.md](preservation-after.md): bloco 0,95 → 0,93 ms (5 leituras); página 10,94 → 11,69 ms
(+6,9%, dentro de 10%), mesmas leituras (11,1–11,4 por variação de cache; nenhuma consulta nova).

## Pacote, i18n e testes

- Pacote 0.8.0: 89 arquivos, `tools/package/check.php` sem erro (lista de permissão aceita `amd/`).
- i18n: 0 erro, 6 avisos esperados; 12 chaves novas em en e pt_br, `block:suggestion` removida.
- PHPUnit: 296 testes verdes (4.5); Behat do plugin: 86 cenários verdes (4.5) depois do ajuste da jornada
  de teclado; aba "Em detalhes" aberta pelo endereço recebe o foco na aba (não mais no painel inteiro, que
  ganhava um contorno preto).
- CI: run 39249839494 falhou (o build AMD não estava versionado porque `.gitignore` ignorava `build/` em
  qualquer pasta; e estilo de uma assinatura de teste); corrigido em `cb5640e`, run 39251480884.

## Capturas e comparação com o mockup

[comparison.md](comparison.md) e `screenshots/approval/` (5.3 en, 4.5 pt_br Boost e Classic; desktop e
celular). **Aprovação do proprietário do produto pendente.** As capturas do Marketplace (`docs/marketplace`)
só são trocadas depois dessa aprovação.

## Ilustração

[illustration.md](illustration.md): original do projeto, GPL, sem elementos de terceiros; titularidade na
mesma situação do restante do projeto (pendente, relatório da 007).

## Validações humanas

- Aprovação das capturas finais e do tom das strings novas (grupo B): pendente.
- Teste com leitor de tela das abas e do bloco (grupo D): pendente.
- Avaliação com professores (grupo C): pendente, pode usar esta versão.

## Constitution Check da entrega (T025)

| Princípio | Entrega |
| --- | --- |
| I. Educação | Estados positivos limitados ao avaliado; sugestão como possibilidade; nenhuma ação automática |
| II. Simplicidade | Bloco com uma mensagem e um indicador; página em duas áreas; detalhes técnicos recolhidos |
| III. Recomendações | Só a sugestão da 004, critério inalterado; sem priorização nem valores |
| IV. Honestidade | Nenhuma promessa; indicadores inalterados (inventário); positivo nunca esconde falha, desatualização ou parcial |
| V. Integração nativa | APIs do núcleo, botões do tema, módulo AMD próprio sem Bootstrap JS, compilado pelo `grunt` do Moodle |
| VI. Desempenho | Nenhuma consulta nova; tempo dentro de 10% |
| VII. Segurança e privacidade | Nenhum dado novo; mesmo controle de acesso |
| VIII. Internacionalização | Strings completas en e pt_br; identidade por string |
| IX. Qualidade | PHPUnit, Behat com e sem JavaScript, axe, CI |
| X. Experiência visual | Identidade aprovada; WCAG 2.2 AA automática; sem mascote |
| XI. Evolução incremental | Itens futuros fora |
| XII. Software livre | Ilustração original GPL, origem documentada |

Resultado: **PASSA**.

## Prontidão

Implementação completa e testada; **pronta para a sua avaliação das capturas**. Não está pronta para merge
antes dessa aprovação e do CI verde.
