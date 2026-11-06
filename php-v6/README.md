# ORIDE v6 · Originalidad Declarada / Originality Declaration

*Toda creación tiene un proceso. Hazlo visible.*

Versión simplificada del sitio. Una sola página, **Crea tu declaración**, genera el Código ORIDE en tiempo real. Sitio bilingüe: español e inglés.

- PHP 8.1 o superior. **Sin base de datos**, sin cuentas, sin cookies y sin librerías externas.
- El código se calcula en el navegador. Sin JavaScript, el servidor lo calcula igual. No se guarda nada.
- El formulario «¿Cómo puede mejorar el Código ORIDE?» envía los mensajes por correo a `info@itakora.com`.

Las versiones anteriores siguen sin cambios: `../php/` (completa, con cuentas), `../php-v2/` (código A/B/C), `../php-v3/` (código por áreas, solo en español) `../php-v4/` (código por áreas, bilingüe) y `../php-v5/` (código por tipo de obra, con uso mínimo para instituciones).

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

## Código ORIDE (v5)

Ejemplo: `LIT-IA1-DOC44-GEN24-V1`

| Parte | Significado |
|---|---|
| `LIT` | Tipo de obra: SCI, LIT, MUS, PHO, IL, DES, AUD, SW, TRA. |
| `IA0` / `IA1` | Sin IA / con IA. Con el tipo y esto ya hay código (`LIT-IA0`, `LIT-IA1`). Con IA0 solo se pueden añadir las evidencias (`LIT-IA0-V1`). |
| `DOC44` | Actividad (opcional, tres letras, propia de cada tipo de obra) + dos cifras: grado de uso (0–5) y alcance (0–4). Con los grados 0 y 1 la segunda cifra es 0: `DOC10`. |
| `V0` / `V1` | Evidencias (opcional, al final), con IA y sin IA. |

- Tipos de obra y actividades: `includes/taxonomy.php` (fuente: `codigos_oride_2.0.xlsx`; Ilustración usa `IL` en lugar de `ILL`, y «Diseño y artes visuales» se llama «Diseño»).
- Grados de uso (0–5): no utilicé IA · consulté sin incorporar · creé algo propio usando los resultados como referencia · transformé sustancialmente · modifiqué parcialmente · incorporé prácticamente sin cambios.
- Alcance (0–4), desde el grado 2: menos del 5% · 5–25% · 25–50% · 50–75% · 75–100%.

### Enlace a cualquier código

`oride.php?LIT-IA1-DOC44-GEN24-V1` explica el código en frases sencillas. Admite cualquier combinación válida, las actividades en cualquier orden, minúsculas, «:» en lugar de guiones, `V` como `V1` y omitir `IA1` si siguen actividades (`oride.php?LIT:DOC44-GEN24-V`). Si el código no es válido, explica el error.

### Instituciones

`instituciones.php` usa el mismo formulario para fijar un uso máximo de IA. El código va precedido de «El autor debe garantizar que el uso de la IA que hace no supera el siguiente código:».

Los formularios no muestran códigos en las preguntas ni en las opciones: el código aparece solo en la caja.

## Ficheros

```
index.php            Inicio
crear.php            Crea tu declaración (formulario + código en tiempo real)
como-funciona.php    Cómo funciona
codigo.php           Código ORIDE (tablas y explicador)
preguntas.php        Preguntas frecuentes
marco-normativo.php  Artículo 50 del Reglamento de IA
legal.php            Aviso legal y privacidad
mejora.php           ¿Cómo puede mejorar el Código ORIDE?
sobre.php            Sobre este sitio
instituciones.php    Instituciones (uso máximo de IA)
oride.php            Página enlazable de cada código
config.php           Configuración (correo de destino y remitente)
includes/app.php     Lógica y plantilla (no accesible por web)
includes/i18n.php    Textos en español e inglés (no accesible por web)
includes/taxonomy.php Tipos de obra, actividades, grados y alcance (no accesible por web)
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
php -S 127.0.0.1:8095 tests/router.php
```

```bash
ORIDE_URL=http://127.0.0.1:8095/ node tests/e2e.js
```

Para probar el formulario de mejora sin enviar correos, crea `config.local.php` con `<?php return ['debug' => true];`: los mensajes se guardan en `storage/correo-pruebas.log`.

## Privacidad y seguridad

- No hay cookies, sesiones ni almacenamiento en el navegador.
- El formulario de mejora no guarda nada en el servidor: envía un correo. Para frenar el spam usa un campo trampa, una marca de tiempo firmada y un límite global de mensajes por hora. No guarda direcciones IP.
- Política de seguridad de contenidos estricta (sin scripts ni estilos en línea).

## Pendiente

- El aviso legal incluye lo indicado: responsable, contacto, ausencia de cookies y datos recogidos. Conviene que lo revise un profesional; según la actividad, la LSSI puede exigir datos adicionales, como el NIF o un domicilio.
