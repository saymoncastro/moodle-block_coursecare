# Quickstart: validar a Feature 009

1. Dados: na sala de demonstração, tarefas com (a) limite próprio e 1 tentativa, (b) limite do padrão do
   site, (c) tentativas ilimitadas e reabertura manual, (d) ilimitadas e "até passar" sem nota para
   aprovação, (e) ilimitadas, envio em grupo, (f) ilimitadas e oculta, (g) ilimitadas sem participantes.
2. Rodar a análise (`cli/diagnostics.php --run --course=ID`).
3. Página, aba "Em detalhes": cada tarefa (a)–(e) tem a orientação recolhida com os números da 005, os
   passos com os nomes do Moodle e, para quem edita, o link; nenhuma tem valor sugerido.
4. Aba "Sugestões para você": só (c) e (e) geram cartão; (d), (f), (g) e (a)/(b) não.
5. Bloco: "Há uma ideia para a sua sala" com a mensagem de tarefas (ou a genérica, com materiais grandes),
   sem números; sem sugestões, o estado da 008.
6. Leituras: bloco com 5 leituras (`render.php`); página +1.
7. 4.5 e 5.3: os nomes dos campos citados existem no formulário da tarefa; axe, teclado, 320 px, 200%.
8. Capturas reais para a aprovação do proprietário do produto.
