# Análisis Estadístico del Motor de Juego y Propuestas de Nuevas Características (Features) para OpenSoccer

Documento técnico integral que detalla el análisis matemático, estadístico e hidrodinámico de la simulación de partidos y la progresión de los personajes en **OpenSoccer**, acompañado de una propuesta estructurada para nuevos *features* orientados a jugadores y clubes.

---

## Índice

1. [Visión General e Introducción](#1-visión-general-e-introducción)
2. [Análisis Estadístico y Matemático del Motor de Juego](#2-análisis-estadístico-y-matemático-del-motor-de-juego)
   - [A. Generación Estadística de Jugadores Juveniles](#a-generación-estadística-de-jugadores-juveniles)
   - [B. Arquitectura y Mecánica del Motor de Simulación de Partidos](#b-arquitectura-y-mecánica-del-motor-de-simulación-de-partidos)
   - [C. Modelo de Progresión, Desarrollo y Envejecimiento](#c-modelo-de-progresión-desarrollo-y-envejecimiento)
   - [D. Algoritmos de Valoración Económica, Ojeo y Clasificación ELO](#d-algoritmos-de-valoración-económica-ojeo-y-clasificación-elo)
3. [Propuestas de Nuevos Features para Jugadores](#3-propuestas-de-nuevos-features-para-jugadores)
   - [A. Atributos Secundarios Técnicos y Físicos](#a-atributos-secundarios-técnicos-y-físicos)
   - [B. Sistema de Rasgos de Personalidad y Habilidades Especiales (Traits)](#b-sistema-de-rasgos-de-personalidad-y-habilidades-especiales-traits)
   - [C. Sistema Dinámico de Química y Afinidad de Plantilla](#c-sistema-dinámico-de-química-y-afinidad-de-plantilla)
   - [D. Gestión de Lesiones Específicas y Biomecánica de Recuperación](#d-gestión-de-lesiones-específicas-y-biomecánica-de-recuperación)
4. [Propuestas de Nuevos Features para Clubes](#4-propuestas-de-nuevos-features-para-clubes)
   - [A. Centros de Entrenamiento Especializados por Sector](#a-centros-de-entrenamiento-especializados-por-sector)
   - [B. Sistema Dinámico de Patrocinadores por Objetivos](#b-sistema-dinámico-de-patrocinadores-por-objetivos)
   - [C. Expansión Modular del Estadio e Infraestructura Comercial](#c-expansión-modular-del-estadio-e-infraestructura-comercial)
   - [D. Academia Internacional y Ojeo de Cantera Focalizado](#d-academia-internacional-y-ojeo-de-cantera-focalizado)

---

## 1. Visión General e Introducción

El sistema de simulación de **OpenSoccer** combina algoritmos deterministas con procesos estocásticos (probabilísticos montecarlo) para recrear la gestión deportiva y la simulación táctica de fútbol.

Este documento analiza en profundidad:
1. El sustento estadístico del motor de partidos (`aa_spieltag_simulation.php`), generación de jugadores (`aa_spieler_erzeugen.php`), mejora/declive (`aa_spieler_verbesserung.php`) y algoritmos auxiliares de ELO, ojeador y mercado (`zzserver.php`).
2. Diseños funcionales para expandir la complejidad táctica y estratégica de los **Jugadores** y los **Clubes**.

---

## 2. Análisis Estadístico y Matemático del Motor de Juego

### A. Generación Estadística de Jugadores Juveniles

Los jugadores juveniles nacen mediante el script automatizado `aa_spieler_erzeugen.php`.

#### 1. Distribución Probabilística de Posiciones
La selección de la posición táctica se calcula mediante la función `choosePosition()`:
* **Portero (`T`):** 12% ($P(T) = 0.12$)
* **Delantero (`S`):** 24% ($P(S) = 0.24$)
* **Centrocampista (`M`):** 50% ($P(M) = 0.50$)
* **Defensa (`A`):** 14% ($P(A) = 0.14$)

#### 2. Distribución Log-Normal del Talento y Fuerza
La asignación de talento no es lineal, sino que sigue una distribución logarítmico-exponencial a través de `getRandomStrength($min, $max)`:

$$\text{ln\_low} = \ln(\text{min}), \quad \text{ln\_high} = \ln(\text{max}), \quad \text{scale} = \text{ln\_high} - \text{ln\_low}$$
$$X \sim U(0,1), \quad \text{rand} = X^{1.15} \times \text{scale} + \text{ln\_low}$$
$$\text{Talento} = \text{round}\left(e^{\text{rand}}, 1\right)$$

Donde el parámetro de exponente $1.15$ sesga ligeramente los resultados hacia valores inferiores, garantizando que los súper talentos sean estadísticamente inusuales.

La fuerza inicial se deriva del talento generado multiplicándolo por una proporción aleatoria:
$$\text{Anfangsstärke} \sim U(0.5, 0.9)$$
$$\text{Stärke} = \text{round}\left(\text{Talento} \times \text{Anfangsstärke}, 1\right)$$

#### 3. Techo de Talento según Nivel de Cantera
El nivel de la cantera del club (`jugendarbeit` del 1 al 5) delimita el intervalo $[T_{\min}, T_{\max}]$ y el costo salarial inicial:

| Nivel de Cantera | $T_{\min}$ | $T_{\max}$ | Salario Base (€) |
| :---: | :---: | :---: | :---: |
| 1 | 2.1 | 5.9 | 300.000 € |
| 2 | 2.8 | 6.9 | 500.000 € |
| 3 | 3.5 | 7.9 | 700.000 € |
| 4 | 4.2 | 8.9 | 900.000 € |
| 5 | 4.9 | 9.9 | 1.200.000 € |

---

### B. Arquitectura y Mecánica del Motor de Simulación de Partidos

El motor `aa_spieltag_simulation.php` simula partidos iterando sobre eventos por minutos en función de la fuerza de las líneas tácticas.

```
+---------------------------------------------------------------------------------+
|                       FLUJO DE SIMULACIÓN DE PARTIDO                            |
+---------------------------------------------------------------------------------+
|                                                                                 |
| 1. Ponderación de Fuerza Lineal (T, A, M, S) considerando Frescura               |
| 2. Ponderación Táctica (Orientación Defensiva / Ofensiva)                       |
| 3. Determinación de Posesión y Distribución del Número de Ataques (get_minutes) |
| 4. Árbol Estocástico de Resoluciones de Ataque (M -> A -> S/T)                   |
| 5. Actualización de Frescura, Moral, Lesiones, Tarjetas y Finanzas              |
|                                                                                 |
+---------------------------------------------------------------------------------+
```

#### 1. Ponderación de Fuerza Ajustada por Frescura
Para cada una de las 4 líneas ($P \in \{T, A, M, S\}$), se suma el nivel de los 11 titulares ponderado por la frescura física ($F \in [0, 100]$):

$$\text{Stärke}_{\text{bruta}}(P) = \sum_{i \in P} \text{staerke}_i \times \left(0.33 + 0.67 \times \frac{F_i}{100}\right)$$

Posteriormente, las líneas colectivas se normalizan por el número de jugadores teóricos en dicha posición:
* Defensa: $\text{Stärke}(A) = \frac{\text{Stärke}_{\text{bruta}}(A)}{4} \times \frac{\text{taktik}_v}{100}$
* Medio campo: $\text{Stärke}(M) = \frac{\text{Stärke}_{\text{bruta}}(M)}{4}$
* Delantera: $\text{Stärke}(S) = \frac{\text{Stärke}_{\text{bruta}}(S)}{2} \times \frac{\text{taktik}_a}{100}$

Donde $\text{taktik}_v$ y $\text{taktik}_a$ ajustan según la orientación táctica del equipo (`ausrichtung`):
* **Ultra-defensiva:** $\text{taktik}_v = 115\%, \quad \text{taktik}_a = 80\%$
* **Defensiva:** $\text{taktik}_v = 110\%, \quad \text{taktik}_a = 90\%$
* **Normal:** $\text{taktik}_v = 100\%, \quad \text{taktik}_a = 100\%$
* **Ofensiva:** $\text{taktik}_v = 90\%, \quad \text{taktik}_a = 110\%$
* **Ultra-ofensiva:** $\text{taktik}_v = 70\%, \quad \text{taktik}_a = 125\%$

#### 2. Ponderación de Transformación No Lineal de Fuerzas y Tácticas
Para evaluar el éxito de un evento dentro del partido, el motor aplica transformaciones:

$$\text{strengths\_weight}(W) = 0.125 \times W + 0.0625$$
$$\text{tactics\_weight}(T) = T \times 0.25 + 0.5$$

#### 3. Distribución Estadística de Posesión de Balón y Minutos de Ataque
La posesión del medio campo de los equipos 1 y 2 se asigna como:

$$\text{Poss}_1 = \text{round}\left( \frac{100}{\frac{\text{Stärke}_2(M)}{\text{Stärke}_1(M)} + 1} \right) + \text{Bonus}_{\text{Local}} (+4\%)$$
$$\text{Poss}_2 = 100 - \text{Poss}_1$$

La función `get_minutes(20, \text{Poss}_1)` distribuye 20 ataques en los 90 minutos mediante intervalos aleatorios:
$$\text{Interv} = \frac{90}{20} = 4.5, \quad \text{spielraum} = \lceil 4.5 \rceil - 1 = 4$$
$$\Delta t \sim U(-4, 4), \quad t_{k} = t_{k-1} + 4.5 + \Delta t$$

#### 4. Árbol Probabilístico de Resolución de Ataques
Para cada ataque, el éxito de superar las líneas defensivas se calcula con probabilidades compuestas:

1. **Superar el primer tercio (Lucha de Centrocampistas):**
   $$P(\text{Tercio}_1) = 50\% \times \frac{\text{strengths\_weight}(M_{att})}{\text{strengths\_weight}(M_{def})} \times \frac{\text{tactics\_weight}(T_{att}[0]) \times \text{tactics\_weight}(T_{att}[1])}{\text{tactics\_weight}(T_{def}[0]) \times \text{tactics\_weight}(T_{att}[2])}$$

2. **Probabilidad de Falta Defensiva:**
   $$P(\text{Foul}) = 25\% \times \text{tactics\_weight}(T_{def}[\text{aggress}])$$
   * Si ocurre falta: 30% tarjeta amarilla, 3% tarjeta roja directa.

3. **Probabilidad de Disparo Directo a Gol:**
   $$P(\text{Disparo}) = 62\% \times \frac{\text{strengths\_weight}(S_{att})}{\text{strengths\_weight}(A_{def})} \times \text{tactics\_weight}(T_{att}[2]) \times \text{tactics\_weight}(T_{att}[3])$$

4. **Conversión de Gol vs. Parada del Portero:**
   $$P(\text{Gol}) = 30\% \times \frac{\text{strengths\_weight}(S_{att})}{\text{strengths\_weight}(T_{def})}$$
   * En caso de no convertirse en gol: 50% de probabilidad de ser bloqueado por la defensa ($\text{strengths\_weight}(A_{def})$) y 50% salvado por el portero.

---

### C. Modelo de Progresión, Desarrollo y Envejecimiento

El motor de desarrollo de personajes (`aa_spieler_verbesserung.php`) procesa ciclos periódicos de mejora o deterioro.

#### 1. Jóvenes y Adultos (< 31 años / < 11.315 días)
Un jugador progresará únicamente si ha disputado más de 8 partidos (`spiele_gesamt > 8`):
$$Z \sim U(1, 6), \quad \Delta S_{\text{bruto}} = \frac{Z / 10}{\text{Stärke}} \times 5$$
$$\Delta S = \min\left(\lceil \Delta S_{\text{bruto}} \times 10 \rceil / 10, \quad \text{Talento} - \text{Stärke}\right)$$
Con restricción $0.1 \le \Delta S \le 1.2$.

#### 2. Veteranos ($\ge 31$ años / $\ge 11.315$ días)
Los jugadores pierden nivel de forma determinista proporcional a su edad:
$$\text{minusP} = \frac{\lfloor \text{Edad}_{\text{años}} - 28 \rfloor}{70}$$
$$\Delta S_{\text{pérdida}} = \text{round}(\text{Stärke} \times \text{minusP}, 1)$$
La fuerza no puede caer por debajo de $0.1$.

---

### D. Algoritmos de Valoración Económica, Ojeo y Clasificación ELO

#### 1. Ecuación Estándar de Valor de Mercado
Definida en `zzserver.php`:

$$\text{Marktwert} = \text{round}\left( \frac{1.75^{\text{Stärke}} \times \text{Talento} \times 30000}{\left( \frac{\lfloor \text{Edad} / 365 \rfloor}{27} \right)^{1.7}} + \lfloor 100 + \text{Rand}(0,1000) \rfloor \right)$$

* La dependencia exponencial con la fuerza ($1.75^{\text{Stärke}}$) genera un aumento no lineal del valor para jugadores estrella.
* El denominador penaliza el valor económico a medida que el jugador supera los 27 años.

#### 2. Algoritmo de Estimación Oculta del Ojeador (`schaetzungVomScout`)
El juego calcula una estimación determinista pseudoaleatoria utilizando el hash MD5 de la combinación del ID del club, el nivel del ojeador (1 a 5) y el ID del jugador:

```
ScoutHash = MD5(TeamID + ScoutLevel + PlayerID)
```

La desviación máxima posible se reduce conforme aumenta el nivel del ojeador:
$$\text{Abweichung}_{\max} = \pm \left(0.35 - 0.05 \times \text{ScoutLevel}\right)$$

La estimación final retornada al manager es:
$$\text{Talento}_{\text{Scout}} = \text{round}\left( \text{Talento}_{\text{Real}} \times (1 + \text{HashFactor} \times \text{Abweichung}_{\text{paso}}), 1 \right)$$
Limitado siempre a un suelo igual a la fuerza actual del jugador.

#### 3. Sistema de Calificación ELO (`eloChange`)
El ajuste de ELO de los equipos tras un partido oficial aplica la fórmula internacional ELO modificada con margen de victoria y peso por tipo de torneo:

$$\Delta \text{ELO} = 40 \times W_{\text{Torneo}} \times K_{\text{Goles}} \times (S - E)$$

* **Peso del Torneo ($W_{\text{Torneo}}$):** Liga = 1.0, Copa Nacional = 2.0, Copa Internacional = 1.3, Amistosos = 0.
* **Expectativa de Resultado ($E$):**
  $$E = \frac{1}{10^{\frac{\Delta \text{ELO}_{\text{Ajustado}}}{400}} + 1}, \quad \text{donde } \Delta \text{ELO}_{\text{Ajustado}} = \text{ELO}_{\text{Rival}} - (\text{ELO}_{\text{Propio}} + 100_{\text{Local}})$$
* **Factor por Diferencia de Goles ($K_{\text{Goles}}$):**
  * Diferencia 0 o 1 gol: $K = 1.0$
  * Diferencia 2 goles: $K = 1.5$
  * Diferencia $\ge 3$ goles: $K = \frac{11 + \Delta \text{Goles}}{8}$

---

## 3. Propuestas de Nuevos Features para Jugadores

### A. Atributos Secundarios Técnicos y Físicos
Actualmente, los jugadores cuentan únicamente con **Staerke** (Fuerza global). Se propone desglosar la capacidad táctica en 6 atributos secundarios (escala 1 a 100):

```
+-------------------------------------------------------------------------+
|                    DESGLOSE DE ATRIBUTOS SECUNDARIOS                    |
+-------------------------------------------------------------------------+
|                                                                         |
|  [ FÍSICOS ]     --->  Velocidad (VEL), Resistencia (RES)               |
|  [ TÉCNICOS ]    --->  Pase (PAS), Remate (REM), Regate (REG)           |
|  [ DEFENSIVOS ]  --->  Intercepción / Entradas (DEF)                    |
|                                                                         |
+-------------------------------------------------------------------------+
```

#### Impacto en el Motor de Simulación:
* **Velocidad (VEL):** Incrementa la probabilidad de éxito en contragolpes rápidos (`quickCounterAttack`).
* **Resistencia (RES):** Reduce el consumo de frescura por partido. La pérdida de frescura pasa de ser un valor fijo a:
  $$\Delta \text{Frescura} = \text{Base} \times \left(1.5 - \frac{\text{RES}}{100}\right)$$
* **Pase (PAS):** Incrementa la precisión al superar el primer tercio del campo (lucha de centrocampistas).
* **Remate (REM):** Eleva la probabilidad de gol frente al portero en tiros lejanos y penaltis.

---

### B. Sistema de Rasgos de Personalidad y Habilidades Especiales (Traits)
Cada jugador podrá desarrollar o nacer con hasta 2 **Rasgos Únicos** que afectan la dinámica de equipo y partido:

1. **Líder de Vestuario (`Leader`):**
   * Previene la pérdida acelerada de moral del equipo tras una derrota.
2. **Especialista en Balón Parado (`SetPieceSpec`):**
   * Aumenta un $+20\%$ la efectividad en faltas directas y saques de esquina.
3. **Propenso a Lesiones (`GlassMan`):**
   * Aumenta al doble la probabilidad de lesión cuando la frescura cae por debajo del 70%.
4. **Jugador de Partidos Clave (`ClutchPlayer`):**
   * Recibe un bono temporal de $+0.5$ de fuerza en derbis y eliminatorias de Copa.

---

### C. Sistema Dinámico de Química y Afinidad de Plantilla
Introducción del parámetro **Química de Equipo** ($Q \in [0, 100]$):

$$\text{Química} = w_1 \cdot \text{NacionalidadCompartida} + w_2 \cdot \text{AntigüedadPromedio} + w_3 \cdot \text{EstabilidadTáctica}$$

* **Efecto Funcional:** La Química añade un multiplicador global del $\pm 5\%$ a las fuerzas de línea durante los partidos.
* **Mecanismo:** Cambios masivos de plantilla en un solo periodo de traspasos reducen drásticamente la química, obligando a mantener una estructura base.

---

### D. Gestión de Lesiones Específicas y Biomecánica de Recuperación
Sustituir el contador genérico de días de baja por un sistema de **Diagnóstico Médico**:

| Tipo de Lesión | Severidad | Tiempo Base | Impacto Fisioterapeuta | Secuela Potencial |
| :--- | :--- | :--- | :--- | :--- |
| Sobrecarga Muscular | Leve | 2 - 4 días | $-50\%$ tiempo | Ninguna |
| Esguince de Tobillo | Moderada | 7 - 14 días | $-30\%$ tiempo | Reducción temporal de VEL |
| Rotura de Ligamento Cruzado | Grave | 60 - 90 días | $-15\%$ tiempo | Pérdida de $-0.5$ de Fuerza permanente |

---

## 4. Propuestas de Nuevos Features para Clubes

### A. Centros de Entrenamiento Especializados por Sector
Expandir las instalaciones del club permitiendo la construcción y mejora modular de instalaciones deportivas:

```
+------------------------------------------------------------------------+
|                     NUEVA INFRAESTRUCTURA DE CLUB                      |
+------------------------------------------------------------------------+
|                                                                        |
|  [ Pabellón Táctico ]       ---> Aumenta velocidad de aprendizaje     |
|  [ Centro Médico / SPA ]    ---> Recupera Frescura +15% más rápido     |
|  [ Gimnasio de Alto Riego ] ---> Aumenta atributo de Resistencia       |
|                                                                        |
+------------------------------------------------------------------------+
```

1. **Pabellón de Análisis Táctico:** Aumenta la adaptabilidad del equipo a cambiar de formación táctica sin penalizaciones de rendimiento.
2. **Centro de Fisioterapia y Hidroterapia:** Aumenta la velocidad de recuperación de frescura diaria en reposo de $+1\%$ a $+3\%$ extra.
3. **Gimnasio de Alto Rendimiento:** Permite programar sesiones específicas para mejorar atributos físicos individuales.

---

### B. Sistema Dinámico de Patrocinadores por Objetivos
Reemplazar los contratos de sponsor estáticos por contratos negociables con estructura de riesgo y recompensa:

* **Sponsor Conservador:** Pago fijo alto por temporada, primas reducidas por victoria. Penalización baja si se desciende.
* **Sponsor Agresivo:** Pago fijo bajo, pero bonus multiplicado por ganar el título o clasificarse a copas internacionales. Penalizaciones severas por incumplimiento de objetivos deportivos.

---

### C. Expansión Modular del Estadio e Infraestructura Comercial
Actualmente el estadio cuenta con capacidad global y comercios básicos. Se propone dividir la expansión en sectores y niveles de confort:

1. **Zonas VIP / Palcos Corporativos:** Generan ingresos altos independientemente del resultado del partido, orientados a empresas.
2. **Cubierta y Climatización del Estadio:** Reduce la caída de asistencia en días de mal tiempo (nieve o lluvia simulada).
3. **Centro Comercial del Club (Retail Complex):** Genera flujo de caja continuo diario libre de mantenimiento en días sin partido.

---

### D. Academia Internacional y Ojeo de Cantera Focalizado
Permitir la apertura de **Sedes de Cantera en el Extranjero**:

* Los mánagers pueden invertir fondos en establecer academias en países específicos (ej. Brasil, Argentina, España, Alemania).
* Aumenta la probabilidad de generar jóvenes talentos de dichas nacionalidades con atributos técnicos específicos elevados (ej. regate en Sudamérica, disciplina defensiva en Europa).
