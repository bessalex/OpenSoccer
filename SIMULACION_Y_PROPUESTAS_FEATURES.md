# Análisis Estadístico del Motor de Juego, Determinación de Características y Propuestas de Nuevos Features

Este documento proporciona un análisis exhaustivo y riguroso de la arquitectura matemática y estadística empleada en **OpenSoccer** para la simulación de partidos, el desarrollo de personajes (jugadores) y el valor de mercado, seguido de una propuesta detallada de nuevos *features* funcionales para jugadores y clubes.

---

## ÍNDICE
1. [Análisis Estadístico y Matemático del Motor de Simulación](#1-análisis-estadístico-y-matemático-del-motor-de-simulación)
   - [A. Distribución Temporal e Intervalos de Ataque](#a-distribución-temporal-e-intervalos-de-ataque)
   - [B. Modelo de Posesión de Balón](#b-modelo-de-posesión-de-balón)
   - [C. Funciones de Ponderación Táctica y de Fuerza](#c-funciones-de-ponderación-táctica-y-de-fuerza)
   - [D. Arbol Estocástico de Decisiones en Transición de Ataque](#d-árbol-estocástico-de-decisiones-en-transición-de-ataque)
   - [E. Modelo de Valoración Elo](#e-modelo-de-valoración-elo)
2. [Determinación y Modelado de Características de Jugadores](#2-determinación-y-modelado-de-características-de-jugadores)
   - [A. Generación Estocástica de Jóvenes Talentos](#a-generación-estocástica-de-jóvenes-talentos)
   - [B. Curvas de Progresión y Declinación Físico-Técnica](#b-curvas-de-progresión-y-declinación-físico-técnica)
   - [C. Modelo Exponencial de Valor de Mercado](#c-modelo-exponencial-de-valor-de-mercado)
3. [Propuestas de Nuevos Features para Jugadores](#3-propuestas-de-nuevos-features-para-jugadores)
4. [Propuestas de Nuevos Features para Clubes](#4-propuestas-de-nuevos-features-para-clubes)

---

## 1. Análisis Estadístico y Matemático del Motor de Simulación

El simulador de partidos de OpenSoccer (`Website/aa_spieltag_simulation.php`) es un motor probabilístico basado en eventos estocásticos discretos y cadenas de decisiones deterministas ponderadas por factores de rendimiento y configuración táctica.

### A. Distribución Temporal e Intervalos de Ataque

Un partido regular consta de $N = 20$ jugadas o ataques clave distribuidos a lo largo de los 90 minutos reglamentarios.

#### 1. Cálculo de Minutos de Ataque
Para evitar un espaciado homogéneo o artificial, el juego introduce variabilidad estocástica en cada intervalo $i \in \{1, \dots, N\}$:
$$\text{Intervall} = \frac{90}{N} = \frac{90}{20} = 4.5 \text{ minutos}$$
$$\text{Spielraum} = \lceil \text{Intervall} \rceil - 1 = 5 - 1 = 4 \text{ minutos}$$

En cada iteración, se calcula un desplazamiento aleatorio $Z_i \sim U(-\text{Spielraum}, \text{Spielraum})$. Para garantizar que la suma de desviaciones no desplace dramáticamente el partido, se aplica una técnica de corrección por saldo alternado:
$$M_i = M_{i-1} + \text{Intervall} + Z_i, \quad Z_i = -Z_{i-1}$$

#### 2. Asignación Estocástica del Atacante
La asignación de cuál equipo ejecuta el ataque $i$ se rige por un muestreo sin reemplazo probabilístico basado en el porcentaje de posesión del Equipo 1 ($P_1$):
$$\text{Ataques Restantes Team 1} (A_{1,i}) = \text{round}\left(N \times \frac{P_1}{100}\right)$$
$$p(\text{Ataque Team 1 en jugada } i) = \frac{A_{1,i}}{N - i + 1} \times 100$$
Se genera $R \sim U(0, 100)$. Si $R < p$, ataca el Equipo 1; de lo contrario, ataca el Equipo 2.

---

### B. Modelo de Posesión de Balón

La posesión de balón refleja la superioridad del mediocampo y la ventaja de localía.

$$\text{Posesión Base Team 1 } (P_{1,\text{base}}) = \text{round}\left( \frac{100}{\frac{S_{M,2}}{S_{M,1}} + 1} \right)$$
donde $S_{M,1}$ y $S_{M,2}$ representan la fuerza ponderada de la línea del mediocampo de ambos equipos.

#### Ajuste por Localía:
En partidos de Liga (donde existe factor de cancha):
$$P_1 = \min(100, \max(0, P_{1,\text{base}} + 4))$$
$$P_2 = 100 - P_1$$

En torneos neutrales (Copa/Cup o amistosos), no se aplica el bono de $+4\%$.

---

### C. Funciones de Ponderación Táctica y de Fuerza

El simulador transforma los parámetros discretos de alineación (rango $1..3$ o $1..4$) y fuerza ($0.1..9.9$) a factores multiplicativos mediante funciones de escalado.

#### 1. Función de Ponderación Táctica $W_{\text{tactic}}(x)$
$$W_{\text{tactic}}(x) = 0.25 \cdot x + 0.5$$

| Valor Táctico ($x$) | Significado | Multiplicador $W_{\text{tactic}}(x)$ |
| :---: | :--- | :---: |
| **1** | Conservador / Defensivo / Corto | **0.75** |
| **2** | Normal / Mixto | **1.00** |
| **3** | Arriesgado / Ofensivo / Largo | **1.25** |
| **4** | Ultraofensivo | **1.50** |

#### 2. Función de Ponderación de Fuerza $W_{\text{strength}}(S)$
$$W_{\text{strength}}(S) = 0.125 \cdot S + 0.0625$$
Esta función suaviza la brecha entre jugadores de distintas categorías para evitar dominancias absolutas infalibles y permitir sorpresas deportivas.

---

### D. Árbol Estocástico de Decisiones en Transición de Ataque

Cada jugada ejecutada en la función `starte_angriff()` atraviesa una secuencia de pruebas de probabilidad condicional:

```
[ INICIO DEL ATAQUE ]
         |
         v
1ª Fase: ¿Supera la 1ª línea defensiva (Mediocampo)?
   Probabilidad: 50% * [W_str(S_M1) / W_str(S_M2)] * [W_tac(ausrichtung1) * W_tac(ausrichtung2) * W_tac(geschw1) / W_tac(pass1)]
         |
         +---> NO --> ¿Es interceptado con Contraataque Rápido? (15% * [W_str(S_A2) / W_str(S_S1)] * ...)
         |              |
         |              +---> SI: Se invierte el ataque con la defensa del atacante reducida al 80%.
         |              +---> NO: Balón fuera (Córner / Saque de banda).
         |
         +---> SÍ --> 2ª Fase: Avance a 3/4 de Campo
                        |
                        +---> ¿Falta defensiva? (25% * W_tac(aggress2))
                        |        |
                        |        +---> Tarjeta: Amarilla (30%) / Roja (3%)
                        |        +---> Tiro Libre Indirecto (Tiro a gol: 30% * [W_str(S_S1)/W_str(S_A2)])
                        |
                        +---> ¿Fuera de Juego? (17% * W_tac(ausrichtung1) * W_tac(pass1))
                        |
                        +---> OPORTUNIDAD CLARA DE GOL
                                 |
                                 +---> ¿Falta en el área (Penalti)? (19% * [W_str(S_S1)/W_str(S_A2)])
                                 |        |
                                 |        +---> Gol (77% / W_str(S_T2)) | Fallo (50%) | Parada (50%)
                                 |
                                 +---> Tiro a Puerta Directo
                                          |
                                          +---> Gol: 30% * [W_str(S_S1) / W_str(S_T2)]
                                          +---> Bloqueo Defensivo: 50% * W_str(S_A2)
                                          +---> Parada del Portero / Desvío
```

---

### E. Modelo de Valoración Elo

OpenSoccer utiliza una adaptación del sistema Elo para clasificar clubes en `Website/zzserver.php`:

1. **Puntos Esperados ($E_H$):**
   $$D = -|S_H + 100 - S_G| \quad (\text{si } S_H > S_G) \quad \text{ó} \quad D = |S_H + 100 - S_G|$$
   $$E_H = \frac{1}{10^{D/400} + 1}$$
   *(Donde $+100$ representa la ventaja de local).*

2. **Factor de Tordifferenz ($F_{\text{goal}}$):**
   * $\Delta G = 0 \text{ ó } 1 \implies F_{\text{goal}} = 1.0$
   * $\Delta G = 2 \implies F_{\text{goal}} = 1.5$
   * $\Delta G \ge 3 \implies F_{\text{goal}} = \frac{11 + \Delta G}{8}$

3. **Multiplicador de Torneo ($W_{\text{tour}}$):**
   Liga: $1.0$ | Copa Nacional: $2.0$ | Copa Internacional: $1.3$ | Amistoso: $0.0$

4. **Variación Final de Elo ($\Delta \text{Elo}$):**
   $$\Delta \text{Elo} = 40 \cdot W_{\text{tour}} \cdot F_{\text{goal}} \cdot (R - E_H)$$
   *(Donde $R = 1$ en victoria, $0.5$ en empate, $0$ en derrota).*

---

## 2. Determinación y Modelado de Características de Jugadores

### A. Generación Estocástica de Jóvenes Talentos

En `Website/aa_spieler_erzeugen.php`, los juveniles son generados según el nivel de infraestructura de cantera ($L \in \{1, 2, 3, 4, 5\}$):

1. **Rango de Talento Base ($T$):**
   $$T_{\min} = 2.1 + (L - 1) \cdot 0.7, \quad T_{\max} = 5.9 + (L - 1) \cdot 1.0$$
   $$T = T_{\min} + \frac{\text{rand}(0, 100)}{100} \cdot (T_{\max} - T_{\min})$$

2. **Fuerza Inicial ($S_{\text{init}}$):**
   $$S_{\text{init}} = \frac{T}{2} + \frac{\text{rand}(0, 5)}{10}$$

3. **Matriz de Posición Proyectada:**
   * Portero (`T`): $12\%$
   * Defensa (`A`): $14\%$
   * Mediocampo (`M`): $50\%$
   * Delantero (`S`): $24\%$

---

### B. Curvas de Progresión y Declinación Físico-Técnica

El desarrollo del jugador está regido por `Website/aa_spieler_verbesserung.php`:

#### 1. Incremento de Fuerza por Experiencia ($S < 31 \text{ años} / 11.315 \text{ días}$)
Se activa cuando el jugador acumula $\text{spiele\_gesamt} > 8$:
$$\Delta S = \left\lceil \left( \frac{\frac{\text{rand}(0, 5) + 1}{10}}{S} \cdot 5 \right) \cdot 10 \right\rceil \cdot \frac{1}{10}$$
$$\text{Sujeto a: } 0.1 \le \Delta S \le 1.2, \quad S_{\text{nuevo}} = \min(T, S + \Delta S)$$

#### 2. Declinación por Edad ($S \ge 31 \text{ años} / 11.315 \text{ días}$)
$$\text{Factor Pérdida } (F_{\text{age}}) = \frac{\lfloor \text{Edad en Años} - 28 \rfloor}{70}$$
$$\Delta S_{\text{decline}} = \text{round}(S \cdot F_{\text{age}}, 1)$$
$$S_{\text{nuevo}} = \max(0.1, S - \Delta S_{\text{decline}})$$

---

### C. Modelo Exponencial de Valor de Mercado

El valor monetario de un jugador (`marktwert`) se determina en `Website/zzserver.php` mediante la expresión:

$$\text{Valor de Mercado} = \text{ROUND}\left( \frac{1.75^{S} \cdot T \cdot 30.000}{\left( \frac{\lfloor \text{Edad}/365 \rfloor}{27} \right)^{1.7}} + \text{FLOOR}(100 + \text{RAND}() \cdot 1000) \right)$$

#### Propiedades del Modelo:
* **Escala Exponencial $1.75^S$:** Refleja la escasez del talento de élite (un jugador con fuerza $8.0$ vale exponenacialmente más que dos de fuerza $4.0$).
* **Penalización por Edad $(\text{Edad}/27)^{1.7}$:** A partir de los 27 años, la curva amortiza drásticamente el valor de mercado para desincentivar traspasos millonarios de veteranos.

---

## 3. Propuestas de Nuevos Features para Jugadores

Para elevar la profundidad táctica y el realismo de la simulación, se proponen los siguientes nuevos módulos para los jugadores:

### A. Desglose Multidimensional de Atributos
Reemplazar la fuerza única (`staerke`) por un perfil técnico y físico completo:
1. **Atributos Físicos:** Velocidad, Aceleración, Resistencia, Fuerza Física, Agilidad.
2. **Atributos Técnicos:** Pase Corto, Pase Largo, Remate, Control/Regate, Juego Aéreo, Entradas/Tackles.
3. **Atributos Mentales:** Visión de Juego, Posicionamiento, Agresividad, Liderazgo, Sangre Fría (Bajo Presión).

### B. Sistema de Rasgos Especiales y Cualidades (*Traits*)
Asignar cualidades únicas que alteren eventos específicos en la simulación:
* **Especialista a BP:** +15% de efectividad en tiros libres e indirectos.
* **Motor Incansable:** Reduce un 50% la pérdida de frescura durante el partido.
* **Líder de Campo:** Otorga un bono de +5% de moral a los compañeros alineados.
* **Propenso a Lesiones:** Duplica el riesgo de lesión al jugar con frescura baja ($< 70\%$).

### C. Pierna Preferida y Versatilidad Posicional
* Introducir definición de pie hábil (Diestro, Zurdo, Ambidextro).
* Habilitar posiciones secundarias (ej. `CB/RB`, `CM/CAM`, `RW/ST`) con una penalización menor de rendimiento ($90\%$) si se alinean en su posición secundaria.

### D. Dinámicas de Vestuario y Química
* **Química de Equipo:** Ponderación colectiva basada en la antigüedad compartida y el idioma/nacionalidad.
* **Liderazgo y Roles:** Definición de Capitán y Segundo Capitán, que amortiguan caídas de moral tras derrotas.

### E. Gestión Avanzada de Lesiones y Fatiga
* **Historial Médico:** Lesiones clasificadas por gravedad y zona afectada (Tobillo, Isquiotibiales, Ligamentos).
* **Sobrecarga Físico-Muscular:** Acumulación de micro-fatiga si no descansa en 3 jornadas consecutivas.

---

## 4. Propuestas de Nuevos Features para Clubes

Para enriquecer el ecosistema de gestión técnica, financiera e infraestructura, se detallan las siguientes mejoras para los clubes:

### A. Cuerpo Técnico Ampliado
Expandir la tabla de personal (`man_personal`) con especialistas que aporten bonificaciones directas:
1. **Director Técnico / Entrenador Principal:** Aumenta el progreso de los jugadores jóvenes en los entrenamientos.
2. **Entrenador de Porteros:** Otorga un bono directo de parada en tiros libres y penaltis.
3. **Preparador Físico / Fisioterapeuta:** Aumenta la velocidad de recuperación de frescura diaria ($+2\%$ diario).
4. **Director Deportivo / Ojeador Head Scout:** Permite estimar con mayor precisión ($> 95\%$) el talento de jugadores rivales.

### B. Red Internacional de Scouting e Inteligencia Deportiva
* Posibilidad de enviar ojeadores a regiones específicas (Sudamérica, Europa del Este, África, Asia) para descubrir jóvenes promesas antes de que ingresen al mercado de fichajes público.

### C. Patrocinadores Dinámicos con Cláusulas de Rendimiento
* Contratos de patrocinio variables con metas cuantitativas negociables:
  * Bono por clasificación a Copas Internacionales.
  * Cláusula por superar $X$ goles anotados en la temporada.
  * Penalización por descenso o rachas de más de 3 derrotas consecutivas.

### D. Sistema de Tácticas Avanzadas y Jugadas Ensayadas
* **Estrategias en Balón Parado:** Asignar rematadores clave en saques de esquina (primer palo, segundo palo, al borde del área).
* **Cambios Tácticos Automatizados:** Configuración de instrucciones condicionales pre-partido (ej. *"Si perdiendo en el min 70, cambiar a orientación Ultraofensiva e ingresar un Delantero"*).
* **Presión por Zonas:** Seleccionar en qué líneas presionar (Presión Alta, Bloque Medio, Autobús en el Área).

---
*Documentación elaborada como análisis técnico para OpenSoccer.*
