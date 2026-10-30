# Librerías de terceros incluidas

Se incluyen copiadas en el proyecto para que no sea necesario instalar nada (sin Composer).

| Librería | Versión | Licencia | Uso |
|---|---|---|---|
| [setasign/tfpdf](https://github.com/Setasign/tFPDF) (basada en FPDF de Olivier Plathey) | 1.34 | LGPL-2.1 | Generación de PDF con UTF-8 |
| Fuentes DejaVu | 2.x | Bitstream Vera / Arev (libre) — ver `tfpdf/font/unifont/DejaVu_LICENSE.txt` | Fuentes del PDF |
| [chillerlan/php-qrcode](https://github.com/chillerlan/php-qrcode) | 5.0.5 | MIT / Apache-2.0 | Códigos QR |
| [chillerlan/php-settings-container](https://github.com/chillerlan/php-settings-container) | 3.3.0 | MIT | Dependencia de php-qrcode |

Para actualizar una librería, sustituye su carpeta por la nueva versión y comprueba que `tests/run.php` sigue pasando.
