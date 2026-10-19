# Atualização (Feature 007, T010)

Executado em 2026-10-18 pelo agente, nos sites descartáveis de `install.md`, com depuração de
desenvolvedor. Pacotes: `block_coursecare-0.6.0.zip` (de `origin/main`, `91f1aa0`) e
`block_coursecare-0.7.0.zip` (`aa6bcc4`). Dados: ferramenta de demonstração (12 salas, cada estado do
diagnóstico, arquivos reais pela File API, histórico). A 0.7.0 não muda o banco (último savepoint
`2026101900`, da 0.6.0); a atualização só registra a versão `2026102000`.

## 0.6.0 → 0.7.0 com dados

| Tabela | 4.5 antes | 4.5 depois | 5.3 antes | 5.3 depois |
| --- | --- | --- | --- | --- |
| `block_coursecare_diag` | 11 linhas, md5 `40ca4c319703` | igual | 11 linhas, md5 `0ed48b6a05f3` | igual |
| `block_coursecare_cat` | 10, `d70fb31fe828` | igual | 10, `d70fb31fe828` | igual |
| `block_coursecare_act` | 29, `06c91216025a` | igual | 29, `06c91216025a` | igual |
| `block_coursecare_large` | 2, `fb6000ea1e55` | igual | 2, `fb6000ea1e55` | igual |
| `block_coursecare_hist` | 2253, `647e989b5667` | igual | 2253, `b1e0eeb8e9b6` | igual |

(Os md5 diferem entre 4.5 e 5.3 porque cada site guarda as datas da sua própria demonstração.)

- Upgrade: `++ 2026102000: Success ++`, saída 0, sem aviso, nas duas versões.
- Configurações, tarefa, capabilities e as 12 instâncias do bloco iguais antes e depois.
- Segunda execução: "No upgrade needed", nas duas versões.
- No 5.3 a 0.6.0 instala pelo CLI apesar de declarar só 4.5 (`supported` é declaração, não bloqueio);
  por isso o caminho 0.6.0 → 0.7.0 também foi verificado no 5.3.

## Atualização do Moodle 4.5 → 5.3 com o plugin e dados

Site 4.5 com a 0.7.0 e os dados acima → código do Moodle 5.3 com a 0.7.0 em `public/blocks/coursecare`,
mesmo banco e mesmo moodledata → `admin/cli/upgrade.php --non-interactive`.

- Upgrade do núcleo "from 4.5.15 (2024100715) to 5.3+ (2026100500.02) completed successfully", saída 0;
  nenhuma linha de aviso ligada ao plugin.
- As cinco tabelas do plugin, configurações, tarefa, capabilities e instâncias **idênticas** antes e depois.
- Depois: `--run --course=2` concluiu (0,012 s); a tarefa agendada rodou e enfileirou 2 salas; segunda
  execução do upgrade: "No upgrade needed".
