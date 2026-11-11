# SENATI — Login negro

Pantalla de inicio de sesión moderna, responsive, con fondo negro y detalles morados.

## Requisitos
- Laragon con Apache y PHP 8.0 o superior, con permisos de escritura en la carpeta del proyecto.
- No requiere MySQL ni dependencias de Composer.

## Instalación
1. Coloca los archivos del repositorio en `C:\\laragon\\www\\senati`.
2. Inicia Apache desde Laragon.
3. Abre `http://localhost/senati/`.
4. En la **primera visita**, crea el usuario administrador con una contraseña de 12 o más caracteres.
5. Inicia sesión con esa cuenta. La sesión muestra una pantalla de bienvenida y botón para cerrar sesión.

## Seguridad
- No incluye credenciales predeterminadas.
- Contraseñas almacenadas con `password_hash` (nunca en texto plano).
- Las cuentas se guardan en `private/users.php`, que comienza con `<?php exit; ?>` para impedir que Apache/PHP muestre los datos.
- Usa sesiones PHP, token CSRF y regeneración del identificador de sesión al acceder.
- Para un despliegue público real: añade HTTPS, limitación de intentos de inicio de sesión y almacena las cuentas fuera del directorio público con una base de datos.
- No subas la carpeta `private/` a GitHub. Se genera automáticamente y está en `.gitignore`.

**Nota:** Este proyecto es un login funcional con un panel de bienvenida básico. No incluye todavía módulo de creación de usuarios secundarios ni conexión a los escenarios 3D.
