# Informe de Análisis de Código y Posibles Mejoras - OpenSoccer

Este documento contiene un análisis integral de las posibles mejoras en la base de código de **OpenSoccer** clasificadas por desactualización, seguridad, compatibilidad con versiones recientes de PHP, rendimiento y arquitectura.

---

## 1. Desactualización y Compatibilidad con PHP 8+

### A. Uso de la Extensión `mysql_*` (Eliminada en PHP 7.0+)
* **Diagnóstico:** El proyecto utiliza funciones nativas `mysql_connect`, `mysql_select_db`, `mysql_query`, `mysql_real_escape_string`, `mysql_fetch_assoc`, `mysql_num_rows`, `mysql_result`, etc., a lo largo de casi todos los scripts PHP de la carpeta `Website/`.
* **Impacto:** En PHP 7.0+ y PHP 8.x, estas funciones fueron completamente eliminadas. Ejecutar el sistema en PHP 8.3 genera errores fatales (*Fatal Error: Call to undefined function mysql_connect()*).
* **Solución recomendada:**
  1. Proveer un adaptador/polyfill de compatibilidad global basado en `mysqli` o `PDO` para evitar fallos fatales inmediatos.
  2. Migrar progresivamente todas las consultas de la base de datos hacia una capa de abstracción PDO con sentencias preparadas.

### B. Dependencias de Terceros Desactualizadas
* **PHPMailer (`Website/phpmailer/`):** Utiliza una versión heredada basada en sintaxis PHP 4/5 (`class.phpmailer.php` con PHPMailerAutoload).
* **Impacto:** Posibles advertencias de llamadas a métodos obsoletos (*Deprecation Warnings*) en PHP 8.x.
* **Solución recomendada:** Migrar la librería PHPMailer a la versión más reciente a través de Composer (`phpmailer/phpmailer`).

---

## 2. Seguridad

### A. Vulnerabilidades de Inyección SQL (SQL Injection)
* **Diagnóstico:** Las consultas SQL se construyen mediante concatenación directa de variables y uso de `mysql_real_escape_string` o `intval`.
* **Riesgo:** Si algún parámetro no pasa por `mysql_real_escape_string` o si se concatenan identificadores de tabla/columna dinámicamente, el sistema queda expuesto a inyecciones SQL.
* **Solución recomendada:** Reemplazar las consultas dinámicas por sentencias preparadas (*Prepared Statements*) con parámetros vinculados (*bound parameters*) mediante PDO o `mysqli_stmt`.

### B. Validación de Entradas y Prevención de XSS (Cross-Site Scripting)
* **Diagnóstico:** En varios archivos se utiliza `strip_tags()` y `trim()` de manera manual antes de procesar entradas de usuario.
* **Solución recomendada:** Utilizar funciones de filtrado nativas de PHP (`filter_input`, `filter_var`) y codificación de salida contextual (`htmlspecialchars($val, ENT_QUOTES, 'UTF-8')`).

### C. Protección contra CSRF (Cross-Site Request Forgery)
* **Diagnóstico:** Los formularios de acción (p. ej., ofertas en el mercado, cambios de táctica, transferencias) carecen de un token CSRF único por sesión.
* **Solución recomendada:** Implementar un middleware de verificación de tokens CSRF en las peticiones POST.

---

## 3. Calidad de Código y Arquitectura

### A. Ausencia de Carga Automática de Clases (Autoloading PSR-4)
* **Diagnóstico:** Uso de múltiples `include` / `require` manuales de archivos en la carpeta `classes/`.
* **Solución recomendada:** Configurar Composer o un autoloader PSR-4 para la carga automática de clases.

### B. Manejo Centralizado de Errores y Conexión
* **Diagnóstico:** La conexión a la base de datos y la función `reportError` se definen en `zzserver.php`.
* **Solución recomendada:** Encapsular la gestión de base de datos e historial de errores en una clase singleton o un contenedor de servicios.

---

## 4. Mejora Seleccionada e Implementada

Para resolver la incompatibilidad más urgente que impide la ejecución del juego en entornos PHP 8+, se ha diseñado e implementado una **capa de compatibilidad (Polyfill) transparente basada en `mysqli`** en el archivo principal `Website/zzserver.php`.

Esta mejora define las funciones `mysql_*` faltantes mapeándolas sobre un objeto `mysqli` global, permitiendo que todas las consultas del sistema funcionen sin modificar cientos de archivos individuales de forma disruptiva, restaurando la compatibilidad con PHP 8.x y mejorando la estabilidad del servidor.
