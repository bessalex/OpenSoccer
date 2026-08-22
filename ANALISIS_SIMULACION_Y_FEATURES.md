# Análisis Estadístico de Simulaciones de Juego, Determinación de Atributos y Propuesta de Nuevas Funcionalidades (Features)

Este documento presenta una guía técnica, matemática y funcional exhaustiva del simulador **OpenSoccer**. Se divide en dos partes fundamentales:
1. **Análisis Estadístico y Matemático del Motor del Juego:** Detalla los algoritmos de simulación de partidos, asignación de características, evolución de jugadores y cálculo de rendimiento.
2. **Propuesta y Diseño de Nuevas Funcionalidades (*Features*):** Ampliaciones estratégicas para potenciar la profundidad del desarrollo de jugadores y la gestión integral de los clubes.

---

## ÍNDICE
- [PARTE I: ANÁLISIS ESTADÍSTICO Y MATEMÁTICO DEL MOTOR DEL JUEGO](#parte-i-análisis-estadístico-y-matemático-del-motor-del-juego)
  - [1. Determinación de Atributos y Características del Jugador](#1-determinación-de-atributos-y-características-del-jugador)
    - [A. Estructura de Datos Base](#a-estructura-de-datos-base)
    - [B. Generación de Jugadores en Cantera (`aa_spieler_erzeugen.php`)](#b-generación-de-jugadores-en-cantera-aa_spieler_erzeugenphp)
    - [C. Evolución y Crecimiento de Jóvenes (`aa_spieler_verbesserung.php`)](#c-evolución-y-crecimiento-de-jóvenes-aa_spieler_verbesserungphp)
    - [D. Envejecimiento y Declive Físico/Técnico](#d-envejecimiento-y-declive-físicotécnico)
    - [E. Modelo Dinámico de Moral y Frescura Física](#e-modelo-dinámico-de-moral-y-frescura-física)
    - [F. Valor de Mercado y Salarios](#f-valor-de-mercado-y-salarios)
  - [2. Arquitectura Matemática de Simulación de Partidos (`aa_spieltag_simulation.php`)](#2-arquitectura-matemática-de-simulación-de-partidos-aa_spieltag_simulationphp)
    - [A. Fuerza Efectiva por Línea y Ponderación de Frescura](#a-fuerza-efectiva-por-línea-y-ponderación-de-frescura)
    - [B. Cálculo de Posesión de Balón](#b-cálculo-de-posesión-de-balón)
    - [C. Transformaciones Tácticas y Ponderación de Fuerza](#c-transformaciones-tácticas-y-ponderación-de-fuerza)
    - [D. Algoritmo Temporal y Distribución de Ataques ($N=20$)](#d-algoritmo-temporal-y-distribución-de-ataques-n20)
    - [E. Árbol Estocástico de Decisiones de Ataque](#e-árbol-estocástico-de-decisiones-de-ataque)
    - [F. Sistema de Evaluación Periódistica y Calificaciones ("Bolzblatt")](#f-sistema-de-evaluación-periódistica-y-calificaciones-bolzblatt)
    - [G. Prórroga y Tanda de Penaltis](#g-prórroga-y-tanda-de-penaltis)
- [PARTE II: PROPUESTA DE NUEVAS FUNCIONALIDADES (FEATURES)](#parte-ii-propuesta-de-nuevas-funcionalidades-features)
  - [3. Nuevas Funcionalidades para Jugadores](#3-nuevas-funcionalidades-para-jugadores)
    - [A. Desglose de Atributos Secundarios (Matriz de Habilidades)](#a-desglose-de-atributos-secundarios-matriz-de-habilidades)
    - [B. Sistema de Rasgos, Personalidad y Especialidades (Traits)](#b-sistema-de-rasgos-personalidad-y-especialidades-traits)
    - [C. Entrenamiento Posicional y Pie Hábil](#c-entrenamiento-posicional-y-pie-hábil)
    - [D. Historial Médico, Lesiones Recurrentes y Secuelas](#d-historial-médico-lesiones-recurrentes-y-secuelas)
    - [E. Sistema de Liderazgo, Jerarquía de Vestuario y Química de Equipo](#e-sistema-de-liderazgo-jerarquía-de-vestuario-y-química-de-equipo)
  - [4. Nuevas Funcionalidades para Clubes](#4-nuevas-funcionalidades-para-clubes)
    - [A. Cuerpo Técnico Especializado y Staff Multifuncional](#a-cuerpo-técnico-especializado-y-staff-multifuncional)
    - [B. Red Internacional de Scouting y Cantera Avanzada](#b-red-internacional-de-scouting-y-cantera-avanzada)
    - [C. Infraestructura Compleja e Inversiones Tecnológicas](#c-infraestructura-compleja-e-inversiones-tecnológicas)
    - [D. Patrocinadores Dinámicos por Objetivos y Reputación de Club](#d-patrocinadores-dinámicos-por-objetivos-y-reputación-de-club)
    - [E. Modo Torneos Propios y Filiales (Equipo B)](#e-modo-torneos-propios-y-filiales-equipo-b)

---

# PARTE I: ANÁLISIS ESTADÍSTICO Y MATEMÁTICO DEL MOTOR DEL JUEGO

---

## 1. Determinación de Atributos y Características del Jugador

### A. Estructura de Datos Base
Cada jugador en OpenSoccer está definido por un conjunto de parámetros numéricos clave almacenados en la tabla `man_spieler`:

| Atributo | Campo SQL | Rango | Descripción |
| :--- | :--- | :--- | :--- |
| **Posición** | `position` | `T`, `A`, `M`, `S` | Torwart (Portero), Abwehr (Defensa), Mittelfeld (Medio), Sturm (Delantero). |
| **Fuerza (Stärke)** | `staerke` | `0.1` a `10.0` | Nivel de habilidad global actual del jugador. |
| **Talento (Talent)** | `talent` | `0.1` a `10.0` | Límite máximo (techo) de fuerza alcanzable. |
| **Edad** | `wiealt` | Entero (Días) | Edad en días ($365\text{ días} = 1\text{ año}$). |
| **Frescura** | `frische` | `0` a `100` | Condición física disponible. |
| **Moral** | `moral` | `0.00` a `100.00` | Estado anímico y psicológico. |

---

### B. Generación de Jugadores en Cantera (`aa_spieler_erzeugen.php`)

Los jóvenes de la cantera se generan periódicamente según el nivel de inversión en infraestructura de juventud (`jugendarbeit` de nivel 1 a 5).

#### 1. Rango de Talento y Salario Base según Nivel de Cantera:
$$Talent_{min} \quad \text{y} \quad Talent_{max}$$

| Nivel Cantera (`jugendarbeit`) | $Talent_{min}$ | $Talent_{max}$ | Salario Inicial (€) |
| :---: | :---: | :---: | :---: |
| **Nivel 1** | 2.1 | 5.9 | 300.000 € |
| **Nivel 2** | 2.8 | 6.9 | 500.000 € |
| **Nivel 3** | 3.5 | 7.9 | 700.000 € |
| **Nivel 4** | 4.2 | 8.9 | 900.000 € |
| **Nivel 5** | 4.9 | 9.9 | 1.200.000 € |

#### 2. Función de Distribución Logarítmica del Talento:
Para evitar una distribución puramente uniforme y concentrar los talentos excepcionales en probabilidades más bajas, se emplea la función `getRandomStrength($min, $max)`:

$$\ln_{low} = \ln(Talent_{min}), \quad \ln_{high} = \ln(Talent_{max}), \quad \Delta_{\ln} = \ln_{high} - \ln_{low}$$

$$U \sim \text{Uniforme}(0, 1)$$

$$R = U^{\gamma} \cdot \Delta_{\ln} + \ln_{low} \quad (\text{donde el factor logarítmico } \gamma = 1.15)$$

$$Talento = \text{redondear}\left(e^{R}, 1\right)$$

#### 3. Determinación de la Fuerza Inicial:
La fuerza inicial ($Stärke_{init}$) se calcula multiplicando el talento generado por un factor aleatorio entre $0.5$ y $0.9$:

$$Factor_{init} = \text{getRandomStrength}(0.5, 0.9)$$

$$Stärke_{init} = \text{redondear}\left(Talento \cdot Factor_{init}, 1\right)$$

#### 4. Distribución Probabilística de Posiciones:
- **Portero (`T`):** 12%
- **Delantero (`S`):** 24%
- **Centrocampista (`M`):** 50%
- **Defensa (`A`):** 14%

---

### C. Evolución y Crecimiento de Jóvenes (`aa_spieler_verbesserung.php`)

El desarrollo ocurre cuando un jugador menor de 31 años ($\text{wiealt} < 11.315\text{ días}$) acumula más de 8 partidos jugados ($\text{spiele\_gesamt} > 8$).

#### Algoritmo de Incremento de Fuerza:
1. Se genera un valor aleatorio flotante $Z \in [0.1, 0.6]$:
   $$Z = \frac{\text{mt\_rand}(0, 5) + 1}{10}$$
2. Se calcula el incremento base $\Delta_{base}$ inversamente proporcional a la fuerza actual:
   $$\Delta_{base} = \left\lceil \left( \frac{Z}{Stärke_{actual}} \cdot 5 \right) \cdot 10 \right\rceil / 10$$
3. Se acota la mejora dentro del rango $[0.1, 1.2]$:
   $$\Delta_{efectivo} = \min(\max(\Delta_{base}, 0.1), 1.2)$$
4. **Techo de Talento:** Si $Stärke_{actual} + \Delta_{efectivo} > Talento$, la mejora se limita a:
   $$\Delta_{efectivo} = Talento - Stärke_{actual}$$
   Si $Stärke_{actual} = Talento$, el jugador alcanza su cénit y deja de crecer.

---

### D. Envejecimiento y Declive Físico/Técnico

A partir de los 31 años ($\text{wiealt} \ge 11.315\text{ días}$), los jugadores pierden fuerza tras acumular partidos.

#### 1. Jugadores de Campo (`A`, `M`, `S`):
$$\text{Edad}_{años} = \left\lfloor \frac{\text{wiealt}}{365} \right\rfloor$$

$$Factor_{pérdida} = \frac{\text{Edad}_{años} - 28}{70}$$

$$Pérdida = \text{redondear}\left(Stärke_{actual} \cdot Factor_{pérdida}, 1\right)$$

$$\text{Pérdida efect} = \max(Pérdida, 0.1)$$

$$Stärke_{nueva} = \max(Stärke_{actual} - \text{Pérdida efect}, 0.1)$$

$$Talento_{nuevo} = Stärke_{nueva}$$

#### 2. Porteros (`T`):
Los porteros sufren una degradación menor y lineal:
$$Pérdida = \frac{\text{mt\_rand}(1, 2)}{10} \in \{0.1, 0.2\}$$

---

### E. Modelo Dinámico de Moral y Frescura Física

#### 1. Frescura (`frische`):
- **Desgaste por partido:** Se resta un valor de agotamiento $E$ según la agresividad/presión táctica:
  $$E = \text{druck} + 1 \quad (E \in [2, 6] \text{ puntos})$$
  *(En prórrogas se incrementa en $+1$ punto adicional).*
- **Recuperación diaria:** $+1$ punto de frescura tras cada jornada de liga (`aa_spieltag_simulation.php`).

#### 2. Moral (`moral`):
- **Pérdida diaria natural:** $-1.0\%$ por jornada.
- **Participación en partidos:** $+1.8\%$ en partidos oficiales / $+1.0\%$ en amistosos.
- **Mercado de fichajes:** $-10\%$ a $-15\%$ si el jugador es puesto en venta.
- **Renovación contractual:** $+5\%$ a $+10\%$ al firmar extensión.

---

### F. Valor de Mercado y Salarios

El valor de mercado (`marktwert`) se recalcula en `aa_marktwert_berechnen.php` mediante el parámetro del sistema `$marktwertAusdruck`:

$$Valor = f(Stärke, Talent, Edad, Posición)$$

---

## 2. Arquitectura Matemática de Simulación de Partidos (`aa_spieltag_simulation.php`)

La simulación de un partido es un proceso probabilístico paso a paso guiado por las fortalezas de las líneas, frescura de los jugadores, decisiones tácticas y tiradas estocásticas.

```
+-----------------------------------------------------------------------------------+
|                            FLUJO DEL PARTIDO EN SIMULACIÓN                        |
+-----------------------------------------------------------------------------------+
|                                                                                   |
|  1. Alineación (11 jugadores) -> Cálculo de Fuerza Efectiva ajustada por Frescura  |
|  2. Cálculo de Posesión de Balón -> Distribución de N=20 ataques en 90 minutos    |
|  3. Bucle de Ataques:                                                             |
|      - Transición 1er Tercio: Posesión vs Presión -> Faltas / Fueras de juego     |
|      - Transición 2º Tercio: Pase / Dribbling -> Penaltis / Tiros libres          |
|      - Remate a Portería: Potencia vs Portero -> Gol / Parada / Bloqueo           |
|      - Opción de Contraataque Rápido                                              |
|  4. Evaluación Periodística (Bolzblatt) -> Asignación de notas (1.0 a 6.0)         |
|                                                                                   |
+-----------------------------------------------------------------------------------+
```

---

### A. Fuerza Efectiva por Línea y Ponderación de Frescura

Para cada equipo, la fuerza de los 11 titulares se agrupa por línea ($T, A, M, S$) y se pondera según su frescura individual:

$$Stärke_{efectiva}(i) = Stärke_i \cdot \left(0.33 + 0.67 \cdot \frac{Frescura_i}{100}\right)$$

$$Fuerza_{Línea} = \sum_{i \in Línea} Stärke_{efectiva}(i)$$

#### Ajuste por Formación Táctica:
Las líneas defensiva ($A$) y ofensiva ($S$) se escalan según la orientación táctica (`ausrichtung`):
- **Ultra-defensiva (`1`):** $A \times 1.15$, $S \times 0.80$
- **Defensiva (`2`):** $A \times 1.10$, $S \times 0.90$
- **Normal (`3`):** $A \times 1.00$, $S \times 1.00$
- **Ofensiva (`4`):** $A \times 0.90$, $S \times 1.10$
- **Ultra-ofensiva (`5`):** $A \times 0.70$, $S \times 1.25$

#### Promedios por Posición:
$$Stärke_{Defensa} = \frac{Fuerza_A}{4} \cdot \text{Factor}_v, \quad Stärke_{Medio} = \frac{Fuerza_M}{4}, \quad Stärke_{Ataque} = \frac{Fuerza_S}{2} \cdot \text{Factor}_a$$

---

### B. Cálculo de Posesión de Balón

La posesión de balón para el equipo local ($Team_1$) y visitante ($Team_2$) depende de la fuerza comparativa del centro del campo ($M$):

$$\text{Balón}_{Team1} = \text{redondear}\left( \frac{100}{\frac{Stärke_{M2}}{Stärke_{M1}} + 1} \right)$$

$$\text{Balón}_{Team2} = 100 - \text{Balón}_{Team1}$$

#### Ajuste por Ventaja de Campo (Salvo en derbis, copas o amistosos):
$$\text{Balón}_{Team1} \leftarrow \text{Balón}_{Team1} + 4, \quad \text{Balón}_{Team2} \leftarrow \text{Balón}_{Team2} - 4$$

---

### C. Transformaciones Tácticas y Ponderación de Fuerza

El simulador aplica dos funciones de ponderación continua:

#### 1. Ponderación Táctica ($W_{\text{táctica}}$):
Para un parámetro táctico $x \in [1, 5]$:
$$W_{\text{táctica}}(x) = 0.25 \cdot x + 0.5$$

#### 2. Ponderación de Fuerza ($W_{\text{fuerza}}$):
Para un valor de fuerza de línea $S \in [0.1, 10.0]$:
$$W_{\text{fuerza}}(S) = 0.125 \cdot S + 0.0625$$

---

### D. Algoritmo Temporal y Distribución de Ataques ($N=20$)

El partido consta de $N = 20$ oportunidades de ataque distribuidas a lo largo de los 90 minutos.

1. **Intervalo base entre ataques:**
   $$Intervall = \frac{90}{20} = 4.5 \text{ minutos}$$
2. **Determinación estocástica de qué equipo ataca en la jugada $i$:**
   $$P(\text{Ataca } Team_1) = \frac{\text{Ataques Restantes}_{Team1}}{21 - i} \cdot 100$$
   Si $U \sim \text{Uniforme}(0, 100) < P(\text{Ataca } Team_1)$, ataca el $Team_1$; de lo contrario, ataca el $Team_2$.
3. **Variación temporal del minuto de jugada:**
   $$Minuto_i = Minuto_{i-1} + Intervall + \delta_i \quad (\delta_i \text{ es un ruido aleatorio entre } -3 \text{ y } +3 \text{ minutos})$$

---

### E. Árbol Estocástico de Decisiones de Ataque

Cada ataque iniciado por un equipo atacante ($att$) contra un equipo defensor ($def$) evoluciona según la siguiente cascada de probabilidades:

```
Inicio del Ataque
  |
  +---> Probabilidad Superar 1er Tercio:
  |       P_1 = 50% * [W_fuerza(M_att) / W_fuerza(M_def)] * [W_táctica(Ausrichtung_att) / W_táctica(Ausrichtung_def)] * ...
  |
  |       +---> SI FALLA: ¿Falta o Fuera de Juego o Balón fuera?
  |       |       - Balón al área / Saque de banda
  |       |       - Oportunidad de Contraataque Rápido (15% * [W_fuerza(A_def) / W_fuerza(S_att)])
  |       |
  |       +---> SI TIENE ÉXITO: Transición al 2º Tercio
  |               |
  |               +---> ¿Falta defensiva en 2º Tercio? (25% * W_táctica(Aggress_def))
  |               |       |
  |               |       +---> SI: Tarjeta Amarilla (33%) / Tarjeta Roja (3%)
  |               |       +---> ¿Penalti o Tiro Libre Directo?
  |               |               - Penalti: P_gol = (77%) / W_fuerza(T_def)
  |               |               - Tiro Libre Directo: P_tiro = 40% * W_fuerza(S_att), P_gol = 40% / W_fuerza(T_def)
  |               |
  |               +---> ¿Fuera de Juego? (17% * W_táctica)
  |               |
  |               +---> Remate a Portería en Jugada Abierta:
  |                       P_remate = 62% * [W_fuerza(S_att) / W_fuerza(A_def)] * W_táctica
  |                       P_gol = 30% * [W_fuerza(S_att) / W_fuerza(T_def)]
  |                       SI NO HAY GOL: Parada del Portero o Bloqueo Defensivo
```

---

### F. Sistema de Evaluación Periódistica y Calificaciones ("Bolzblatt")

Al finalizar el encuentro, la revista deportiva ficticia "Bolzblatt" asigna notas en la escala alemana ($1.0$ = Matrícula de Honor, $6.0$ = Pésimo):

$$\text{Nota}(S) = \text{staerkeBenoten}(W_{\text{fuerza}}(S))$$

#### Algoritmo de Conversión a Nota:
Para $i \in \{6, 5, 4, 3, 2, 1\}$:
$$Límite(i) = 1.485626 - 0.2759375 \cdot (i - 1)$$
Si $W_{\text{fuerza}}(S) \le Límite(i)$, la nota otorgada es $i$.

| Rango $W_{\text{fuerza}}$ | Calificación (Nota) | Estado de Rendimiento |
| :---: | :---: | :---: |
| $\le 0.106$ | **6.0** | Insuficiente / Muy Deficiente |
| $0.107 - 0.382$ | **5.0** | Deficiente |
| $0.383 - 0.658$ | **4.0** | Suficiente |
| $0.659 - 0.934$ | **3.0** | Satisfactorio |
| $0.935 - 1.209$ | **2.0** | Bueno |
| $> 1.209$ | **1.0** | Sobresaliente |

---

### G. Prórroga y Tanda de Penaltis

En partidos de eliminatoria previa o copas (`Pokal` / `Cup`) que terminan en empate:
1. Se añaden $8$ ataques suplementarios entre los minutos 96 y 119.
2. Si persiste el empate en el minuto 120, se ejecuta una tanda de penaltis estocástica:
   $$P(Team_1 \text{ gana penaltis}) = 50\%$$

---

# PARTE II: PROPUESTA DE NUEVAS FUNCIONALIDADES (FEATURES)

Para llevar **OpenSoccer** al siguiente nivel de simulación futbolística y enganche de la comunidad, se proponen una serie de mejoras modulares tanto para los jugadores como para la gestión del club.

---

## 3. Nuevas Funcionalidades para Jugadores

```
+-------------------------------------------------------------------------------+
|                    NUEVO MODELO EXPANDIDO DEL JUGADOR                         |
+-------------------------------------------------------------------------------+
|                                                                               |
|  [ ATRIBUTOS PRIMARIOS ] -> Fuerza Global (Stärke), Talento (Talent)          |
|  [ MATRIZ TÉCNICA    ] -> Pase, Regate, Remate, Visión de Juego               |
|  [ MATRIZ FÍSICA     ] -> Velocidad, Aceleración, Resistencia, Fuerza Física |
|  [ PERSONALIDAD      ] -> Liderazgo, Inmunidad a Presión, Profesionalidad     |
|  [ RASGOS ESPECIALES ] -> "Especialista a Balón Parado", "Muro Defensivo"     |
|  [ VERSATILIDAD      ] -> Posición Principal, Posición Secundaria (% Eficacia)|
|                                                                               |
+-------------------------------------------------------------------------------+
```

### A. Desglose de Atributos Secundarios (Matriz de Habilidades)

Actualmente, el jugador dispone de un único atributo lineal de fuerza (`staerke`). Se propone desglosar la fuerza en una matriz de 6 sub-atributos específicos que determinen su rendimiento diferencial según la jugada:

1. **Atributos Técnicos:**
   - **Pase y Visión (`pas`):** Influye directamente en la probabilidad de éxito en transiciones del 1er y 2º tercio.
   - **Remate y Finalización (`rem`):** Incrementa la probabilidad de gol frente al portero ($P_{gol}$).
   - **Regate y Control (`reg`):** Reduce la efectividad de las faltas y robos defensivos enemigas.
2. **Atributos Físicos y Defensivos:**
   - **Velocidad y Aceleración (`vel`):** Determina la probabilidad de generar y detener contraataques rápidos (`quickCounterAttack`).
   - **Robo y Posicionamiento (`def`):** Aumenta el éxito del bloqueo de disparos y recuperaciones.
   - **Resistencia y Vigor (`res`):** Atenúa la tasa de pérdida de frescura por partido.

---

### B. Sistema de Rasgos, Personalidad y Especialidades (Traits)

Agregar rasgos pasivos y activos a los jugadores para aportar una identidad única:

| Rasgo / Especialidad | Efecto Mecánico en Simulación |
| :--- | :--- |
| **Líder de Vestuario (*Capitán*)** | Aumenta la moral de todo el equipo en $+0.5\%$ por partido disputado y frena caídas de moral tras derrotas. |
| **Especialista a Balón Parado** | Otorga un $+15\%$ a la probabilidad de gol en tiros libres directos (`dFreeKick`) y penaltis. |
| **Inmune a la Presión (*Clutch Player*)** | Incrementa un $+10\%$ la efectividad en minutos $> 80'$ o en tandas de penaltis. |
| **Jugador Frágil (*Propenso a Lesiones*)** | Duplica el riesgo base de sufrir lesiones durante el partido. |
| **Especialista en Contraataque** | Aumenta en $+20\%$ el éxito en ataques tras recuperación rápida. |

---

### C. Entrenamiento Posicional y Pie Hábil

1. **Entrenamiento de Posición Secundaria:**
   - Permitir que un mediocampista (`M`) entrene durante 30 días para aprender la posición de defensa (`A`) o delantero (`S`).
   - Si juega en una posición secundaria no dominada al 100%, su fuerza sufre una penalización del $20\%$.
2. **Pie Hábil y Dominio Ambidextro:**
   - Atributo de pie preferido: Izquierdo, Derecho, Ambidextro.
   - Los jugadores ambidextros sufren un $0\%$ de penalización al jugar en bandas opuestas.

---

### D. Historial Médico, Lesiones Recurrentes y Secuelas

1. **Expediente Clínico Histórico:**
   - Registro permanente de todas las lesiones pasadas del jugador (tipo, duración y fecha).
2. **Secuelas Físicas por Lesión Grave:**
   - Si un jugador sufre una lesión grave ($> 10$ días, ej. rotura de ligamentos o fractura), existe un $25\%$ de probabilidad de que pierda un $-0.3$ permanente en su fuerza máxima o talento.

---

### E. Sistema de Liderazgo, Jerarquía de Vestuario y Química de Equipo

1. **Química de Equipo (*Team Chemistry*):**
   - Basada en el tiempo que los titulares llevan jugando juntos (`spiele_verein`).
   - Otorga una bonificación de hasta $+5\%$ a la fuerza efectiva total si el equipo mantiene una columna vertebral estable durante la temporada.
2. **Conflicto de Roles / Egos:**
   - Si hay más de 3 jugadores con fuerza $> 8.0$ disputando la misma posición, la moral de los suplentes cae el doble de rápido ($-2\%$ diario).

---

## 4. Nuevas Funcionalidades para Clubes

```
+-------------------------------------------------------------------------------+
|                      ESTRUCTURA DE AMPLIACIÓN PARA CLUBES                     |
+-------------------------------------------------------------------------------+
|                                                                               |
|  [ CUERPO TÉCNICO ]   -> Entrenadores por Línea, Médicos, Ojeadores           |
|  [ INFRAESTRUCTURA ]  -> Centro de Rendimiento, Ciudad Deportiva, Data Center  |
|  [ SCOUTING/CANTERA]  -> Red de Ojeadores Internacionales, Escuela Mundial    |
|  [ FINANZAS & BRAND]  -> Patrocinadores Dinámicos por Objetivo, Reputación     |
|                                                                               |
+-------------------------------------------------------------------------------+
```

### A. Cuerpo Técnico Especializado y Staff Multifuncional

Actualmente solo existen fisioterapeutas y psicólogos en acciones puntuales (`ver_personal.php`). Se propone un cuerpo técnico contratado a sueldo fijo mensual:

1. **Entrenador de Porteros:** Aumenta la velocidad de desarrollo de los guardametas en cantera y primer equipo.
2. **Preparador Físico:** Reduce el consumo de frescura en partidos intensos e incrementa la velocidad de recuperación post-partido.
3. **Jefe Médico / Traumatólogo:** Reduce en un $30\%$ el tiempo de baja de los jugadores lesionados.
4. **Director Deportivo / Analyst Data:** Permite visualizar el rango exacto de talento de los jugadores en el mercado antes de ofertar.

---

### B. Red Internacional de Scouting y Cantera Avanzada

1. **Ojeadores en el Extranjero:**
   - Enviar ojeadores a regiones específicas (América del Sur, Europa del Este, África, Asia) durante 14 días con un coste económico.
   - Posibilidad de descubrir perlas ocultas con talento superior al de la cantera local.
2. **Torneos Juveniles de Cantera:**
   - Liga paralela de filiales/juveniles para que los jugadores jóvenes con pocos minutos en el primer equipo puedan acumular partidos disputados (`spiele_gesamt`) y progresar de fuerza sin arriesgar puntos en la liga principal.

---

### C. Infraestructura Compleja e Inversiones Tecnológicas

Ampliación del módulo de estadio (`man_stadien`) incorporando la **Ciudad Deportiva**:

1. **Centro de Alto Rendimiento (CAR):**
   - Coste: 5.000.000 €.
   - Beneficio: Aumenta la frescura máxima base de la plantilla a 105 puntos.
2. **Academia Digital y Data Analytics:**
   - Coste: 2.500.000 €.
   - Beneficio: Muestra informes tácticos avanzados del rival pre-partido (probabilidad estimada de victoria y patrones de ataque preferidos).
3. **Clínica de Rehabilitación Avanzada:**
   - Coste: 3.500.000 €.
   - Beneficio: Previene recaídas en lesiones graves.

---

### D. Patrocinadores Dinámicos por Objetivos y Reputación de Club

1. **Sistema de Reputación de Club (0 a 100 puntos):**
   - La reputación aumenta al ganar títulos, ascender o ganar derbis, y disminuye tras descensos o bancarrotas.
2. **Contratos de Patrocinio Negociados:**
   - Reemplazar el patrocinador fijo por contratos negociables a principio de temporada con cláusulas por cumplimiento de objetivos:
     - **Objetivo Liga:** Permanencia, Top 4, Campeón.
     - **Prima de Avanzada en Copa:** Bonificación por alcanzar semifinales o final.
     - **Penalización por Descenso:** Cláusula de rescisión del contrato si el equipo cae de categoría.

---

### E. Modo Torneos Propios y Filiales (Equipo B)

1. **Creación de Equipos B (Filiales):**
   - Permitir a los clubes de máxima categoría fundar un filial en ligas inferiores para foguear jugadores de 17 a 21 años.
2. **Organización de Torneos Amistosos Personalizados:**
   - Los mánagers pueden crear copas amistosas privadas de 4 u 8 equipos (ej. *Copa de Verano*) estableciendo una cuota de inscripción y un premio económico para el ganador.

---

## 5. Resumen de Impacto Técnico y Roadmap Sugerido

| Módulo | Complejidad de Implementación | Impacto en Jugabilidad | Prioridad Recomendada |
| :--- | :---: | :---: | :---: |
| **Atributos Secundarios de Jugador** | Media (Modificación en DB y Simulación) | **Muy Alto** | **Fase 1 (Alta)** |
| **Staff y Cuerpo Técnico Fijo** | Baja (Nueva tabla SQL y cronjob) | **Alto** | **Fase 1 (Alta)** |
| **Sistema de Rasgos y Especialidades** | Media (Ajuste en árbol estocástico) | **Alto** | **Fase 2 (Media)** |
| **Red de Scouting Internacional** | Media (Nuevos scripts PHP) | **Medio** | **Fase 2 (Media)** |
| **Ciudad Deportiva e Infraestructura** | Baja (Extensión de `man_stadien`) | **Alto** | **Fase 3 (Baja)** |
| **Equipos Filiales (Equipo B)** | Alta (Arquitectura de competición) | **Muy Alto** | **Fase 3 (Baja)** |
