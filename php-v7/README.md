# ORIDE v7 · Originalidad Declarada / Originality Declaration

*Toda creación tiene un proceso. Hazlo visible.*

Versión simplificada del sitio. Una sola página, **Crea tu declaración**, genera el Código ORIDE en tiempo real. Sitio bilingüe: español e inglés.

- PHP 8.1 o superior. **Sin base de datos**, sin cuentas, sin cookies y sin librerías externas.
- El código se calcula en el navegador. Sin JavaScript, el servidor lo calcula igual. No se guarda nada.
- El formulario «¿Cómo puede mejorar el Código ORIDE?» envía los mensajes por correo a `info@itakora.com`.

Las versiones anteriores siguen sin cambios: `../php/` (completa, con cuentas), `../php-v2/` (código A/B/C), `../php-v3/` (código por áreas, solo en español) `../php-v4/` (código por áreas, bilingüe), `../php-v5/` (código por tipo de obra, con uso mínimo para instituciones) y `../php-v6/` (código en una línea, sin Índice Global).

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

## Código ORIDE (v7)

Dos líneas. Ejemplo:

```
LIT-IA1-V1
DOC44-GEN24
```

| Parte | Significado |
|---|---|
| Línea 1 (tríada) | Tipo de obra (SCI, LIT, MUS, PHO, IL, DES, AUD, SW, TRA) · `IA0`/`IA1` sin IA / con IA · `V0`/`V1` evidencias. Las tres son obligatorias. |
| Línea 2 (opcional, solo con IA1) | Actividades: tres letras + grado de uso (0–5) + alcance (0–4). Con los grados 0 y 1 la segunda cifra es 0: `DOC10`. |

- En una sola línea (enlaces y JSON) se escribe todo seguido: `LIT-IA1-V1-DOC44-GEN24`.
- Se siguen entendiendo los formatos anteriores: `LIT-IA1-DOC44-GEN24-V1` (V al final) y `AUD-IA1` (sin V).
- Si se declara IA1 pero todas las actividades marcadas tienen grado 0, se avisa de que hay que explicar el uso de la IA fuera de las actividades establecidas.
- Tipos de obra y actividades: `includes/taxonomy.php` (fuente: `codigos_oride_2.0.xlsx`).

## Índice Global ORIDE

Estimación de 0 a 100 de la contribución de la IA (`includes/indice.php`, versión `ORIDE-IG-1.0-experimental`). Metodología pública en `indice.php`.

```
IG = G + (100 − G) · P
G  = 100 · q · f(H)                 actividades generativas
P  = min(1, Σ w · f(H) · S)          actividades auxiliares
```

- Factores f(H) por grado de uso: 0 → 0 · 1 → 0 · 2 → 0,20 · 3 → 0,50 · 4 → 0,85 · 5 → 1,00.
- El alcance (S) del Código ORIDE son tramos de porcentaje (0–4); se usa el intervalo de cada tramo, no s/5, y el índice se da como intervalo.
- q: pregunta opcional del formulario («¿Qué proporción aproximada del contenido final procede de material generado mediante IA?», siete tramos y un porcentaje exacto opcional). Si no se responde, se usa el alcance declarado de la actividad generativa.
- Pesos por disciplina en `IG_VERSIONS`: los de literatura son los de la especificación; los demás, provisionales por analogía (techo auxiliar 0,50), cada uno con su justificación.
- No calculable si hay IA pero no se detallan actividades, o si todas están a 0. Sin IA, 0.
- Cada cambio de factores o pesos exige una versión nueva. El enlace a un código lleva la versión (`ig=…`) y la respuesta a la pregunta de proporción (`q=…` o `qp=…`), y `calc.php?code=…&format=json` descarga el cálculo completo (versión, parámetros, valores y fecha). El sitio no guarda declaraciones.
- Limitaciones: el formulario declara la obra como un único componente (el motor ya admite varios, con pesos que sumen 1); con varias actividades generativas, el índice es el intervalo entre la que menos y la que más incorpora.

### Enlace a cualquier código

`oride.php?LIT-IA1-V1-DOC44-GEN24` explica el código en frases sencillas, con su Índice Global ORIDE. Admite cualquier combinación válida, las actividades en cualquier orden, minúsculas, «:» en lugar de guiones, `V` como `V1` y omitir `IA1` si siguen actividades (`oride.php?LIT:DOC44-GEN24-V`). Si el código no es válido, explica el error.

### Instituciones

`instituciones.php` usa el mismo formulario para fijar un uso máximo de IA. El código va precedido de «El autor debe garantizar que el uso de la IA que hace no supera el siguiente código:».

Los formularios no muestran códigos en las preguntas ni en las opciones: el código aparece solo en la caja.

## Ficheros

```
index.php            Inicio
crear.php            Crea tu declaración (formulario + código en tiempo real)
como-funciona.php    Cómo funciona
codigo.php           Código ORIDE (tablas y explicador)
documentacion.php    Documentación: cómo funcionan el código y el índice, y preguntas frecuentes
preguntas.php        Redirige a documentacion.php#preguntas
marco-normativo.php  Artículo 50 del Reglamento de IA
legal.php            Aviso legal y privacidad
mejora.php           ¿Cómo puede mejorar el Código ORIDE?
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
php -S 127.0.0.1:8096 tests/router.php
```

```bash
ORIDE_URL=http://127.0.0.1:8096/ node tests/e2e.js
```

Para probar el formulario de mejora sin enviar correos, crea `config.local.php` con `<?php return ['debug' => true];`: los mensajes se guardan en `storage/correo-pruebas.log`.

## Privacidad y seguridad

- No hay cookies, sesiones ni almacenamiento en el navegador.
- El formulario de mejora no guarda nada en el servidor: envía un correo. Para frenar el spam usa un campo trampa, una marca de tiempo firmada y un límite global de mensajes por hora. No guarda direcciones IP.
- Política de seguridad de contenidos estricta (sin scripts ni estilos en línea).

## Pendiente

- El aviso legal incluye lo indicado: responsable, contacto, ausencia de cookies y datos recogidos. Conviene que lo revise un profesional; según la actividad, la LSSI puede exigir datos adicionales, como el NIF o un domicilio.

## Licencia

ORIDE no es una marca registrada. El Código ORIDE, su metodología y los contenidos del sitio son © 2026 Olga Revilla y se publican con la licencia [CC BY-NC-ND 4.0](https://creativecommons.org/licenses/by-nc-nd/4.0/). Los iconos de la licencia del pie se cargan desde `mirrors.creativecommons.org` (está permitido en la política de seguridad de contenidos y se indica en el aviso de privacidad). Para servirlos desde el propio sitio, descárgalos en `assets/img/cc/` y cambia la ruta en `page_end()`.
