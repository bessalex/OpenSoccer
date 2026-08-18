# Guía Explicativa del Motor de Simulaciones de Partidos de OpenSoccer

---

## 1. Introducción: ¿Qué es y cómo funciona el motor de partidos?

Imagínate que el motor de simulaciones de OpenSoccer es como un **árbitro e historiador virtual** que ejecuta un partido en fracción de segundos. Cada vez que llega la hora programada para un encuentro (por ejemplo, a las 14:00 para la Liga o a las 18:00 para la Copa), el script `aa_spieltag_simulation.php` toma las decisiones tácticas de ambos mánagers, mide la calidad y condición física de los jugadores, y "tira los dados" de forma probabilística para decidir qué ocurre en cada minuto del partido.

Para entenderlo fácilmente, seguiremos paso a paso un ejemplo práctico entre dos equipos ficticios:
* **Equipo Local:** *Los Leones FC*
* **Equipo Visitante:** *Águilas Reales*

---

## 2. Paso a Paso: La Simulación de un Partido

```
+-------------------------------------------------------------------------------+
|                      FLUJO COMPLETO DEL MOTOR DE PARTIDOS                     |
+-------------------------------------------------------------------------------+
|                                                                               |
|  [ PASO 1: ASISTENCIA Y VENTA DE ENTRADAS ]                                    |
|  - Asistencia al estadio y cálculo de ingresos por taquilla.                  |
|                                                                               |
|  [ PASO 2: FORMACIÓN Y CÁLCULO DE FUERZA LINEA POR LINEA ]                    |
|  - Evaluación del 11 titular (Fuerza + Frescura) y relleno con Amateurs.       |
|                                                                               |
|  [ PASO 3: DISTRIBUCIÓN DE POSESIÓN Y MINUTOS DE ATAQUE ]                     |
|  - ¿Quién tiene la pelota y en qué minutos ocurren los 20 lances clave?       |
|                                                                               |
|  [ PASO 4: RESOLUCIÓN DE CADA ATAQUE ]                                        |
|  - Avance en el campo, faltas, tarjetas, disparos y goles.                    |
|                                                                               |
|  [ PASO 5: FINAL DEL PARTIDO Y CONSECUENCIAS ]                                |
|  - Desgaste físico, subida de moral, cobro de patrocinadores y puntos.        |
|                                                                               |
+-------------------------------------------------------------------------------+
```

---

### Paso 1: Cálculo de Asistencia e Ingresos por Taquilla

Antes de dar el pitido inicial, el sistema calcula cuántos aficionados van al estadio.
* **Factores considerados:** La base de hinchas de *Los Leones FC*, el ranking del rival (*Águilas Reales*), el tipo de competición y el **precio de la entrada**.
* **Ejemplo:**
  Si el estadio de *Los Leones FC* tiene 50.000 asientos y la entrada cuesta 20 €, el motor calcula que asistirán **42.000 espectadores**.
  Esto genera unos ingresos de $42.000 \times 20 = 840.000\,\text{€}$ que se guardan para abonarse al club tras el partido.

---

### Paso 2: Alineaciones, Frescura y Fuerza Efectiva

El motor lee la alineación de ambos mánagers. Para cada uno de los 11 jugadores, calcula su **fuerza real en el partido** combinando su Nivel de Fuerza base y su Frescura Física:

$$\text{Fuerza Efectiva} = \text{Fuerza Base} \times \left(0.33 + 0.67 \times \frac{\text{Frescura}}{100}\right)$$

* **Ejemplo:**
  * Un delantero con **Fuerza 8.0** que llega al **100% de Frescura** rinde al $100\%$ de su capacidad (**8.0**).
  * Si el mánager lo hace jugar cansado con solo **50% de Frescura**, su fuerza cae a **5.3**.
* **¿Qué pasa si falta un titular?** Si el mánager no alineó a 11 jugadores, el sistema recluta automáticamente a un *Jugador Amateur* para cubrir el puesto vacante, pero sanciona al equipo con una multa del patrocinador.

Con estos datos, el motor suma la fuerza de cada línea: **Portería (T)**, **Defensa (A)**, **Centrocampo (M)** y **Delantera (S)**, e integra los ajustes tácticos (ej. una postura "Ultra-ofensiva" aumenta la fuerza de ataque pero resta en defensa).

---

### Paso 3: Posesión del Balón y Minutos de Jugada

Un partido completo en OpenSoccer no simula cada pase individual, sino **20 jugadas clave (ataques)** repartidos durante los 90 minutos.

1. **Cálculo de Posesión:** Comparando la fuerza del centro del campo de ambos equipos (más un bono del $4\%$ para el local en liga), se asigna el porcentaje de balón.
   * *Ejemplo:* *Los Leones FC* obtienen el **55% de posesión** y *Águilas Reales* el **45%**.
2. **Reparto de Ataques:** De los 20 ataques del partido, **11 ataques** serán para *Los Leones FC* y **9 ataques** para *Águilas Reales*.
3. **Distribución Temporal:** El motor distribuye los 20 ataques a lo largo de los 90 minutos añadiendo minutos aleatorios (por ejemplo, minutos 4', 9', 15', 23', 31'...).

---

### Paso 4: ¿Cómo se resuelve un Ataque? (Minuto a Minuto)

Cuando llega el turno de un ataque (por ejemplo, en el **Minuto 31'** para *Los Leones FC*), el motor realiza una cadena de comprobaciones probabilísticas:

```
[ ATAQUE EN MINUTO 31' ]
    │
    ├── 1) ¿Supera la línea del centro del campo?
    │       ├── NO ──> Balón fuera o contraataque del rival.
    │       └── SÍ ──> Avanza a la zona de peligro.
    │
    ├── 2) ¿Comete falta la defensa rival?
    │       ├── SÍ ──> ¿Es amarilla o roja? ──> Tiro Libre o Penalti.
    │       └── NO ──> El atacante busca el disparo a puerta.
    │
    └── 3) Disparo a Puerta
            ├── PARADA del Portero / Bloqueo del Defensa.
            └── ¡GOOOOL! ──> Se actualiza el marcador.
```

* **Cálculo del Gol:** Si el delantero de *Los Leones FC* (Fuerza 7.5) dispara contra el portero de *Águilas Reales* (Fuerza 6.0), el sistema calcula la probabilidad de gol:
  $$\text{Probabilidad de Gol} \approx 30\% \times \frac{\text{Fuerza Delantero}}{\text{Fuerza Portero}} = 30\% \times \frac{7.5}{6.0} = 37.5\%$$
* Si el número aleatorio generado cae dentro de ese $37.5\%$, ¡es Gol! El motor elige aleatoriamente cuál de los atacantes marcó y redacta la frase descriptiva en el reporte.

---

### Paso 5: Final del Partido y Consecuencias

Al llegar al minuto 90' (o 120' con penaltis en caso de eliminatoria de Copa), el partido concluye y el motor realiza las siguientes actualizaciones en la base de datos:

1. **Puntos y Clasificación:** Se otorgan 3 puntos al ganador (o 1 a cada uno en empate) y se actualizan goles a favor/en contra.
2. **Desgaste y Lesiones:** Se resta frescura física a los 11 jugadores que participaron. Si la frescura media del equipo era muy baja, existe riesgo de que algún jugador sufra una **lesión** (ej. esguince o rotura muscular) y quede de baja unos días.
3. **Moral y Desarrollo:** Todos los jugadores que disputaron el encuentro ganan $+1.8$ puntos de **Moral**. Además, acumulan partidos jugados para su entrenamiento diario.
4. **Caja y Finanzas:** Los ingresos por taquilla y las primas por victoria del patrocinador se guardan en el búfer financiero para transferirse a la cuenta bancaria del club.

---

## 3. Resumen de la Simulación en Cifras

| Concepto | Valor / Regla |
| :--- | :--- |
| **Duración Simulada** | 90 minutos (o 120' en prórroga/penaltis de Copa). |
| **Jugadas Clave por Partido** | 20 ataques totales divididos según la posesión. |
| **Alineación Titular** | 11 jugadores (se completa con Amateurs si falta alguno). |
| **Recompensa de Moral** | $+1.8$ puntos de moral por disputar partido oficial. |
| **Impacto de Expulsión (Roja)** | Mantiene al equipo con un $-10\%$ de fuerza efectiva. |

---
*Esta guía técnica simplificada permite comprender la lógica interna con la que OpenSoccer resuelve cada encuentro deportivo.*
