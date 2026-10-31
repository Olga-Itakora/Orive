# ORIDE v2 · Originalidad Declarada

*Toda creación tiene un proceso. Hazlo visible.*

Versión simplificada del sitio. Una sola página, **Crea tu declaración**, genera el Código ORIDE en tiempo real a partir de tres preguntas (A, B y C).

- PHP 8.1 o superior. **Sin base de datos**, sin cuentas, sin cookies y sin librerías externas.
- El código se calcula en el navegador. Sin JavaScript, el servidor lo calcula igual. No se guarda nada.
- El formulario «¿Cómo puede mejorar el Código ORIDE?» envía los mensajes por correo a `info@itakora.com`.

La versión anterior, completa y con cuentas, sigue en `../php/` sin cambios.

## Instalar en IONOS

1. En el panel de IONOS elige PHP 8.1 o superior (Hosting → PHP).
2. Sube por SFTP **el contenido** de esta carpeta a la carpeta del dominio, incluidos los ficheros ocultos `.htaccess`.
3. Comprueba que la carpeta `storage/` tiene permiso de escritura (755).
4. Abre el dominio. No hay instalador ni nada más que configurar.

Para recibir bien los correos, crea en IONOS un buzón `no-reply@tudominio` (o pon otro remitente del dominio en `config.php`, clave `mail_from`). Cuando actives el certificado SSL, quita el `#` de las tres líneas de HTTPS en `.htaccess`.

## Código ORIDE (v2)

| Dimensión | Valores |
|---|---|
| A · Función de la IA | A0 No se utilizó IA · A1 Consulta o documentación · A2 Ideas o sugerencias · A3 Revisión o crítica · A4 Corrección, edición o traducción · A5 Generación de contenido |
| B · Qué se incorporó a la obra | B0 Nada · B1 Aportación conceptual · B2 Aportación formal · B3 Incorporación de contenido generado por IA |
| C · Intervención humana | C0 No aplicable · C1 Selección o aceptación · C2 Edición puntual · C3 Transformación sustancial · C4 Reelaboración creativa · C5 Creación propia a partir de referencias |

- Varios valores de una dimensión se juntan en orden: A1 + A3 + A5 → `A135`; B1 + B2 + B3 → `B123`.
- Cada tipo de aportación B tiene su intervención C. El código une los valores C sin repetir: B1 → C5 y B2 → C1 dan `B12C15`.
- `A0B0C0`: no se usó IA. Si no se incorporó nada: `B0C0`.
- La dimensión D (documentación y trazabilidad) se incorporará en una próxima versión.
- El explicador de `codigo.php` acepta también la notación antigua (`A2+A4 | B1 | C5 | D2` → `A24B1C5`).

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
config.php           Configuración (correo de destino y remitente)
includes/app.php     Lógica y plantilla (no accesible por web)
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
php -S 127.0.0.1:8091 tests/router.php
```

```bash
ORIDE_URL=http://127.0.0.1:8091/ node tests/e2e.js
```

Para probar el formulario de mejora sin enviar correos, crea `config.local.php` con `<?php return ['debug' => true];`: los mensajes se guardan en `storage/correo-pruebas.log`.

## Privacidad y seguridad

- No hay cookies, sesiones ni almacenamiento en el navegador.
- El formulario de mejora no guarda nada en el servidor: envía un correo. Para frenar el spam usa un campo trampa, una marca de tiempo firmada y un límite global de mensajes por hora. No guarda direcciones IP.
- Política de seguridad de contenidos estricta (sin scripts ni estilos en línea).

## Pendiente

- El aviso legal incluye lo indicado: responsable, contacto, ausencia de cookies y datos recogidos. Conviene que lo revise un profesional; según la actividad, la LSSI puede exigir datos adicionales, como el NIF o un domicilio.
