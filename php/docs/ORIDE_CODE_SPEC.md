# ORIDE Code — Especificación v1.0

Estado: publicada (2026-10-30). Implementación de referencia (versión PHP): `includes/engine.php` y `includes/taxonomy.php` (pruebas en `tests/run.php`).

## 1. Propósito

ORIDE Code es un sistema de clasificación multidimensional y descriptivo de la utilización de inteligencia artificial durante la creación de obras. Ofrece un lenguaje común, comprensible y técnicamente verificable para comunicar **cómo** intervino la IA en un proceso creativo.

## 2. Principios

1. **Descriptivo, no evaluativo.** No es un porcentaje de autoría, una puntuación de originalidad o mérito, ni una valoración de cumplimiento legal.
2. **Categórico.** Los números son identificadores de categoría. No se suman, no se promedian, no se comparan como magnitudes. A5 no es «más» que A3; C5 no es «más autoría» que C4; D0 no es menos válida que D3.
3. **Por uso.** La unidad de clasificación es el *uso individual* de IA; las síntesis se derivan de los usos sin perder sus relaciones.
4. **Determinista y reproducible.** Mismas respuestas → misma clasificación. Sin modelos generativos.
5. **Sin invenciones.** La falta de información produce «pendiente» o «información insuficiente», nunca un valor arbitrario.
6. **Versionado.** Cada clasificación registra la versión del esquema y las reglas aplicadas; los códigos históricos no cambian.

## 3. Dimensiones y valores

### A — Función de la IA («¿para qué se utilizó?»)
| Código | Valor |
|---|---|
| A0 | No utilización de IA (solo a nivel de declaración) |
| A1 | Consulta, documentación o exploración de información |
| A2 | Generación de ideas, sugerencias o alternativas |
| A3 | Revisión, crítica o evaluación |
| A4 | Corrección, edición o transformación (incluye traducción) |
| A5 | Generación de contenido creativo |

### B — Tipo de aportación incorporada («¿qué hay de la IA en la obra final?»)
| Código | Valor |
|---|---|
| B0 | Ninguna aportación incorporada |
| B1 | Aportación conceptual (ideas, estructuras, conceptos desarrollados por la persona) |
| B2 | Aportación formal (correcciones, reformulaciones, traducciones, estilo) |
| B3 | Aportación de contenido expresivo (texto, imagen, música, sonido, vídeo, código creativo) |

B1, B2 y B3 pueden coexistir (`B1+B3`). No existe «B4 combinada». B0 es incompatible con cualquier otro B en el mismo uso.

### C — Modalidad de intervención humana («¿qué hizo la persona con esa aportación?»)
| Código | Valor |
|---|---|
| C0 | No aplicable (nada incorporado) |
| C1 | Selección o aceptación |
| C2 | Edición puntual |
| C3 | Transformación sustancial (se reconocen elementos del original) |
| C4 | Reelaboración creativa (expresión considerablemente diferente) |
| C5 | Creación propia a partir de referencias |

### D — Documentación y trazabilidad («¿qué información complementaria se aportó?»)
| Código | Valor |
|---|---|
| D0 | Declaración básica |
| D1 | Herramientas y usos identificados |
| D2 | Proceso creativo descrito en detalle |
| D3 | Evidencias complementarias registradas (no auditadas) |

## 4. Relación B–C

B describe *qué* se incorporó; C describe *qué hizo la persona* con ello. Se almacenan como **parejas** (tabla `contributions`) dentro de cada uso. Nunca se empareja el B de un uso con el C de otro, ni se calcula un C global.

Ejemplos de referencia: descartar sugerencias `B0·C0`; trama propuesta y novela escrita por el autor `B1·C5`; erratas aceptadas `B2·C1`; ilustración incorporada sin cambios `B3·C1`; imagen transformada sustancialmente `B3·C3`; texto reelaborado profundamente `B3·C4`.

## 5. Reglas de clasificación (v1.0)

Las reglas se identifican en `rulesApplied`. Catálogo completo en `includes/taxonomy.php` (`ORIDE_RULES`) y en Administración → Taxonomía.

**Derivación de A**
- R-A-01: los valores A del uso son todas las funciones declaradas.
- R-A-02: con varias funciones, si el declarante identifica una principal, el código compacto usa esa.
- R-A-04: varias funciones sin principal → perfil multifunción sin código compacto.
- R-A-03: A0 no es válido en un uso.

**Derivación de B y C** (a partir de respuestas originales)
- «No se incorporó nada» → B0·C0 (R-B-01, R-BC-01).
- «Se incorporó algo» → uno o más tipos B1/B2/B3 (R-B-02); cada tipo una sola vez por uso (R-B-04: si hay resultados heterogéneos, subusos).
- «No puedo precisarlo» → información insuficiente (R-B-05).
- Intervención: «acepté/seleccioné» → C1; «ajustes menores» → C2; «transformé» + «se reconocen elementos del original» → C3, + «expresión considerablemente diferente» → C4 (R-C-03/04); «tomé la idea y realicé yo la expresión» → C5; «no puedo precisarlo» → insuficiente.
- La pregunta aclaratoria de C3/C4 es obligatoria cuando se responde «transformé» (R-C-07).

**Avisos de compatibilidad** (no bloquean; invitan a revisar): W-BC-01 `B3·C5` (¿quizá B1?), W-BC-02 `B2·C4/C5`, W-AB-01 B3 sin A5 ni A4. Las combinaciones no previstas se analizan por sus definiciones; no se prohíben automáticamente.

**Dimensión D** (criterios objetivos, acumulativos; el usuario no puede elegir D)
- D1 (R-D-01): herramienta identificada por su nombre ∧ ≥1 función ∧ descripción de la actividad ≥10 caracteres.
- D2 (R-D-02): D1 ∧ ≥1 fase ∧ resultado ≥10 caracteres ∧ incorporación respondida ∧ descripción de la intervención ≥10 caracteres en cada aportación.
- D3 (R-D-03): D2 ∧ ≥1 evidencia vinculada al uso o a la declaración completa.
- D de la declaración (R-D-MIN): el mínimo de los D de sus usos (criterio conservador).
- Sin IA o uso indeterminado (R-D-NOAI): D0; D2 si hay un relato del proceso ≥100 caracteres; D3 si además hay evidencias. D1 no es aplicable.

## 6. Perfiles

- **Perfil por uso**: funciones A, parejas B–C, D, código compacto si es inequívoco, notación completa, reglas aplicadas.
- **Perfil por componente**: agregación de los usos de un componente (texto, ilustraciones, audio…).
- **Perfil de la obra**: agregación de todos los usos.

Agregación (R-G-05): el **código compacto global** solo se emite si todos los usos tienen una única función, una única pareja B–C completa y comparten esa misma combinación. En otro caso se muestra el **perfil compuesto** con la unión de valores presentes, ordenados por identificador:

```
A2+A4+A5 | B1+B2+B3 | C1+C3+C5 | D2
```

El perfil compuesto es una síntesis de valores presentes, **no** una secuencia de parejas B–C.

## 7. Formatos

| Formato | Ejemplo | Uso |
|---|---|---|
| Código compacto | `A5B3C4D2` (opcionalmente `ORIDE A5B3C4D2`) | Uso o declaración inequívocos. Regex: `^A[0-5]B[0-3]C[0-5]D[0-3]$` |
| Notación de uso | `A2+A4 (principal A2) · B1C5 + B2C1 · D2` | Conserva parejas B–C |
| Perfil compuesto | `A2+A4+A5 \| B1+B2+B3 \| C1+C3+C5 \| D2` | Síntesis de obra o componente |

Compatibilidad sintáctica (`oride_validate_code()`): A0 ⇒ B0∧C0; B0 ⇔ C0; A0 ⇒ D≠D1.

Un código **no identifica** una declaración: muchas obras pueden compartirlo. El identificador documental es `ORD-XXXX-XXXX-XXXX`.

## 8. Gestión de la incertidumbre

| Situación | Resultado |
|---|---|
| Falta una respuesta esencial | «Clasificación pendiente» · no emitible |
| El declarante indica que no puede precisar (incorporación o intervención) | «Información insuficiente» en ese uso · emitible · sin código compacto global |
| No sabe si se usó IA | «Uso de IA no determinado» · emitible · sin código |
| No recuerda la herramienta | Herramienta «no identificada» · clasificable · afecta a D |

## 9. Funciones de la implementación de referencia

`oride_classify_use()`, `oride_classify_declaration()`, `oride_validate_profile()`, `oride_validate_code()`, `oride_explain_code()`, `oride_derive_intervention()` (en `includes/engine.php`).

## 10. Versionado

Cada clasificación almacena: versión del esquema, fecha de cálculo, entradas utilizadas (tabla `profiles`), reglas aplicadas y resultado. La instantánea emitida contiene la sección `classification` completa.

**Procedimiento para publicar una nueva versión (p. ej. v1.1):**
1. Redactar el cambio en este documento (sección nueva, sin modificar la v1.0) y someterlo a revisión.
2. Crear un motor nuevo (por ejemplo `includes/engine-1.1.php`) sin modificar el de v1.0 y seleccionar el motor según la versión del esquema.
3. Añadir pruebas que cubran las diferencias y conservar intactas las pruebas de v1.0.
4. Añadir la nueva versión a la tabla `schemes` (`includes/seed-data.php`).
5. Publicar el esquema; adaptar el asistente si cambian las preguntas. Las declaraciones ya emitidas conservan su versión; los borradores nuevos usan la activa.
6. Marcar la versión anterior como *obsoleta* (sigue siendo interpretable).

## 11. Limitaciones

- La clasificación refleja lo **declarado**; no detecta ni verifica el uso real de IA.
- Los umbrales de D (longitudes mínimas) son criterios objetivos simples, no una valoración de calidad.
- La distinción C3/C4 depende de la percepción del declarante sobre la reconocibilidad del original.
- Las categorías A pueden solaparse en usos complejos; por eso se conservan todas y se admite la función principal opcional.
