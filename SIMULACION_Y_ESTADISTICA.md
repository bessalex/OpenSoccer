# Análisis Estadístico y Matemático del Motor de Simulación y Determinación de Características en OpenSoccer

Este documento presenta una especificación técnica y un análisis estadístico profundo sobre los modelos matemáticos y algoritmos probabilísticos que gobiernan **OpenSoccer**. Se analizan en detalle la simulación de partidos en tiempo real, la determinación de características de los jugadores, la evolución de los atributos, los modelos de mercado y la generación de la cantera.

---

## 1. Introducción y Arquitectura de Datos

El sistema de datos de OpenSoccer representa a cada jugador (`man_spieler`) mediante un vector de características cuantitativas y categóricas. Las variables fundamentales involucradas en las ecuaciones estadísticas son:

* $S \in [0.1, 10.0]$: **Fuerza actual del jugador** (`staerke`).
* $T \in [0.1, 10.0]$: **Talento / Techo máximo** (`talent`).
* $E \in \mathbb{N}$ (días): **Edad del jugador** (`wiealt`), donde $365 \text{ días} = 1 \text{ año}$.
* $F \in [0, 100]$: **Frescura física** (`frische`).
* $M \in [0.00, 100.00]$: **Moral / Estado anímico** (`moral`).
* $P \in \{T, A, M, S\}$: **Posición táctica** (Portero, Defensa, Mediocampista, Delantero).

---

## 2. Motor de Simulación de Partidos (`aa_spieltag_simulation.php`)

El motor de simulación opera evento por evento sobre una representación discreta del tiempo regular de juego ($90 \text{ minutos}$).

### A. Distribución Temporal de Ataques (`get_minutes`)

La cantidad total de ataques por partido ($N_{angriffe}$) se distribuye estocásticamente a lo largo de los 90 minutos.

1. **Intervalo Medio:**
   $$\Delta t = \frac{90}{N_{angriffe}}$$
2. **Margen de Varianza Temporal:**
   $$\text{Margen} = \lceil \Delta t \rceil - 1$$
   $$\tau_i = \text{Uniforme}(-\text{Margen}, \text{Margen})$$
3. **Minuto del Evento ($t_i$):**
   $$t_i = t_{i-1} + \Delta t + \tau_i$$
4. **Asignación de Posesión por Ataque:**
   Para un porcentaje de posesión del equipo 1 ($B_1 \in [0, 100]$), la probabilidad de que el ataque $i$ pertenezca al Equipo 1 es:
   $$P(\text{Ataque } i \in \text{Equipo } 1) = \frac{R_{\text{restantes}}}{N_{angriffe} - i + 1} \times 100$$
   donde $R_{\text{restantes}}$ es la cantidad de ataques restantes presupuestados para el Equipo 1.

---

### B. Funciones de Ponderación Numérica

El simulador aplica transformaciones lineales a las fuerzas tácticas y de línea para ajustar la sensibilidad del azar en los enfrentamientos.

#### 1. Ponderador Táctico (`tactics_weight`)
Mapea el valor táctico configurado $W_{taktik}$ a un multiplicador operacional:
$$f_{\text{taktik}}(W) = 0.25 \cdot W + 0.5$$

#### 2. Ponderador de Fuerza por Línea (`strengths_weight`)
Comprime el rango de fuerza lineal $[0.1, 10.0]$ para evitar dominancia matemática absoluta y mantener incertidumbre competitiva:
$$f_{\text{fuerza}}(S) = 0.125 \cdot S + 0.0625$$

---

### C. Árbol de Decisiones de Ataque (`starte_angriff`)

Cada ataque procesa una cadena de eventos estocásticos encadenados basados en las fuerzas compuestas de las líneas del atacante ($att$) y defensor ($def$).

```
                      [ INICIO DEL ATAQUE ]
                               |
                               v
            +--------------------------------------+
            | Paso del 1er Tercio de Campo (Medio) |
            +--------------------------------------+
                               |
             +-----------------+-----------------+
             | (Probabilidad P_1)                | (Fallo)
             v                                   v
      [ Avanza a 2º Tercio ]             [ Ataque Desperdiciado ]
             |
     +-------+-------+
     |               |
(Falta Defensiva) (Fuera de Juego / Limpio)
     |               |
     v               v
 [Tiro Libre]  [Paso a Área / Ocasión Clara]
```

#### 1. Probabilidad de Superación del Primer Tercio ($P_1$)
$$P_1 = 50 \% \times \frac{f_{\text{fuerza}}(S_{att, M})}{f_{\text{fuerza}}(S_{def, M})} \times \frac{f_{\text{taktik}}(T_{att, 0}) \cdot f_{\text{taktik}}(T_{def, 0}) \cdot f_{\text{taktik}}(T_{att, 1})}{f_{\text{taktik}}(T_{att, 2})}$$

#### 2. Ocurrencia de Faltas e Infracciones
* **Probabilidad de Falta Defensiva:**
  $$P_{\text{foul}} = 25 \% \times f_{\text{taktik}}(T_{def, \text{agresividad}})$$
* **Distribución de Tarjetas tras Falta:**
  * Tarjeta Amarilla: $P(\text{Amarilla} \mid \text{Falta}) = 30\%$ (en 1er tercio) / $33\%$ (en 2º tercio).
  * Tarjeta Roja Directa: $P(\text{Roja} \mid \text{Falta}) = 3\%$.

#### 3. Impacto de Expulsiones / Tarjetas (`weakenTeam`)
Cuando un equipo recibe sanción disciplinaria, la fuerza efectiva de sus líneas sufre un factor de degradación $k_{\text{weaken}}$:
$$S_{\text{línea, nueva}} = S_{\text{línea, actual}} \times k_{\text{weaken}}$$

#### 4. Ocasión de Gol y Penalti
* **Penalti Concedido:**
  $$P_{\text{penalti}} = 19 \% \times \frac{f_{\text{fuerza}}(S_{att, S})}{f_{\text{fuerza}}(S_{def, A})}$$
* **Conversión de Penalti:**
  $$P_{\text{gol, penalti}} = \frac{77 \%}{f_{\text{fuerza}}(S_{def, T})}$$
* **Efectividad del Tiro a Puerta:**
  $$P_{\text{tiro}} = 30 \% \times \frac{f_{\text{fuerza}}(S_{att, S})}{f_{\text{fuerza}}(S_{def, A})}$$
  $$P_{\text{gol}} = 30 \% \times \frac{f_{\text{fuerza}}(S_{att, S})}{f_{\text{fuerza}}(S_{def, T})}$$

---

### D. Algoritmo de Lesiones (`create_verletzung`)

La ocurrencia de una lesión durante un lance del partido sigue la siguiente matriz probabilística discreta:

| Tipo de Lesión | Probabilidad Condicional | Días de Baja ($D$) |
| :--- | :---: | :---: |
| Sobrecarga / Tirón Muscular | $70.0\%$ | $1$ día |
| Esguince | $9.0\%$ | $3$ días |
| Contusión | $6.3\%$ | $5$ días |
| Rotura de Fibras Musculares | $4.4\%$ | $7$ días |
| Rotura de Ligamentos | $3.1\%$ | $9$ días |
| Daño de Cartílago | $2.2\%$ | $11$ días |
| Fractura Ósea | $< 5.0\%$ | $13$ días |

---

## 3. Deterministmo de Atributos y Desarrollo de Jugadores

### A. Progresión y Desarrollo Juvenil (`aa_spieler_verbesserung.php`)

Los jugadores progresan en función de su participación activa en partidos oficiales hasta alcanzar los 31 años de edad ($E < 11.315 \text{ días}$).

#### 1. Incremento de Fuerza por Entrenamiento / Partidos ($E < 31$ años)
Si el jugador ha disputado más de 8 partidos en la temporada ($N_{\text{partidos}} > 8$):
$$\Delta S = \frac{\text{Zufall}(0, 1)}{S} \times 5$$
Sujeto al límite estricto del talento:
$$S_{nuevo} = \min(S + \Delta S, T)$$

#### 2. Declive Físico por Edad ($E \ge 31$ años / $11.315 \text{ días}$)
A partir de los 31 años, el jugador experimenta degradación senescente según la función:
$$\text{Pérdida}(S) = \frac{\lfloor \frac{E}{365} - 28 \rfloor}{70} \times S$$
$$S_{nuevo} = S - \text{Pérdida}(S)$$

---

### B. Generación de Cantera Juvenil (`aa_spieler_erzeugen.php`)

Los jóvenes generados periódicamente nacen en el rango de edad de 17 a 19 años. Las probabilidades de posición y potencial se determinan según el nivel de la cantera ($L_{\text{cantera}} \in \{1, 2, 3, 4, 5\}$):

#### 1. Distribución Probabilística de Posiciones
$$\begin{cases}
P(\text{Portero } T) = 12\% \\
P(\text{Defensa } A) = 14\% \\
P(\text{Centrocampista } M) = 50\% \\
P(\text{Delantero } S) = 24\%
\end{cases}$$

#### 2. Modelo de Talento por Nivel de Cantera
$$T \sim \text{Uniforme}(T_{\min}(L), T_{\max}(L))$$

$$\begin{array}{|c|c|c|c|}
\hline
\textbf{Nivel Cantera } (L) & \mathbf{T_{\min}} & \mathbf{T_{\max}} & \textbf{Salario Base } (€) \\
\hline
1 & 2.1 & 5.9 & 300.000 \\
2 & 2.8 & 6.9 & 500.000 \\
3 & 3.5 & 7.9 & 700.000 \\
4 & 4.2 & 8.9 & 900.000 \\
5 & 4.9 & 9.9 & 1.200.000 \\
\hline
\end{array}$$

---

### C. Valoración Económica y Valor de Mercado (`aa_marktwert_berechnen.php`)

El valor de mercado ($MV$) de un jugador se calcula de forma no lineal integrando su fuerza actual ($S$), su talento ($T$) y un factor multiplicador por edad y posición:

$$MV = f_{\text{posición}}(P) \cdot \left( S^{2.3} \times 125.000 + T^{1.8} \times 45.000 \right) \cdot \delta(\text{Edad})$$

Donde $\delta(\text{Edad})$ castiga progresivamente a los jugadores mayores de 29 años y prima a los jóvenes promesas de 17-21 años.

---

## 4. Conclusiones del Análisis Estadístico

1. **Balance entre Azar y Estrategia:** El uso de la función compresión de fuerza $f_{\text{fuerza}}(S) = 0.125 \cdot S + 0.0625$ garantiza que un equipo superior gane estadísticamente la mayoría de encuentros, pero conservando un margen de imprevistos ("efecto copa").
2. **Ciclo de Vida del Jugador:** El modelo de envejecimiento exponencial inverso asegura una alta rotación de plantillas, obligando a los mánagers a mantener inversiones continuas en la cantera.
