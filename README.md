# Donaciones Solidarias · Unidad 3 DWI

Prototipo educativo en PHP 8.1+ y HTML/CSS/JavaScript. No procesa pagos reales.

## Inicio rápido

1. Instala PHP 8.1 o posterior; en Windows puedes usar el PHP de XAMPP.
2. En PowerShell, desde esta carpeta:

```powershell
$env:ADMIN_PASSWORD='UsaUnaClaveLargaDiferente123!'
php -S 127.0.0.1:8000 router.php
```

3. Abre http://127.0.0.1:8000 y realiza una aportación demostrativa. El panel está en `/admin` (usuario `admin`, contraseña definida en `ADMIN_PASSWORD`).
4. La carpeta `storage` debe tener permisos de escritura. El almacenamiento del ejemplo usa JSON con bloqueo de archivo; para uso en producción, cambiar por MySQL/PostgreSQL, revisar seguridad y alojar en HTTPS.

## Servicios web

| Tipo | Método | Ruta | Descripción |
|---|---|---|---|
| Propio | GET | `/api/campaigns` | Campañas y recaudación demostrativa (JSON) |
| Propio | GET | `/api/stats` | Totales globales (JSON) |
| Tercero | GET | `https://api.frankfurter.dev/v2/rate/mxn/usd` | Conversión informativa MXN/USD en navegador |

El servicio de tercero requiere Internet y puede fallar; el frontend informa de indisponibilidad. Las cantidades son compromisos de demostración, no dinero transferido.

## Seguridad implementada

- Contraseña del panel mediante variable de entorno; la validación utiliza hash de contraseña (sin persistir contraseñas).
- Sesión con cookie HttpOnly, SameSite=Lax y Secure si hay HTTPS; regeneración de ID al iniciar y cerrar sesión.
- CSRF en formularios con token aleatorio y comparación segura.
- Validación de datos en servidor (correo, límites de caracteres, campaña existente y monto entero entre 10 y 100000).
- Escape HTML para evitar XSS; CSP, X-Frame-Options y X-Content-Type-Options.
- Limitación básica por sesión: intentos de login y frecuencia de aportaciones.
- Panel protegido, API solamente pública y bloqueo de rutas privadas en servidor PHP de desarrollo.
- NOTA: almacenamiento JSON y rate limit por sesión solo sirven como prototipo académico. No sustituyen controles de producción (BD transaccional, rate limiting por IP/identidad, auditoría, monitoreo, HTTPS obligatorio).

## Pruebas

- `curl -i http://127.0.0.1:8000/api/campaigns`
- `curl -i http://127.0.0.1:8000/api/stats`
- `curl -i http://127.0.0.1:8000/admin` devuelve redirección a login cuando no hay sesión.
- En navegador: formulario correcto, correo inválido, token CSRF alterado, login incorrecto y correcto.

## Repositorio

Subir con nombre `ApellidosNombresUnidad3` (de acuerdo con la instrucción de clase), historial de commits y permisos de lectura al docente. **No subir información personal de los formularios**; `storage/pledges.json` está ignorado. El archivo de datos puede crearse automáticamente al primer guardado si la carpeta existe y es escribible.

## Organización

`index.php` rutas, formularios y APIs · `bootstrap.php` seguridad/datos · `router.php` enrutamiento local y bloqueo · `assets/` CSS y JS · `storage/` datos excluidos de Git.
