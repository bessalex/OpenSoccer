# Análisis Estadístico, Motor de Simulación y Propuestas de Nuevas Características (Features) para OpenSoccer

Este documento ofrece un análisis técnico, matemático y estadístico en profundidad sobre los motores de simulación, determinación de atributos y mecánicas del juego **OpenSoccer**, así como un análisis completo y estructurado de nuevas características (*features*) potenciales para jugadores y clubes.

---

## ÍNDICE
1. [PARTE I: ANÁLISIS ESTADÍSTICO Y MATEMÁTICO DEL MOTOR DE JUEGO](#1-parte-i-análisis-estadístico-y-matemático-del-motor-de-juego)
   - [1.1 Generación de Jugadores y Determinación de Atributos](#11-generación-de-jugadores-y-determinación-de-atributos)
   - [1.2 Algoritmo de Simulación de Partidos (`aa_spieltag_simulation.php`)](#12-algoritmo-de-simulación-de-partidos-aa_spieltag_simulationphp)
   - [1.3 Evolución, Desarrollo y Envejecimiento (`aa_spieler_verbesserung.php`)](#13-evolución-desarrollo-y-envejecimiento-aa_spieler_verbesserungphp)
   - [1.4 Valoración Económica y Mercado (`marktwertAusdruck`)](#14-valoración-económica-y-mercado-marktwertausdruck)
   - [1.5 Sistema de Ranking Elo (`eloChange`)](#15-sistema-de-ranking-elo-elochange)
   - [1.6 Economía del Estadio y Finanzas](#16-economía-del-estadio-y-finanzas)
2. [PARTE II: ANÁLISIS Y PROPUESTAS DE NUEVAS CARACTERÍSTICAS (FEATURES)](#2-parte-ii-análisis-y-propuestas-de-nuevas-características-features)
   - [2.1 Nuevas Características para Jugadores](#21-nuevas-características-para-jugadores)
   - [2.2 Nuevas Características para Clubes](#22-nuevas-características-para-clubes)
   - [2.3 Esquema SQL e Impacto Técnico Estimado](#23-esquema-sql-e-impacto-técnico-estimado)

---

## 1. PARTE I: ANÁLISIS ESTADÍSTICO Y MATEMÁTICO DEL MOTOR DE JUEGO

### 1.1 Generación de Jugadores y Determinación de Atributos

La generación de nuevos jugadores juveniles (*cantera*) está gobernada por el script `aa_spieler_erzeugen.php`.

#### 1. Distribución Probabilística de Posiciones
La asignación de posición para cada nuevo jugador sigue una distribución discreta de probabilidad:
$$P(\text{Posición}) = \begin{cases}
0.12 & \text{si Posición = 'T' (Portero / Torwart)} \\
0.24 & \text{si Posición = 'S' (Delantero / Sturm)} \\
0.50 & \text{si Posición = 'M' (Centrocampista / Mittelfeld)} \\
0.14 & \text{si Posición = 'A' (Defensa / Abwehr)}
\end{cases}$$

#### 2. Distribución Logarítmico-Exponencial de Talento
El talento ($T$) no se distribuye de forma uniforme lineal, sino mediante una transformación logarítmica ponderada para favorecer una menor frecuencia de súper-talentos y una mayor densidad en rangos medios-bajos:

Dado un rango de talento $[T_{\min}, T_{\max}]$ según el nivel de la cantera (`jugendarbeit`):
* **Nivel 1:** $T \in [2.1, 5.9]$, Salario = 300.000 €
* **Nivel 2:** $T \in [2.8, 6.9]$, Salario = 500.000 €
* **Nivel 3:** $T \in [3.5, 7.9]$, Salario = 700.000 €
* **Nivel 4:** $T \in [4.2, 8.9]$, Salario = 900.000 €
* **Nivel 5:** $T \in [4.9, 9.9]$, Salario = 1.200.000 €

La fórmula de generación es:
$$U \sim \text{Uniforme}(0, 1)$$
$$R = U^{1.15} \cdot (\ln T_{\max} - \ln T_{\min}) + \ln T_{\min}$$
$$T = \text{round}\left(e^{R}, 1\right)$$

Donde el parámetro $1.15$ es el exponente de escoramiento de la distribución.

#### 3. Determinación de la Fuerza Inicial
La fuerza inicial ($S_0$) del jugador recién generado se calcula como una fracción del talento mediante un factor aleatorio logarítmico de inicio:
$$A = \text{getRandomStrength}(0.5, 0.9)$$
$$S_0 = \text{round}(T \cdot A, 1)$$

---

### 1.2 Algoritmo de Simulación de Partidos (`aa_spieltag_simulation.php`)

El motor de simulación opera como un proceso estocástico discreto basado en una cadena de decisiones continuas / árbol de probabilidades de Markov durante 90 minutos más tiempo añadido.

#### 1. Cálculo de Posesión de Balón
La posesión de balón de cada equipo ($P_1, P_2$) depende de la relación de fuerza de sus centrocampistas ($S_{M1}, S_{M2}$) ajustada por el factor de localía:

$$P_1' = \text{round}\left(\frac{100}{\frac{S_{M2}}{S_{M1}} + 1}\right)$$

Ajuste de ventaja de campo (aplica salvo en partidos amistosos, finales de copa o torneos neutrales):
$$P_1 = \min(100, P_1' + 4), \quad P_2 = 100 - P_1$$

#### 2. Cronograma de Ataques
Se fija un total de $N = 20$ ataques por partido. El número de ataques destinados al Equipo 1 es:
$$N_1 = \text{round}\left(N \cdot \frac{P_1}{100}\right)$$

Los minutos de cada ataque $i \in \{1, \dots, N\}$ se distribuyen a intervalos de $\Delta t = \frac{90}{N} = 4.5$ minutos, con una perturbación aleatoria simétrica $\delta_i \in [-\text{spielraum}, \text{spielraum}]$ que se compensa progresivamente:
$$t_i = t_{i-1} + 4.5 + \delta_i, \quad \delta_i = -\delta_{i-1}$$

#### 3. Árbol de Decisiones de Cada Ataque

##### Funciones de Ponderación Táctica y de Fuerza:
* **Ponderación Táctica:** $W_t(x) = 0.25x + 0.5$, donde $x \in \{1, 2, 3\}$ representa el nivel táctico fijado por el mánager.
* **Ponderación de Fuerza Reescalada:** $W_s(S) = 0.125 S + 0.0625$, donde $S$ es la fuerza de la línea correspondiente.

##### Paso 1: Superar el Primer Tercio del Campo
Probabilidad de que el ataque progrese:
$$P(\text{Avanzar 1º Tercio}) = 50\% \cdot \frac{W_s(S_{M,\text{att}})}{W_s(S_{M,\text{def}})} \cdot \frac{W_t(\text{ausrichtung}_{\text{att}}) \cdot W_t(\text{ausrichtung}_{\text{def}}) \cdot W_t(\text{geschw}_{\text{att}})}{W_t(\text{pass\_auf}_{\text{att}})}$$

* **Si no progresa:**
  * Con probabilidad $15\% \cdot \frac{W_s(S_{A,\text{def}})}{W_s(S_{S,\text{att}})} \cdot W_t(\text{risk\_pass}_{\text{att}}) \cdot W_t(\text{druck}_{\text{def}})$, el defensor inicia un **contraataque rápido**.
  * De lo contrario, se produce un saque de banda ($P = 33\%$), repartido según fuerza de medio campo.

##### Paso 2: Desarrollo del Ataque y Faltas en el Primer Tercio
Si el equipo atacante supera el primer tercio:
1. **Falta defensiva:** Probabilidad $P(\text{Falta}) = 25\% \cdot W_t(\text{aggress}_{\text{def}})$.
   * Si hay falta:
     * Amonestación (Tarjeta Amarilla): $P = 30\%$. (Aplica reducción de fuerza de equipo $\times 0.98$).
     * Expulsión (Tarjeta Roja): $P = 3\%$. (Aplica reducción de fuerza de equipo $\times 0.90$).
     * **Tiro Libre Indirecto:**
       * Probabilidad de remate: $30\% \cdot \frac{W_s(S_{S,\text{att}})}{W_s(S_{A,\text{def}})}$.
       * Probabilidad de gol: $30\% \cdot \frac{W_s(S_{S,\text{att}})}{W_s(S_{T,\text{def}})}$.
2. **Fuera de Juego:** Probabilidad $P(\text{Abseits}) = 17\% \cdot W_t(\text{ausrichtung}_{\text{att}}) \cdot W_t(\text{pass\_auf}_{\text{att}})$.

##### Paso 3: Llegada a Zona de Peligro y Disparo a Puerta
Si el ataque avanza al último tercio:
1. **Falta en área / borde del área:** $P = 25\% \cdot W_t(\text{aggress}_{\text{def}})$.
   * Tarjeta Amarilla ($P=33\%$) / Roja ($P=3\%$).
   * **Penalti:** Probabilidad $19\% \cdot \frac{W_s(S_{S,\text{att}})}{W_s(S_{A,\text{def}})}$.
     * Probabilidad de conversión en gol: $\frac{77\%}{W_s(S_{T,\text{def}})}$.
   * **Tiro Libre Directo:** (Si no fue penalti)
     * Probabilidad de tiro directo: $40\% \cdot W_s(S_{S,\text{att}})$.
     * Probabilidad de gol: $\frac{40\%}{W_s(S_{T,\text{def}})}$.
2. **Jugada de Disparo Prometedora:**
   * Probabilidad de disparo: $62\% \cdot \frac{W_s(S_{S,\text{att}})}{W_s(S_{A,\text{def}})} \cdot W_t(\text{pass\_auf}_{\text{att}}) \cdot W_t(\text{risk\_pass}_{\text{att}})$.
   * **Probabilidad de Gol:**
     $$P(\text{Gol}) = 30\% \cdot \frac{W_s(S_{S,\text{att}})}{W_s(S_{T,\text{def}})}$$
   * **Si no es Gol:**
     * Bloqueo defensivo: $P = 50\% \cdot W_s(S_{A,\text{def}})$.
     * Parada del portero: En caso contrario.

##### Paso 4: Calificación Periodística del Bolzblatt
Al finalizar el partido, la revista deportiva ficticia *Bolzblatt* asigna notas del 1 (Excelente) al 6 (Pésimo) a cada línea del equipo basándose en la función de fuerza ponderada:
$$\text{Nota}(W_s) = i \quad \text{donde } W_s \le 1.485626 - 0.2759375(i-1) \quad \text{para } i \in \{6, 5, 4, 3, 2, 1\}$$

---

### 1.3 Evolución, Desarrollo y Envejecimiento (`aa_spieler_verbesserung.php`)

El desarrollo de los jugadores se calcula de forma estocástica cuando han disputado más de 8 partidos oficiales (`spiele_gesamt > 8`).

#### 1. Progresión de Jugadores Jóvenes (Edad < 31 años / < 11.315 días)
Un jugador joven incrementa su fuerza en un valor diferencial $\Delta S$:
$$Z \sim \text{UniformeDiscreta}(1, 6)$$
$$\Delta S' = \frac{Z / 10}{S} \cdot 5$$
$$\Delta S = \min\left(1.2, \max\left(0.1, \left\lceil \Delta S' \cdot 10 \right\rceil / 10\right)\right)$$

El nuevo nivel $S_{\text{nuevo}} = \min(T, S + \Delta S)$ queda acotado superiormente por el **Talento** ($T$).

#### 2. Declive por Edad (Edad $\ge 31$ años / $\ge 11.315$ días)
Para jugadores de campo (defensas, medios, delanteros), la pérdida de fuerza por envejecimiento se rige por:
$$\text{Pérdida\%} = \frac{\lfloor \text{Edad en años} - 28 \rfloor}{70}$$
$$\Delta S_{\text{decline}} = \max\left(0.1, \text{round}\left(S \cdot \text{Pérdida\%}, 1\right)\right)$$
$$S_{\text{nuevo}} = \max(0.1, S - \Delta S_{\text{decline}})$$

Para porteros (`T`), la pérdida es menos drástica y fija: $\Delta S_{\text{decline}} \in \{0.1, 0.2\}$ aleatorio.

---

### 1.4 Valoración Económica y Mercado (`marktwertAusdruck`)

El **Valor de Mercado** (`marktwert`) se actualiza mediante una ecuación no lineal exponencial ajustada por edad y talento:

$$\text{ValorMercado} = \text{round}\left( \frac{1.75^{S} \cdot T \cdot 30.000}{\left(\frac{\lfloor \text{Edad}/365 \rfloor}{27}\right)^{1.7}} \right) + \text{floor}\left(100 + \text{rand}(0, 1000)\right)$$

#### Propiedades Matemáticas de la Fórmula:
1. **Crecimiento Exponencial con la Fuerza ($S$):** La base $1.75^S$ genera una curva exponencial acelerada para jugadores de alto nivel.
2. **Proporcionalidad Lineal con el Talento ($T$):** A igual fuerza, un jugador con mayor techo potencial cotiza más alto.
3. **Penalización Potencial por Edad:** El denominador $\left(\frac{\text{Edad}}{27}\right)^{1.7}$ actúa como un factor de descuento acelerado a partir de los 27 años.

---

### 1.5 Sistema de Ranking Elo (`eloChange`)

OpenSoccer implementa una variante del algoritmo Elo de ajedrez adaptada al fútbol con ponderación por tipo de torneo y diferencia de goles.

#### 1. Distancia Elo con Ventaja de Campo
$$D = \text{Elo}_{\text{local}} + 100 - \text{Elo}_{\text{visitante}}$$

#### 2. Esperanza Matemático-Estadística de Victoria
$$E_{\text{local}} = \frac{1}{10^{(-D / 400)} + 1}$$

#### 3. Factor de Diferencia de Goles ($G$)
$$G = \begin{cases}
1.0 & \text{si } \Delta \text{goles} \in \{0, 1\} \\
1.5 & \text{si } \Delta \text{goles} = 2 \\
\frac{11 + \Delta \text{goles}}{8} & \text{si } \Delta \text{goles} \ge 3
\end{cases}$$

#### 4. Ponderación por Competición ($K_{\text{torneo}}$)
* **Liga:** $K = 1.0$
* **Copa Nacional (Pokal):** $K = 2.0$
* **Copa Internacional (Cup):** $K = 1.3$
* **Amistoso:** $K = 0.0$ (no altera el Elo)

#### 5. Variación Final de Puntos Elo
$$\Delta \text{Elo} = 40 \cdot K_{\text{torneo}} \cdot G \cdot (R - E_{\text{local}})$$
Donde $R \in \{1.0 \text{ (victoria)}, 0.5 \text{ (empate)}, 0.0 \text{ (derrota)}\}$.

---

### 1.6 Economía del Estadio y Finanzas

#### Aforo y Venta de Entradas
La asistencia a los partidos en casa ($A$) se determina mediante:
$$A_{\text{base}} = \text{fanaufkommen} + \frac{15.000}{1.4^{(\text{rank}_{\text{rival}} - 1)}} + \text{bonus}_{\text{competición}}$$

Donde $\text{bonus}_{\text{competición}}$ es $+30.000$ (Pokal), $+10.000$ (Cup), $+15.000$ (Liga), o $+5.000$ (Amistoso).
El efecto del precio de la entrada ($P_{\text{ticket}}$) ajusta la demanda:
$$A_{\text{calculado}} = A_{\text{base}} + (70 - P_{\text{ticket}}) \cdot 750$$
$$A_{\text{final}} = \min(\text{capacidad\_estadio}, A_{\text{calculado}})$$

---

## 2. PARTE II: ANÁLISIS Y PROPUESTAS DE NUEVAS CARACTERÍSTICAS (FEATURES)

Para profundizar el realismo táctico, estratégico y financiero de OpenSoccer sin romper el equilibrio del motor existente, se analiza la adición de nuevas características divididas en **Jugadores** y **Clubes**.

---

### 2.1 Nuevas Características para Jugadores

```
+------------------------------------------------------------------------+
|                   NUEVAS FEATURES PARA JUGADORES                       |
+------------------------------------------------------------------------+
|                                                                        |
|  1. Habilidades Especiales / Estilos de Juego (Traits)                 |
|  2. Polivalencia Posicional (Secondary Positions)                      |
|  3. Sistema de Personalidad, Química y Lealtad                        |
|  4. Gestión Físico-Médica y Micro-lesiones Específicas                 |
|                                                                        |
+------------------------------------------------------------------------+
```

#### 1. Habilidades Especiales / Estilos de Juego (Traits / Special Skills)
* **Concepto:** Asignar 1 o 2 rasgos distintivos pasivos o activos a ciertos jugadores (especialmente aquellos con alto talento o generados por canteras nivel 4-5).
* **Ejemplos de Traits:**
  * `Especialista en Faltas`: Aumenta un $+15\%$ la conversión en lanzamientos de tiro libre directo (`dFreeKick`).
  * `Líder en el Campo`: Incrementa la moral de sus compañeros de línea en $+0.5$ tras recibir un gol en contra.
  * `Muro Defensivo`: Incrementa en $+10\%$ la efectividad de bloqueo de tiros (`shot_block`).
  * `Goleador de Razas`: Aumenta en $+8\%$ la efectividad en balones divididos / remates al primer toque.
  * `Propenso a Lesiones`: Incrementa un $+20\%$ el riesgo de lesión por baja frische.
* **Impacto en el Motor:** Se integra como multiplicador directo en las comprobaciones `Chance_Percent()` dentro de `aa_spieltag_simulation.php`.

#### 2. Polivalencia Posicional (Posición Secundaria)
* **Concepto:** Actualmente, los jugadores son estrictamente `T`, `A`, `M` o `S`. Un jugador alineado fuera de su posición sufre penalizaciones de rendimiento. Una posición secundaria permitida (ej. `A/M` o `M/S`) reduce esta penalización.
* **Mecánica:**
  * Posición Principal (`position`) + Posición Secundaria (`sec_position`).
  * Si un jugador juega en su posición secundaria, rinde al $90\%$ de su fuerza nominal. Si juega en una posición no compatible, rinde al $50\%$.

#### 3. Sistema de Personalidad, Química de Equipo y Lealtad
* **Concepto:** Introducir un parámetro de **Química** entre jugadores que compartan misma nacionalidad (`land`), hayan coincidido en la misma cantera o lleven más de 2 temporadas en el club.
* **Efecto Táctico:**
  * Si 3 o más centrocampistas/delanteros comparten alta química ($>80\%$), la probabilidad de pase con éxito en el primer y segundo tercio aumenta un $+5\%$.
  * **Factor de Lealtad:** Jugadores con lealtad alta aceptan renovaciones de contrato con un sueldo $10-15\%$ inferior al valor estándar del mercado.

#### 4. Gestión Físico-Médica y Micro-Lesiones Específicas
* **Concepto:** Reemplazar el cálculo genérico de lesión por un cuadro médico detallado (Tobillo, Isquiotibiales, Ligamentos, Pubalgia, Sobrecarga Muscular).
* **Mecánica:**
  * **Micro-lesiones (1-2 días):** Permiten al mánager decidir si "infiltrar" al jugador para un partido crucial (arriesgando una lesión grave de 14-30 días).

---

### 2.2 Nuevas Características para Clubes

```
+------------------------------------------------------------------------+
|                    NUEVAS FEATURES PARA CLUBES                         |
+------------------------------------------------------------------------+
|                                                                        |
|  1. Red de Ojeadores Internacionales (Scouting Network)                |
|  2. Patrocinios Dinámicos y Objetivos de Rendimiento                   |
|  3. Infraestructura del Estadio: VAR, Paneles Solares, Palcos VIP      |
|  4. Cuerpo Técnico Extendido (Especialistas de Staff)                  |
|                                                                        |
+------------------------------------------------------------------------+
```

#### 1. Red de Ojeadores Internacionales (Scouting Network)
* **Concepto:** Expandir la función básica del scout (`scout` en `man_teams`).
* **Mecánica:**
  * El club puede enviar ojeadores a regiones geográficas específicas (ej. Sudamérica, Europa del Este, África) abonando un coste semanal.
  * Tras un ciclo de 7-14 días, el ojeador presenta una lista de 3 jóvenes promesas invisibles en el mercado de transferencias público, permitiendo fichajes directos antes de que salgan a subasta global.

#### 2. Patrocinios Dinámicos con Cláusulas de Rendimiento
* **Concepto:** Actualmente, los patrocinadores otorgan un pago fijo más una suma por victoria.
* **Propuesta de Patrocinios Especializados:**
  * *Sponsor Defensivo:* Otorga un bono de $150.000$ € por cada partido con la portería a cero (*clean sheet*).
  * *Sponsor Canterano:* Otorga un bono de $200.000$ € si en el 11 titular juegan al menos 3 canteranos.
  * *Sponsor de Torneo:* Otorga un premio de $2.000.000$ € si el equipo alcanza semifinales de Pokal/Cup.

#### 3. Ampliación Tecnológica y Sostenible del Estadio (`man_stadien`)
* **Nuevos Edificios / Módulos:**
  1. **Tecnología VAR (`var_system`):** Reduce en un $50\%$ la concesión de penaltis dudosos en contra cuando se juega en casa.
  2. **Paneles Solares (`solar_power`):** Reduce los costes diarios de mantenimiento del estadio (`aa_stadion_kosten.php`) en un $30\%$.
  3. **Palcos VIP (`vip_lounge`):** Genera ingresos fijos de alto valor por partido independiente del precio de las entradas generales.

#### 4. Cuerpo Técnico Extendido (Staff Técnico)
* **Puestos Contratables:**
  * **Entrenador de Porteros:** Aumenta la progresión de fuerza de los porteros en un $+20\%$.
  * **Analista Táctico:** Otorga una vista previa de la táctica probable del equipo rival antes del partido.
  * **Preparador Físico Master:** Reduce el consumo de frescura (`frische`) por partido de $2$ puntos a $1$ punto.

---

### 2.3 Esquema SQL e Impacto Técnico Estimado

Para implementar estas características en una futura versión de OpenSoccer, se proponen las siguientes modificaciones de base de datos SQL y sus archivos PHP afectados:

#### 1. Modificaciones en la Base de Datos (`Database/STRUCTURE.sql`)

```sql
-- 1. Soporte de Traits, Posición Secundaria y Lealtad en Jugadores
ALTER TABLE `man_spieler`
  ADD COLUMN `sec_position` CHAR(1) NOT NULL DEFAULT 'N' AFTER `position`,
  ADD COLUMN `trait_1` VARCHAR(50) NOT NULL DEFAULT 'KEINER' AFTER `moral`,
  ADD COLUMN `trait_2` VARCHAR(50) NOT NULL DEFAULT 'KEINER' AFTER `trait_1`,
  ADD COLUMN `lealtad` TINYINT(1) UNSIGNED NOT NULL DEFAULT '50' AFTER `trait_2`;

-- 2. Soporte de Infraestructura Tecnológica del Estadio
ALTER TABLE `man_stadien`
  ADD COLUMN `var_system` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  ADD COLUMN `solar_power` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  ADD COLUMN `vip_lounge` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';

-- 3. Tabla para Red de Ojeadores Internacionales
CREATE TABLE `man_scouting_missions` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `team` VARCHAR(32) NOT NULL,
  `region` VARCHAR(50) NOT NULL,
  `finish_time` INT(11) NOT NULL DEFAULT '0',
  `status` ENUM('Active','Completed') NOT NULL DEFAULT 'Active',
  PRIMARY KEY (`id`),
  KEY `team` (`team`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 4. Tabla para Cuerpo Técnico Extendido
CREATE TABLE `man_coaching_staff` (
  `team` VARCHAR(32) NOT NULL,
  `gk_coach_level` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `tactical_analyst_level` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `fitness_master_level` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`team`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
```

#### 2. Matriz de Impacto Técnico en Archivos PHP

| Archivo PHP | Feature Afectada | Cambio Requerido |
| :--- | :--- | :--- |
| `aa_spieltag_simulation.php` | Traits, VAR, Preparador Físico, Posición Secundaria | Incluir lógica de traits en `starte_angriff()`, modificar consumo de `frische` y verificar VAR en penaltis. |
| `aa_spieler_verbesserung.php` | Entrenador de Porteros, Traits | Aplicar multiplicadores de progresión para porteros y verificar el trait `Propenso a Lesiones`. |
| `aa_stadion_kosten.php` | Paneles Solares | Descontar el porcentaje de ahorro energético antes de facturar gastos del estadio. |
| `spieler.php` & `kader.php` | Traits, Posición Secundaria | Mostrar insignias visuales de traits y permitir selección de posición secundaria. |
| `ver_stadion.php` | VAR, Paneles Solares, Palcos VIP | Añadir botones de construcción y ampliación con interfaz de progreso. |
| `beobachtung.php` | Ojeadores Internacionales | Crear panel para enviar misiones de scouting y ver resultados de promesas encontradas. |

---
*Documento preparado como guía técnica y estratégica para el ecosistema OpenSoccer.*
