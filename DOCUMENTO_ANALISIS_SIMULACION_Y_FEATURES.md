# Documento de Análisis Estadístico de Simulación, Determinación de Características y Propuestas de Nuevos Features para OpenSoccer

---

## Índice
1. [Introducción y Contexto Técnico](#1-introducción-y-contexto-técnico)
2. [Análisis Estadístico y Matemático del Motor de Simulación](#2-análisis-estadístico-y-matemático-del-motor-de-simulación)
   - [2.1. Estructura Temporal y Oportunidades de Ataque](#21-estructura-temporal-y-oportunidades-de-ataque)
   - [2.2. Ponderaciones Matemáticas de Fuerza y Táctica](#22-ponderaciones-matemáticas-de-fuerza-y-táctica)
   - [2.3. Árbol Estadístico de Decisión durante un Ataque](#23-árbol-estadístico-de-decisión-durante-un-ataque)
   - [2.4. Evaluación de Calificaciones ("Bolzblatt Notes")](#24-evaluación-de-calificaciones-bolzblatt-notes)
3. [Determinación de Características y Evolución Matemática del Jugador](#3-determinación-de-características-y-evolución-matemática-del-jugador)
   - [3.1. Fuerza Efectiva en Campo (Forma y Frescura)](#31-fuerza-efectiva-en-campo-forma-y-frescura)
   - [3.2. Desarrollo y Declive por Edad](#32-desarrollo-y-declive-por-edad)
   - [3.3. Determinación del Valor de Mercado](#33-determinación-del-valor-de-mercado)
4. [Propuesta de Nuevos Features para Jugadores](#4-propuesta-de-nuevos-features-para-jugadores)
   - [4.1. Atributos Secundarios y Sistema de Rasgos (Traits)](#41-atributos-secundarios-y-sistema-de-rasgos-traits)
   - [4.2. Especialización de Posición y Adaptabilidad](#42-especialización-de-posición-y-adaptabilidad)
   - [4.3. Planes de Entrenamiento Individualizados](#43-planes-de-entrenamiento-individualizados)
   - [4.4. Química de Vestuario e Historial Médico](#44-química-de-vestuario-e-historial-médico)
5. [Propuesta de Nuevos Features para Clubes](#5-propuesta-de-nuevos-features-para-clubes)
   - [5.1. Sistema Avanzado de Cuerpo Técnico](#51-sistema-avanzado-de-cuerpo-técnico)
   - [5.2. Red Internacional de Ojeadores y Canteras Regionales](#52-red-internacional-de-ojeadores-y-canteras-regionales)
   - [5.3. Infraestructura Deportiva y Centro de Alto Rendimiento](#53-infraestructura-deportiva-y-centro-de-alto-rendimiento)
   - [5.4. Patrocinadores Dinámicos y Sistema de Reputación](#54-patrocinadores-dinámicos-y-sistema-de-reputación)
6. [Conclusiones y Recomendaciones](#6-conclusiones-y-recomendaciones)

---

## 1. Introducción y Contexto Técnico

**OpenSoccer** opera mediante un motor de simulación probabilístico programado en PHP (`Website/aa_spieltag_simulation.php`). La gestión de entidades (jugadores y clubes) se basa en un modelo cuantitativo centrado en la variable unidimensional de **Fuerza** (`staerke`), modulada por la **Frescura** (`frische`) y la **Moral** (`moral`).

El presente documento detalla el funcionamiento interno del motor de simulación de partidos y los modelos matemáticos de progresión de jugadores. Posteriormente, se presenta un estudio integral de nuevas características (*features*) diseñadas para profundizar la dimensión táctica, estratégica y económica del videojuego.

---

## 2. Análisis Estadístico y Matemático del Motor de Simulación

El script `aa_spieltag_simulation.php` simula partidos oficiales y amistosos calculando eventos minuto a minuto mediante iteraciones de ataque estocásticas.

```
+-----------------------------------------------------------------------+
|                    FLUJO DE SIMULACIÓN DE PARTIDO                     |
+-----------------------------------------------------------------------+
|                                                                       |
|  1. Cálculo de Posesión: P1 = 100 / ( (S_M2 / S_M1) + 1 ) ± Localía   |
|  2. Generación de 20 Intervalos de Ataque (get_minutes)               |
|  3. Bucle de Eventos por Ataque (starte_angriff):                     |
|     [Tercio 1: Centrocampo] ---> [Tercio 2: Área] ---> [Remate/Gol]   |
|     (Ponderado por Fuerza, Tácticas, Faltas, Tarjetas y Contraataques)|
|  4. Recalculo de Frische, Lesiones, Moral y Finanzas                  |
|                                                                       |
+-----------------------------------------------------------------------+
```

### 2.1. Estructura Temporal y Oportunidades de Ataque

Un partido regular consta de un número fijo de **20 oportunidades de ataque** repartidas en los 90 minutos reglamentarios mediante la función `get_minutes()`:

1. **Intervalo Base entre Ataques:**
   $$\text{Intervalo} = \frac{90}{20} = 4.5 \text{ minutos}$$
2. **Desviación Aleatoria (Jitter):**
   $$Z \in [-\text{spielraum}, \text{spielraum}], \quad \text{donde } \text{spielraum} = \lceil 4.5 \rceil - 1 = 4 \text{ minutos}$$
   Esto genera una variabilidad temporal donde los minutos de ataque no son fijos pero mantienen una distribución uniforme sobre los 90 minutos.

3. **Distribución Estocástica de Posesión:**
   La posesión del equipo local ($P_1$) depende de la relación de fuerzas en el centrocampo ($S_{M,1}$ vs $S_{M,2}$):
   $$P_1 = \text{round}\left( \frac{100}{\frac{S_{M,2}}{S_{M,1}} + 1} \right) + \text{Ventaja Localía} \quad (+4\% \text{ en liga})$$
   $$P_2 = 100 - P_1$$

   Durante la simulación de cada uno de los 20 ataques, la probabilidad de que el ataque corresponda al Equipo 1 es:
   $$P(\text{Ataque } 1) = \frac{\text{Ataques Restantes}_1}{20 - i + 1} \times 100\%$$
   Este algoritmo de muestreo sin reemplazo asegura que la proporción final de ataques corresponda exactamente con la posesión calculada.

---

### 2.2. Ponderaciones Matemáticas de Fuerza y Táctica

El simulador transforma los valores absolutos de fuerza y configuración táctica mediante dos funciones clave de ponderación:

#### A. Ponderación de Fuerza (`strengths_weight`)
$$f_{\text{fuerza}}(W) = 0.125 \times W + 0.0625$$

| Fuerza Base ($W$) | Valor Ponderado $f_{\text{fuerza}}(W)$ |
| :---: | :---: |
| 0.1 (Amateur / Mínimo) | 0.0750 |
| 2.0 (Bajo) | 0.3125 |
| 5.0 (Medio) | 0.6875 |
| 8.0 (Alto) | 1.0625 |
| 10.0 (Estrella) | 1.3125 |

*Efecto Matemático:* La función lineal reduce las diferencias extremas. Un equipo con fuerza 10.0 respecto a uno de fuerza 2.0 no es 5 veces más fuerte en la simulación, sino solo **4.2 veces** ($1.3125 / 0.3125$).

#### B. Ponderación Táctica (`tactics_weight`)
$$f_{\text{táctica}}(T) = 0.25 \times T + 0.5$$

| Nivel Táctico ($T$) | Factor Ponderado $f_{\text{táctica}}(T)$ | Impacto Funcional |
| :---: | :---: | :---: |
| 1 | 0.75 | Modificador defensivo / conservador (-25%) |
| 2 | 1.00 | Modificador neutro / estándar (0%) |
| 3 | 1.25 | Modificador ofensivo / intenso (+25%) |
| 4 | 1.50 | Modificador ultra-ofensivo / extremo (+50%) |

---

### 2.3. Árbol Estadístico de Decisión durante un Ataque (`starte_angriff`)

Cada intento de ataque sigue un flujo secuencial condicional:

```
[Inicio Ataque]
       |
       +---> ¿Supera 1er Tercio (Centrocampo)? (Probabilidad P_T1)
       |            |
       |            +-- NO --> ¿Robo y Contraataque Rápido? (15% prob)
       |            |                 |--> SÍ: Invierte roles, debilita defensa 20%
       |            |                 |--> NO: Saque de banda (33% prob nuevo ataque)
       |            |
       |            +-- SÍ --> [2do Tercio: Llegada al Área]
       |                              |
       |                              +---> ¿Comete Falta Defensor? (25% * Táctica)
       |                              |            |
       |                              |            +-- Amarilla (30%) / Roja (3%)
       |                              |            +-- Tiro Libre Indirecto / Penalti (19%)
       |                              |
       |                              +---> ¿Fuera de Juego? (17% * Táctica)
       |                              |
       |                              +---> ¿Disparo a Puerta Regular?
       |                                           |
       |                                           +-- Probabilidad de Gol = 30% * (S_S / S_T)
       |                                           +-- Parada del Portero / Bloqueo Defensivo
```

#### Probabilidades Matemáticas Clave por Evento:

1. **Superar Centrocampo:**
   $$P_{\text{T1}} = 50\% \times \frac{f_{\text{fuerza}}(S_{M,\text{att}})}{f_{\text{fuerza}}(S_{M,\text{def}})} \times \frac{f_{\text{tác}}(\text{orientación}_{\text{att}}) \times f_{\text{tác}}(\text{velocidad}_{\text{att}})}{f_{\text{tác}}(\text{orientación}_{\text{def}}) \times f_{\text{tác}}(\text{pases}_{\text{att}})}$$

2. **Probabilidad de Gol en Disparo Abierto:**
   $$P_{\text{Gol}} = 30\% \times \frac{f_{\text{fuerza}}(S_{S,\text{att}})}{f_{\text{fuerza}}(S_{T,\text{def}})}$$
   *Donde $S_{S,\text{att}}$ es la fuerza de la delantera atacante y $S_{T,\text{def}}$ es la fuerza del portero defensor.*

3. **Faltas y Sanciones:**
   * Probabilidad de falta en avance: $25\% \times f_{\text{táctica}}(\text{agresividad}_{\text{def}})$.
   * Tarjeta Amarilla tras falta: 30% de probabilidad (debilita la línea defensiva un 2%).
   * Tarjeta Roja tras falta: 3% de probabilidad (debilita la línea defensiva un 10%).

---

### 2.4. Evaluación de Calificaciones ("Bolzblatt Notes")

Al finalizar la simulación, la prensa virtual asigna calificaciones escolares alemanas ($1.0$ excelente a $6.0$ pésima) a cada línea del equipo mediante la función `staerkeBenoten($W$)`:

$$\text{Umbral}(i) = 1.485626 - 0.2759375 \times (i - 1), \quad \text{para } i \in [1, 6]$$

* **Nota 1 (Excelente):** $f_{\text{fuerza}}(W) > 1.4856$
* **Nota 2 (Notable):** $1.2097 < f_{\text{fuerza}}(W) \le 1.4856$
* **Nota 3 (Bien):** $0.9337 < f_{\text{fuerza}}(W) \le 1.2097$
* **Nota 4 (Suficiente):** $0.6578 < f_{\text{fuerza}}(W) \le 0.9337$
* **Nota 5 (Insuficiente):** $0.3818 < f_{\text{fuerza}}(W) \le 0.6578$
* **Nota 6 (Deficiente):** $f_{\text{fuerza}}(W) \le 0.3818$

---

## 3. Determinación de Características y Evolución Matemática del Jugador

### 3.1. Fuerza Efectiva en Campo (Forma y Frescura)

La fuerza teórica de un jugador (`staerke`) es atenuada en cada partido por su nivel de **Frescura** (`frische` $\in [0, 100]$):

$$S_{\text{efectiva}} = S_{\text{base}} \times \left(0.33 + 0.67 \times \frac{\text{Frische}}{100}\right)$$

* Un jugador al **100% de Frescura** rinde al **100%** de su fuerza base.
* Un jugador exhausto al **50% de Frescura** rinde únicamente al **66.5%** de su fuerza base.
* Un jugador al **0% de Frescura** rinde únicamente al **33.0%** de su fuerza base.

---

### 3.2. Desarrollo y Declive por Edad

El progreso del jugador se procesa en `aa_spieler_verbesserung.php`:

```
+------------------------------------------------------------------------+
|                       EVOLUCIÓN SEGÚN LA EDAD                          |
+------------------------------------------------------------------------+
|                                                                        |
|  [ Menores de 31 años (< 11.315 días) ]                                |
|  - Requiere haber jugado > 8 partidos.                                 |
|  - Incremento: ΔS = ceil( Random(1,6) / (10 * S_base) * 5 * 10 ) / 10  |
|  - Límite infranqueable: S_nueva <= Talento.                           |
|                                                                        |
|  [ Mayores de 31 años (>= 11.315 días) ]                               |
|  - Declive diario: ΔS_pérdida = round( S_base * (Edad_años - 28) / 70 )|
|  - Porteros pierden un valor fijo aleatorio entre -0.1 y -0.2.         |
|                                                                        |
+------------------------------------------------------------------------+
```

---

### 3.3. Determinación del Valor de Mercado

El cálculo del valor de mercado (`marktwert`) se ejecuta en `aa_marktwert_berechnen.php`. Sigue un modelo no lineal ponderado por la Fuerza actual, el Talento remanente y la curva de vida útil (Edad):

$$\text{Marktwert} \propto \frac{\text{Stärke}^2 \times \text{Talent}}{\sqrt{\text{Edad}}}$$

---

## 4. Propuesta de Nuevos Features para Jugadores

Actualmente los jugadores son unidimensionales (solo poseen `staerke`). A continuación se detallan las propuestas para transformar a los jugadores en personajes complejos y estratégicos.

```
+-----------------------------------------------------------------------+
|                    NUEVO PERFIL INTEGRAL DEL JUGADOR                  |
+-----------------------------------------------------------------------+
|                                                                       |
|  [ ATRIBUTOS PRIMARIOS ]   ---> Fuerza, Talento, Frescura, Moral.     |
|  [ ATRIBUTOS SECUNDARIOS ] ---> Velocidad, Pase, Remate, Defensa,     |
|                                 Agresividad, Liderazgo.               |
|  [ PERFIL TÁCTICO ]        ---> Posición Natural y Secundarias.       |
|  [ RASGOS ESPECIALES ]     ---> Traits (ej. Especialista a Parado).   |
|  [ PLAN PERSONALIZADO ]    ---> Enfoque de Entrenamiento Específico.  |
|  [ SALUD & SALA MÉDICA ]   ---> Historial de Lesiones y Progresión.   |
|                                                                       |
+-----------------------------------------------------------------------+
```

### 4.1. Atributos Secundarios y Sistema de Rasgos (Traits)

Proponemos la adición de 6 atributos secundarios (escala 1-100) que influyan directamente en la simulación:

1. **Velocidad / Aceleración (`speed`):**
   * *Impacto en simulación:* Aumenta en un $+20\%$ la probabilidad de éxito en el lanzamiento de contraataques rápidos (`quickCounterAttack`).
2. **Pase y Visión de Juego (`passing`):**
   * *Impacto en simulación:* Incrementa la tasa de éxito al superar el primer tercio de campo ($P_{\text{T1}}$).
3. **Remate y Definición (`shooting`):**
   * *Impacto en simulación:* Aumenta el multiplicador de conversión de disparos regulares a gol ($P_{\text{Gol}}$).
4. **Capacidad Defensiva y Duelos (`defending`):**
   * *Impacto en simulación:* Aumenta la probabilidad de interceptar o bloquear remates rivales (`shot_block`).
5. **Agresividad y Disciplina (`discipline`):**
   * *Impacto en simulación:* Modula la probabilidad individual de cometer falta o recibir tarjeta sin depender exclusivamente de la táctica del mánager.
6. **Liderazgo y Templanza (`leadership`):**
   * *Impacto en simulación:* Reduce las pérdidas de moral del equipo tras encajar goles o perder partidos consecutivos.

#### Habilidades Especiales (Traits Únicos):
* **Especialista a Abalón Parado:** $+25\%$ probabilidad de anotar en faltas directas y penaltis.
* **Incombustible:** Reduce el desgaste de frescura en partidos en un $-30\%$.
* **Muro Insuperable (Porteros):** $+15\%$ en la tasa de paradas en mano a mano dentro del área.

---

### 4.2. Especialización de Posición y Adaptabilidad

En el sistema actual, un delantero colocado como defensa no sufre penalización alguna. Proponemos:

* **Posiciones Específicas:** `PO` (Portero), `DFC` (Defensa Central), `LD/LI` (Laterales), `MCD` (Pivote), `MC` (Mediocentro), `MCO` (Mediapunta), `ED/EI` (Extremos), `DC` (Delantero Centro).
* **Penalización por Posición Incorrecta:**
  * Posición Natural: 100% de rendimiento.
  * Posición Secundaria / Afin: 85% de rendimiento.
  * Posición Incompatible (ej. Delantero en Defensa): 50% de rendimiento.

---

### 4.3. Planes de Entrenamiento Individualizados

Permitir al mánager seleccionar el tipo de entrenamiento semanal por jugador en `vertraege.php` / `entwicklung.php`:

* **Enfoque Físico:** Prioriza la recuperación de frescura y la prevención de lesiones.
* **Enfoque Táctico / Técnico:** Aumenta los atributos secundarios (Pase, Remate, Defensa).
* **Reconversión de Posición:** Permite que un jugador aprenda una nueva posición secundaria tras 60 días de entrenamiento dedicado.

---

### 4.4. Química de Vestuario e Historial Médico

1. **Química de Vestuario:**
   * Jugadores de la misma nacionalidad o con más de 2 temporadas en el mismo club generan un bono de **Química (+5% a +10%)** que incrementa la precisión colectiva de pases.
2. **Historial Médico y Lesiones Recurrentes:**
   * Se añade una tabla `man_spieler_verletzungen` para llevar el historial médico.
   * Jugadores con lesiones graves sufridas en el pasado tendrán una probabilidad incrementada de recaída si su frescura cae por debajo del 60%.

---

## 5. Propuesta de Nuevos Features para Clubes

```
+-----------------------------------------------------------------------+
|                      NUEVA ESTRUCTURA DEL CLUB                        |
+-----------------------------------------------------------------------+
|                                                                       |
|  [ CUERPO TÉCNICO ]       ---> Entrenadores Específicos y Ojeadores.  |
|  [ CANTERA REGIONAL ]     ---> Filiales e Identificación Internacional|
|  [ INSTALACIONES ]        ---> Centro Médico, Gimnasio, Analítica.    |
|  [ PATROCINIOS DINÁMICOS] ---> Primas por Objetivos y Reputación ELO. |
|                                                                       |
+-----------------------------------------------------------------------+
```

### 5.1. Sistema Avanzado de Cuerpo Técnico

Ampliar las opciones de contratación del club (`ver_personal.php`):

1. **Director de Cantera:** Mejora el talento mínimo generado en la cantera por `aa_spieler_erzeugen.php` ($+0.5$ a $+1.5$).
2. **Entrenadores de Línea (Defensivo / Ofensivo / Porteros):** Aumentan la ganancia de fuerza durante los entrenamientos semanales para su posición respectiva.
3. **Ojeador Jefe (Scout):** Permite visibilizar el verdadero talento oculta y proyección de jugadores en el mercado de transferencias internacional.

---

### 5.2. Red Internacional de Ojeadores y Canteras Regionales

* **Sedes de Cantera Internacional:** Permitir la apertura de academias juveniles en Sudamérica, África, Europa del Este y Asia.
* Cada región otorgará tendencias estadísticas propias en los juveniles nacidos (ej. Sudamérica: mayor probabilidad de extremos y delanteros de alto talento técnico).

---

### 5.3. Infraestructura Deportiva y Centro de Alto Rendimiento

Ampliación de instalaciones en la sección del estadio (`ver_stadion.php`):

* **Centro de Medicina Deportiva:** Reduce el tiempo de baja por lesión en un $-30\%$.
* **Gimnasio de Alto Rendimiento:** Recupera $+2$ puntos adicionales de frescura diaria a toda la plantilla.
* **Centro de Análisis Táctico y Datos:** Genera un informe previo al partido con las debilidades y porcentaje de efectividad de las tácticas del próximo rival.

---

### 5.4. Patrocinadores Dinámicos y Sistema de Reputación

* **Contratos con Primas por Objetivos:** Sustituir la prima fija por acuerdos negociables que incluyan bonificaciones por:
  * Clasificar a Copa Internacional.
  * Mantener la portería a cero en más de 10 partidos.
  * Promover más de 2 canteranos al primer equipo.
* **Reputación y Juego Limpio:** Clubes con pocas sanciones disciplinarias aumentan la venta de merchandising y abonos de temporada en un $+15\%$.

---

## 6. Conclusiones y Recomendaciones

1. **Robustez del Simulador Actual:** El motor de OpenSoccer posee un diseño matemático sólido, equilibrado y altamente eficiente mediante el uso de distribuciones probabilísticas y funciones de ponderación suaves.
2. **Impacto de las Propuestas:** La implementación de atributos secundarios para jugadores y el despliegue de infraestructuras avanzadas para clubes aumentará exponencialmente la profundidad estratégica del videojuego sin alterar la estabilidad del motor de juego existente.
3. **Hoja de Ruta Recomendada:**
   * **Fase 1:** Incorporación de posiciones específicas y penalización por posición en `aa_spieltag_simulation.php`.
   * **Fase 2:** Implementación del sistema de rasgos (traits) y atributos secundarios de jugadores.
   * **Fase 3:** Ampliación del cuerpo técnico y centro de alto rendimiento para clubes.
