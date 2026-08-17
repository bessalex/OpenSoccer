# OpenSoccer - Guía Técnica y Funcional del Juego

Documentación integral en español sobre las características técnicas, requisitos de infraestructura (hardware y software) y la arquitectura funcional del juego de gestión de fútbol online **OpenSoccer**.

---

## Índice
1. [Visión General](#1-visión-general)
2. [Requisitos Técnicos](#2-requisitos-técnicos)
   - [A. Requisitos de Hardware](#a-requisitos-de-hardware)
   - [B. Requisitos de Software](#b-requisitos-de-software)
   - [C. Guía de Instalación y Configuración](#c-guía-de-instalación-y-configuración)
   - [D. Configuración Completa de Cronjobs](#d-configuración-completa-de-cronjobs)
3. [Análisis Funcional y Construcción de Personajes (Jugadores)](#3-análisis-funcional-y-construcción-de-personajes-jugadores)
   - [A. Estructura y Atributos del Jugador](#a-estructura-y-atributos-del-jugador)
   - [B. Creación de Jugadores y Cantera](#b-creación-de-jugadores-y-cantera)
   - [C. Evolución, Desarrollo y Envejecimiento](#c-evolución-desarrollo-y-envejecimiento)
   - [D. Dinámicas de "Personalidad", Psicología y Moral](#d-dinámicas-de-personalidad-psicología-y-moral)
   - [E. Sistema Táctico y Gestión en el Campo](#e-sistema-táctico-y-gestión-en-el-campo)
4. [Ecosistema de Gestión del Club y Mánager](#4-ecosistema-de-gestión-del-club-y-mánager)
   - [A. Infraestructura del Estadio](#a-infraestructura-del-estadio)
   - [B. Economía y Finanzas](#b-economía-y-finanzas)
   - [C. Mercado de Fichajes y Cesiones](#c-mercado-de-fichajes-y-cesiones)
   - [D. Sistema de Competiciones y Licencias](#d-sistema-de-competiciones-y-licencias)

---

## 1. Visión General

**OpenSoccer** es un simulador web multi-usuario de gestión futbolística (*Online Soccer Manager*) escrito en PHP y MySQL. Permite a los usuarios asumir el rol de mánager general de un club de fútbol, gestionando desde alineaciones tácticas y renovaciones de contratos hasta la ampliación del estadio, patrocinadores, finanzas y mercado de transferencias en tiempo real.

El proyecto cuenta con doble interfaz adaptable:
* **Escritorio (`www`):** Panel completo con todas las herramientas de gestión.
* **Móvil (`m`):** Interfaz simplificada optimizada para teléfonos móviles.

---

## 2. Requisitos Técnicos

### A. Requisitos de Hardware

Los requerimientos de hardware varían según la escala de usuarios activos concurrentes y la frecuencia de ejecución de los procesos en segundo plano (*cronjobs*).

#### 1. Servidor de Pruebas / Desarrollo / Uso Pequeño (< 100 usuarios)
* **CPU:** 1 vCPU (2.0 GHz o superior).
* **RAM:** 1 GB - 2 GB.
* **Almacenamiento:** 10 GB SSD / NVMe.
* **Red:** Conexión de 100 Mbps.

#### 2. Servidor de Producción Escalado (1.000 a 10.000+ usuarios)
* **CPU:** 2 a 4 vCPUs (3.0 GHz+ por núcleo).
* **RAM:** 4 GB - 8 GB RAM (necesario para el almacenamiento en caché del servidor MySQL y ejecución fluida de simulaciones).
* **Almacenamiento:** 25 GB - 50 GB SSD / NVMe con alta velocidad de IOPS (lectura/escritura intensiva durante simulaciones de jornadas).
* **Red:** Conexión de 1 Gbps con baja latencia.

```
+-----------------------------------------------------------------------+
|                        ARQUITECTURA DE HARDWARE                       |
+-----------------------------------------------------------------------+
|                                                                       |
|   [ Cliente Web / Móvil ] <---> [ Servidor HTTP (Apache/Nginx) ]      |
|                                                |                      |
|                                                v                      |
|                                   [ Motor PHP (FPM / ModPHP) ]        |
|                                                |                      |
|                                                v                      |
|                                   [ Base de Datos MySQL / MariaDB ]   |
|                                                ^                      |
|                                                |                      |
|                                   [ Tareas Programadas Cron (21) ]    |
+-----------------------------------------------------------------------+
```

---

### B. Requisitos de Software

* **Servidor Web:** Apache 2.4+ (con módulo `mod_rewrite` activado) o Nginx.
  * Requiere soporte de subdominios (`www` y `m`).
* **Lenguaje de Programación:** PHP 5.6, PHP 7.x o PHP 8.x con las siguientes extensiones instaladas y habilitadas:
  * `php-gettext` / `gettext`: Para la localización e internacionalización de textos (i18n).
  * `php-intl` / `intl`: Para formateo de fechas y moneda internacional.
  * `php-mysqli` / `pdo_mysql`: Para la conexión con la base de datos MySQL.
  * `php-json`: Para la gestión de respuestas AJAX.
  * `php-mbstring`: Para procesamiento de caracteres UTF-8.
* **Base de Datos:** MySQL 5.6+ / MariaDB 10.1+.
  * Cotejamiento (*Collation*): `utf8_general_ci` o `utf8mb4_unicode_ci`.
  * Permisos del usuario DB: `SELECT`, `INSERT`, `UPDATE`, `DELETE`, `DROP`.
* **Permisos de Archivos:**
  * El directorio `Website/cache/` requiere permisos de escritura para el servidor web (`0775` o `0777`).

---

### C. Guía de Instalación y Configuración

1. **Subir archivos:** Copiar la carpeta `Website/` al directorio raíz del servidor web.
2. **Configurar subdominios:** Apuntar `www.tudominio.com` y `m.tudominio.com` al directorio de la aplicación.
3. **Crear la Base de Datos:** Crear una base de datos MySQL vacía con cotejamiento `utf8_general_ci`.
4. **Importar la estructura y datos iniciales:**
   * Ejecutar el script SQL `Database/STRUCTURE.sql`.
   * Ejecutar el script SQL `Database/DATA.sql`.
5. **Configurar el entorno:**
   * Copiar `Website/config.example.php` a `Website/config.php`.
   * Editar los parámetros de conexión MySQL (`CONFIG_DB_HOST`, `CONFIG_DB_USER`, `CONFIG_DB_PASS`, `CONFIG_DB_NAME`) y credenciales del sitio.
6. **Cambiar contraseña de administración:** Cambiar la clave del usuario por defecto (`Admin` / `admin`).
7. **Programar los Cronjobs:** Configurar la ejecución de las 21 tareas automatizadas.

---

### D. Configuración Completa de Cronjobs

El juego utiliza 21 scripts en segundo plano para procesar la simulación de partidos, el mercado de transferencias, la economía y la evolución de los jugadores.

| Archivo | Frecuencia de Ejecución | Descripción Funcional |
| :--- | :--- | :--- |
| `aa_spieltag_simulation.php` | Cada minuto (horas 10-11, 14-15, 18-19, 22-23) | Simula los partidos en directo y calcula los resultados. |
| `aa_tabellen_berechnen.php` | Cada 2 minutos (horas 16-17) | Recalcula la tabla de clasificación y estadísticas. |
| `aa_marktwert_berechnen.php` | Cada 5 minutos | Actualiza el valor de mercado de los jugadores. |
| `aa_multi_detect.php` | Cada 5 minutos | Algoritmo anti-trampas para detectar cuentas múltiples por IP/Hash. |
| `aa_team_staerke_berechnen.php` | Cada 5 minutos | Recalcula la fuerza global del equipo en función del 11 inicial. |
| `aa_buchungenBuffer.php` | Cada 10 minutos (excepto horas de simulación) | Procesa las transacciones bancarias bufferizadas. |
| `aa_computer_managen.php` | Cada 10 minutos | Gestión automatizada de equipos controlados por la IA. |
| `aa_npc_transfermarkt.php` | Cada 15 minutos | Gestión de ofertas y actividad de la IA en el mercado. |
| `aa_praemienAbrechnung.php` | Cada 15 minutos | Procesa el cobro de primas por partido disputado. |
| `aa_spieler_verbesserung.php` | Cada 15 minutos | Procesa el entrenamiento, mejora y declive de jugadores. |
| `aa_cup_auslosen.php` | Cada 30 minutos | Sorteo automatizado de rondas de la Copa Internacional. |
| `aa_spieler_erzeugen.php` | Cada 30 minutos | Genera nuevos jugadores juveniles en las canteras de los clubes. |
| `aa_entlassungen.php` | Cada hora | Procesa las rescisiones de contrato y desempleo. |
| `aa_pokal_auslosen.php` | Cada 6 horas | Sorteo de las fases de la Copa Nacional. |
| `aa_tv_einnahmen.php` | Cada 6 horas | Ingreso de derechos televisivos a las arcas del club. |
| `aa_db_analyse.php` | Cada día | Optimización y análisis de rendimiento de tablas MySQL. |
| `aa_gehaelter_abbuchen.php` | Cada día | Pago de salarios a la plantilla y cuerpo técnico. |
| `aa_lotto.php` | Cada día | Sorteo del juego de lotería oficial. |
| `aa_saisonende.php` | Cada día (a las 22:00) | Finalización de temporada, ascensos, descensos y premios. |
| `aa_spielplan_erstellen.php` | Cada día (a las 23:00) | Generación del calendario de la próxima temporada. |
| `aa_stadion_kosten.php` | Cada día (a las 23:00) | Cobro de mantenimiento del estadio y sus comercios. |

---

## 3. Análisis Funcional y Construcción de Personajes (Jugadores)

En OpenSoccer, cada **Jugador** (`man_spieler`) es un personaje completo con atributos físicos, tácticos, económicos y psicológicos.

```
+------------------------------------------------------------------------+
|                      ESTRUCTURA DEL JUGADOR (PERSONAJE)                |
+------------------------------------------------------------------------+
|                                                                        |
|  [ DATOS PERSONALES ]  --->  Nombre, Apellido, Edad (wiealt)           |
|  [ POSICIÓN TÁCTICA ]  --->  Portero (T), Defensa (A), Medio (M),      |
|                              Delantero (S)                             |
|  [ ATRIBUTOS CLAVE  ]  --->  Fuerza (staerke), Talento (talent)        |
|  [ ESTADO FÍSICO    ]  --->  Frescura (frische), Lesión (verletzung)   |
|  [ ESTADO PSÍQUICO  ]  --->  Moral (moral)                             |
|  [ CONTRATO Y VALOR ]  --->  Salario, Valor Mercado, Vencimiento       |
|                                                                        |
+------------------------------------------------------------------------+
```

---

### A. Estructura y Atributos del Jugador

Los personajes quedan registrados en la tabla SQL `man_spieler`. Sus atributos clave son:

| Campo SQL | Atributo Funcional | Tipo de Dato / Rango | Descripción |
| :--- | :--- | :--- | :--- |
| `position` | Posición en el campo | `T`, `A`, `M`, `S` | **T** (Torwart / Portero), **A** (Abwehr / Defensa), **M** (Mittelfeld / Medio), **S** (Sturm / Delantero). |
| `wiealt` | Edad | Entero (días) | Edad medida en días (365 días = 1 año de juego). |
| `staerke` | Fuerza / Nivel | Decimal (`0.1` a `10.0`) | Nivel actual de rendimiento del jugador. |
| `talent` | Talento / Potencial | Decimal (`0.1` a `10.0`) | Techo máximo de fuerza al que puede aspirar mediante entrenamiento. |
| `frische` | Frescura Física | Entero (`0` a `100`) | Condición física para competir. Cae tras jugar partidos. |
| `moral` | Moral / Estado Anímico | Decimal (`0.00` a `100.00`) | Nivel de motivación psicológica del personaje. |
| `marktwert` | Valor de Mercado | Entero (€) | Valor económico calculado automáticamente según fuerza, edad y talento. |
| `gehalt` | Salario | Entero (€) | Sueldo cobrado por el jugador en el pago diario de salarios. |
| `vertrag` | Duración del Contrato | Timestamp | Fecha en que vence la vinculación contractual con el club. |
| `leiher` | Club de Cesión | Cadena | Indica si el jugador está cedido a préstamo a otro equipo. |
| `verletzung` | Estado de Lesión | Entero (días) | Cantidad de días restantes de baja por lesión física. |

---

### B. Creación de Jugadores y Cantera

Los jugadores no se construyen desde un editor manual de estadísticas arbitrarias, sino que nacen mediante dos vías del ecosistema:

1. **Centro de Formación de Jóvenes / Cantera (`jugendarbeit`):**
   * El mánager invierte en el nivel de su cantera (Nivel 1 a 5).
   * El script `aa_spieler_erzeugen.php` genera de forma periódica jugadores jóvenes (17-19 años) basándose en el nivel del centro:

| Nivel de Cantera | Talento Mínimo | Talento Máximo | Salario Base |
| :---: | :---: | :---: | :---: |
| **Nivel 1** | 2.1 | 5.9 | 300.000 € |
| **Nivel 2** | 2.8 | 6.9 | 500.000 € |
| **Nivel 3** | 3.5 | 7.9 | 700.000 € |
| **Nivel 4** | 4.2 | 8.9 | 900.000 € |
| **Nivel 5** | 4.9 | 9.9 | 1.200.000 € |

* **Distribución aleatoria de posiciones:**
  * Portero (`T`): 12% de probabilidad.
  * Delantero (`S`): 24% de probabilidad.
  * Centrocampista (`M`): 50% de probabilidad.
  * Defensa (`A`): 14% restante.

2. **Mercado de Fichajes y Generación Inicial:**
   * La base de datos incluye un *pool* de nombres y apellidos por país (`man_vNamePool`) para preservar la identidad cultural del jugador.

---

### C. Evolución, Desarrollo y Envejecimiento

El desarrollo del personaje está gobernado por el script `aa_spieler_verbesserung.php`:

* **Progreso de Jóvenes (Menores de 31 años / < 11.315 días):**
  * Progresan acumulando partidos jugados (`spiele_gesamt > 8`).
  * Incremento de fuerza: $\text{Plus} = \frac{\text{Zufall}}{\text{Stärke}} \times 5$
  * El incremento de fuerza nunca puede superar el techo fijado por su **Talento** (`talent`).
* **Declive por Edad (A partir de los 31 años / $\ge$ 11.315 días):**
  * Los veteranos pierden fuerza progresivamente debido al envejecimiento.
  * Pérdida calculada con la fórmula:
    $$\text{Pérdida} = \frac{\lfloor \text{Edad}/365 - 28 \rfloor}{70} \times \text{Stärke}$$

---

### D. Dinámicas de "Personalidad", Psicología y Moral

La **Moral** (`moral`) actúa como el rasgo psicológico y de "personalidad" del personaje dentro del simulador. Determina la felicidad, la disposición para renovar contrato y el rendimiento sobre el terreno de juego.

```
+-----------------------------------------------------------------------+
|                    DINÁMICA DE MORAL DEL JUGADOR                      |
+-----------------------------------------------------------------------+
|                                                                       |
|   (+) Jugar Partido Oficial (+1.0 a +1.8 moral)                       |
|   (+) Renovación de Contrato (+5.0 a +10.0 moral)                     |
|   (+) Sesión con el Psicólogo (+5 a +10 moral)                        |
|                                                                       |
|   (-) Pérdida diaria natural (-1.0 moral)                             |
|   (-) Pagar lista de transferencias (-10.0 a -15.0 moral)             |
|   (-) Estar suplente de forma prolongada                              |
|                                                                       |
+-----------------------------------------------------------------------+
```

#### Factores que afectan la Moral del Jugador:
1. **Desgaste Diario:** Todos los días, el jugador pierde un 1% de moral de manera natural.
2. **Participación en Partidos:**
   * Jugar un partido oficial aumenta la moral en **+1.8 puntos** si el equipo gana o empata, y **+1.0 puntos** por participación general.
3. **Puesta en Venta / Cesión:**
   * Ofrecer un jugador en el mercado de transferencias reduce de inmediato su moral entre **-10% y -15%** (se siente descartado).
4. **Renovación de Contratos:**
   * Extender el contrato aumenta la moral entre **+5% y +10%** dependiendo de la duración pactada. Si un jugador tiene baja moral, puede **rechazar** firmar un nuevo contrato hasta que su actitud mejore.
5. **Cuerpo Técnico Remunerado (`ver_personal.php`):**
   * **Psicólogo (`letzte_psychologe`):** El mánager puede contratar sesiones para motivar al equipo y subir la moral general.
   * **Fisioterapeuta / Entrenador de Fitness (`letzte_regeneration`):** Permite recuperar la **Frescura** (`frische`) para prevenir lesiones.

---

### E. Sistema Táctico y Gestión en el Campo

El rendimiento final de cada personaje durante los partidos depende de su combinación de **Fuerza**, **Frescura**, **Moral** y la configuración táctica dictada en `aufstellung.php` y `taktik.php`.

#### Parámetros Tácticos Colectivos (`man_taktiken`):
* **Orientación (`ausrichtung`):** Ultradefensiva, Defensiva, Normal, Ofensiva, Ultraofensiva.
* **Velocidad de Juego (`geschw_auf`):** Pausado, Normal, Rápido.
* **Pases (`pass_auf`):** En corto, Mixto, Pases largos.
* **Riesgo en Pases (`risk_pass`):** Conservador, Normal, Arriesgado.
* **Presión (`druck`):** Propio campo, Medio campo, Presión alta.
* **Agresividad (`aggress`):** Suave, Normal, Duro (afecta riesgo de tarjetas y faltas).
* **Esfuerzo (`einsatz`):** Nivel de compromiso del equipo (50% a 100%). Un mayor esfuerzo incrementa el rendimiento pero agota aceleradamente la frescura.

---

## 4. Ecosistema de Gestión del Club y Mánager

### A. Infraestructura del Estadio

El estadio del equipo (`man_stadien`) no es solo la sede de los partidos, sino una fuente vital de ingresos que requiere mantenimiento diario.

```
+-----------------------------------------------------------------------+
|                    INFRAESTRUCTURA DEL ESTADIO                        |
+-----------------------------------------------------------------------+
|                                                                       |
|  [ Aforo y Asientos ] ----> Entradas / Taquilla                       |
|  [ Conexiones ]      ----> Aparcamiento (Parkplatz), Metro (U-Bahn)   |
|  [ Hostelería ]      ----> Restaurante, Pizzería, Puesto de Salchichas|
|  [ Club & Fans ]     ----> Museo del Club, Tienda Oficial (Fanshop)   |
|                                                                       |
+-----------------------------------------------------------------------+
```

* **Gestión de Entradas:** Configuración de aforo y precio de la entrada por partido.
* **Edificios y Comercios Complementarios:**
  * Transporte: *Parkplatz* (Aparcamiento), *U-Bahn* (Estación de Metro).
  * Gastronomía: *Restaurant*, *Bierzelt* (Carpa de cerveza), *Pizzeria*, *Imbissstand* (Puesto de comida rápida).
  * Cultura y Merchandising: *Vereinsmuseum* (Museo), *Fanshop* (Tienda oficial).
* **Costes Mantenimiento:** El script `aa_stadion_kosten.php` deduce diariamente los costes operacionales de cada edificio.

---

### B. Economía y Finanzas

Cada club dispone de una cuenta bancaria (`konto` en `man_teams`) con contabilidad detallada en `man_buchungen`.

* **Ingresos:**
  * Venta de entradas y comercios del estadio.
  * Patrocinador principal (`sponsoren.php`): Prima fija por temporada más primas por punto/partido ganado.
  * Derechos de Televisión (`aa_tv_einnahmen.php`): Calculados según la categoría de la liga y clasificación.
  * Lotería Oficial (`ver_lotto.php`): Bote acumulativo diario.
* **Gastos:**
  * Masa salarial diaria de jugadores y técnicos (`aa_gehaelter_abbuchen.php`).
  * Mantenimiento de estadio y cantera.
  * Fichajes y primas por partido (`praemieProEinsatz`).

---

### C. Mercado de Fichajes y Cesiones

El mercado (`transfermarkt.php`) permite negociar jugadores entre clubes controlados por humanos o la IA:

1. **Compra Directa y Subasta:**
   * Subastas con fecha de cierre determinada (`ende`).
   * Posibilidad de fijar cláusula de **Compra Inmediata** (`sofortkauf`).
2. **Préstamos / Cesiones:**
   * Cesión con prima por partido jugado (`praemieProEinsatz`). El equipo receptor paga una comisión al propietario cada vez que el jugador cedido salta al campo.
3. **Mecanismos Anti-Fraude:**
   * El cronjob `aa_multi_detect.php` rastrea direcciones IP, cookies y *hashes* únicos para evitar ventas fraudulentas entre cuentas del mismo usuario.

---

### D. Sistema de Competiciones y Licencias

* **Competiciones Disponibles:**
  * **Liga Regular:** Estructura piramidal con ascensos y descensos automáticos al cierre de temporada (`aa_saisonende.php`).
  * **Copa Nacional (*Pokal*):** Eliminatorias directas entre clubes del mismo país.
  * **Copa Internacional (*Cup*):** Torneo continental entre los mejores clasificados.
  * **Partidos Amistosos (*Testspiele*):** Partidos de preparación pactados entre mánagers.
* **Licencia de Mánager (`man_licenseTasks`):**
  * Sistema de misiones guiadas para nuevos usuarios que desbloquea funciones avanzadas a medida que completan tareas formativas en el juego.
