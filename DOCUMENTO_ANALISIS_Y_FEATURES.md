# Análisis Estadístico de Simulación, Determinación de Características y Propuestas de Nuevas Funcionalidades en OpenSoccer

Este documento ofrece un análisis técnico y estadístico exhaustivo sobre el funcionamiento interno de **OpenSoccer**, abordando el motor de simulación de partidos, las fórmulas matemáticas empleadas para determinar las características de jugadores y clubes, y una propuesta detallada de nuevas características (*features*) para expandir la profundidad jugable del sistema.

---

## ÍNDICE
1. [Análisis Estadístico de la Simulación de Partidos](#1-análisis-estadístico-de-la-simulación-de-partidos)
   - [1.1. Estructura Temporal y Número de Ataques](#11-estructura-temporal-y-número-de-ataques)
   - [1.2. Cálculo del Posesión del Balón](#12-cálculo-del-posesión-del-balón)
   - [1.3. Funciones de Ponderación Táctica y de Fuerza](#13-funciones-de-ponderación-táctica-y-de-fuerza)
   - [1.4. Árbol Estadístico de Decisiones del Ataque](#14-árbol-estadístico-de-decisiones-del-ataque)
   - [1.5. Generación de Eventos Secundarios (Tarjetas, Lesiones y Desgaste)](#15-generación-de-eventos-secundarios-tarjetas-lesiones-y-desgaste)
   - [1.6. Evaluación y Calificaciones del Rendimiento ("Bolzblatt")](#16-evaluación-y-calificaciones-del-rendimiento-bolzblatt)
2. [Determinación Matemática de Características de Jugadores y Clubes](#2-determinación-matemática-de-características-de-jugadores-y-clubes)
   - [2.1. Atributos Fundamentales del Jugador](#21-atributos-fundamentales-del-jugador)
   - [2.2. Modelo de Generación de Canteranos (Juventud)](#22-modelo-de-generación-de-canteranos-juventud)
   - [2.3. Dinámica de Desarrollo, Progreso y Envejecimiento](#23-dinámica-de-desarrollo-progreso-y-envejecimiento)
   - [2.4. Algoritmo de Cálculo del Valor de Mercado](#24-algoritmo-de-cálculo-del-valor-de-mercado)
   - [2.5. Valoración Global del Equipo y Sistema Elo](#25-valoración-global-del-equipo-y-sistema-elo)
3. [Análisis y Propuestas de Nuevas Funcionalidades (Features)](#3-análisis-y-propuestas-de-nuevas-funcionalidades-features)
   - [3.1. Nuevas Funcionalidades para Jugadores](#31-nuevas-funcionalidades-para-jugadores)
   - [3.2. Nuevas Funcionalidades para Clubes](#32-nuevas-funcionalidades-para-clubes)
4. [Conclusiones y Hoja de Ruta Recomendada](#4-conclusiones-y-hoja-de-ruta-recomendada)

---

## 1. Análisis Estadístico de la Simulación de Partidos

La simulación de un encuentro en OpenSoccer se ejecuta a través del script `Website/aa_spieltag_simulation.php`. A diferencia de simuladores basados únicamente en tiro de dados globales, OpenSoccer emplea un modelo estocástico de simulación por eventos discretos basado en un **árbol de probabilidades condicionales**.

### 1.1. Estructura Temporal y Número de Ataques

* **Número total de ataques ($N$):** Fijado numéricamente en $N = 20$ oportunidades ofensivas por partido.
* **Intervalo promedio:** $\Delta t_{promedio} = \frac{90}{20} = 4.5\text{ minutos}$.
* **Distribución temporal:** Para evitar una periodicidad rígida, se calcula un margen de variación (*jitter*):
  $$s = \lceil 4.5 \rceil - 1 = 4\text{ minutos}$$
  La hora exacta en minuto $m_k$ de cada ataque se calcula sumando una variable aleatoria uniforme en $[ -s, s ]$:
  $$\Delta t_k = 4.5 + \text{mt\_rand}(-4, 4)$$
  Garantizando una secuencia uniforme pero dinámicamente distribuida a lo largo de los 90 minutos de juego.

### 1.2. Cálculo del Posesión del Balón

La posesión entre el Equipo 1 (Local, $T_1$) y el Equipo 2 (Visitante, $T_2$) depende directamente del nivel de sus líneas de centrocampistas ($S_{M1}$ y $S_{M2}$):

$$\text{BalónBesitz}_{T1} = \text{round}\left( \frac{100}{\frac{S_{M2}}{S_{M1}} + 1} \right)$$

$$\text{BalónBesitz}_{T2} = 100 - \text{BalónBesitz}_{T1}$$

#### Ajuste por Factor Local (Heimvorteil)
En partidos oficiales de liga (excluyendo torneos amistosos o neutrales como finales de copa):
* $\text{BalónBesitz}_{T1} \leftarrow \text{BalónBesitz}_{T1} + 4\%$
* $\text{BalónBesitz}_{T2} \leftarrow \text{BalónBesitz}_{T2} - 4\%$

#### Asignación de Ataques
Con $N_{restantes}$ ataques pendientes y $N_{T1\_rest}$ ataques que le corresponden al Equipo 1 por probabilidad de posesión, la probabilidad de que el ataque en el paso $i$ corresponda al Equipo 1 es:

$$P(\text{Ataque } i \in T_1) = \frac{N_{T1\_rest}}{N - i + 1} \times 100$$

---

### 1.3. Funciones de Ponderación Táctica y de Fuerza

El motor utiliza dos funciones matemáticas de suavizado (*smoothing*) para acotar y normalizar el impacto de las tácticas y de las diferencias de fuerza:

1. **Ponderación Táctica ($W_T(x)$):**
   Las tácticas se configuran en una escala discreta de $x \in \{1, 2, 3, 4, 5\}$. La función de transformación es:
   $$W_T(x) = 0.25 \cdot x + 0.5$$
   * Para $x=1$ (conservador/mínimo) $\rightarrow W_T(1) = 0.75$
   * Para $x=3$ (normal) $\rightarrow W_T(3) = 1.25$
   * Para $x=5$ (máximo/ultra) $\rightarrow W_T(5) = 1.75$

2. **Ponderación de Fuerza ($W_S(S)$):**
   Mapea la fuerza posicional $S \in [0.1, 9.9]$ a un coeficiente multiplicativo ajustado:
   $$W_S(S) = 0.125 \cdot S + 0.0625$$
   * Para $S=1.0 \rightarrow W_S(1.0) = 0.1875$
   * Para $S=5.0 \rightarrow W_S(5.0) = 0.6875$
   * Para $S=9.0 \rightarrow W_S(9.0) = 1.1875$

---

### 1.4. Árbol Estadístico de Decisiones del Ataque

Cada ataque procesado por la función `starte_angriff()` recorre un grafo probabilístico condicional de múltiples niveles:

```
[ INICIO ATAQUE ]
       |
       +---> Probabilidad Entrada a 1er Tercio (P_fase1)
       |           |
       |           +-- [ÉXITO] --> ¿Falta Defensiva? (P_foul1)
       |           |                  |
       |           |                  +-- [SÍ] --> Tira Falta Indir. (Tiro/Parada/Gol)
       |           |                  +-- [NO] --> ¿Fuera de Juego? (P_offside)
       |           |                                   |
       |           |                                   +-- [NO] --> [2º TERCIO / OCASIÓN DE GOL]
       |           |                                                     |
       |           |                                                     +-- ¿Falta en Área / Penalti?
       |           |                                                     +-- ¿Tiro a Puerta Directo?
       |           |                                                     |     +-- Gol (P_goal)
       |           |                                                     |     +-- Bloqueo / Parada
       |           |                                                     +-- ¿Contraataque Rápido?
       |           |
       |           +-- [FALLO] --> ¿Contraataque Rápido? / Banda (Throw-in)
```

#### Fórmulas de Probabilidad por Fase:

* **Superación del Primer Tercio Campo ($P_{Fase1}$):**
  $$P_{Fase1} = 50\% \times \frac{W_S(S_{M,att})}{W_S(S_{M,def})} \times \frac{W_T(T_{att,ausrichtung}) \cdot W_T(T_{def,ausrichtung}) \cdot W_T(T_{att,geschw})}{W_T(T_{att,pass})}$$

* **Falta Defensiva en 1er Tercio ($P_{foul1}$):**
  $$P_{foul1} = 25\% \times W_T(T_{def,aggress})$$
  Si hay falta: $30\%$ probabilidad de Tarjeta Amarilla, $3\%$ de Tarjeta Roja directa.

* **Probabilidad de Tiro a Puerta en 2º Tercio ($P_{shot}$):**
  $$P_{shot} = 62\% \times \frac{W_S(S_{S,att})}{W_S(S_{A,def})} \times W_T(T_{att,pass}) \times W_T(T_{att,risk})$$

* **Conversión de Gol ($P_{goal}$):**
  $$P_{goal} = 30\% \times \frac{W_S(S_{S,att})}{W_S(S_{T,def})}$$

* **Conversión de Penalti ($P_{penalty\_goal}$):**
  $$P_{penalty\_goal} = \frac{77\%}{W_S(S_{T,def})}$$

* **Desencadenamiento de Contraataque Rápido ($P_{counter}$):**
  $$P_{counter} = 15\% \times \frac{W_S(S_{A,def})}{W_S(S_{M,att})} \times W_T(T_{att,pass}) \times W_T(T_{att,risk}) \times W_T(T_{def,druck})$$
  Cuando se activa un contraataque, la defensa del equipo interceptado se penaliza en un $20\%$ ($S_{A,att} \leftarrow 0.8 \cdot S_{A,att}$) y se invoca recursivamente `starte_angriff()`.

---

### 1.5. Generación de Eventos Secundarios (Tarjetas, Lesiones y Desgaste)

1. **Efecto de Tarjetas en la Fuerza Posicional:**
   Al recibir una tarjeta durante el partido, la fuerza colectiva del equipo se debilita en tiempo real para el resto de la simulación mediante un factor de degradación (`weakenFactor`):
   * **Tarjeta Amarilla:** $S_{pos} \leftarrow S_{pos} \times 0.98$ (Reducción del $2\%$).
   * **Tarjeta Roja:** $S_{pos} \leftarrow S_{pos} \times 0.95$ (Reducción del $5\%$).

2. **Modelos de Lesión (`create_verletzung()`):**
   Las lesiones se generan probabilísticamente y asignan un tipo y tiempo de baja en días:
   * **Muskelzerrung (Tirón muscular):** $P \approx 70\%$, duración: 1 día.
   * **Verstauchung (Esguince):** $P \approx 9\%$, duración: 3 días.
   * **Prellung (Contusión):** $P \approx 6.3\%$, duración: 5 días.
   * **Muskelfaserriss (Rotura fibrilar):** $P \approx 4.4\%$, duración: 7 días.
   * **Bänderriss (Rotura de ligamentos):** $P \approx 3.1\%$, duración: 9 días.
   * **Knorpelschaden (Daño en cartílago):** $P \approx 2.1\%$, duración: 11 días.
   * **Knochenbruch (Fractura ósea):** $P \approx 5.1\%$, duración: 13 días.

3. **Consumo de Frescura/Condición Física (`frische`):**
   Al término de cada partido disputado, la condición física del jugador disminuye según la agresividad/presión táctica (`druck`):
   $$\Delta \text{Frische} = \text{mt\_rand}(1, 5) \times (\text{druck} + 1)$$

---

### 1.6. Evaluación y Calificaciones del Rendimiento ("Bolzblatt")

Al finalizar el partido, la revista deportiva ficticia *Bolzblatt* asigna notas alemanas (de $1.0$ excelente a $6.0$ pésimo) a cada línea según la función de corte `staerkeBenoten()`:

$$\text{Grenze}(i) = 1.485626 - 0.2759375 \times (i - 1) \quad \text{para } i \in \{6, 5, 4, 3, 2, 1\}$$

---

## 2. Determinación Matemática de Características de Jugadores y Clubes

### 2.1. Atributos Fundamentales del Jugador

En el esquema actual (`man_spieler`), las características de un personaje se resumen en los siguientes parámetros continuos y discretos:

| Atributo | Tipo / Rango | Descripción y Significado Estadístico |
| :--- | :--- | :--- |
| `position` | `T`, `A`, `M`, `S` | Posición táctica fija (Portero, Defensa, Medio, Delantero). |
| `wiealt` | Entero ($\ge 5840$ días) | Edad en días ($365 \text{ días} = 1 \text{ año}$). La edad canónica de entrada es 16-19 años. |
| `staerke` | Decimal ($0.1$ a $10.0$) | Nivel de habilidad técnica/física actual en partidos. |
| `talent` | Decimal ($0.1$ a $10.0$) | Techo o límite asintótico máximo de desarrollo de `staerke`. |
| `frische` | Entero ($0$ a $100$) | Porcentaje de energía física utilizable. Modifica la fuerza efectiva: $S_{efectivo} = S \times (0.33 + 0.67 \cdot \frac{\text{frische}}{100})$. |
| `moral` | Decimal ($0.0$ a $100.0$) | Estado de motivación psicológica. Modifica probabilidades de renovación y rendimiento. |

---

### 2.2. Modelo de Generación de Canteranos (Juventud)

En `aa_spieler_erzeugen.php`, los jóvenes nacen de las canteras de los clubes según el nivel de inversión en infraestructura de juventud (`jugendarbeit` $\in \{1, 2, 3, 4, 5\}$):

$$\text{Talento}_{mín} = 2.1 + 0.7 \times (\text{Nivel Cantera} - 1)$$
$$\text{Talento}_{máx} = 5.9 + 1.0 \times (\text{Nivel Cantera} - 1)$$

* La fuerza inicial de todo juvenil al nacer es $S_{inicio} = 0.1$.
* **Distribución de Posiciones:** Portero ($T$) $12\%$, Delantero ($S$) $24\%$, Centrocampista ($M$) $50\%$, Defensa ($A$) $14\%$.

---

### 2.3. Dinámica de Desarrollo, Progreso y Envejecimiento

El desarrollo de los jugadores es procesado por el cronjob `aa_spieler_verbesserung.php`:

#### A. Crecimiento de Jugadores Jóvenes ($\text{Edad} < 31 \text{ años} \iff \text{wiealt} < 11.315 \text{ días}$)
Requiere haber disputado al menos 8 partidos acumulados (`spiele_gesamt > 8`). El incremento de fuerza se determina mediante:

$$\text{FactorZufall} = \frac{\text{mt\_rand}(0, 5) + 1}{10} \in [0.1, 0.6]$$

$$\Delta S = \text{ceil}\left( \frac{\text{FactorZufall}}{S_{actual}} \times 5 \times 10 \right) / 10$$

Con las restricciones:
* $0.1 \le \Delta S \le 1.2$
* $S_{nuevo} = \min(S_{actual} + \Delta S, \text{talent})$

#### B. Declive por Envejecimiento ($\text{Edad} \ge 31 \text{ años} \iff \text{wiealt} \ge 11.315 \text{ días}$)
A partir de los 31 años, el jugador sufre un deterioro paulatino:

$$\text{FactorPérdida} = \frac{\lfloor \text{Edad en años} - 28 \rfloor}{70}$$

$$\Delta S_{pérdida} = \text{round}(S_{actual} \times \text{FactorPérdida}, 1)$$

---

### 2.4. Algoritmo de Cálculo del Valor de Mercado

El valor monetario estimado de un jugador se calcula dinámicamente mediante la siguiente fórmula no lineal definida en `Website/zzserver.php`:

$$\text{Marktwert} = \text{ROUND}\left( \frac{1.75^{\text{staerke}} \times \text{talent} \times 30000}{\left( \frac{\lfloor \text{wiealt} / 365 \rfloor}{27} \right)^{1.7}} + \lfloor 100 + \text{rand}() \times 1000 \rfloor \right)$$

#### Propiedades Matemáticas de la Fórmula:
1. **Crecimiento Exponencial con la Fuerza:** La base $1.75^{\text{staerke}}$ hace que jugadores de fuerza $8.0$ o $9.0$ valgan órdenes de magnitud más que jugadores promedio.
2. **Descuento Exponencial por Edad:** El denominador $(\text{Edad} / 27)^{1.7}$ penaliza fuertemente a los jugadores mayores de 27 años, manteniendo alto el valor de los jóvenes talentos de 18-21 años.

---

### 2.5. Valoración Global del Equipo y Sistema Elo

#### Fuerza Global del Equipo
Calculada por `aa_team_staerke_berechnen.php`:

$$S_{equipo} = \text{Promedio}(S_i) \quad \forall i \in \text{Plantilla del Club}$$

#### Sistema de Clasiﬁcación Elo (`eloChange()`)
El nivel competitivo dinámico de un club se mide mediante el sistema Elo:

$$E_{esperado} = \frac{1}{10^{\frac{\Delta \text{Elo} + 100}{400}} + 1}$$

$$\Delta \text{Elo}_{puntos} = 40 \times K_{competición} \times F_{goles} \times (S_{resultado} - E_{esperado})$$

Donde:
* $K_{competición} = 1.0$ (Liga), $2.0$ (Copa Nacional), $1.3$ (Copa Internacional), $0.0$ (Amistoso).
* $F_{goles} = 1.0$ si dif $= 0, 1$; $1.5$ si dif $= 2$; $\frac{11 + \text{dif}}{8}$ si dif $\ge 3$.

---

## 3. Análisis y Propuestas de Nuevas Funcionalidades (Features)

Para elevar la experiencia de simulación y gestión técnica a un nivel profesional acorde con estándares modernos de gestión deportiva, se presenta la siguiente propuesta de **Features para Jugadores y Clubes**.

---

### 3.1. Nuevas Funcionalidades para Jugadores

```
+------------------------------------------------------------------------+
|                     NUEVAS FEATURES PARA JUGADORES                     |
+------------------------------------------------------------------------+
|                                                                        |
|  1. Sub-atributos Específicos (Pase, Disparo, Regate, Físico, etc.)    |
|  2. Rasgos y Especialidades Pasivas ("Líder", "Super Suplente", etc.)  |
|  3. Dominio de Pie y Polivalencia Posicional (Puestos Secundarios)     |
|  4. Métricas Avanzadas y Métricas Esperadas (xG, xA, Pases Clave)      |
|  5. Agente del Jugador, Personalidad y Dinámicas de Renovación         |
|                                                                        |
+------------------------------------------------------------------------+
```

#### Feature J1: Desglose en Sub-Atributos Técnicos, Físicos y Mentales
* **Problema actual:** La fuerza (`staerke`) es una cifra monolítica. Dos mediocentros con fuerza 7.0 son idénticos.
* **Propuesta:** Descomponer `staerke` en 6 sub-atributos visibles o semi-ocultos:
  1. **Tiro / Finalización (SHO)**
  2. **Pase y Visión (PAS)**
  3. **Regate y Control (DRI)**
  4. **Defensa y Entradas (DEF)**
  5. **Velocidad y Aceleración (PAC)**
  6. **Resistencia y Fuerza Física (PHY)**
* **Impacto en Simulación:**
  En la simulación del ataque, la probabilidad de gol $P_{goal}$ usará el sub-atributo `SHO` del delantero frente al `REF` (reflejos) del portero, en lugar de la fuerza generalizada.

#### Feature J2: Sistema de Rasgos Especiales y Habilidades Únicas (*Traits*)
* **Propuesta:** Asignar entre 0 y 2 rasgos pasivos a cada jugador:
  * **"Especialista a Parada Parada":** $+15\%$ precisión en saques de falta y penaltis.
  * **"Capitán / Líder en el Campo":** Incrementa en $+5\%$ la moral y resistencia al cansancio de los compañeros de línea.
  * **"Super Suplente":** Si entra desde el banquillo en el minuto 60+, obtiene un bonificador temporal de $+1.0$ en fuerza durante el resto del encuentro.
  * **"Inmune a la Presión":** Mantiene su nivel de efectividad independientemente de si el rival aplica presión alta o si el partido es un Derby/Final.

#### Feature J3: Pie Preferido y Polivalencia Posicional
* **Propuesta:**
  * Pie dominante: *Izquierdo*, *Derecho* o *Ambidiestro*.
  * Posiciones secundarias: Permite a un jugador jugar en su posición principal ($100\%$ efectividad) o en una posición secundaria (ej. Defensa central pudiendo jugar de Lateral con $90\%$ efectividad).

#### Feature J4: Estadísticas Avanzadas de Rendimiento (xG, xA, Efectividad)
* **Propuesta:** Registrar métricas avanzadas por temporada en una nueva tabla `man_spieler_stats_advanced`:
  * **Goles Esperados (xG):** Suma de probabilidades de conversión de los tiros realizados.
  * **Asistencias Esperadas (xA):** Pases clave que terminaron en ocasión clara de gol.
  * **Efectividad en Entradas y Duelos Aéreos (%).**

#### Feature J5: Agente del Jugador y Gestión de Descontento
* **Propuesta:** Introducir la figura del Agente/Representante. Los jugadores tendrán un nivel de satisfacción con su contrato, minutos jugados y aspiración del equipo. Si un jugador de fuerza 8.0 juega en 3ª división, su agente solicitará el traspaso (*Transfer Request*) o exigirá una cláusula de rescisión razonable.

---

### 3.2. Nuevas Funcionalidades para Clubes

```
+------------------------------------------------------------------------+
|                       NUEVAS FEATURES PARA CLUBES                      |
+------------------------------------------------------------------------+
|                                                                        |
|  1. Centro de Alto Rendimiento y Ciudad Deportiva Integrada            |
|  2. Patrocinadores Múltiples y Contratos por Objetivos                 |
|  3. Filosofía del Club y Expectativas de la Directiva                 |
|  4. Filial / Equipo "B" y Liga de Reservas                             |
|  5. Herramientas Financieras Avanzadas (Préstamos, Bonos, Presupuestos)|
|                                                                        |
+------------------------------------------------------------------------+
```

#### Feature C1: Expansión de Instalaciones (Ciudad Deportiva / Centro de Alto Rendimiento)
* **Problema actual:** El estadio posee edificios comerciales, pero no hay instalaciones de entrenamiento o médicas para el club.
* **Propuesta:** Añadir el panel de **Ciudad Deportiva (`man_club_facilities`)**:
  * **Gimnasio y Centro de Rendimiento (Niveles 1 a 5):** Acelera la recuperación de frescura post-partido en $+5\%$ por nivel.
  * **Centro Médico y Rehabilitación (Niveles 1 a 5):** Reduce la duración de las lesiones sufridas en un $10\%$ a $40\%$.
  * **Centro de Análisis / Scouting (Niveles 1 a 5):** Revela con mayor precisión el talento real de jugadores de otros equipos en el mercado.

#### Feature C2: Patrocinadores Secundarios y Contratos Estructurados
* **Propuesta:** Reemplazar el patrocinador único por una estructura de patrocinio real:
  1. **Sponsor Principal de Camiseta:** Aporta la mayor cuantía fija y prima por victoria.
  2. **Sponsor de Manga y Equipación Secundaria:** Pagos menores basados en participación internacional.
  3. **Patrocinador de Naming Rights del Estadio:** Aporta ingresos masivos anuales a cambio de renombrar el estadio del club por $X$ temporadas.

#### Feature C3: Filosofía de Club y Exigencias de la Junta Directiva
* **Propuesta:** Cada temporada, la Junta Directiva establece objetivos e identidad:
  * **Objetivos Deportivos:** Ej. "Clasificar a Copa Internacional" o "Evitar el descenso".
  * **Filosofía de Juego:** Ej. "Apostar por la Cantera" (alinear al menos a 3 canteranos por partido) o "Fútbol de Posesión".
  * Cumplir los objetivos otorga bonificaciones presupuestarias e incrementa el Renombre del club (`renommee`).

#### Feature C4: Equipo Filial / Equipo "B" y Torneo de Reservas
* **Propuesta:** Crear una plantilla secundaria para jugadores menores de 23 años o suplentes.
  * Los jugadores no convocados en el primer equipo juegan automáticamente en la Liga de Reservas, manteniendo su condición física (`frische`) en $90\%+$ y acumulando partidos para su desarrollo sin saturar la plantilla principal.

#### Feature C5: Sistema de Finanzas Avanzadas y Presupuesto Departamental
* **Propuesta:**
  * **Préstamos Bancarios con Interés:** Permitir solicitar crédito al banco para proyectos de estadio o fichajes urgentes, con cuotas de amortización semanales.
  * **Desglose Presupuestario:** Separar el capital del club en *Presupuesto de Salarios*, *Presupuesto de Transferencias* y *Presupuesto de Infraestructura*, con opción de reajuste entre partidas.

---

## 4. Conclusiones y Hoja de Ruta Recomendada

El análisis estadístico revela que **OpenSoccer** posee un motor de simulación probabilístico sólido y coherente, respaldado por fórmulas matemáticas bien calibradas para el valor de mercado, el envejecimiento y la progresión por experiencia.

### Hoja de Ruta de Implementación Sugerida:

1. **Fase 1 (Corto Plazo - Mantenibilidad y Precisión):**
   * Incorporar el registro de estadísticas avanzadas (xG/xA) en la simulación de partidos.
   * Implementar la Ciudad Deportiva (Centro Médico y Gimnasio) en la gestión de clubes.

2. **Fase 2 (Medio Plazo - Profundidad Táctica):**
   * Expandir la fuerza monolítica (`staerke`) al sistema de 6 sub-atributos técnicos/físicos.
   * Asignar rasgos pasivos (*Traits*) a los jugadores para diversificar perfiles tácticos.

3. **Fase 3 (Largo Plazo - Ecosistema e Inmersión):**
   * Crear la Liga de Reservas / Equipo Filial.
   * Integrar la Filosofía de Club, objetivos de la directiva y sistema multi-sponsor.
