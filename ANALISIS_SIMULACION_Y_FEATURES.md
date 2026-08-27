# Análisis Estadístico de Simulación de Juego, Determinación de Características y Nuevos Features

Este documento proporciona un análisis exhaustivo del funcionamiento del motor de simulación de partidos, el cálculo estadístico y matemático de características para jugadores y clubes en OpenSoccer, así como una propuesta detallada de nuevos features que pueden integrarse en el ecosistema.

---

## 1. Análisis Estadístico de la Simulación de Juego (`aa_spieltag_simulation.php`)

La simulación de partidos en OpenSoccer se basa en un **modelo estocástico continuo discreto por eventos (Cadenas de Markov y Probabilidad Condicionada)**. A diferencia de otros simuladores basados meramente en un número estático aleatorio global, OpenSoccer desglosa el partido en secuencias de ataques con tiempos y resoluciones generadas probabilísticamente.

### A. Determinación de la Posesión del Balón y Asistencia

#### 1. Cálculo de Posesión de Balón
La posesión de balón se determina comparando la fuerza agregada del centro del campo (`M`) de ambos equipos, modulada según si el encuentro es un derbi o partido oficial con ventaja de localía:

$$\text{Posesión}_{\text{Team 1}} = \operatorname{round}\left(\frac{100}{\frac{\text{Stärke}_{\text{Team 2, M}}}{\text{Stärke}_{\text{Team 1, M}}} + 1}\right)$$

* **Ajuste por Heimvorteil (Ventaja de Local):** En partidos regulares de Liga, se añade un **+4%** a la posesión del equipo local (`Team 1`) y se resta un **-4%** al visitante (`Team 2`). En partidos de Copa Internacional (`Cup`), playoffs de Copa o amistosos, este ajuste no se aplica.
* **Límite:** La posesión se trunca en el intervalo $[0\%, 100\%]$.

#### 2. Cálculo de Aforo e Ingresos por Taquilla
La asistencia al estadio depende de la base de afición del equipo local (`fanaufkommen`), la posición en la tabla del rival (`rank`), el tipo de torneo y el precio de la entrada (`preis`):

$$\text{Asistencia Estimada} = \text{fanaufkommen} + \frac{15000}{1.4^{(\text{rank}_{\text{Team 2}} - 1)}} + \text{Bonus Torneo} + (70 - \text{preis}) \times 750 + \text{Bonus Derbi}$$

* **Bonus Torneo:** Pokal (+30.000), Cup (+10.000), Liga (+15.000), Amistosos (+5.000).
* **Bonus Derbi:** Si los nombres de los equipos coinciden (eliminando prefijos/sufijos), se suman +20.000 espectadores adicionales.
* **Aforo Final:** $\min(\text{plaetze}_{\text{estadio}}, \text{Asistencia Estimada})$.

---

### B. Distribución Temporal de Eventos (`get_minutes`)

Un partido promedio se compone de $N = 20$ oportunidades o secuencias de ataque repartidas en los 90 minutos reglamentarios.

1. **Intervalo Medio entre Ataques:** $\text{Intervall} = \frac{90}{N} = 4.5\text{ minutos}$.
2. **Selección del Equipo Atacante (Bernoulli Trial dinámico):**
   Para cada jugada $i \in [1, N]$, la probabilidad de que ataque el `Team 1` es:
   $$P(\text{Ataque Team 1}) = \frac{\text{Angriffe Restantes Team 1}}{N - i + 1} \times 100\%$$
   Donde el número inicial de ataques asignados al `Team 1` es $\operatorname{round}\left(N \times \frac{\text{Posesión}_{\text{Team 1}}}{100}\right)$.
3. **Distribución del Minuto:** Se genera con una perturbación estocástica uniforme para evitar minutos fijos:
   $$\text{Minute}_{i} = \text{Minute}_{i-1} + \text{Intervall} + \text{Rand}(-\text{spielraum}, \text{spielraum})$$

---

### C. Árbol de Decisiones del Ataque (Cadena de Markov Estocástica)

Cada secuencia de ataque (`starte_angriff`) atraviesa hasta tres tercios del terreno de juego. El éxito o interrupción en cada fase depende de funciones ponderadas de fuerza y táctica.

#### Funciones de Ponderación Táctica y Fuerza:
* **Ponderación Táctica:** $W_{\text{taktik}}(v) = 0.25 v + 0.5$  (donde $v \in [1, 5]$ representa el ajuste táctico).
* **Ponderación de Fuerza:** $W_{\text{fuerza}}(s) = 0.125 s + 0.0625$ (donde $s \in [0.1, 9.9]$ es la fuerza lineal del sector).

```
[ INICIO DEL ATAQUE ]
       |
       v
¿Supera el 1er Tercio (Mittelfeld)?
       |---> NO (15% * A_def/S_att * Taktik) ---> ¿Contraataque Rápido? / Saque de Banda.
       |
       +---> SÍ (50% * M_att/M_def * Taktik)
              |
              +---> ¿Falta Cometida por la Defensa? (25% * Druck_def)
              |        |---> T. Amarilla (30%) / T. Roja (3%)
              |        +---> Tiro Libre Indirecto (30% * S_att/A_def) ---> Gol (30% * S_att/T_def)
              |
              +---> ¿Fuera de Juego? (17% * Taktik)
              |
              +---> Supera 2º Tercio (Área Rival / Avance Profundo)
                     |
                     +---> ¿Falta en Área / Límite? (25% * Druck_def)
                     |        |---> Penalti (19% * S_att/A_def) ---> Gol (77% / T_def)
                     |        +---> Tiro Libre Directo (40% * S_att) ---> Gol (40% / T_def)
                     |
                     +---> Disparo a Puerta Directo (62% * S_att/A_def * Taktik)
                              |---> GOL (30% * S_att / T_def)
                              |---> Desviado / Bloqueado por Defensa (50% * A_def)
                              |---> Parada del Portero (Restante)
```

---

### D. Sistema de Evaluaciones ("Bolzblatt"), Tarjetas y Lesiones

1. **Puntaje Periodístico de Sectores (`staerkeBenoten`):**
   Las notas de rendimiento periodístico (de 1.0 = Excelente a 6.0 = Pésimo) se calculan comparando el valor $W_{\text{fuerza}}(\text{Sector})$ frente a umbrales predefinidos:
   $$\text{Nota} = i \quad \text{si } W_{\text{fuerza}}(\text{Sector}) \le 1.485626 - 0.2759375 \times (i - 1)$$
2. **Debilitamiento por Tarjetas en Vivo (`weakenTeam`):**
   * **Tarjeta Amarilla:** Reduce en un **-2%** la fuerza de los sectores `A`, `M` y `S` del equipo sancionado durante el resto de la simulación.
   * **Tarjeta Roja:** Reduce en un **-10%** la fuerza de todos los sectores de campo (`A`, `M`, `S`).
3. **Mecanismo de Lesiones por Desgaste:**
   La probabilidad de lesión en el encuentro aumenta inversamente con la frescura promedio del equipo:
   $$P(\text{Lesión}) = \lfloor (100 - \overline{\text{Frescura}}) \times 1.35 \rfloor$$
   Si se desencadena la lesión, el jugador afectado es seleccionado mediante un sorteo ponderado por su nivel de frescura actual (los jugadores con menor frescura tienen mayor probabilidad) y la baja oscila entre 1 y 13 días según el tipo de dolencia muscular o articular.

---

## 2. Determinación Matemática de Características (Jugadores y Clubes)

### A. Jugadores

#### 1. Generación de Talentos Juveniles (Cantera)
La fuerza inicial y el talento de los jóvenes no se asignan con una distribución uniforme estándar, sino mediante una **distribución logarítmica sesgada** (`getRandomStrength`) para reflejar la escasez de "cracks" de clase mundial:

$$\text{Talento} = \exp\left( (\operatorname{rand}()^{1.15}) \times (\ln(\text{Talent}_{\max}) - \ln(\text{Talent}_{\min})) + \ln(\text{Talent}_{\min}) \right)$$

* **Fuerza Inicial:** $\text{Stärke}_{\text{inicial}} = \operatorname{round}(\text{Talento} \times \text{getRandomStrength}(0.5, 0.9), 1)$.

#### 2. Desarrollo y Declive por Edad
* **Desarrollo (Menores de 31 años / $< 11.315$ días):**
  $$\Delta\text{Stärke} = \min\left( \operatorname{ceil}\left( \frac{\text{Rand}(0.1, 0.6)}{\text{Stärke}} \times 5 \times 10 \right) / 10, \, \text{Talento} - \text{Stärke} \right)$$
  *El crecimiento es más lento a medida que el jugador aumenta de nivel.*
* **Envejecimiento (A partir de 31 años / $\ge 11.315$ días):**
  $$\text{Pérdida}_{\text{Campo}} = \operatorname{round}\left( \text{Stärke} \times \frac{\lfloor \text{Edad}_{\text{años}} - 28 \rfloor}{70}, 1 \right), \quad \text{Pérdida}_{\text{Portero}} = \text{Rand}(0.1, 0.2)$$

#### 3. Algoritmo de Valor de Mercado (`marktwert`)
El valor de mercado equilibra exponencialmente el talento, la fuerza actual y los días de vida restantes antes del retiro.

---

### B. Clubes

#### 1. Fuerza Global del Equipo (`aa_team_staerke_berechnen.php`)
Calcula la suma ponderada del 11 titular considerando su frescura:
$$\text{Fuerza Global} = \sum_{i=1}^{11} \text{Stärke}_i \times \left(0.33 + 0.67 \times \frac{\text{Frische}_i}{100}\right)$$

#### 2. Sistema de Puntuación Elo (`eloChange`)
Al finalizar cada encuentro, la actualización de puntos Elo del club sigue el modelo estándar de competición:
$$R_{\text{nuevo}} = R_{\text{actual}} + K \times (S - E)$$
Donde $E = \frac{1}{1 + 10^{(\text{Elo}_{\text{rival}} - \text{Elo}_{\text{propio}})/400}}$ representa la expectativa de victoria.

---

## 3. Propuesta y Análisis de Nuevos Features

Para elevar la profundidad táctica y la inmersión del usuario en OpenSoccer sin romper el rendimiento computacional de las cronjobs en PHP, se proponen los siguientes nuevos features para Jugadores y Clubes:

---

### A. Nuevos Features para Jugadores

```
+-------------------------------------------------------------------------+
|                    NUEVOS FEATURES PARA JUGADORES                       |
+-------------------------------------------------------------------------+
|                                                                         |
|  1. Rasgos / Habilidades Especiales (Player Traits)                     |
|     - Ej: "Especialista a BP", "Veloz en Konter", "Líder de Zaga"        |
|                                                                         |
|  2. Racha de Forma Dinámica (Form Curve / Hot Streak)                   |
|     - Modificador flotante (-1.0 a +1.0) basado en rendimiento reciente |
|                                                                         |
|  3. Polivalencia Posicional (Secondary Positions)                       |
|     - Posición primaria (100%) y secundaria (85% rendimiento)          |
|                                                                         |
|  4. Sistema de Química y Afinidad (Teammate Chemistry)                  |
|     - Bonus de compenetración por partidos compartidos                  |
|                                                                         |
+-------------------------------------------------------------------------+
```

#### 1. Rasgos y Habilidades Especiales (*Player Traits*)
* **Concepto:** Atributos cualitativos únicos asignados aleatoriamente o desarrollados mediante cantera.
* **Tipos de Rasgos:**
  * **Especialista a Balón Parado (*Freistoß-Spezialist*):** Incrementa en un +15% la probabilidad de gol en faltas directas y penaltis durante la simulación.
  * **Especialista en Contraataque (*Konter-Spezialist*):** Duplica la efectividad de las jugadas `quickCounterAttack` cuando el jugador encabeza el ataque.
  * **Líder de Zaga (*Abwehrchef*):** Mitiga en un 50% la penalización de fuerza colectiva tras sufrir una tarjeta amarilla o roja en el equipo.
  * **Cazagoles (*Abstauber*):** +20% de probabilidad de convertir remates tras rechaces del portero o balón bloqueado.
* **Impacto Técnico:** Se agregan como un campo tipo `SET` o JSON en `man_spieler` y se leen durante el bucle de `starte_angriff()`.

#### 2. Racha de Forma Dinámica (*Form Curve / Hot Streak*)
* **Concepto:** Introducción de un atributo flotante `forma_racha` (de -1.0 a +1.0).
* **Dinámica:** Si un delantero marca goles en partidos consecutivos o recibe buenas notas del *Bolzblatt*, su racha sube. Un jugador con racha positiva (+0.8) suma un bono temporal a su fuerza efectiva en los partidos.
* **Beneficio:** Evita que el usuario use siempre la misma alineación estática y premia la rotación de jugadores en racha.

#### 3. Polivalencia Posicional (*Versatility / Secondary Positions*)
* **Concepto:** Actualmente las posiciones son rígidas (`T`, `A`, `M`, `S`). Se propone un campo `posicion_secundaria`.
* **Mecánica:** Un centrocampista (`M`) con posición secundaria de delantero (`S`) podrá ser alineado en la delantera sufriendo solo una penalización menor (ej. 85% de su fuerza) en lugar de la penalización severa actual de amateur.

#### 4. Química de Alineación y Afinidad entre Jugadores
* **Concepto:** Acumulación de "horas de vuelo" compartidas entre parejas o bloques de jugadores (ej. central-lateral o doble pivote).
* **Mecánica:** Si dos jugadores han disputado juntos más de 15 partidos, reciben un bono de **+0.3 de fuerza efectiva** en su sector debido al entendimiento táctico.

---

### B. Nuevos Features para Clubes

```
+-------------------------------------------------------------------------+
|                      NUEVOS FEATURES PARA CLUBES                        |
+-------------------------------------------------------------------------+
|                                                                         |
|  1. Centro de Tecnificación / Instalaciones de Entrenamiento            |
|     - Niveles de mejora que aumentan la velocidad de desarrollo         |
|                                                                         |
|  2. Equipo Filial / Reserva (Sub-21 / B-Team)                           |
|     - Espacio para dar minutos a jóvenes sin saturar el primer equipo   |
|                                                                         |
|  3. Cuerpo Técnico Ampliado (Staff Técnico Especializado)              |
|     - Entrenador de Porteros, Preparador Físico, Ojeador de Rivales     |
|                                                                         |
|  4. Acuerdos de Patrocinio por Objetivos (Performance-Based Sponsors)    |
|     - Contratos dinámicos con primas por posición final o goles          |
|                                                                         |
+-------------------------------------------------------------------------+
```

#### 1. Centro de Tecnificación e Instalaciones Deportivas (*Trainingszentrum*)
* **Concepto:** Extensión de las infraestructuras del club (más allá del estadio) mediante niveles de construcción (Nivel 1 al 5).
* **Efectos:**
  * **Mejora de Recuperación:** Reduce la pérdida diaria de frescura post-partido.
  * **Acelerador de Talento:** Aumenta el multiplicador de mejora en `aa_spieler_verbesserung.php` para jóvenes menores de 23 años.

#### 2. Equipo Filial / Equipo Reserva (Sub-21 / *Amateure*)
* **Concepto:** Creación de un plantel secundario donde los canteranos puedan jugar partidos simulados de desarrollo.
* **Beneficio Solucionado:** Actualmente, si un joven de 17 años no juega en el primer equipo, su atributo `spiele_gesamt` no supera el umbral ($>8$) y nunca evoluciona. El filial resuelve este cuello de botella.

#### 3. Cuerpo Técnico Especializado (*Staff Técnico*)
* **Concepto:** Reemplazar el sistema simplificado de técnicos por roles especializados contratables:
  * **Entrenador de Porteros:** Aumenta la nota y eficacia del portero (`T`).
  * **Preparador Físico:** Reduce el porcentaje de riesgo de lesión en partidos con baja frescura.
  * **Analista Táctico / Ojeador de Rivales:** Permite ver la configuración táctica exacta del rival antes del encuentro.

#### 4. Contratos de Patrocinio por Objetivos (*Dynamic Sponsorship*)
* **Concepto:** Diversificación de la elección de patrocinadores al inicio de temporada:
  * **Patrocinador Conservador:** Alta prima fija por jornada, primas bajas por victoria.
  * **Patrocinador Agresivo / De Rendimiento:** Prima fija baja, pero bonus multimillonarios por clasificar a Copas Internacionales o conseguir el título de Liga.

---

## 4. Conclusiones y Hoja de Ruta Recomendada

1. **Simulación Robusta:** El motor estocástico actual en PHP es sólido y matemáticamente equilibrado. La inclusión de los *Player Traits* y la *Racha de Forma Dinámica* se puede realizar modulando directamente las funciones `strengths_weight` y `tactics_weight` dentro de `starte_angriff()`.
2. **Escalabilidad:** Las propuestas planteadas no requieren reestructuraciones drásticas de la base de datos MySQL existente y garantizan tiempos de ejecución eficientes para los cronjobs diarios.
