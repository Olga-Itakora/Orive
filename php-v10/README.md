# ORIDE v10 · Originalidad Declarada / Originality Declaration

*Toda creación tiene un proceso. Hazlo visible.*

Versión simplificada del sitio. Una sola página, **Crea tu declaración**, genera el Código ORIDE en tiempo real. Sitio bilingüe: español e inglés.

- PHP 8.1 o superior. **Sin base de datos**, sin cuentas, sin cookies y sin librerías externas.
- El código se calcula en el navegador. Sin JavaScript, el servidor lo calcula igual. No se guarda nada.
- El formulario «¿Cómo puede mejorar el Código ORIDE?» envía los mensajes por correo a `info@itakora.com`.

Las versiones anteriores siguen sin cambios: `../php/` (completa, con cuentas), `../php-v2/` (código A/B/C), `../php-v3/` (código por áreas, solo en español) `../php-v4/` (código por áreas, bilingüe), `../php-v5/` (código por tipo de obra, con uso mínimo para instituciones) `../php-v6/` (código en una línea, sin Índice Global) y `../php-v7/` (tipos de tres letras y V0/V1).

## Instalar en IONOS

1. En el panel de IONOS elige PHP 8.1 o superior (Hosting → PHP).
2. Sube por SFTP **el contenido** de esta carpeta a la carpeta del dominio, incluidos los ficheros ocultos `.htaccess`.
3. Comprueba que la carpeta `storage/` tiene permiso de escritura (755).
4. Abre el dominio. No hay instalador ni nada más que configurar.

Para recibir bien los correos, crea en IONOS un buzón `no-reply@tudominio` (o pon otro remitente del dominio en `config.php`, clave `mail_from`). Cuando actives el certificado SSL, quita el `#` de las tres líneas de HTTPS en `.htaccess`.

## Idiomas

- Cada página lleva el idioma en la dirección: `?lang=es` o `?lang=en`. Así no hacen falta cookies.
- Si la dirección no lo indica, se usa el idioma preferido del navegador (español por defecto).
- El botón de la cabecera cambia de idioma y conserva las respuestas del formulario.
- Los textos están en `includes/i18n.php` (taxonomía y mensajes comunes) y, los de cada página, en la propia página con `tr('español', 'English')`.
- Las letras y cifras del código son las mismas en los dos idiomas.

## Novedades de la v10

Rediseño del formulario y de la caja a partir de las pruebas `prototipos/prueba-v1.html` a `prueba-v4.html`. El algoritmo del índice no cambia.

- **Caja de la declaración** (`code_box()`, `decl_view()`, `decl_svg()` en `includes/app.php`): el Índice Global ORIDE pasa a primer plano. Muestra el tipo de obra, una escala de 0 a 100 % en seis tramos proporcionales (A–F, con su nombre, sin letras) y un globo que explica el índice con palabras («Uso de IA bajo. Se estima que la IA aportó entre el 18 % y el 23 %…»). Con IA = No: «Obra 100 % libre de IA». Con evidencias: un tick junto al índice («Se puede mostrar el proceso de creación»). Debajo: descargar la imagen en PNG y SVG y, en letra pequeña, el código con «Copiar código» y «Copiar enlace». La figura tiene una versión ancha y otra estrecha (se elige por el ancho de la caja con una consulta de contenedor) y una versión para descargar, con el código al pie.
- **Formulario** (`includes/builder.php`): tipo de obra, uso de IA y evidencias son botones (sin el círculo del radio) y, al responder, la página baja sola a la siguiente pregunta. Cada actividad tiene un desplegable («No utilicé IA» por defecto) y, solo con los grados 2 a 5, el alcance en pastillas («5% – 25%»). Una actividad entra en el código si su grado es mayor que 0; los enlaces anteriores con `acts_TIPO[]` se siguen respetando.
- **Lee un código** (`leer.php`): en el menú superior, junto a «Crea tu declaración». Lleva a `oride.php`, que muestra el código explicado, la caja y, debajo, el desglose del cálculo.
- **Móvil**: barra fija con el resultado y «Ver declaración».
- «Trabajos académicos» pasa a «Trabajo académico».

## Código ORIDE (v9, taxonomía ORIDE Code 3.1)

Dos líneas. Ejemplo:

```
LI-IGB-EV1
DOC44-GEN24
```

| Parte | Significado |
|---|---|
| Línea 1 (tríada) | Tipo de obra (SC, LI, MU, PH, IL, DS, AU, SW, TR y ACA) · uso de IA · `EV0`/`EV1` evidencias. Las tres son obligatorias. |
| Uso de IA | `IA0`: no se usó IA (nunca se convierte en `IGA`). `IGA`…`IGF`: se usó IA; categoría del Índice Global, calculada a partir de las actividades (no se elige). `IA1`: se usó IA pero no hay categoría (sin actividades o todas a 0). |
| Línea 2 (opcional, solo con IA) | Actividades: tres letras + grado de uso (0–5) + alcance (0–4). Con los grados 0 y 1 la segunda cifra es 0: `DOC10`. |

- En una sola línea (enlaces y JSON) se escribe todo seguido: `LI-IGB-EV1-DOC44-GEN24`.
- Compatibilidad con códigos anteriores: `LI-IA1-EV1-GEN24` (IA1 con actividades) sigue siendo válido; conserva IA1 y la categoría se muestra como información derivada. También se entienden los tipos de tres letras (`LIT`, `SCI`…, `SOF`, `ILU`), `V0`/`V1`, V al final (`LIT-IA1-DOC44-GEN24-V1`) y sin V (`AUD-IA1`).
- Una letra con actividades tiene que coincidir con la calculada: `LI-IGC-EV1-GEN24` no es válido (corresponde a IGB). Una letra sin actividades (`LI-IGB-EV0`) es una categoría fijada en el código: sirve para requisitos que solo limitan la categoría.
- Si se declara IA pero todas las actividades marcadas tienen grado 0, se avisa de que hay que explicar el uso de la IA fuera de las actividades establecidas (y el código queda en IA1).
- Grados de uso 3 y 4: «Incorporé los resultados transformados sustancialmente» y «Incorporé los resultados transformados parcialmente».
- Tipos de obra y actividades: `includes/taxonomy.php` (fuente: `codigos_oride_2.0.xlsx`, más Trabajos académicos).

### Trabajos académicos (ACA)

Décimo tipo de obra (*Academic work*), añadido en ORIDE Code 3.1: trabajos elaborados en contextos educativos o formativos (trabajos de asignatura, ensayos, informes de prácticas, revisiones bibliográficas, proyectos, TFG, TFM, actividades de evaluación…), también para participantes que no son estudiantes. Se elige por separado de Investigación científica (SC); los trabajos ya declarados con otro tipo no se reclasifican.

- Identificador `ACA`, igual en los dos idiomas. Es el único de tres letras; no coincide con ningún tipo actual ni con los códigos antiguos (`LEGACY_TYPES`), y nada en el código supone tipos de dos letras.
- Actividades, en este orden: DOC documentación y búsqueda de fuentes · IDE generación y desarrollo de ideas · EST planificación y estructuración · ANA análisis de información, datos o materiales · ARG razonamientos y argumentación · GEN generación de contenido académico · REV revisión del contenido · FOR revisión formal y de estilo · REF referencias y citas · VAL comprobación y validación.
- Pesos propios (`basis: proposal`), experimentales y revisables: DOC 0,05 · IDE 0,08 · EST 0,08 · ANA 0,10 · ARG 0,12 · GEN generación directa (G) · REV 0,07 · FOR 0,03 · REF 0,02 · VAL 0,05. Suman 0,60, por encima del techo de 0,50 que se usa en las disciplinas por analogía; P sigue limitado a 1. ANA y ARG son auxiliares: no cuentan como contenido generado, y con grado 1 (consultar sin incorporar) no suman.
- Añadir una disciplina no cambia ningún cálculo anterior, así que el algoritmo sigue siendo `ORIDE-IG-1.0-experimental`; sí cambia la taxonomía (3.1).
- Ejemplos y un requisito de universidad en `documentacion.php#academicos`. Ejemplo de sintaxis: `ACA-IGB-EV1` + `DOC21-GEN24` (la B se calcula).

## Versiones

| Qué | Versión | Dónde |
|---|---|---|
| Taxonomía del código | `ORIDE Code 3.1` (añade ACA; los códigos de 3.0 no cambian) | `ORIDE_TAXONOMY`, `includes/indice.php` |
| Algoritmo del índice | `ORIDE-IG-1.0-experimental` | `IG_CURRENT`, `includes/indice.php` |
| Categorías A–F | `ORIDE-IG-CAT-1.0` | `IG_CAT_VERSION`, `includes/categories.php` |
| Verificador | `ORIDE-VERIFY-1.2` | `VERIFY_VERSION`, `includes/compare.php` |

## Índice Global ORIDE

Estimación de 0 a 100 de la contribución de la IA (`includes/indice.php`). Metodología pública en `indice.php`. El algoritmo no cambia en v9: el índice se calcula como intervalo y se muestra como una categoría (A–F) en una caja destacada bajo el código, con una escala A–F accesible (no depende solo del color). El intervalo, el punto medio y el cálculo completo están en el desglose y en la descarga JSON.

```
IG = G + (100 − G) · P
G  = 100 · q · f(H)                 actividades generativas
P  = min(1, Σ w · f(H) · S)          actividades auxiliares
```

- Factores f(H) por grado de uso: 0 → 0 · 1 → 0 · 2 → 0,20 · 3 → 0,50 · 4 → 0,85 · 5 → 1,00.
- El alcance (S) son tramos de porcentaje (0–4); se usa el intervalo de cada tramo y el índice es un intervalo.
- Pesos por disciplina en `IG_VERSIONS`: los de literatura son los de la especificación; los demás, provisionales por analogía (techo auxiliar 0,50).
- No calculable si hay IA pero no se detallan actividades, o si todas están a 0. Sin IA (IA0) no hay categoría.
- El enlace a un código lleva las versiones (`ig=…`, `igc=…`); `calc.php?code=…&format=json` descarga el cálculo completo, con el punto medio y la categoría.

### Categorías (ORIDE-IG-CAT-1.0, `includes/categories.php`)

Punto medio `m = (mín + máx) / 2`, sin redondear:

| Categoría | Nombre | Punto medio |
|---|---|---|
| A | Muy bajo / Very low | 0 ≤ m ≤ 10 |
| B | Bajo / Low | 10 < m ≤ 25 |
| C | Moderado / Moderate | 25 < m ≤ 50 |
| D | Alto / High | 50 < m ≤ 75 |
| E | Muy alto / Very high | 75 < m ≤ 90 |
| F | Extremo / Extreme | 90 < m ≤ 100 |

Intervalo incompleto → sin categoría; intervalo invertido o fuera de 0–100 → error. Las letras son las mismas en todos los idiomas. No son calificaciones de calidad, mérito, originalidad ni autoría.

### Enlace a cualquier código

`oride.php?LI-IGB-EV1-DOC44-GEN24` explica el código en frases sencillas, con su categoría. Admite cualquier combinación válida, las actividades en cualquier orden, minúsculas, «:» en lugar de guiones y los formatos anteriores.

### Instituciones y verificador (ORIDE-VERIFY-1.2)

`instituciones.php` usa el mismo formulario para crear un requisito. La caja muestra el «Índice global máximo»: la categoría del requisito es la máxima permitida. El botón y la pestaña «Verificar una entrega» llevan el código a `comprobar.php`.

`comprobar.php` tiene dos columnas: a la izquierda, el código del requisito, el declarado, la comparación y «Verificar»; a la derecha, «Declaración Compatible» o «Declaración No Compatible», con «Copiar resultado», las comprobaciones y un resumen plegado. Los dos códigos tienen que ser válidos. Reglas (`includes/compare.php`):

1. **Tipo de obra, IA y evidencias**: distinto tipo → no compatible; requisito IA0 y declaración con IA → no compatible; requisito EV1 exige EV1.
2. **Categoría**, en todas las modalidades: A < B < … < F; la declarada no puede superar la del requisito. Una declaración IA0 cumple. Si el requisito fija categoría y la declaración no la tiene (IA1), «No es posible determinar la compatibilidad».
3. **Comparación** (`mode=`):
   - `global` — Índices Globales (recomendada, predeterminada): solo categorías, sin detalle de actividades;
   - `weighted` — Actividades ponderadas: además, la contribución ponderada de cada actividad no puede superar la del requisito;
   - `digits` — Actividades por grado y alcance: además, grado y alcance, cada uno por separado.
   En `weighted` y `digits`: las actividades del requisito que faltan en la declaración se entienden como no usadas; las declaradas que el requisito no menciona generan un aviso de revisión manual que no cambia el resultado.
4. **Resultado**: se informa por separado de códigos, categoría y actividades. Una categoría baja nunca compensa una actividad que supera su límite, y cumplir una categoría no garantiza que todo el intervalo quede por debajo de un porcentaje.

Cambios respecto a VERIFY-1.1: se eliminan el límite global en % y las opciones de actividades no especificadas/ausentes; se añade la comparación por categorías.

## Ficheros

```
index.php            Inicio
crear.php            Crea tu declaración (formulario + código en tiempo real)
como-funciona.php    Cómo funciona
codigo.php           Código ORIDE (tablas y explicador)
que-es.php           ¿Qué es ORIDE?
documentacion.php    Documentación: cómo funcionan el código y el índice, enlaces a codigo.php e indice.php, y preguntas frecuentes
preguntas.php        Redirige a documentacion.php#preguntas
marco-normativo.php  Redirige a documentacion.php#preguntas (el artículo 50 está en las preguntas)
comprobar.php        Instituciones › Verificar una entrega
leer.php             Lee un código (lleva a oride.php)
legal.php            Aviso legal y privacidad
mejora.php           Contacto
sobre.php            Sobre este sitio
instituciones.php    Instituciones (uso máximo de IA)
oride.php            Página enlazable de cada código
indice.php           Cómo se calcula el Índice Global ORIDE
calc.php             Cálculo del código y del índice (JSON para el formulario y descarga)
config.php           Configuración (correo de destino y remitente)
includes/app.php     Lógica y plantilla (no accesible por web)
includes/i18n.php    Textos en español e inglés (no accesible por web)
includes/taxonomy.php Tipos de obra, actividades, grados y alcance (no accesible por web)
includes/indice.php  Motor y configuración versionada del Índice Global ORIDE (no accesible por web)
includes/categories.php Categorías A–F del Índice Global (no accesible por web)
includes/compare.php Verificador ORIDE-VERIFY-1.2 (no accesible por web)
includes/builder.php Formulario compartido por crear.php e instituciones.php (no accesible por web)
assets/              CSS, JavaScript e icono
storage/             Secreto antispam, contador de mensajes y registro de errores (no accesible por web)
tests/               Pruebas (no accesible por web)
```

## Pruebas

```bash
php tests/run.php
```

Prueba en navegador (solo desarrollo, requiere Node y Playwright):

```bash
php -S 127.0.0.1:8099 tests/router.php
```

```bash
ORIDE_URL=http://127.0.0.1:8099/ node tests/e2e.js
```

Para probar el formulario de mejora sin enviar correos, crea `config.local.php` con `<?php return ['debug' => true];`: los mensajes se guardan en `storage/correo-pruebas.log`.

## Privacidad y seguridad

- No hay cookies, sesiones ni almacenamiento en el navegador.
- El formulario de mejora no guarda nada en el servidor: envía un correo. Para frenar el spam usa un campo trampa, una marca de tiempo firmada y un límite global de mensajes por hora. No guarda direcciones IP.
- Política de seguridad de contenidos estricta (sin scripts ni estilos en línea).

## Pendiente

- El aviso legal incluye lo indicado: responsable, contacto, ausencia de cookies y datos recogidos. Conviene que lo revise un profesional; según la actividad, la LSSI puede exigir datos adicionales, como el NIF o un domicilio.

## Accesibilidad

Objetivo: WCAG 2.2 nivel AA. Revisado con axe-core en todas las páginas (los dos idiomas, escritorio y móvil, con desgloses abiertos) y con comprobaciones adicionales de reflujo a 320 px, espaciado de texto, tamaño mínimo de objetivos (24 × 24 px), navegación con teclado y foco visible:

```bash
ORIDE_URL=http://127.0.0.1:8099/ AXE_DIR=node_modules/axe-core node tests/a11y-axe.js
```

```bash
ORIDE_URL=http://127.0.0.1:8099/ node tests/a11y-manual.js
```

Las herramientas automáticas no sustituyen una prueba con personas usuarias de lectores de pantalla.

## Licencia

El Código ORIDE, el Índice Global ORIDE, su metodología y los contenidos del sitio son © 2026 Olga Revilla y se publican con la licencia [CC BY-NC-ND 4.0](https://creativecommons.org/licenses/by-nc-nd/4.0/). Los iconos de la licencia del pie se cargan desde `mirrors.creativecommons.org` (está permitido en la política de seguridad de contenidos y se indica en el aviso de privacidad). Para servirlos desde el propio sitio, descárgalos en `assets/img/cc/` y cambia la ruta en `page_end()`.
