# Instalar ORIDE en IONOS (alojamiento web)

Guía paso a paso para un plan de alojamiento compartido de IONOS con PHP y MySQL. No hace falta instalar ningún programa en el servidor.

## Lo que necesitas

- Un plan de alojamiento IONOS con PHP y base de datos MySQL.
- Un dominio conectado al alojamiento (por ejemplo `oridecode.com`).
- Un programa de SFTP, como FileZilla o Cyberduck (gratuitos).
- El contenido de la carpeta `php/` de este repositorio.

## 1. Elegir la versión de PHP

En el panel de IONOS: **Hosting → (tu contrato) → PHP**. Selecciona **PHP 8.2 o superior** (8.3 recomendado). IONOS incluye las extensiones necesarias (PDO MySQL, GD y mbstring).

## 2. Crear la base de datos

1. Panel de IONOS → **Hosting → Bases de datos → Crear base de datos**. Elige MySQL 8.0.
2. Pon una descripción (p. ej. «ORIDE») y una contraseña segura.
3. Anota los datos de acceso que muestra IONOS: **servidor** (algo como `db5000000000.hosting-data.io`), **nombre de la base de datos**, **usuario** y **puerto** (3306).

## 3. Subir los ficheros

1. Panel de IONOS → **Hosting → SFTP y SSH**: crea o consulta el usuario SFTP.
2. Conéctate con FileZilla (protocolo SFTP, puerto 22).
3. Sube **todo el contenido** de la carpeta `php/` a la carpeta del sitio (la que tiene asignado el dominio en **Dominios y SSL → Destino**). Incluye los ficheros ocultos `.htaccess`.
4. Comprueba que la carpeta `storage/` tiene permiso de escritura (normalmente ya lo tiene; si no, en FileZilla: clic derecho → Permisos → 755).

> Puedes instalar ORIDE en una subcarpeta (p. ej. `https://midominio.com/oride/`); las rutas se adaptan solas.

## 4. Activar HTTPS

Panel de IONOS → **Dominios y SSL**: activa el certificado SSL del dominio (incluido en los planes de IONOS). Después, en el fichero `.htaccess` de la raíz, quita el `#` de las tres líneas de redirección a HTTPS:

```apache
RewriteEngine On
RewriteCond %{HTTPS} !=on
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

## 5. Ejecutar el instalador

1. Abre `https://tu-dominio/instalar.php`.
2. Si falta algo en el servidor, el instalador lo indica.
3. Rellena: datos de la base de datos (del paso 2), URL pública del sitio (con `https://`), dirección remitente de correo (mejor del propio dominio, p. ej. `no-reply@oridecode.com`) y la cuenta de administración.
4. Pulsa **Instalar**.

Si el instalador avisa de que **no ha podido crear los disparadores** de la base de datos, es normal en alojamiento compartido: ORIDE funciona igual (ver «Limitaciones» en el README).

## 6. Después de instalar

1. **Elimina `instalar.php`** del servidor (queda bloqueado, pero es mejor no dejarlo).
2. Inicia sesión con la cuenta de administración y revisa **Administración → Textos legales**: completa el aviso legal y la política de privacidad con los datos del responsable, y haz que los revise un profesional.
3. Crea una cuenta de correo `no-reply@…` en IONOS si quieres que los correos de recuperación de contraseña se entreguen bien.
4. Haz una declaración de prueba completa y verifícala desde **Verificar**.

## Copias de seguridad

- **Base de datos**: IONOS permite exportarla desde phpMyAdmin (Bases de datos → Abrir). Hazlo periódicamente.
- **Evidencias**: descarga por SFTP la carpeta `storage/evidencias/`.
- **Configuración**: guarda una copia de `config.php` en un lugar seguro (contiene la contraseña de la base de datos y el secreto de la aplicación). Si pierdes `app_secret`, las sesiones se cerrarán, pero las declaraciones siguen siendo verificables.

## Actualizar

Sube los ficheros nuevos **sin sobrescribir** `config.php` ni la carpeta `storage/`. Si una versión trae cambios en la base de datos, se indicará en `docs/CHANGELOG.md`.

## Problemas frecuentes

| Síntoma | Solución |
|---|---|
| Error 500 al abrir el sitio | Comprueba la versión de PHP (≥ 8.2). Revisa `storage/logs/php-errors.log`. |
| El instalador no conecta con la base de datos | El servidor no es `localhost` en IONOS: usa el nombre `db….hosting-data.io` que aparece en el panel. |
| Los estilos no cargan | Comprueba que la carpeta `assets/` se subió completa. |
| No llegan los correos | Usa como remitente una dirección del propio dominio. |
| Se ve la lista de ficheros de una carpeta | Asegúrate de haber subido los ficheros `.htaccess` (están ocultos; actívalos en FileZilla: Servidor → Forzar mostrar archivos ocultos). |
