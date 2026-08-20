# Análisis y Propuesta de Diseño: Sistema de Batalla por Zonas y Cuadrantes para OpenSoccer

## 1. Resumen Ejecutivo
El presente documento propone la evolución del motor de simulación de partidos y del modelo de jugadores para **OpenSoccer**, transicionando de un sistema estadístico global a un **Sistema Dinámico de Control Espacial y Batalla por Cuadrantes** inspirado en análisis tácticos modernos y mapas de calor (similares a los reportes de *Sofascore*).

La nueva lógica introduce:
1. **5 Atributos Clave de Perfil de Jugador** (Ataque, Creación, Técnica, Táctica y Defensa) complementarios a la Fuerza (`staerke`) y Talento (`talent`) existentes.
2. **Matriz Espacial de Cancha de 16 Cuadrantes Tácticos + Zona Especial de Arquero**.
3. **Radio de Influencia Decreciente**, que considera la posición principal, secundaria, perfil/pierna hábil y adyacencia de cuadrantes.
4. **Líneas Tácticas y Desplazamiento Dinámico** del centro de gravedad del equipo según 5 mentalidades tácticas (Ultra Defensiva a Ultra Ofensiva).
5. **Algoritmo Probabilístico de Batalla por Zonas** y degradación física (*Frische* / Cansancio).

---

## 2. Integración de Nuevos Atributos de Jugador (Radar Sofascore)

En la actualidad, OpenSoccer evalúa a los jugadores mediante un valor general de Fuerza (`staerke`) y Talento (`talent`). Para dar profundidad táctica a las batallas por zona, se proponen **5 atributos específicos** (puntuados de 1 a 99) visibles en la ficha del jugador:

| Atributo | Código | Descripción en el Juego | Impacto Principal en la Batalla por Cuadrantes |
| :--- | :--- | :--- | :--- |
| **Ataque** | `ATT` | Capacidad de remate, desmarque y definición. | Aumenta el poder de combate ofensivo en el área rival y cuadrantes de definición. |
| **Creación** | `CRE` | Visión de juego, pase filtrado y creatividad. | Genera opciones de progresión hacia cuadrantes adyacentes avanzados. |
| **Técnica** | `TEC` | Control del balón, regate y precisión en espacios reducidos. | Mantiene la posesión en cuadrantes con alta densidad de rivales. |
| **Táctica** | `TAC` | Posicionamiento, cobertura, lecturas de interceptación y disciplina. | Amplía el radio de influencia a cuadrantes circundantes e incrementa robos sin falta. |
| **Defensa** | `DEF` | Entradas, cuerpo a cuerpo, juego aéreo defensivo y despejes. | Domina las disputas físicas y recuperaciones en cuadrantes defensivos y de contención. |

### Relación entre `staerke` y Atributos Específicos
Para mantener la compatibilidad hacia atrás con el código base de OpenSoccer, la fuerza general (`staerke`, de 1.0 a 9.9) se calcula como la media ponderada de los 5 atributos según la posición natural del jugador.
$$\text{Fuerza General (Staerke)} = \frac{\sum (\text{Atributo}_i \times \text{Peso}_i)}{10}$$

---

## 3. Matriz Espacial de la Cancha y Cuadrantes Tácticos

La cancha se divide en **4 líneas transversales** que contienen un total de **16 cuadrantes de campo** más **1 zona exclusiva de meta**:

```
+-----------------------------------------------------------------------+
|                       ZONA DE META (ARQUERO)                          |
+---------------+---------------+---------------+---------------+-------+
| LAT IZQ (DEF) | CENTR IZQ(DEF)|  LIBERO (DEF) | CENTR DER(DEF)|LAT DER| (5)
+---------------+---------------+---------------+---------------+-------+
| MED IZQ (MED) | MED C.IZQ(MED)|  MEDIO CENTRO | MED C.DER(MED)|MED DER| (5)
+---------------+---------------+---------------+---------------+-------+
|               | INT IZQ (ENG) | ENGANCHE (ENG)| INT DER (ENG) |       | (3)
+---------------+---------------+---------------+---------------+-------+
|               | EXTREMO IZQ   | CENTRO DELANT.| EXTREMO DER   |       | (3)
+---------------+---------------+---------------+---------------+-------+
```

### Detalle de Distribución de los 16 Cuadrantes:
1. **Línea Defensiva (5 Cuadrantes):**
   - Lateral Izquierdo (`LAT_IZQ`)
   - Central Izquierdo (`CENTR_IZQ`)
   - LÍBERO / Central Cero (`LIBERO`)
   - Central Derecho (`CENTR_DER`)
   - Lateral Derecho (`LAT_DER`)
2. **Línea de Mediocampo Bajo/Contención (5 Cuadrantes):**
   - Medio Izquierdo (`MED_IZQ`)
   - Medio Centro Izquierdo (`MED_C_IZQ`)
   - Medio Centro (`MEDIO_CENTRO`)
   - Medio Centro Derecho (`MED_C_DER`)
   - Medio Derecho (`MED_DER`)
3. **Línea de Mediocampo Alto / Enganche (3 Cuadrantes):**
   - Interior / Volante Izquierdo (`INT_IZQ`)
   - Enganche / Mediapunta (`ENGANCHE`)
   - Interior / Volante Derecho (`INT_DER`)
4. **Línea de Delantera / Ataque (3 Cuadrantes):**
   - Extremo Izquierdo (`EXTREMO_IZQ`)
   - Centro Delantero (`CENTRO_DELANTERO`)
   - Extremo Derecho (`EXTREMO_DER`)
5. **Zona Especial de Portería (`ZONA_ARQUERO`):**
   - Injerencia exclusiva del Guardameta (100% de eficacia en atajadas e interceptaciones de área chica/grande).

---

## 4. Injerencia y Porcentaje de Control por Jugador

Un jugador de campo desplegado en el 11 titular ocupa un **cuadrante asignado (Cuadrante Principal)**, pero proyecta presencia sobre los **cuadrantes adyacentes (circundantes)**.

### 4.1. Factores de Ponderación de Influencia

La influencia efectiva $I(j, q)$ del jugador $j$ sobre el cuadrante $q$ se determina por:

$$I(j, q) = P(j, q) \times F_{\text{pos}} \times F_{\text{pie}} \times F_{\text{frische}}$$

Donde:

1. **Grado de Proximidad $P(j, q)$:**
   - **Cuadrante Principal (100%):** $P = 1.00$
   - **Cuadrante Adyacente Directo (Ortogonal: Norte, Sur, Este, Oeste):** $P = 0.45$
   - **Cuadrante Adyacente Diagonal:** $P = 0.25$
   - **Cuadrantes Lejanos:** $P = 0.00$

2. **Ajuste por Posición ($F_{\text{pos}}$):**
   - **Posición Natural / Ideal:** $1.00$
   - **Posición Secundaria:** $0.85$ (ej. Central jugando de Lateral).
   - **Fuera de Posición:** $0.60$ (ej. Delantero jugando de Central).

3. **Perfil y Pierna Hábil ($F_{\text{pie}}$):**
   - **Ambas Piernas / Ambidiestro:** $1.00$ en cualquier banda.
   - **Perfil Natural (ej. Zurdo en banda izquierda):** $1.00$
   - **Perfil Cambiado / Perfil Inverso:** $0.90$ para pases/centros, pero $+0.05$ de bono de tiro hacia el centro (recorte).
   - **Pierna Inhábiles en Banda Opuesta:** $0.75$

4. **Condición Física / Frische ($F_{\text{frische}}$):**
   $$F_{\text{frische}} = \frac{\text{Frische Actual}}{100}$$
   A medida que la *Frische* disminuye durante el partido (de 100 a 0), la presencia física en los cuadrantes circundantes cae proporcionalmente.

---

## 5. Desplazamiento Táctico de Líneas (Actitud del Equipo)

La táctica del equipo desplaza la distribución espacial de los jugadores en la matriz de la cancha:

```
[ULTRA DEFENSIVA]  ==> Retraso de 1.5 cuadrantes. Concentración masiva en zona defensiva.
[DEFENSIVA]        ==> Retraso de 0.8 cuadrantes.
[NORMAL]           ==> Posicionamiento equilibrado estándar.
[OFENSIVA]         ==> Adelanto de 0.8 cuadrantes.
[ULTRA OFENSIVA]   ==> Adelanto de 1.5 cuadrantes. Presión alta en territorio rival.
```

### Tabla de Modificadores por Actitud Táctica:

| Actitud Táctica | Presencia en Cuadrantes Propios | Presencia en Cuadrantes Rival | Radio de Presión Adyacente | Riesgo de Espaldas |
| :--- | :--- | :--- | :--- | :--- |
| **Ultra Defensiva** | **+40%** | **-50%** | Corto (Bloque bajo) | Muy Bajo |
| **Defensiva** | **+20%** | **-20%** | Estándar | Bajo |
| **Normal** | **0%** | **0%** | Estándar | Normal |
| **Ofensiva** | **-20%** | **+20%** | Extendido | Moderado |
| **Ultra Ofensiva** | **-40%** | **+40%** | Asfixiante (Alta) | Alto (Contraataque) |

---

## 6. Algoritmo de Batalla por Zonas y Simulación de Duelos

Cuando el balón entra en un cuadrante $q$, el motor calcula el **Dominio Espacial ($D$)** de cada equipo (Equipo A vs. Equipo B):

$$D_A(q) = \sum_{j \in \text{Equipo A}} I(j, q) \times \left( \text{TAC}_j \times 0.4 + \text{TEC}_j \times 0.3 + \text{DEF}_j \times 0.3 \right)$$

$$D_B(q) = \sum_{k \in \text{Equipo B}} I(k, q) \times \left( \text{TAC}_k \times 0.4 + \text{TEC}_k \times 0.3 + \text{DEF}_k \times 0.3 \right)$$

### Resolución de la Batalla en el Cuadrante:
1. **Probabilidad de Ganar el Duelo:**
   $$P(\text{Ganar}_A) = \frac{D_A(q)}{D_A(q) + D_B(q)}$$
2. **Acciones Posteriores según Atributo Dominante:**
   - Si la batalla la gana un jugador de **alto `CRE`**, se ejecuta un pase en profundidad saltando líneas hacia un cuadrante ofensivo.
   - Si gana un jugador con **alto `TEC`**, mantiene el balón y progresa al siguiente cuadrante.
   - Si la disputa es defensiva y gana un jugador con **alto `DEF`**, se produce una recuperación o despeje.
   - En caso de **alta agresividad táctica** (`aggress`), se incrementa la probabilidad de recuperación pero aumenta el riesgo de falta/tarjeta.

---

## 7. Propuesta de Implementación para OpenSoccer

### 7.1. Esquema de Base de Datos (Extensión de `man_spieler`)

Se agregarán las siguientes columnas a la tabla `man_spieler`:

```sql
ALTER TABLE `man_spieler`
  ADD COLUMN `att` tinyint(3) unsigned NOT NULL DEFAULT '50' AFTER `staerke`,
  ADD COLUMN `cre` tinyint(3) unsigned NOT NULL DEFAULT '50' AFTER `att`,
  ADD COLUMN `tec` tinyint(3) unsigned NOT NULL DEFAULT '50' AFTER `cre`,
  ADD COLUMN `tac` tinyint(3) unsigned NOT NULL DEFAULT '50' AFTER `tec`,
  ADD COLUMN `def` tinyint(3) unsigned NOT NULL DEFAULT '50' AFTER `tac`,
  ADD COLUMN `posicion_secundaria` char(1) NOT NULL DEFAULT '' AFTER `position`,
  ADD COLUMN `pierna_habil` enum('Der','Izq','Ambas') NOT NULL DEFAULT 'Der' AFTER `posicion_secundaria`;
```

### 7.2. Estrategia en la Simulación (`aa_spieltag_simulation.php`)

1. Al inicio del minuto de juego, construir la **Matriz de Control Espacial 16x2** (16 cuadrantes x 2 equipos).
2. Determinar la ubicación del balón en la grilla según la jugada anterior.
3. Ejecutar la función `resolverBatallaCuadrante($cuadrante, $equipoA, $equipoB, $actitudA, $actitudB)`.
4. Transicionar la pelota al cuadrante resultante y generar comentarios narrativos detallados para la transmisión en vivo.

---

## 8. Conclusión y Próximos Pasos

Esta propuesta dota a OpenSoccer de una simulación táctica moderna, realista y transparente para los usuarios. Permite a los mánagers configurar con precisión milimétrica el comportamiento de sus equipos, recompensando la planificación estratégica en función del rival y el manejo de los cuadrantes clave de la cancha.
