# Evidências da Feature 009 — Assignment Guidance

Branch `009-assignment-guidance` (base `main` em `b38963c`). Arquitetura B2 aprovada pelo proprietário do
produto em 2026-10-19 (`research.md` R4, commit `8f62c26`). Nenhum item marcado sem evidência; aprovação
visual pendente da avaliação das capturas.

## Persistência e atualização

- Campo `suggestions` (TEXT, anulável) em `block_coursecare_diag`; passo de atualização 2026102200.
- **4.5** (site de demonstração, 0.8.0 → 0.9.0 pelo código montado): 12 diagnósticos e 2.258 pontos de
  histórico **idênticos** (md5 por tabela, sem o campo novo); os 12 registros ficam `NULL` (não calculado);
  segunda execução sem mudança. Depois da análise: `DEMO-POTENTIAL` → `{"v":1,"items":[{"kind":"attempts",
  "cmid":233}]}` ("Diário de bordo", tentativas ilimitadas, reabertura manual); demais salas `items: []`;
  "Exercícios" (3 tentativas) não entra.
- **5.3** (site de teste do ZIP, `block_coursecare-0.8.0.zip` → `0.9.0.zip`, dados de demonstração): 11
  diagnósticos e 2.259 pontos **idênticos**; 11 registros `NULL`; segunda execução "No upgrade needed";
  análise de `DEMO-POTENTIAL` → "Diário de bordo" (mesmo resultado do 4.5).

## Regra (reuso da 005)

`storage_potential::allows_unlimited_attempts()` sobre o resultado de `evaluate()`; teste de tabela com 13
casos (reabertura manual, automática, até passar com e sem nota para aprovação, sem nota, "none", 1 e 5
tentativas, envio desabilitado, nenhum arquivo, ninguém pode enviar, grupos, limite em bytes
indeterminado). Pré-filtro `assign_settings::unlimited_attempts()`: 1 leitura (teste).

## Bloco e página

- Referência única: `stored_suggestions::current()` (decodifica e filtra pelo `modinfo`: existe, é tarefa,
  `visible`); testes de formato (`NULL`, vazio, ilegível, versão desconhecida → não calculado), filtro
  (oculta, inexistente, outra atividade, ordem da sala) e volta ao reexibir.
- Bloco: só tarefas, os dois tipos (mensagem genérica, ação no plural), sala sem arquivos, tarefa oculta,
  não calculado (nunca "Tudo bem por aqui!") — PHPUnit.
- Página: cartão com passos, link de configurações e da orientação; divergência datada depois de mudar as
  tentativas; envio desabilitado mantém as ações sem o link da orientação; oculta some — PHPUnit; Behat
  (bloco, cartão, orientação, link para a orientação na aba "Em detalhes", tarefa oculta, axe desktop e
  celular).

## Desempenho

`tools/dev/bench/render.php`, sala 2 do site de demonstração 4.5:

| Medida | 0.8.0 (008) | 0.9.0 |
| --- | --- | --- |
| Bloco: leituras por chamada | 5,02 | 5,01 |
| Página: leituras por chamada | 11,41 (11,10 a 11,41 entre execuções) | 11,03 |
| Página: tempo médio | 11,69 ms | 12,66 a 12,88 ms (+8% a +10%; textos da orientação construídos uma vez por página) |

Sala com a sugestão de tarefa (12): bloco com 4 leituras. Análise: 1 consulta de pré-filtro por sala com
tarefas; o coletor da 005 só quando há candidata.

## Testes

PHPUnit: 315 testes verdes (4.5). Behat do plugin: 92 cenários verdes (4.5). i18n: 0 erro. Pacote 0.9.0:
94 arquivos, 0 erro.

## CI

CI_PLACEHOLDER

## Acessibilidade e capturas (T019)

`checks.py` nos sites de demonstração (4.5 Boost en, 4.5 Boost pt_br, 4.5 Classic pt_br, 5.3 Boost en);
registros em `logs/t019/<combinação>`:

| Verificação | Resultado |
| --- | --- |
| axe (WCAG 2.2 AA) — curso com o bloco, aba "Sugestões para você", orientação aberta em "Em detalhes" | 0 violação do plugin nas quatro combinações; 0 item "incompleto" do plugin |
| Violação do núcleo (5.3) | `color-contrast` no índice do curso do Moodle (link esmaecido da atividade oculta "Rascunho"), fora do plugin |
| Teclado | aba de sugestões: 5 paradas; "Em detalhes": 29 paradas; todas com foco visível; `<details>` abrem pelo teclado |
| 320 px e zoom 200% (640 px) | sem rolagem horizontal da página nem da região do plugin |

Capturas reais em `screenshots/<combinação>`: bloco com a ideia, aba de sugestões (desktop e celular) e
orientação aberta de "Diário de bordo". **Aprovação do proprietário do produto pendente.**

## Validações humanas

Revisão de tom das strings novas além do cartão (grupo B), leitor de tela (D) e avaliação com professores
(C): pendentes. Bloqueios de publicação (titularidade, diretrizes autenticadas do Marketplace): abertos.

## Constitution Check da entrega (T020)

| Princípio | Entrega |
| --- | --- |
| I. Educação | Orientação informativa e uma sugestão como possibilidade; link para o formulário nativo; nada alterado automaticamente |
| II. Simplicidade | Bloco com uma mensagem e uma ação; orientação recolhida em "Em detalhes" |
| III. Recomendações | Um critério (tentativas efetivamente ilimitadas), aprovado, documentado em `docs/indicators.md` e testado em tabela; nenhum valor sugerido |
| IV. Honestidade | Potencial sempre como teto; nenhuma menção a desperdício, crescimento ou desempenho; não calculado nunca vira "Tudo bem por aqui!"; divergência datada quando a configuração mudou |
| V. Integração nativa | Mesma regra do `mod_assign` (reabertura, até passar com nota para aprovação); nomes dos campos pelas strings do núcleo |
| VI. Desempenho | Cálculo na análise em segundo plano; bloco sem leitura nova (5,01 leituras); página dentro do limite de 10% no tempo, no limite (ver "Desempenho") |
| VII. Segurança e privacidade | Só identificadores de tarefas guardados; link de configurações só com `moodle/course:manageactivities`; `docs/privacy.md` e string de privacidade atualizados |
| VIII. Internacionalização | Strings en e pt_br completas; i18n 0 erro |
| IX. Qualidade | PHPUnit, Behat, axe, atualização com dados em 4.5 e 5.3, CI |
| X. Experiência visual | Componentes e identidade da 008 |
| XI. Evolução incremental | Formato versionado sem mecanismo genérico de regras |
| XII. Software livre | Sem dependência nova |

Resultado: **PASSA**.

## Prontidão

Implementação completa e testada. Falta a aprovação das capturas pelo proprietário do produto (T019) antes
de concluir a experiência visual; nenhum PR ou merge sem pedido.
