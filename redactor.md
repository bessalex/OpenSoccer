# Resumen de Posibles Mejoras en el Código (OpenSoccer)

Tras realizar un análisis del código fuente del proyecto, se identifican diversas áreas de mejora relacionadas con seguridad, compatibilidad con versiones modernas de PHP, rendimiento y mantenibilidad.

---

## 1. Seguridad y Autenticación

### A. Algoritmo de Hashing de Contraseñas Desactualizado (MD5)
- **Estado actual**: Las contraseñas de los usuarios se almacenan utilizando un hash MD5 con prefijo/sufijo fijo (`md5('1'.$password.'29')`).
- **Riesgo**: MD5 es criptográficamente vulnerable, susceptible a ataques por tablas arcoíris (rainbow tables) y de fuerza bruta de altísima velocidad.
- **Mejora propuesta / aplicada**:
  - Migrar al uso de la API nativa de PHP `password_hash()` y `password_verify()` utilizando el algoritmo predeterminado seguro (`PASSWORD_DEFAULT` / BCRYPT/ARGON2).
  - Implementar un mecanismo de actualización transparente (auto-upgrade) en el login (`login.php`), detectando si el usuario autenticado tiene el formato antiguo en MD5 y rehash de la contraseña con `password_hash()`.

### B. Uso de Extensiones y Funciones MySQL Obsoletas (`mysql_*`)
- **Estado actual**: Múltiples scripts hacen uso directo de las funciones `mysql_query()`, `mysql_real_escape_string()`, etc., las cuales fueron eliminadas en PHP 7.0+.
- **Riesgo**: Falta de soporte en versiones modernas de PHP y mayor probabilidad de vulnerabilidades de inyección SQL si no se escapan rigurosamente todos los parámetros.
- **Mejora propuesta**:
  - Migrar la capa de base de datos a `PDO` o `mysqli` utilizando sentencias preparadas (prepared statements).

### C. Vulnerabilidades Potenciales de XSS (Cross-Site Scripting)
- **Estado actual**: En varios puntos de la aplicación se imprimen variables de entrada directamente o sin el debido escape HTML contextual.
- **Mejora propuesta**:
  - Sanitizar exhaustivamente toda salida mediante `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')`.

---

## 2. Compatibilidad y Mantenibilidad del Código

### A. Librerías Externas Desactualizadas
- **Estado actual**: El proyecto incluye una versión antigua de `PHPMailer` directamente en el repositorio (`Website/phpmailer/`).
- **Mejora propuesta**:
  - Actualizar `PHPMailer` a la versión más reciente mediante Composer y gestionar dependencias de forma estándar (`composer.json`).

### B. Separación de Lógica de Negocio y Presentación
- **Estado actual**: Los archivos PHP combinan consultas SQL, lógica de sesión, manipulaciones de datos y renderizado HTML en los mismos archivos.
- **Mejora propuesta**:
  - Reestructurar el código siguiendo una arquitectura en capas (por ejemplo, MVC o controladores/servicios independientes).

---

## 3. Estado de Aplicación de Mejoras

Se ha seleccionado e implementado la **Mejora 1.A: Modernización del Sistema de Hashing de Contraseñas y Auto-Upgrade**.

### Cambios realizados:
1. `Website/registrierung.php`: Almacenamiento de contraseñas de nuevos usuarios mediante `password_hash($password, PASSWORD_DEFAULT)`.
2. `Website/passwort_vergessen.php`: Almacenamiento de contraseñas restablecidas mediante `password_hash($newpw, PASSWORD_DEFAULT)`.
3. `Website/einstellungen.php`: Verificación de contraseña anterior compatible con `password_verify()` y fallback a MD5 legacy, actualizando la nueva contraseña con `password_hash()`.
4. `Website/login.php`: Autenticación dual que soporta contraseñas con `password_verify()` y contraseñas legacy con MD5 salted, actualizando automáticamente el hash en base de datos al iniciar sesión exitosamente.
