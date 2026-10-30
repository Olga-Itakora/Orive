# Relación del cuestionario con el marco normativo

> **Documento preparado para revisión jurídica profesional.** No constituye asesoramiento jurídico. ORIVA **no** satisface automáticamente las obligaciones del Reglamento (UE) 2024/1689 ni certifica su cumplimiento.

## Fuentes y estado de verificación (30/10/2026)

| Fuente | Estado |
|---|---|
| Reglamento (UE) 2024/1689 (DO L, 12.7.2024), arts. 3, 50, 113 | Texto original conocido. **Contrastar con la versión consolidada vigente** en EUR-Lex (no accesible desde el entorno de desarrollo). |
| Reglamento (UE) 2026/1744 («Digital Omnibus on AI») | Solo fuentes secundarias: publicado en el DOUE el 24/7/2026, en vigor el 27/7/2026; aplaza obligaciones de alto riesgo; periodo transitorio del art. 50.2 hasta el 2/12/2026 para sistemas ya comercializados; corrección de errores en septiembre de 2026. **Verificar en el DOUE.** |
| Directrices de la Comisión sobre el art. 50 | Fuentes secundarias indican publicación en julio de 2026. **No consultadas directamente.** |
| Código de prácticas sobre transparencia del contenido generado por IA | Versión final publicada en junio de 2026 según fuentes secundarias. Adhesión voluntaria. |
| AI Act Service Desk (art. 50) y FAQ de la Comisión | Enlaces incluidos; no consultados desde el entorno. |

## Distinción de naturalezas

| Naturaleza | Ejemplos | Tratamiento en ORIVA |
|---|---|---|
| Obligación legal específica | Art. 50.4 (ultrasuplantaciones; texto de interés público) para responsables del despliegue; art. 50.2 para proveedores | Orientación separada del código, con referencias. Nunca certificación. |
| Requisito de terceros | Editoriales, concursos, revistas, instituciones | Mencionado en avisos; ORIVA ofrece un formato para cumplirlos voluntariamente. |
| Obligación contractual o sectorial | Contratos de edición o encargo | Fuera del alcance; se remite a revisión profesional. |
| Buena práctica voluntaria | Declarar el uso de IA en una novela | Ámbito principal de ORIVA. |

## Preguntas del cuestionario

| Pregunta | Finalidad | Naturaleza | Referencias | Supuestos | Limitaciones |
|---|---|---|---|---|---|
| Identificación de la obra | Identificar el objeto declarado | Voluntaria (necesaria para emitir) | — | Todos | ORIVA no verifica los datos de la obra. |
| Declarante, rol y alcance | Responsabilidad y cobertura | Voluntaria (necesaria) | — | Obras individuales y colectivas | No acredita identidad. En obras colectivas no se atribuye a otros participantes. |
| ¿Se usó IA? (sí/no/incierto/solo mi contribución) | Base de la clasificación | Voluntaria | — | Todos | Se registra la incertidumbre sin inferencias. |
| Herramientas | Trazabilidad (D) | Voluntaria | Art. 3.3 (proveedor) a título informativo | Usos con IA | No se exige recordar modelo o versión. |
| Usos: función, incorporación, intervención | ORIVA Code (A, B, C) | Voluntaria | — | Usos con IA | Clasificación descriptiva; no determina autoría ni protección por derechos de autor. |
| Evidencias | Documentación (D3) | Voluntaria | RGPD (minimización) | Opcional | No auditadas; privadas por defecto. |
| Papel respecto del sistema (propia autoridad / organización / proveedor) | Orientar sobre quién podría ser responsable del despliegue o proveedor | Orientativa | Art. 3.3, art. 3.4 | Art. 50 | No toda persona que usa una herramienta es responsable del despliegue; la organización puede serlo. |
| Contexto profesional / personal no profesional | Exclusión del art. 3.4 | Orientativa | Art. 3.4 | Art. 50.4 | La calificación concreta requiere análisis. |
| ¿Imagen, audio o vídeo sintéticos publicados? | Detectar posible art. 50.4 párr. 1 | Orientativa | Art. 50.4 | Contenido audiovisual | No toda imagen generada es una ultrasuplantación. |
| ¿Se asemeja a personas, lugares o sucesos reales y puede parecer auténtico? | Definición de ultrasuplantación | Orientativa | Art. 3.60 | Contenido audiovisual sintético | Juicio del declarante; puede requerir análisis. |
| ¿Obra manifiestamente artística, creativa, satírica o de ficción? | Régimen limitado del art. 50.4 | Orientativa | Art. 50.4 (último inciso del párr. 1) | Ultrasuplantaciones | La obligación se limita a revelar de forma que no dificulte el disfrute de la obra. |
| ¿Texto publicado para informar sobre asuntos de interés público? | Art. 50.4 párr. 2 | Orientativa | Art. 50.4 | Textos informativos | Una obra literaria normalmente no lo es. |
| ¿Revisión humana o control editorial? ¿Responsabilidad editorial? | Excepción del art. 50.4 párr. 2 | Orientativa | Art. 50.4 | Textos de interés público | Ambas condiciones deben concurrir. |
| Aceptación de la declaración responsable | Responsabilidad del declarante | Voluntaria (necesaria para emitir) | — | Todos | No es declaración jurada ni firma electrónica cualificada; no tiene fe pública. |

## Resultados de la orientación

| Resultado | Texto base |
|---|---|
| Posible obligación específica | «El uso declarado podría estar sujeto a una obligación específica de transparencia del artículo 50. Se recomienda revisar las condiciones aplicables.» |
| Posible obligación limitada | Régimen de obras manifiestamente creativas (art. 50.4). |
| Posible excepción | Texto de interés público con revisión humana y responsabilidad editorial. |
| Requiere aclaración | Respuestas «no lo sé» en elementos determinantes. |
| Supuesto no identificado | «Con la información facilitada no se ha identificado uno de los supuestos específicos analizados. Esto no equivale a una certificación de cumplimiento legal.» |

Implementación: `src/lib/legal/article50.ts` (pruebas en `tests/unit/article50.test.ts`). El código ORIVA no incorpora ninguna puntuación de cumplimiento.

## Fuera de alcance de la orientación

Arts. 50.1 y 50.3 (interacción con personas, reconocimiento de emociones/categorización biométrica), obligaciones de modelos de uso general, propiedad intelectual (reproducción para entrenamiento, reserva de derechos), protección de consumidores y normas nacionales.

## Pendiente para revisión jurídica

1. Contrastar todas las referencias con la versión consolidada y el Reglamento (UE) 2026/1744.
2. Validar el texto de la declaración responsable (`LegalText` `declaration_statement` v1.0).
3. Completar política de privacidad, condiciones de uso y aviso legal (identidad del responsable, plazos de conservación, encargados).
4. Revisar la formulación de las preguntas y resultados orientativos.
5. Valorar la posible aplicación del Reglamento de Servicios Digitales a las declaraciones públicas (procedimiento de notificación ya disponible mediante «Comunicar una declaración pública»).
