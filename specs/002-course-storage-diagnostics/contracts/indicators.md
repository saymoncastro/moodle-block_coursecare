# Contract: metodologia e algoritmos dos indicadores

Define, para a camada de análise e para os testes, o que cada indicador soma, o que exclui, o que
conta uma vez e o que **não** representa (FR-030). As consultas abaixo mostram a **forma** exigida
(agregação no banco, uma instrução por consulta); o texto final do SQL é da implementação e MUST
usar apenas a DML API e funções portáveis.

## Conjuntos

- **S** (registros da sala): `files` com contexto = contexto do curso ou descendente (R1), exceto
  diretórios (`filename <> '.'`).
- **R** (referências): registros de S com `referencefileid` não nulo.
- **X** (excluídos): registros de S \ R classificados como "excluído" (regras 1–7).
- **BR** (backup e lixeira): registros de S \ R classificados nas regras 8–10.
- **P** (escopo principal): S \ (R ∪ X ∪ BR).
- `cat(f)` ∈ {material, submission, other, unclassified} para f ∈ P; `cmid(f)` = instância do
  contexto de módulo que é o próprio contexto de f ou um ancestral dele; 0 se não houver (nível da
  sala) (FR-019).
- `T` = limite de grande porte em bytes, lido da configuração no início da análise (padrão 50 ×
  1.048.576 = 52.428.800) e gravado no resultado.

## Indicadores

| Indicador | Definição | Não representa |
| --- | --- | --- |
| A. Armazenamento lógico | `Σ filesize(f)`, f ∈ P | Consumo físico, espaço exclusivo, espaço liberável |
| Contagem | `|P|` | — |
| B. Conteúdo único | `Σ_h tamanho(h)` para cada `contenthash` h distinto em P, com `tamanho(h) = MAX(filesize)` dos registros de P com hash h | Espaço exclusivo da sala (o mesmo conteúdo pode estar em outras salas) |
| C. Por categoria | Para cada c: `|P_c|`, `Σ filesize` em P_c, e B restrito a P_c | A soma de B_c pode ser maior que B (conteúdo em mais de uma categoria) |
| D. Grande porte | `L = {f ∈ P : filesize(f) ≥ T}`: `|L|`, `Σ filesize`, hashes distintos em L e B restrito a L | Uso inadequado |
| E. Por atividade | Para cada `cmid` (incluindo 0): `|P_cm|`, `Σ filesize`, `Σ` por categoria e D restrito a P_cm | — |
| F. Atualização | `timecompleted`, `timeattempted`, `lastattemptfailed`, `failurecount`, estado (data-model) | — |
| Referências | Por tipo (R4): interna e externa sincronizada, contagem e `Σ filesize` registrado; indeterminada, só contagem | Armazenamento da sala, cópia local em cache |
| Agregado BR | Para backup e lixeira: contagem, `Σ filesize`, B restrito; e B do conjunto BR (um conteúdo presente nos dois conta uma vez) | Total de backups da sala, consumo adicional, espaço liberável |

**Registros com `status ≠ 0` que não são referências**: contam normalmente; o core usa `status`
para sincronização de referências.

## Consultas (uma sala)

Parâmetros comuns: `:ctxid` (contexto do curso), `:path` (`<caminho do curso>/%`).
`FROM {files} f JOIN {context} ctx ON ctx.id = f.contextid WHERE (ctx.id = :ctxid OR ctx.path LIKE
:path) AND f.filename <> '.'` = **base**.

**Q0 — contextos** (pequeno: atividades + blocos):
`SELECT id, contextlevel, instanceid, path FROM {context} WHERE id = :ctxid OR path LIKE :path`.
Em PHP: mapa `contextid → cmid` (ancestral de nível módulo, pelo caminho).

**Q1 — agregados por área**:
```text
SELECT f.contextid, f.component, f.filearea, <reftype> AS reftype,
       COUNT(1), SUM(f.filesize),
       SUM(CASE WHEN f.filesize >= :t THEN 1 ELSE 0 END),
       SUM(CASE WHEN f.filesize >= :t THEN f.filesize ELSE 0 END)
  <base> LEFT JOIN {files_reference} r ... LEFT JOIN {repository_instances} ri ... LEFT JOIN {repository} rp ...
 GROUP BY f.contextid, f.component, f.filearea, <reftype>
```
`<reftype>` = `none` | `internal` | `external` | `undetermined` (R4; externa com `r.lastsync` nulo
é indeterminada). Linhas ≈ contextos × áreas usadas. Em PHP: classificação de cada grupo, e
acumulação de A, contagem, C (lógico), D (lógico), E, referências e BR (lógico).

**Q2 — únicos por categoria** (inclui `backup` e `recyclebin`):
```text
SELECT cat, COUNT(1), SUM(sz) FROM (
  SELECT cat, contenthash, MAX(filesize) AS sz FROM (
    SELECT <CASE gerado da tabela de regras> AS cat, f.contenthash, f.filesize
      <base> AND f.referencefileid IS NULL
  ) c WHERE cat <> 'excluded' GROUP BY cat, contenthash
) u GROUP BY cat
```

**Q3 — únicos dos conjuntos**: mesma forma, com `grp` = `main` (categorias de P) ou `br` (backup e
lixeira), agrupando por `grp, contenthash` e depois por `grp`. Fornece B, `uniquecount`, e o
conteúdo único do conjunto BR.

**Q4 — grande porte**:
```text
SELECT f.id, f.contextid, f.component, f.filearea, f.contenthash, f.filesize
  <base> AND f.referencefileid IS NULL AND f.filesize >= :t
```
Resultado limitado ao número de arquivos grandes (recordset, fechado ao fim). Em PHP:
classificação; para f ∈ P, D único (geral e por `cmid`, por conjuntos de hashes pequenos) e
ligações das 1.000 maiores da categoria Materiais.

## Invariantes verificados ao fim da análise (R11)

`A = Σ_c A_c`; `B ≤ A`; `B ≤ Σ_c B_c`; `B_c ≤ A_c`; `|L| ≤ |P|`; `D_único ≤ D_lógico`;
`A = Σ_cm A_cm`; para cada categoria, a contagem de conteúdos distintos de Q2 ≤ contagem de
registros de Q1. Falha → refazer Q1–Q4 uma vez → falha de novo → `failurereason = 'inconsistent'`.

## Casos de conteúdo compartilhado (exemplos que viram teste)

| Situação | A | B | C | Observação |
| --- | --- | --- | --- | --- |
| Mesmo conteúdo de 10 MB em 3 recursos (CT-04) | 30 MB | 10 MB | Materiais: A 30, B 10 | — |
| Mesmo conteúdo em material e envio (CT-21) | 20 MB | 10 MB | Materiais B 10; Envios B 10 | Σ B_c = 20 > B = 10, documentado |
| Recurso + atalho interno para ele (CT-32) | 10 MB | 10 MB | — | Atalho: 1 interna, 10 MB registrados, fora de A e B |
| Backup e lixeira com o mesmo conteúdo de 5 MB (CT-30) | não afeta | não afeta | — | BR: backup 5, lixeira 5, único do conjunto 5 |
| Mesmo conteúdo de 60 MB em 2 recursos (CT-34) | 120 MB | 60 MB | — | D: 2 registros, 120 MB; 1 conteúdo, 60 MB |
| Conteúdo de 10 MB nas salas X e Y (CT-05) | 10 MB em cada | 10 MB em cada | — | Nenhuma exclusividade é afirmada |

## Versão da metodologia

`methodversion = 1` nesta feature. Qualquer mudança que altere valores para os mesmos dados (nova
regra de classificação, novo tratamento de referência) incrementa a versão. Resultados com versão
anterior continuam exibíveis, mas a tarefa agendada os trata como devidos para reanálise.
