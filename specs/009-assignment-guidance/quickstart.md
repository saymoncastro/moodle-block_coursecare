# Quickstart: validar a Feature 009

1. Dados: na sala de demonstração, tarefas com (a) limite próprio e 1 tentativa, (b) limite do padrão do
   site, (c) tentativas ilimitadas e reabertura manual, (d) ilimitadas e "até passar" sem nota para
   aprovação, (e) ilimitadas, envio em grupo, (f) ilimitadas e oculta, (g) ilimitadas sem participantes.
2. Rodar a análise (`cli/diagnostics.php --run --course=ID`).
3. Página, aba "Em detalhes": cada tarefa (a)–(e) tem a orientação recolhida com os números da 005, os
   passos com os nomes do Moodle e, para quem edita, o link; nenhuma tem valor sugerido.
4. Aba "Sugestões para você": só (c) e (e) geram cartão; (d), (f), (g) e (a)/(b) não. Depois, mudar (c)
   para 3 tentativas: o cartão continua com o aviso datado e o bloco continua com a sugestão até a próxima
   análise; ocultar (e): some do bloco e da página na hora; desabilitar o envio de arquivos em (c): cartão
   sem link para a orientação. Sala sem arquivos com uma tarefa como (c): "Há uma ideia" e abas.
5. Bloco: "Há uma ideia para a sua sala" com a mensagem de tarefas (ou a genérica, com materiais grandes),
   sem números; sem sugestões, o estado da 008.
6. Leituras (`tools/dev/bench/render.php`): bloco com as mesmas 5 leituras e página com as mesmas leituras
   da 0.8.0 na mesma sala; na análise, contar as consultas com e sem tarefa candidata.
7. 4.5 e 5.3: os nomes dos campos citados existem no formulário da tarefa; axe, teclado, 320 px, 200%.
8. Capturas reais para a aprovação do proprietário do produto.
