# Quickstart: validar a Feature 008

Pré-requisitos: pilhas `moodle-docker` da 007 (4.5 com o repositório montado; 5.3 em `public/blocks`),
dados de demonstração (`tools/dev/demo/setup.php`), Node 22 para compilar o módulo AMD.

1. **Compilar**: `grunt amd` do Moodle com o plugin em `blocks/coursecare` (contêiner `node:22`); o build
   em `amd/build/` precisa ficar igual ao gerado (o CI confere).
2. **Testes**: PHPUnit do plugin e Behat (`@block_coursecare`) em 4.5; CI nas quatro combinações.
3. **Bloco**: nas salas de demonstração, conferir o estado educativo de cada sala contra
   `contracts/ui.md` (DEMO-COMPLETE com sugestão → `idea`; DEMO-NOFILES → `nofiles`; DEMO-OUTDATED,
   DEMO-FAILED, DEMO-PARTIAL → notas, nunca "Tudo bem por aqui!"; uma sala completa sem materiais grandes →
   `allgood`; DEMO-NEW → `notavailable`; DEMO-QUEUED → `preparing`).
4. **Página**: aba inicial com e sem sugestão; setas, Home, End; `#block_coursecare-history` abre "Em
   detalhes" e foca o histórico; voltar/avançar; sem JavaScript tudo aparece; impressão mostra tudo.
5. **Acessibilidade e responsividade**: `tools/dev/demo/checks.py` (axe, teclado, zoom 200%, 320 px) em
   4.5 Boost, 4.5 Classic e 5.3 Boost, gaveta de blocos aberta e tours concluídos.
6. **Preservação**: inventário seção a seção da página 0.7.0 × 0.8.0 na sala DEMO-COMPLETE (mesmos
   números); `tools/dev/bench/render.php` para bloco e página (leituras iguais, tempo dentro de 10%).
7. **Capturas**: en e pt_BR, 4.5 e 5.3, desktop e celular, lado a lado com o mockup aprovado → aprovação
   do proprietário do produto.
