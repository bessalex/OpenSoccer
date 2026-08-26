# Análisis Técnico del Motor de Juego y Propuesta de Nuevas Funcionalidades (OpenSoccer)

---

## 1. Análisis Estadístico y Matemático del Sistema de Simulación

El motor de simulación de partidos de OpenSoccer (`Website/aa_spieltag_simulation.php`) implementa un modelo estocástico discreto guiado por probabilidades porcentuales (`Chance_Percent`), ponderaciones tácticas y relaciones de fuerza entre líneas de juego.

---

### 1.1 Distribución del Tiempo y Estructura de Ataques

Cada partido se compone de un número prefijado de oportunidades de ataque ($N = 20$).

#### 1. Algoritmo de Generación de Minutos (`get_minutes`):
* **Intervalo Base ($I$):**
  $$I = \frac{90}{N} = \frac{90}{20} = 4.5 \text{ minutos}$$
* **Rango de Variación Estocástica ($S$):**
  $$S = \lceil I \rceil - 1 = 4 \text{ minutos}$$
* **Ruido Temporal Balanceado ($t_{\text{rand}}$):**
  Para evitar desviaciones sistemáticas del tiempo total de 90 minutos, los desvíos aleatorios en la jugada $k$ se compensan inversamente en la jugada $k+1$:
  $$t_{\text{rand}, k} \in [-S, S] \quad \implies \quad t_{\text{rand}, k+1} = -t_{\text{rand}, k}$$
* **Atribución Estocástica de Posesión por Ataque:**
  Dada una cantidad objetivo de ataques para el Equipo 1 ($N_1 = \text{round}(N \times \frac{\text{Ballbesitz}_1}{100})$), la probabilidad de que el ataque $i$ sea iniciado por el Equipo 1 viene dada por la probabilidad condicional:
  $$P(\text{Ataque}_i = \text{Equipo 1}) = \frac{\text{Ataques Restantes}_1}{N - i + 1} \times 100$$

---

### 1.2 Posesión de Balón y Ventaja de Localia

#### 1. Posesión Base por Fuerza del Mediocampo ($M_1, M_2$):
$$\text{Ballbesitz}_1 = \text{round}\left( \frac{100}{\frac{M_2}{M_1} + 1} \right) = \text{round}\left( \frac{100 \times M_1}{M_1 + M_2} \right)$$
$$\text{Ballbesitz}_2 = 100 - \text{Ballbesitz}_1$$

#### 2. Factor de Ventaja de Local:
En partidos de Liga y primeras rondas de Copa, se aplica un ajuste fijo de $+4\%$ de posesión al equipo local:
$$\text{Ballbesitz}_{\text{Heim}} \leftarrow \min(100, \text{Ballbesitz}_{\text{Heim}} + 4)$$
$$\text{Ballbesitz}_{\text{Gast}} \leftarrow \max(0, \text{Ballbesitz}_{\text{Gast}} - 4)$$

---

### 1.3 Ponderación de Tácticas y Fortalezas Posicionales

#### 1. Ponderación Táctica (`tactics_weight`):
Cada parámetro táctico $v \in \{1, 2, 3, 4, 5\}$ (alineación, velocidad de construcción, pase, riesgo, presión, agresividad) se transforma linealmente:
$$W_{\text{tactics}}(v) = 0.25 \times v + 0.5$$
* $v = 2$ (Predeterminado) $\implies W_{\text{tactics}}(2) = 1.0$
* $v = 1$ (Defensivo/Conservador) $\implies W_{\text{tactics}}(1) = 0.75$
* $v = 5$ (Ultrasfensivo/Riesgo Máximo) $\implies W_{\text{tactics}}(5) = 1.75$

#### 2. Ponderación de Fuerza Posicional (`strengths_weight`):
Las valoraciones de fuerza posicional $S_{\text{pos}} \in [0.1, 9.9]$ (Portería $T$, Defensa $A$, Mediocampo $M$, Delantera $S$) se ajustan según:
$$W_{\text{strength}}(S_{\text{pos}}) = 0.125 \times S_{\text{pos}} + 0.0625$$

---

### 1.4 Árbol de Decisiones Probabilísticas de un Ataque (`starte_angriff`)

Un ataque progresa secuencialmente a través de fases de campo:

```
[Inicio de Ataque]
        │
        ├── P(Avanzar Fase 1) ──► Fail ──► P(Contraataque Rápido) / Saque de Banda
        │        │
        │     Success
        │        │
        │        ├── P(Falta en Fase 1) ──► Tarjeta (Amarilla 30% / Roja 3%) + Tiro Libre Ind.
        │        ├── P(Fuera de Juego)
        │        └── P(Avanzar a Fase 2 - Área Rival)
        │                 │
        │                 ├── P(Falta en Área) ──► Penalti (77%/T_def Gol) O Tiro Libre Directo
        │                 └── P(Tiro a Gol Regular) ──► Gol / Bloqueo Defensor / Parada Portero
```

#### 1. Probabilidad de Superar el Primer Tercio (Fase 1):
$$P(\text{Fase 1}) = 50\% \times \frac{W_{\text{strength}}(M_{\text{att}})}{W_{\text{strength}}(M_{\text{def}})} \times \frac{W_{\text{tactics}}(\text{ausrichtung}_{\text{att}}) \times W_{\text{tactics}}(\text{ausrichtung}_{\text{def}}) \times W_{\text{tactics}}(\text{geschw}_{\text{att}})}{W_{\text{tactics}}(\text{pass}_{\text{att}})}$$

#### 2. Faltas y Sanciones Disciplinarias:
* **Probabilidad de Falta:** $P(\text{Falta}) = 25\% \times W_{\text{tactics}}(\text{aggress}_{\text{def}})$
* **Tarjeta Amarilla:** $P(\text{Amarilla} \mid \text{Falta}) = 30\%$ (Fase 1) ó $33\%$ (Fase 2). Penaliza la fuerza del equipo defensivo en un $-2\%$ ($W_{\text{team}} \times 0.98$).
* **Tarjeta Roja:** $P(\text{Roja} \mid \text{Falta}) = 3\%$. Penaliza la fuerza del equipo defensivo en un $-10\%$ ($W_{\text{team}} \times 0.90$).

#### 3. Opciones de Disparo en Fase 2 (Área Rival):
* **Penalti:**
  $$P(\text{Penalti} \mid \text{Falta en Área}) = 19\% \times \frac{W_{\text{strength}}(S_{\text{att}})}{W_{\text{strength}}(A_{\text{def}})}$$
  $$P(\text{Gol Penalti}) = \frac{77\%}{W_{\text{strength}}(T_{\text{def}})}$$
* **Disparo Normal a Puerta:**
  $$P(\text{Tiro}) = 62\% \times \frac{W_{\text{strength}}(S_{\text{att}})}{W_{\text{strength}}(A_{\text{def}})} \times W_{\text{tactics}}(\text{pass}_{\text{att}}) \times W_{\text{tactics}}(\text{risk}_{\text{att}})$$
  $$P(\text{Gol} \mid \text{Tiro}) = 30\% \times \frac{W_{\text{strength}}(S_{\text{att}})}{W_{\text{strength}}(T_{\text{def}})}$$

---

### 1.5 Sistema de Clasificación ELO (`eloChange`)

El cambio en la puntuación ELO tras un encuentro se calcula mediante:

$$\Delta_{\text{ELO}} = 40 \times W_{\text{torneo}} \times F_{\text{gol}} \times (S - E)$$

Donde:
* **Ponderación del Torneo ($W_{\text{torneo}}$):**
  Liga $= 1.0$, Copa Nacional $= 2.0$, Cup Internacional $= 1.3$, Amistoso $= 0.0$.
* **Diferencia RATING ($D$):**
  $$D = \text{ELO}_{\text{Gast}} - (\text{ELO}_{\text{Heim}} + 100)$$
* **Puntuación Esperada ($E$):**
  $$E = \frac{1}{10^{(D / 400)} + 1}$$
* **Factor de Margen de Gol ($F_{\text{gol}}$):**
  $$F_{\text{gol}} = \begin{cases} 1.0 & \text{si } |G_1 - G_2| \le 1 \\ 1.5 & \text{si } |G_1 - G_2| = 2 \\ \frac{11 + |G_1 - G_2|}{8} & \text{si } |G_1 - G_2| \ge 3 \end{cases}$$
* **Resultado ($S$):** Victoria $= 1.0$, Empate $= 0.5$, Derrota $= 0.0$.

---

## 2. Determinación y Evolución de Características de Jugadores

---

### 2.1 Generación de Jugadores Jóvenes (`aa_spieler_erzeugen.php`)

Los jugadores canteranos se generan mediante una distribución logarítmico-exponencial continua:

#### 1. Algoritmo de Generación de Fuerza/Talento (`getRandomStrength`):
Dada una variable uniforme $U \sim \text{Uniforme}(0, 1)$ y un parámetro de no-linealidad $\alpha = 1.15$:
$$R = U^{\alpha} \times (\ln(\text{max}) - \ln(\text{min})) + \ln(\text{min})$$
$$\text{Valor} = e^R$$

#### 2. Escalamiento por Nivel de Cantera (`jugendarbeit`):

| Nivel Cantera | Rango Talento ($[T_{\min}, T_{\max}]$) | Sueldo Inicial Base (€) |
| :---: | :---: | :---: |
| **1** | $[2.1, 5.9]$ | 300.000 € |
| **2** | $[2.8, 6.9]$ | 500.000 € |
| **3** | $[3.5, 7.9]$ | 700.000 € |
| **4** | $[4.2, 8.9]$ | 900.000 € |
| **5** | $[4.9, 9.9]$ | 1.200.000 € |

Fuerza inicial del canterano:
$$\text{Staerke}_{\text{inicial}} = \text{round}\left( \text{Talent} \times \text{getRandomStrength}(0.5, 0.9), 1 \right)$$

---

### 2.2 Evolución y Declive Físico por Edad (`aa_spieler_verbesserung.php`)

#### 1. Progresión de Jugadores Jóvenes ($\text{Edad} < 31 \text{ años} / 11.315 \text{ días}$):
Requiere al menos 8 partidos jugados (`spiele_gesamt > 8`). El incremento en la fuerza viene dado por:
$$\Delta_{\text{fuerza}} = \frac{\lceil \text{mt\_rand}(1, 6) \times \frac{5}{\text{Staerke}_{\text{actual}}} \rceil}{10}$$
Acotado estrictamente en:
$$0.1 \le \Delta_{\text{fuerza}} \le 1.2 \quad \text{y} \quad \text{Staerke}_{\text{nueva}} \le \text{Talent}$$

#### 2. Declive de Veteranos ($\text{Edad} \ge 31 \text{ años} / 11.315 \text{ días}$):
* **Factor de Edad ($\text{minusP}$):**
  $$\text{minusP} = \frac{\lfloor \text{Edad en años} \rfloor - 28}{70}$$
* **Pérdida de Fuerza:**
  $$\Delta_{\text{decline}} = \max\left(0.1, \text{round}(\text{Staerke} \times \text{minusP}, 1)\right)$$
  $$\text{Staerke}_{\text{nueva}} = \max(0.1, \text{Staerke}_{\text{actual}} - \Delta_{\text{decline}})$$

---

### 2.3 Valor de Mercado (`zzserver.php`)

El valor económico estimado del jugador en el mercado de transferencias se rige por la siguiente fórmula exponencial con atenuación por edad:

$$\text{Marktwert} = \text{ROUND}\left( \frac{1.75^{\text{Staerke}} \times \text{Talent} \times 30.000}{\left( \frac{\lfloor \text{Edad en años} \rfloor}{27} \right)^{1.7}} \right) + \lfloor 100 + \text{RAND}() \times 1000 \rfloor$$

---

## 3. Propuestas de Nuevos Features para Jugadores y Clubes

A continuación se presentan propuestas arquitectónicas para expandir la profundidad estratégica de OpenSoccer sin alterar la ligereza ni la compatibilidad con la base de datos existente.

---

### 3.1 Nuevas Funcionalidades para Jugadores

#### 1. Atributos Secundarios (Sub-Stats Físicas y Mentales)
Actualmente los jugadores cuentan únicamente con `staerke`, `talent`, `frische` y `moral`. Se propone integrar 3 atributos secundarios adicionales (escala $1 - 100$):
* **Resistencia / Stamina (`stamina`):**
  - *Mecánica:* Modifica la tasa de pérdida de frische durante la simulación. Un jugador con alta resistencia pierde menos frische por partido ($1.0 - \frac{\text{stamina}}{200}$).
* **Sensibilidad a Lesiones (`injury_resistance`):**
  - *Mecánica:* Influye directamente en la probabilidad condicional de sufrir lesiones durante lances agresivos de juego.
* **Liderazgo (`leadership`):**
  - *Mecánica:* Si el capitán posee liderazgo $> 80$, otorga un bono de $+0.2$ a la fuerza de la línea en momentos críticos o desventaja en el marcador.

#### 2. Posiciones Secundarias y Versatilidad Polivalente
* **Campo DB en `man_spieler`:** `pos_secundario CHAR(1)` (ej. 'M' con secundaria 'A').
* **Mecánica:** Si un jugador es alineado en su posición secundaria, rinde al $85\%$ de su `staerke` en lugar de requerir la adición de un "Amateurspieler" (jugador genérico sin valor).

#### 3. Habilidades Especiales / Perfiles Tácticos (Traits / Specialties)
Asignación de 1 trait especial a jugadores con `talent >= 7.5`:
* **Especialista a Balón Parado (`FreeKickSpecialist`):** $+15\%$ probabilidad de gol en faltas directas.
* **Cabeceador de Área (`AerialThreat`):** $+20\%$ probabilidad de remate a gol en saques de esquina / centros.
* **Especialista Parapenaltis (`PenaltyStopper`):** $+25\%$ probabilidad de detener penaltis.
* **Especialista en Contraataques (`CounterAttacker`):** Incrementa la efectividad en jugadas de `quickCounterAttack`.

#### 4. Sistema Dinámico de Moral y Expectativas de Rol
Actualmente la moral sube/baja de forma genérica. Se propone un sistema de **Expectativa de Rol**:
* Roles: *Clave*, *Rotación*, *Joven Promesa*.
* Un jugador *Clave* que no juegue en 3 partidos consecutivos pierde $-15$ puntos de moral, reduciendo su valor de mercado y afectando el rendimiento en campo.

---

### 3.2 Nuevas Funcionalidades para Clubes

#### 1. Infraestructura de Centro de Entrenamiento y Centro Médico
Ampliación de los edificios del club en `man_stadien` / `man_teams`:
* **Centro Médico (Nivel 1 - 5):**
  - Reduce la duración de las lesiones sufridas por jugadores en un $10\%$ por nivel (ej. nivel 5 reduce $50\%$ días lesionado).
* **Centro de Alto Rendimiento / Cantera (Nivel 1 - 5):**
  - Aumenta la probabilidad de mejora periódica de jóvenes (`aa_spieler_verbesserung.php`) en $+5\%$ por nivel.

#### 2. Cuerpo Técnico Especializado (Coaching Staff)
Permitir la contratación de personal técnico en `man_teams`:
* **Entrenador de Porteros:** Aumenta la efectividad de la posición `T` en simulación.
* **Preparador Físico:** Aumenta la velocidad de recuperación de `frische` diaria entre partidos ($+1$ a $+3$ puntos de frische adicionales por día).
* **Ojeador Internacional (Scout regional):** Revela con mayor precisión el verdadero `talent` de los jugadores en el mercado de transferencias sin depender únicamente del hash del scout.

#### 3. Contratos de Patrocinio Escalonados con Objetivos de Rendimiento
En lugar del patrocinador único con pago estático por partido/victoria:
* **Sponsor Principal con Bonos por Objetivo:**
  - Pago base moderado + Bonificación por alcanzar ronda de Copa / Puestos de clasificación a Cup / Mantener la categoría.

#### 4. Dinámica de Afición, Fanatismo y Clima del Estadio
* **Sentimiento de la Afición (`fan_morale`):**
  - Se calcula a partir de las ráfagas de victorias/derrotas y la política de precios de entradas.
  - Afecta la asistencia base en el estadio (`temp_fanaufkommen`) y las ventas de artículos en la tienda del club (`fanshop`).

---
