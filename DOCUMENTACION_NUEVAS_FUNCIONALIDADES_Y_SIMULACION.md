# Documento de Análisis: Nuevas Funcionalidades y Análisis Estadístico de Simulación (OpenSoccer)

---

## 1. Análisis de Posibles Nuevos Features (Jugadores y Clubes)

A partir del análisis de la arquitectura actual de OpenSoccer, donde la gestión de jugadores se basa fundamentalmente en atributos globales (`staerke`, `talent`, `frische`, `moral`) y la gestión de clubes en finanzas, estadios y cantera, se presentan propuestas detalladas para expandir la profundidad estratégica del juego.

### A. Nuevos Features para Jugadores (Personajes)

1. **Desglose de Atributos Específicos (Sub-atributos):**
   * **Situación actual:** Los jugadores poseen un único valor global de fuerza (`staerke`).
   * **Propuesta:** Introducir sub-atributos complementarios según la posición:
     * *Físicos:* Velocidad (Aceleración / Esprint), Resistencia, Fuerza Física.
     * *Técnicos:* Pase (Corto / Largo), Remate / Definición, Regate, Juego Aéreo (Cabeceo).
     * *Tácticos/Mentales:* Visión de Juego, Posicionamiento Defensivo, Agresividad, Liderazgo.
   * **Impacto:** Permite diferenciar perfiles (ej. un delantero tanque vs. un extremo veloz), enriqueciendo las decisiones tácticas y las negociaciones en el mercado.

2. **Rasgos, Especializaciones y Personalidades (*Traits*):**
   * **Propuesta:** Asignar 1 o 2 rasgos únicos a cada jugador (nacidos de la cantera o mediante entrenamiento):
     * *Especialista a Balón Parado:* Incrementa la efectividad en lanzamientos de falta directa y penaltis (+15% en simulación).
     * *Jugador Clave / Grandes Citas:* Incrementa su moral y rendimiento en derbis y fases eliminatorias de Copa/Cup.
     * *Líder de Vestuario:* Aumenta progresivamente la moral de sus compañeros de equipo.
     * *Propenso a Lesiones / Inmune:* Modifica las probabilidades en el cálculo de lesiones durante los partidos.

3. **Polivalencia y Posiciones Secundarias:**
   * **Situación actual:** Posiciones fijas (`T`, `A`, `M`, `S`).
   * **Propuesta:** Incluir posiciones secundarias y nivel de adaptación (ej. Posición Principal: `M`, Posición Secundaria: `A` al 80% de efectividad). Si juega fuera de posición natural sin adaptación, su fuerza efectiva en el partido sufre una penalización proporcional.

4. **Entrenamientos Individuales Focalizados:**
   * **Propuesta:** Sistema de entrenamientos semanales donde el mánager asigna enfoques específicos a los jugadores jóvenes o veteranos (ej. Entrenamiento de Recuperación de Frescura, Entrenamiento Táctico o Entrenamiento de Potenciación de Talento).

5. **Historial Médico y Centro de Fisioterapia:**
   * **Propuesta:** Registro del historial de lesiones. Jugadores con lesiones graves recurrentes sufren pérdidas temporales en frescura máxima o mayor riesgo de recaída a menos que el club invierta en fisioterapeutas de mayor nivel.

---

### B. Nuevos Features para Clubes y Mánagers

1. **Ampliación del Cuerpo Técnico y Staff:**
   * **Situación actual:** Actualmente solo existen opciones básicas de psicólogo y fisioterapeuta (`ver_personal.php`).
   * **Propuesta:** Crear un panel completo de gestión de empleados:
     * *Segundo Entrenador:* Aumenta la eficacia de las tácticas y la lectura de los cambios en vivo.
     * *Entrenador de Porteros / Juveniles:* Incrementa el ritmo de desarrollo de jóvenes en la cantera (`aa_spieler_verbesserung.php`).
     * *Red de Ojeadores (Scouting Network):* Ojeadores contratados por región geográfica para mejorar la precisión en las estimaciones de talento de jugadores rivales y descubrir perlas en el mercado internacional.

2. **Infraestructura: Centro de Entrenamiento y Residencia:**
   * **Propuesta:** Ampliar las instalaciones más allá del estadio (`ver_stadion.php`):
     * *Centro de Alto Rendimiento:* Reduce la pérdida de frescura tras los partidos y mitiga el riesgo de lesiones.
     * *Residencia de Cantera:* Aumenta la probabilidad de que `aa_spieler_erzeugen.php` genere jugadores con talento $> 8.0$.

3. **Patrocinios Dinámicos y Contratos con Objetivos:**
   * **Situación actual:** Patrocinadores estáticos con primas fijas y por victoria.
   * **Propuesta:** Ofertas de patrocinios dinámicas cada temporada con contratos negociables:
     * Elección entre patrocinador conservador (base alta, primas bajas) o arriesgado (base baja, altas primas por clasificar a Copas internacionales o ganar el título).

4. **Fidelidad de la Afición y Reputación del Club:**
   * **Propuesta:** Índice de reputación ($0-100\%$). Afecta la venta de camisetas en el *Fanshop*, el interés de patrocinadores de alto nivel y la asistencia al estadio en partidos contra rivales de menor rango.

5. **Variantes y Sistemas Tácticos Personalizables:**
   * **Propuesta:** Permitir esquemas tácticos específicos (ej. 4-3-3, 3-5-2, 4-4-2) con instrucciones específicas para cada línea (línea defensiva alta/baja, contraataque estructurado, juego por bandas o por el centro).

---

## 2. Análisis Estadístico y Matemático de la Simulación de Juego

La simulación de partidos en OpenSoccer está gobernada por el script `aa_spieltag_simulation.php`. A continuación se presenta el desglose matemático y estadístico completo de su funcionamiento.

---

### A. Estructura Temporal y Determinación de Ataques

En cada partido se generan **20 oportunidades/ataques totales** distribuidos durante los 90 minutos de juego.

#### 1. Distribución de Minutos y Posesión de Balón (Lógica estocástica)
La posesión de balón de cada equipo se calcula a partir de la fuerza media de la línea de medio campo (`M`):

$$\text{Ballbesitz}_{\text{Team 1}} = \operatorname{round}\left( \frac{100}{\frac{\operatorname{Stärke}_{M, \text{Team 2}}}{\operatorname{Stärke}_{M, \text{Team 1}}} + 1} \right)$$

* **Ajuste por Heimvorteil (Ventaja de Local):**
  En partidos de liga regular (salvo en campo neutral o test-matches), se suma $+4\%$ a $\text{Ballbesitz}_{\text{Team 1}}$ y se resta $-4\%$ a $\text{Ballbesitz}_{\text{Team 2}}$. El valor final se acota en el intervalo $[0, 100]$.

#### 2. Generación de Intervalos (Función `get_minutes`)
Para $N = 20$ ataques:
* Intervall base: $I = \frac{90}{N} = 4.5\text{ minutos}$.
* Margen de perturbación: $S = \lceil I \rceil - 1 = 4\text{ minutos}$.
* En cada paso $i \in \{1, \dots, N\}$, el minuto del ataque $m_i$ se calcula como:
  $$m_i = m_{i-1} + I + \delta_i$$
  donde $\delta_i \sim U[-S, S]$ es una variable aleatoria uniforme con perturbación compensatoria en la iteración siguiente para mantener el tiempo acotado a 90 minutos.

#### 3. Asignación del Atacante (Experimento Bernoulli Dinámico)
El número esperado de ataques para el Team 1 es:
$$k_1 = \operatorname{round}\left( N \cdot \frac{\text{Ballbesitz}_{\text{Team 1}}}{100} \right)$$
En cada evento $i$, la probabilidad instantánea de que el Team 1 sea el atacante es:
$$P(\text{Ataca Team 1} \mid n_{\text{restantes}}, k_{\text{restantes}}) = \frac{k_{\text{restantes}}}{n_{\text{restantes}}} \times 100$$
Se genera $u \sim U[0, 100]$. Si $u < P$, ataca el Team 1; de lo contrario, ataca el Team 2.

---

### B. Funciones de Ponderación Táctica y de Fuerza

El motor transforma las configuraciones tácticas y la fuerza bruta de los jugadores mediante funciones lineales de escalado.

#### 1. Ponderación Táctica (`tactics_weight`)
Dada una opción táctica $x \in \{1, 2, 3, 4, 5\}$:
$$T_{\text{weight}}(x) = 0.25 \cdot x + 0.5$$

| Opción ($x$) | Significado | Valor $T_{\text{weight}}$ |
| :---: | :---: | :---: |
| 1 | Ultra Defensivo / Pausado / Corto / Conservador | $0.75$ |
| 2 | Defensivo / Normal / Mixto | $1.00$ |
| 3 | Normal / Rápido / Largos / Arriesgado | $1.25$ |
| 4 | Ofensivo / Presión Alta | $1.50$ |
| 5 | Ultra Ofensivo / Máxima Presión | $1.75$ |

#### 2. Ponderación de Fuerza (`strengths_weight`)
Dado un nivel de fuerza de línea $S \in [0.1, 10.0]$:
$$S_{\text{weight}}(S) = 0.125 \cdot S + 0.0625$$

---

### C. Árbol Probabilístico de Ejecución de un Ataque (`starte_angriff`)

Cada ataque simula la progresión de la jugada en hasta tres zonas del campo:

```
                  [ INICIO DEL ATAQUE ]
                            |
               Probabilidad Fase 1 (Medio Campo)
                            |
           +----------------+----------------+
           | (Éxito)                         | (Fallo)
           v                                 v
   [ 1er Tercio Superado ]         +-------------------+
           |                       | ¿Contraataque?    |
   +-------+-------+               | P = 15% * R_def   |
   | Foul?         | No Foul       +---------+---------+
   | (25% * Druck) |                         |
   v               v                         v
[Libre Indir.] [2º Tercio Superado]   [Nuevo Ataque Def]
                   |
           +-------+-------+
           | Foul?         | No Foul
           | (25% * Druck) |
           v               v
  [Penalti / Libre Directo] [Oportunidad de Disparo]
                           | (62% * S_att / A_def * ...)
                           v
                [ DISPARO A PUERTA ]
                           |
            (30% * S_att / T_def)
                           v
                     [ ¡ G O L ! ]
```

#### Fase 1: Paso por el primer tercio del campo (Centro del Campo)
La probabilidad de superar la línea media rival es:
$$P(\text{Fase 1}) = 50\% \cdot \frac{S_{\text{weight}}(M_{\text{att}})}{S_{\text{weight}}(M_{\text{def}})} \cdot \frac{T_{\text{weight}}(\text{ausrichtung}_{\text{att}})}{T_{\text{weight}}(\text{ausrichtung}_{\text{def}})} \cdot \frac{T_{\text{weight}}(\text{geschw}_{\text{att}})}{T_{\text{weight}}(\text{pass}_{\text{att}})}$$

* **Si la Fase 1 falla:**
  * Se evalúa la probabilidad de **Contraataque Rápido** (*Quick Counter Attack*):
    $$P(\text{Contraataque}) = 15\% \cdot \frac{S_{\text{weight}}(A_{\text{def}})}{S_{\text{weight}}(S_{\text{att}})} \cdot T_{\text{weight}}(\text{risk}_{\text{att}}) \cdot T_{\text{weight}}(\text{druck}_{\text{def}})$$
    Si se activa, la defensa del equipo atacante original se debilita temporalmente ($\text{Stärke}_{A, \text{att}} \leftarrow \text{Stärke}_{A, \text{att}} \times 0.8$) y la posesión pasa de inmediato al rival invocando recursivamente `starte_angriff()`.
  * Si no hay contraataque, el balón sale a saque de banda (*Throw-In*), con $33\%$ de probabilidad de generar una nueva posesión aleatoria.

#### Fase 2: Progresión al último tercio del campo
Si supera el primer tercio:
1. **Falta de la defensa:** $P(\text{Falta}) = 25\% \cdot T_{\text{weight}}(\text{aggress}_{\text{def}})$.
   * Si hay falta:
     * **Tarjeta Amarilla:** $P = 30\%$. Factor de debilitamiento del equipo: $\text{Stärke}_{\text{def}} \leftarrow \text{Stärke}_{\text{def}} \times 0.98$.
     * **Tarjeta Roja:** $P = 3\%$. Factor de debilitamiento del equipo: $\text{Stärke}_{\text{def}} \leftarrow \text{Stärke}_{\text{def}} \times 0.90$.
     * **Resultado:** Libre indirecto. Probabilidad de tiro: $30\% \cdot \frac{S_{\text{weight}}(S_{\text{att}})}{S_{\text{weight}}(A_{\text{def}})}$. Probabilidad de gol: $30\% \cdot \frac{S_{\text{weight}}(S_{\text{att}})}{S_{\text{weight}}(T_{\text{def}})}$.
2. **Abseits (Fuera de juego):** $P(\text{Abseits}) = 17\% \cdot T_{\text{weight}}(\text{ausrichtung}_{\text{att}}) \cdot T_{\text{weight}}(\text{pass}_{\text{att}})$.
3. **Avance limpio al área rival:** Si no hay falta ni fuera de juego, el ataque logra una gran ocasión.

#### Fase 3: Disparo a Puerta y Gol
Probabilidad de tiro a puerta directo en juego abierto:
$$P(\text{Disparo}) = 62\% \cdot \frac{S_{\text{weight}}(S_{\text{att}})}{S_{\text{weight}}(A_{\text{def}})} \cdot T_{\text{weight}}(\text{pass}_{\text{att}}) \cdot T_{\text{weight}}(\text{risk}_{\text{att}})$$

Una vez producido el disparo:
$$P(\text{Gol}) = 30\% \cdot \frac{S_{\text{weight}}(S_{\text{att}})}{S_{\text{weight}}(T_{\text{def}})}$$
Si no es gol:
* Con $50\% \cdot S_{\text{weight}}(A_{\text{def}})$, la defensa bloquea el tiro.
* En caso contrario, el portero realiza una parada (*Shot Save*).

---

### D. Penaltis y Faltas Directas en el Área

Si la defensa comete falta en el último tercio ($P = 25\% \cdot T_{\text{weight}}(\text{aggress}_{\text{def}})$):
* Tarjeta Amarilla: $33\%$.
* Tarjeta Roja: $3\%$.
* **Evaluación de Penalti:** $P(\text{Penalti}) = 19\% \cdot \frac{S_{\text{weight}}(S_{\text{att}})}{S_{\text{weight}}(A_{\text{def}})}$.
  * Probabilidad de Gol en Penalti: $P(\text{Gol}) = \frac{77\%}{S_{\text{weight}}(T_{\text{def}})}$.
  * Si falla el penalti: $50\%$ fuera / poste, $50\%$ parada del portero.
* **Libre Directo:** Si no es penalti:
  * Probabilidad de superar la barrera: $40\% \cdot S_{\text{weight}}(S_{\text{att}})$.
  * Probabilidad de Gol: $\frac{40\%}{S_{\text{weight}}(T_{\text{def}})}$.

---

### E. Cálculo del Aforo, Taquilla e Ingresos del Estadio

La asistencia al partido ($W$) depende de la capacidad del estadio ($P_{\text{asientos}}$), el precio fijado ($P_{\text{precio}}$), la reputación/posición del rival y la categoría del torneo:

$$\text{Fanaufkommen} = \text{Base}_{\text{team}} + \frac{15000}{1.4^{(\text{Rank}_{\text{rival}} - 1)}} + \text{Bonus}_{\text{torneo}} + (70 - P_{\text{precio}}) \times 750 + \text{Bonus}_{\text{derby}}$$

* $\text{Bonus}_{\text{torneo}}$: Pokal ($+30.000$), Liga ($+15.000$), Cup ($+10.000$), Amistoso ($+5.000$).
* $\text{Bonus}_{\text{derby}}$: Si el nombre base del equipo coincide (misma ciudad), $+20.000$ espectadores adicionales.
* Aforo definitivo: $W = \min(P_{\text{asientos}}, \operatorname{intval}(\text{Fanaufkommen}))$.
* **Ingresos por Taquilla:**
  * Partido de Liga / Normal: $\text{Ingreso}_{\text{Heim}} = W \times P_{\text{precio}}$.
  * Eliminatoria neutral / Pokal Runde 5 / Cup: $\text{Ingreso}_{\text{Heim}} = \operatorname{round}\left(\frac{W \times P_{\text{precio}}}{2}\right)$, $\text{Ingreso}_{\text{Gast}} = \operatorname{round}\left(\frac{W \times P_{\text{precio}}}{2}\right)$.

---

## 3. Determinación de Características y Evolución de Jugadores

### A. Valor de Mercado (`marktwert`)

El valor económico del jugador se calcula dinámicamente en `zzserver.php` / `aa_marktwert_berechnen.php` con la siguiente fórmula exponencial-logarítmica:

$$\text{Marktwert} = \operatorname{ROUND}\left( \frac{1.75^{\text{stärke}} \cdot \text{talent} \cdot 30000}{\left( \frac{\lfloor \text{wiealt}/365 \rfloor}{27} \right)^{1.7}} + \operatorname{FLOOR}(100 + U[0, 1000]) \right)$$

* **Interpretación:**
  * Crece exponencialmente con la fuerza ($1.75^{\text{stärke}}$).
  * Escala de forma lineal con el potencial/talento ($\text{talent}$).
  * Sufre una depreciación penalizadora a medida que la edad supera los 27 años ($(\text{Edad}/27)^{1.7}$).

---

### B. Generación de Jugadores en Cantera (`aa_spieler_erzeugen.php`)

Los jóvenes generados por el centro de formación nacen con edad entre 17 y 21 años ($6.205$ a $7.665$ días).

#### 1. Muestreo Logarítmico Aleatorio de Talento (`getRandomStrength`)
Dado el rango $[\text{Min}, \text{Max}]$ dictado por el nivel del centro de formación ($1$ a $5$):

$$y = \ln(\text{Min}), \quad z = \ln(\text{Max}), \quad \text{scale} = z - y$$
$$u = \left( U[0, 1] \right)^{1.15}$$
$$\text{Talent} = \operatorname{round}\left( e^{u \cdot \text{scale} + y}, 1 \right)$$

* El exponente $1.15$ concentra la distribución probabilística hacia los valores inferiores del rango, haciendo que los jugadores con talentos máximos sean estadísticamente más escasos y valiosos.

#### 2. Fuerza Inicial y Salario
* Fuerza Inicial: $\text{Stärke} = \operatorname{round}(\text{Talent} \times \text{getRandomStrength}(0.5, 0.9), 1)$.
* Salario base según nivel de cantera: Nivel 1 ($300.000\ €$), Nivel 2 ($500.000\ €$), Nivel 3 ($700.000\ €$), Nivel 4 ($900.000\ €$), Nivel 5 ($1.200.000\ €$).

---

### C. Progresión y Declive por Edad (`aa_spieler_verbesserung.php`)

Los jugadores que han acumulado más de 8 partidos oficiales (`spiele_gesamt > 8`) procesan su desarrollo según su edad.

```
                    [ JUGADOR CON > 8 PARTIDOS ]
                                 |
                 +---------------+---------------+
                 |                               |
        ¿Edad < 31 años?                 ¿Edad >= 31 años?
        (< 11.315 días)                  (>= 11.315 días)
                 |                               |
                 v                               v
    Incremento por Practica             Declive por Edad
  Plus = (mt_rand(1,6)/10) / Stärke * 5   Pérdida = (floor(Edad/365-28)/70) * Stärke
  Stärke_nueva = min(Talent, Stärke + Plus) Stärke_nueva = max(0.1, Stärke - Pérdida)
```

#### 1. Progresión de Jóvenes ($< 31$ años / $< 11.315$ días)
El incremento de fuerza está inversamente relacionado con su nivel actual (cuesta más subir cuanto más fuerte es el jugador):

$$\text{Plus} = \operatorname{ceil}\left( \frac{\frac{\operatorname{rand}(0, 5) + 1}{10}}{\text{Stärke}} \times 5 \times 10 \right) / 10$$

* El incremento $\text{Plus}$ se acota en el intervalo $[0.1, 1.2]$.
* Si $\text{Stärke} + \text{Plus} > \text{Talent}$, el incremento se recorta para no superar el techo de **Talento** ($\text{Stärke}_{\text{nueva}} = \text{Talent}$).

#### 2. Declive de Veteranos ($\ge 31$ años / $\ge 11.315$ días)
La pérdida de fuerza anual/periódica aumenta con la edad:

$$\text{Pérdida}_{\% } = \frac{\lfloor \text{Edad}/365 - 28 \rfloor}{70}$$
$$\text{Pérdida} = \operatorname{round}\left( \text{Stärke} \times \text{Pérdida}_{\% }, 1 \right)$$

* Para los porteros (`T`), la pérdida es aleatoria suave: $\text{Pérdida} \in \{0.1, 0.2\}$.
* Para jugadores de campo, si $\text{Pérdida} < 0.1$, se fija en $0.1$. La fuerza mínima permitida es $0.1$.
* El **Talento** del veterano se reduce al nuevo nivel de fuerza ($\text{Talent} = \text{Stärke}_{\text{nueva}}$).

---

## 4. Conclusión y Hoja de Ruta Sugerida

El motor actual de OpenSoccer posee un fundamento determinista-estocástico muy sólido y calibrado. Las propuestas descritas en la Sección 1 pueden integrarse sin romper el equilibrio actual:
1. **Fase 1:** Incorporación de sub-atributos visuales y de rendimiento en `man_spieler`.
2. **Fase 2:** Implementación del sistema de Rasgos (*Traits*) en la simulación `aa_spieltag_simulation.php` como modificadores de probabilidad en la Fase 3 de tiro y en faltas.
3. **Fase 3:** Expansión del cuerpo técnico y centro de entrenamiento para interactuar dinámicamente con `aa_spieler_verbesserung.php` y `aa_spieler_erzeugen.php`.
