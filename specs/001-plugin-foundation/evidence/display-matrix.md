# Matriz de exibição e permissões (T020) — Moodle real

Executada em 2026-10-16T14:57:50.080Z com Playwright (Chromium headless) contra http://localhost:8045 (Moodle 4.5.15, tema Boost, idioma en, 1280×900). Dados fictícios (quickstart §3).

| Cenário | Usuário | Página | Edição | Esperado | Obtido | Resultado |
| --- | --- | --- | --- | --- | --- | --- |
| V1 main page | editor | course main | off | block with content | `{"present":true,"hasContent":true,"hasControls":false}` | ✅ |
| V1 main page | editor | course main | on | block with content and controls | `{"present":true,"hasContent":true,"hasControls":true}` | ✅ |
| V2 duplicate | editor | course main | on | CourseCare not offered (0) | `0` | ✅ |
| V3 section page | editor | section 1 | on | no block, no header | `{"present":false,"hasContent":false,"hasControls":false}` | ✅ |
| V3 section page | editor | section 1 | on | CourseCare not offered (0) | `0` | ✅ |
| V3 section page | editor | section 1 | off | no block | `{"present":false,"hasContent":false,"hasControls":false}` | ✅ |
| V3 activity page (pattern *, subcontexts) | editor | Announcements forum | on | no block, no header | `{"present":false,"hasContent":false,"hasControls":false}` | ✅ |
| V3 activity page (pattern *, subcontexts) | editor | Announcements forum | off | no block | `{"present":false,"hasContent":false,"hasControls":false}` | ✅ |
| V3 control: same instance on main page | editor | course main | off | block with content | `{"present":true,"hasContent":true,"hasControls":false}` | ✅ |
| V4 roles | gestor | course main | off | block with content | `{"present":true,"hasContent":true,"hasControls":false}` | ✅ |
| V4 roles | assistente | course main | off | no block | `{"present":false,"hasContent":false,"hasControls":false}` | ✅ |
| V4 roles | aluno | course main | off | no block | `{"present":false,"hasContent":false,"hasControls":false}` | ✅ |
| V4 visitor | not logged in | course main | off | no block (redirected to login) | `{"present":false,"hasContent":false,"hasControls":false} url=http://localhost:8045/login/index.php` | ✅ |
| V5 view granted to Teacher | assistente | course main | n/a | block with content, no edit mode toggle | `{"present":true,"hasContent":true,"hasControls":false} editModeToggle=0` | ✅ |
| V6 addinstance prohibited | editor | CC-DEMO2 main | on | CourseCare not offered (0) | `0` | ✅ |
| V6 control: override removed | editor | CC-DEMO2 main | on | CourseCare offered (1) | `1` | ✅ |
| V3a format changed to weeks | editor | course main (page-course-view-weeks) | on | same block with content | `{"present":true,"hasContent":true,"hasControls":true}` | ✅ |
| V3a format changed to weeks | editor | course main | on | CourseCare not offered (0) | `0` | ✅ |
| V3a instances in database | n/a | n/a | n/a | 1 instance | `1` | ✅ |

Total: 19/19 conforme o esperado.
