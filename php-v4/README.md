# ORIDE v4 · Originalidad Declarada / Originality Declaration

*Toda creación tiene un proceso. Hazlo visible.*

Versión simplificada del sitio. Una sola página, **Crea tu declaración**, genera el Código ORIDE en tiempo real. Sitio bilingüe: español e inglés.

- PHP 8.1 o superior. **Sin base de datos**, sin cuentas, sin cookies y sin librerías externas.
- El código se calcula en el navegador. Sin JavaScript, el servidor lo calcula igual. No se guarda nada.
- El formulario «¿Cómo puede mejorar el Código ORIDE?» envía los mensajes por correo a `info@itakora.com`.

Las versiones anteriores siguen sin cambios: `../php/` (completa, con cuentas), `../php-v2/` (código A/B/C) y `../php-v3/` (código por áreas, solo en español).

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

## Código ORIDE

Ejemplo: `IA1-I2-C43-T51-V1`

| Parte | Significado |
|---|---|
| `IA0` | No se usó IA (código completo; fin del formulario). |
| `IA1` | Se usó IA. Siguen las áreas y las evidencias. |
| Área + forma de uso | Una letra por área y una cifra de 0 a 5: `I2`. |
| + alcance | Con las formas de uso 3, 4 y 5 se añade una cifra de 0 a 4: `C43` = forma de uso 4, alcance 3. |
| `V0` / `V1` | No conserva / conserva evidencias. Con V1 se pide añadir un medio de contacto o un enlace a las evidencias. |

Áreas, en el orden en que aparecen en el código: **D** Consulta de documentación · **I** Generación de ideas · **E** Estructuración de la obra · **C** Generación de contenidos · **R** Revisión del contenido · **F** Revisión de forma · **T** Traducción.

Formas de uso (0–5): 0 no utilicé IA; 1 consulté sin incorporar; 2 creé algo propio a partir de las propuestas; 3 reelaboré sustancialmente; 4 cambios parciales o menores; 5 incorporé sin cambios. El texto exacto de cada nivel depende del área (ver `codigo.php`).

Alcance (0–4): menos del 5% · del 5% al 25% · del 25% al 50% · del 50% al 75% · del 75% al 100%.

El lector de `codigo.php` admite minúsculas, el prefijo «ORIDE» y espacios o puntos en lugar de guiones.

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
config.php           Configuración (correo de destino y remitente)
includes/app.php     Lógica y plantilla (no accesible por web)
includes/i18n.php    Textos en español e inglés (no accesible por web)
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
php -S 127.0.0.1:8093 tests/router.php
```

```bash
ORIDE_URL=http://127.0.0.1:8093/ node tests/e2e.js
```

Para probar el formulario de mejora sin enviar correos, crea `config.local.php` con `<?php return ['debug' => true];`: los mensajes se guardan en `storage/correo-pruebas.log`.

## Privacidad y seguridad

- No hay cookies, sesiones ni almacenamiento en el navegador.
- El formulario de mejora no guarda nada en el servidor: envía un correo. Para frenar el spam usa un campo trampa, una marca de tiempo firmada y un límite global de mensajes por hora. No guarda direcciones IP.
- Política de seguridad de contenidos estricta (sin scripts ni estilos en línea).

## Pendiente

- El aviso legal incluye lo indicado: responsable, contacto, ausencia de cookies y datos recogidos. Conviene que lo revise un profesional; según la actividad, la LSSI puede exigir datos adicionales, como el NIF o un domicilio.
