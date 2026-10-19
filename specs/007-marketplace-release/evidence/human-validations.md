# Validações pendentes das features 001–006 (Feature 007, T023)

Estado real em 2026-10-19, conferido nos `tasks.md` de `main` (`91f1aa0`). Grupos: **A** verificação que o
agente executa; **B** aprovação do proprietário do produto; **C** avaliação com professores; **D** teste
humano de acessibilidade; **E** bloqueio formal para publicação. Nenhuma validação humana foi marcada como
feita sem evidência.

| Feature | Tarefa | Conteúdo | Grupo | Estado em 2026-10-19 |
| --- | --- | --- | --- | --- |
| 001 | T045 | Validação visual, responsiva e de acessibilidade completa (quickstart §6) | B, D | Pendente: verificações automáticas existem nas features seguintes; aprovação e leitor de tela não |
| 003 | T047 | Revisão de tom e rótulos (en e pt_br) | B | Pendente de aprovação registrada |
| 003 | T048 | Validação visual com capturas e leitor de tela | B, D | Direção visual aprovada; aprovação das capturas finais e teste com leitor de tela pendentes |
| 003 | T050 | Avaliação com cinco professores (protocolo consolidado 003+004) | C | Não realizada |
| 003 | T051 | Correções críticas da avaliação | C | Depende de T050 |
| 003 | T052 | Quickstart num ambiente limpo, inclusive leitor de tela | A, D | Partes automatizáveis cobertas pela Feature 007 (T007, T010, T011, T014); leitor de tela pendente |
| 003 | T055 | Constitution Check sobre o código | A | **Reavaliação técnica feita** (evidência da 003); fecha depois de T047–T052 |
| 003 | T057 | Registro da decisão de formatação de tamanhos | A, B | Registro já presente nos contratos e no research (R9); confirmação do proprietário do produto pendente |
| 004 | T031 | Avaliação com cinco professores (junto da 003) | C | Não realizada |
| 004 | T032 | Constitution Check sobre o código | A | **Reavaliação técnica feita** (evidência da 004); fecha depois de T031 |
| 005 | T029 | Revisão de tom | B | Pendente de aprovação |
| 005 | T030 | Validação visual (capturas existentes) | B, D | Pendente de aprovação; leitor de tela pendente |
| 005 | T033 | Constitution Check sobre o código | A | **Reavaliação técnica feita** (evidência da 005); fecha depois de T029–T030 |

## Por grupo

- **A (agente, executadas agora)**: reavaliações técnicas dos Constitution Checks de 003, 004 e 005;
  conferência do registro de T057; partes automatizáveis de T052 dentro da Feature 007.
- **B (proprietário do produto)**: T045, T047, T048, T057 (confirmação), T029, T030.
- **C (professores)**: T050, T051 (003) e T031 (004), num protocolo único
  (`specs/003-teacher-experience/evaluation/protocol.md`).
- **D (acessibilidade humana)**: teste com leitor de tela (roteiro único da 003/004), estendido às seções
  de 005 e 006.
- **E (bloqueios formais)**: titularidade pendente (relatório em `ownership-report.md`); conferência das
  diretrizes autenticadas do Marketplace; e, para READY FOR SUBMISSION, C e D concluídos ou formalmente
  adiados pelo proprietário do produto, com o motivo registrado (FR-120).
