# Documento Técnico: Análisis Estadístico de Simulación, Características y Propuesta de Nuevos Features en OpenSoccer

## Índice
1. [Visión General](#1-visión-general)
2. [Análisis Estadístico y Matemático del Motor del Juego](#2-análisis-estadístico-y-matemático-del-motor-del-juego)
   - [A. Determinación y Generación de Atributos del Jugador](#a-determinación-y-generación-de-atributos-del-jugador)
   - [B. Desarrollo, Evolución y Declive por Edad](#b-desarrollo-evolución-y-declive-por-edad)
   - [C. Valor de Mercado y Modelo Económico](#c-valor-de-mercado-y-modelo-económico)
   - [D. Motor de Simulación de Partidos (Algoritmo Estocástico)](#d-motor-de-simulación-de-partidos-algoritmo-estocástico)
   - [E. Sistema de Puntuación Periodística (Bolzblatt) y Rating Elo](#e-sistema-de-puntuación-periodística-bolzblatt-y-rating-elo)
3. [Propuestas de Nuevos Features para Jugadores](#3-propuestas-de-nuevos-features-para-jugadores)
   - [A. Atributos Secundarios Físicos, Tácticos y Mentales](#a-atributos-secundarios-físicos-tácticos-y-mentales)
   - [B. Polivalencia y Posiciones Secundarias](#b-polivalencia-y-posiciones-secundarias)
   - [C. Rasgos de Personalidad y Química / Relaciones de Equipo](#c-rasgos-de-personalidad-y-química--relaciones-de-equipo)
   - [D. Historial Médico y Propensión a Lesiones](#d-historial-médico-y-propensión-a-lesiones)
   - [E. Especialización en Entrenamiento y Tutores / Mentores](#e-especialización-en-entrenamiento-y-tutores--mentores)
4. [Propuestas de Nuevos Features para Clubes](#4-propuestas-de-nuevos-features-para-clubes)
   - [A. Centro de Alto Rendimiento y Departamento Médico](#a-centro-de-alto-rendimiento-y-departamento-médico)
   - [B. Especialización y Expansión de la Cantera](#b-especialización-y-expansión-de-la-cantera)
   - [C. Cuerpo Técnico Extendido (Staff)](#c-cuerpo-técnico-extendido-staff)
   - [D. Infraestructura Avanzada del Estadio y Tecnología](#d-infraestructura-avanzada-del-estadio-y-tecnología)
   - [E. Patrocinadores Dinámicos y Contratos por Objetivos](#e-patrocinadores-dinámicos-y-contratos-por-objetivos)
   - [F. Reputación de Afición y Facciones de Hinchas](#f-reputación-de-afición-y-facciones-de-hinchas)
5. [Conclusiones y Recomendaciones para el Desarrollo](#5-conclusiones-y-recomendaciones-para-el-desarrollo)

---

## 1. Visión General

El presente documento ofrece un análisis integral del sistema de simulación y modelo de datos del juego de gestión futbolística **OpenSoccer**. Se divide en dos componentes fundamentales:

1. **Análisis Estadístico y Matemático:** Desglose riguroso de la arquitectura algorítmica utilizada para generar atributos de jugadores, simular partidos minuto a minuto mediante probabilidades estocásticas, calcular la evolución/envejecimiento, fijar valores de mercado y actualizar la clasificación Elo de los clubes.
2. **Propuesta de Nuevos Features:** Un catálogo técnico estructurado con nuevas funcionalidades diseñadas para elevar la profundidad estratégica, la inmersión del usuario y el equilibrio macroeconómico del juego.

---

## 2. Análisis Estadístico y Matemático del Motor del Juego

### A. Determinación y Generación de Atributos del Jugador

Los jugadores se generan automáticamente a través del sistema de cantera (`aa_spieler_erzeugen.php`). La generación no utiliza una distribución uniforme simple, sino una **distribución logarítmica sesgada** para garantizar que los jugadores de mayor potencial sean estadísticamente más escasos.

#### 1. Fórmula de Distribución Logarítmica para la Fuerza y Talento
La función `getRandomStrength($min, $max)` calcula el nivel de fuerza o talento mediante la siguiente ecuación:

$$\text{Valor} = \exp\left( \text{rand}()^{1.15} \cdot (\ln(\text{max}) - \ln(\text{min})) + \ln(\text{min}) \right)$$

Donde $\text{rand}() \in [0, 1]$ es una variable aleatoria flotante uniforme, y el exponente $1.15$ ajusta la curva para inclinar la frecuencia hacia los valores inferiores del rango.

#### 2. Rangos de Talento según el Nivel de Cantera (`jugendarbeit`)
El mánager invierte en la infraestructura de la cantera (Nivel 1 al 5), determinando los límites mínimos y máximos de talento ($Talent_{\min}, Talent_{\max}$) y el salario base asociado:

| Nivel de Cantera | $Talent_{\min}$ | $Talent_{\max}$ | Salario Base (€) |
| :---: | :---: | :---: | :---: |
| **Nivel 1** | 2.1 | 5.9 | 300.000 € |
| **Nivel 2** | 2.8 | 6.9 | 500.000 € |
| **Nivel 3** | 3.5 | 7.9 | 700.000 € |
| **Nivel 4** | 4.2 | 8.9 | 900.000 € |
| **Nivel 5** | 4.9 | 9.9 | 1.200.000 € |

#### 3. Determinación de Fuerza Inicial
Para un jugador recién creado:
$$\text{Anfangsstärke} = \text{getRandomStrength}(0.5, 0.9)$$
$$\text{Stärke} = \text{round}(\text{Talent} \times \text{Anfangsstärke}, 1)$$

#### 4. Distribución de Posiciones Tácticas
La posición del jugador recién generado se asigna con las siguientes probabilidades probabilísticas:
* **Portero (`T`):** $12\%$
* **Delantero (`S`):** $24\%$
* **Centrocampista (`M`):** $50\%$
* **Defensa (`A`):** $14\%$

---

### B. Desarrollo, Evolución y Declive por Edad

El archivo `aa_spieler_verbesserung.php` gestiona la progresión de los jugadores en función de su experiencia acumulada en partidos disputados (`spiele_gesamt > 8`).

#### 1. Progresión de Jugadores Jóvenes y Adultos ($Edad < 31$ años / $< 11.315$ días)
Cuando un jugador acumula al menos 9 partidos disputados, experimenta una mejora calculada mediante:

$$\text{Zufall} = \frac{\text{mt\_rand}(0, 5) + 1}{10} \in [0.1, 0.6]$$
$$\text{PlusRaw} = \frac{\text{Zufall}}{\text{Stärke}} \times 5$$
$$\text{Plus} = \min\left(1.2, \max\left(0.1, \lceil \text{PlusRaw} \times 10 \rceil / 10\right)\right)$$

* **Techo de Talento:** La nueva fuerza se limita strictly por su valor de talento:
$$\text{Stärke}_{\text{nueva}} = \min(\text{Stärke} + \text{Plus}, \text{Talent})$$
* Al alcanzar $\text{Stärke} = \text{Talent}$, el jugador alcanza su "techo de rendimiento" y se notifica en el protocolo.

#### 2. Declive por Envejecimiento ($Edad \ge 31$ años / $\ge 11.315$ días)
Para jugadores veteranos, la pérdida de fuerza es proporcional a los años cumplidos por encima de los 28:

$$\text{minusP} = \frac{\lfloor \text{Edad}/365 - 28 \rfloor}{70}$$
$$\text{Pérdida} = \max\left(0.1, \text{round}(\text{Stärke} \times \text{minusP}, 1)\right)$$
$$\text{Stärke}_{\text{nueva}} = \max(0.1, \text{Stärke} - \text{Pérdida})$$

* *Excepción para Porteros (`T`):* La pérdida es un decremento fijo aleatorio entre $0.1$ y $0.2$, reflejando una longevidad deportiva superior en dicha posición.

---

### C. Valor de Mercado y Modelo Económico

El valor de mercado (`marktwert`) se calcula de forma centralizada en `zzserver.php` a través de una expresión matemática exponencial regulada por la edad y el talento:

$$\text{Marktwert} = \text{round}\left( \frac{1.75^{\text{Stärke}} \times \text{Talent} \times 30.000}{\left( \frac{\lfloor \text{Edad}/365 \rfloor}{27} \right)^{1.7}} \right) + \lfloor 100 + \text{rand}(0, 1000) \rfloor$$

#### Análisis del Comportamiento de la Ecuación:
1. **Factor Exponencial $1.75^{\text{Stärke}}$:** Provoca un crecimiento fuertemente superlineal. Un jugador de fuerza $8.0$ vale sustancialmente más que dos jugadores de fuerza $4.0$.
2. **Factor de Edad $(\text{Edad}/27)^{1.7}$:** Toma los 27 años como el punto de inflexión.
   * Si $\text{Edad} < 27$, el denominador es $< 1$, lo que incrementa exponencialmente el valor del jugador (prima por juventud).
   * Si $\text{Edad} > 27$, el denominador es $> 1$, lo que deprecia velozmente el valor de mercado del veterano.
3. **Ruido Estocástico $\lfloor 100 + \text{rand}(0, 1000) \rfloor$:** Introduce una fluctuación residual menor para evitar valores idénticos en jugadores con estadísticas similares.

---

### D. Motor de Simulación de Partidos (Algoritmo Estocástico)

La simulación del partido se ejecuta en `aa_spieltag_simulation.php` mediante un motor de **cadena de Markov estocástica** que simula un total fijado de **20 secuencias de ataque** distribuidas aleatoriamente a lo largo de los 90 minutos de juego.

```
+-------------------------------------------------------------------------+
|                  ÁRBOL DE DECISIÓN DEL MOTOR DE PARTIDO                 |
+-------------------------------------------------------------------------+
|                                                                         |
|                          [ Inicio de Ataque ]                           |
|                                   |                                     |
|                   P(Superar 1er Tercio / Medio Campo)                   |
|                      /                         \                        |
|              (Éxito)                            (Fallo)                 |
|                /                                   \                    |
|     P(Falta) / P(Abseits) / P(Avanzar)       P(Contraataque) / P(Saque) |
|         /           |           \                                       |
|    [Tiro Libre] [Fuera Juego] P(Superar 2º Tercio / Área)               |
|                                   /                   \                 |
|                             P(Falta Área)           P(Disparo)          |
|                               /       \               /      \          |
|                         [Penalti]   [Tiro Directo]  [Gol]  [Parada]     |
|                                                                         |
+-------------------------------------------------------------------------+
```

#### 1. Cálculo de Ponderaciones Tácticas y de Fuerza
Para mitigar valores extremos en las probabilidades, el motor aplica funciones de compresión lineal a los parámetros tácticos y de nivel:

* **Ponderación Táctica:**
  $$W_{\text{táctica}}(v) = 0.25 \times v + 0.5 \quad \text{donde } v \in \{1, 2, 3, 4\}$$
* **Ponderación de Fuerza:**
  $$W_{\text{fuerza}}(s) = 0.125 \times s + 0.0625 \quad \text{donde } s \in [0.1, 9.9]$$

#### 2. Fuerza Efectiva Alineada y Efecto Frescura
La fuerza de cada línea táctica ($T, A, M, S$) se pondera directamente con la **Frescura** (`frische`) de los futbolistas alineados:

$$\text{StärkeEfectiva}_{\text{pos}} = \sum_{i \in \text{pos}} \text{Stärke}_i \times \left( 0.33 + 0.67 \times \frac{\text{Frische}_i}{100} \right)$$

Posteriormente se normalizan dividiendo por la cantidad estándar de jugadores por línea (Defensa / 4, Medio / 4, Delantero / 2) y se ajustan según la orientación táctica seleccionada (`ausrichtung`: Ultradefensiva a Ultraofensiva).

#### 3. Cálculo de Posesión de Balón
La posesión del equipo local ($P_1$) frente al visitante ($P_2$) depende de la relación de fuerzas en el centro del campo y el factor cancha:

$$P_1 = \text{round}\left( \frac{100}{\frac{\text{Stärke}_M(Team 2)}{\text{Stärke}_M(Team 1)} + 1} \right) + \text{VentajaLocal}$$

Donde $\text{VentajaLocal} = +4\%$ en partidos de liga regular ($P_2 = 100 - P_1$).

#### 4. Algoritmo de Probabilidades por Fase de Ataque

1. **Superar el Centro del Campo (1er Tercio):**
   $$P(\text{Avanzar}) = 50\% \times \frac{W_{\text{fuerza}}(M_{\text{att}})}{W_{\text{fuerza}}(M_{\text{def}})} \times \frac{W_{\text{táctica}}(\text{Ausrichtung}_{\text{att}})}{W_{\text{táctica}}(\text{Ausrichtung}_{\text{def}})} \times \frac{W_{\text{táctica}}(\text{Geschw}_{\text{att}})}{W_{\text{táctica}}(\text{Pass}_{\text{att}})}$$

2. **Faltas y Sanciones Disciplinarias:**
   * Probabilidad de falta defensiva: $25\% \times W_{\text{táctica}}(\text{Aggress}_{\text{def}})$.
   * Si hay falta: $30\%$ Tarjeta Amarilla, $3\%$ Tarjeta Roja directa.
   * Consecuencia: Se debilita la fuerza de las líneas del equipo sancionado en un $2\%$ por amarilla o $10\%$ por roja.

3. **Penaltis y Tiros Libres Directos / Indirectos:**
   * En el área rival, si la defensa comete falta:
     $$P(\text{Penalti}) = 19\% \times \frac{W_{\text{fuerza}}(S_{\text{att}})}{W_{\text{fuerza}}(A_{\text{def}})}$$
   * Conversión de penalti: $P(\text{Gol}) = \frac{77\%}{W_{\text{fuerza}}(T_{\text{def}})}$.

4. **Remates a Puerta y Goles en Jugada:**
   * Probabilidad de realizar un disparo:
     $$P(\text{Disparo}) = 62\% \times \frac{W_{\text{fuerza}}(S_{\text{att}})}{W_{\text{fuerza}}(A_{\text{def}})} \times W_{\text{táctica}}(\text{Pass}_{\text{att}}) \times W_{\text{táctica}}(\text{RiskPass}_{\text{att}})$$
   * Probabilidad de Gol dado el disparo:
     $$P(\text{Gol}) = 30\% \times \frac{W_{\text{fuerza}}(S_{\text{att}})}{W_{\text{fuerza}}(T_{\text{def}})}$$

5. **Contraataques Rápidos (Pérdida de Balón):**
   * Si la defensa roba el balón en campo propio:
     $$P(\text{Contra}) = 15\% \times \frac{W_{\text{fuerza}}(A_{\text{def}})}{W_{\text{fuerza}}(M_{\text{att}})} \times W_{\text{táctica}}(\text{Geschw}_{\text{att}}) \times W_{\text{táctica}}(\text{RiskPass}_{\text{att}}) \times W_{\text{táctica}}(\text{Druck}_{\text{def}})$$
   * Si se activa el contraataque, la defensa del equipo atacante original se debilita temporalmente un $20\%$ y se lanza una simulación recursiva inmediata.

---

### E. Sistema de Puntuación Periodística (Bolzblatt) y Rating Elo

#### 1. Sistema de Calificación Periodística (*Bolzblatt*)
Tras finalizar la simulación, la prensa deportiva ficticia (*Bolzblatt*) asigna una nota académica (1.0 = Excelente, 6.0 = Deficiente) a cada línea del equipo basada en la fuerza ponderada demostrada:

```
Función staerkeBenoten(wert):
  Para i de 6 a 1 (paso -1):
    Grenze = 1.485626 - 0.2759375 * (i - 1)
    Si valor <= Grenze entonces Retornar i
  Retornar '?'
```

#### 2. Actualización de Clasificación Elo (`eloChange`)
El rendimiento global de los clubes se mide mediante un sistema **Elo adaptado al fútbol**, calculated en `zzserver.php`:

$$\Delta \text{Elo} = 40 \times K_{\text{tor}} \times K_{\text{torneo}} \times (S - E)$$

Donde:
* **Factor Torneo ($K_{\text{torneo}}$):** Liga ($1.0$), Copa Nacional ($2.0$), Copa Internacional ($1.3$), Amistosos ($0.0$).
* **Factor Tordiferencia ($K_{\text{tor}}$):**
  * Diferencia de 0 o 1 gol: $K_{\text{tor}} = 1.0$
  * Diferencia de 2 goles: $K_{\text{tor}} = 1.5$
  * Diferencia $\ge 3$ goles: $K_{\text{tor}} = \frac{11 + \text{DifGoles}}{8}$
* **Resultado Real ($S$):** Victoria ($1.0$), Empate ($0.5$), Derrota ($0.0$).
* **Resultado Esperado ($E$):**
  $$E = \frac{1}{10^{\frac{\Delta \text{Puntos}}{400}} + 1}$$
  Donde $\Delta \text{Puntos} = -|Elo_{\text{local}} + 100 - Elo_{\text{visita}}|$ incorporando $+100$ puntos por ventaja de campo.

---

## 3. Propuestas de Nuevos Features para Jugadores

Actualmente, los jugadores cuentan con atributos principales unificados (`staerke`, `talent`, `frische`, `moral`). Para elevar la riqueza estratégica y variedad del simulador, se proponen los siguientes nuevos módulos:

```
+-----------------------------------------------------------------------+
|                   NUEVA ESTRUCTURA DEL JUGADOR                        |
+-----------------------------------------------------------------------+
|                                                                       |
|  [ ATRIBUTOS BÁSICOS ]  ---> Fuerza, Talento, Edad, Posición Principal|
|  [ ATRIBUTOS SECUND. ]  ---> Velocidad, Pase, Remate, Resistencia,    |
|                              Liderazgo, Inteligencia Táctica          |
|  [ POLIVALENCIA      ]  ---> Posición Secundaria, Eficiencia (%)     |
|  [ PERFIL PERSONAL   ]  ---> Rasgos de Personalidad, Rol Táctico      |
|  [ SALUD & FÍSICO    ]  ---> Propensión a Lesiones, Historial Médico |
|  [ QUÍMICA DE EQUIPO ]  ---> Afinidad con Entrenador y Compañeros   |
|                                                                       |
+-----------------------------------------------------------------------+
```

---

### A. Atributos Secundarios Físicos, Tácticos y Mentales

Desglosar la fuerza unificada (`staerke`) en sub-atributos especializados (rango 1 - 100) que influyan de forma específica durante las fases del partido:

1. **Físicos:**
   * **Velocidad / Aceleración (`speed`):** Incrementa el éxito en mano a mano, coberturas defensivas y efectividad de contraataques.
   * **Resistencia / Estamina (`stamina`):** Reduce la tasa de pérdida de frescura durante el transcurso del partido (actualmente fija por nivel de presión).
2. **Técnicos:**
   * **Precisión de Pase (`passing`):** Aumenta el éxito en transiciones del 1er al 2º tercio y reduce pérdidas no forzadas.
   * **Potencia y Remate (`shooting`):** Incrementa la probabilidad de gol tras realizar un disparo a puerta.
   * **Entrada y Cobertura (`tackling`):** Aumenta robos limpios de balón y reduce la tasa de faltas/tarjetas recibidas.
3. **Mentales:**
   * **Liderazgo (`leadership`):** El capitán con alto liderazgo otorga un bono de $+5\%$ de moral y estabilidad al equipo cuando va perdiendo.
   * **Visión y Táctica (`tactical_iq`):** Permite adaptarse mejor a cambios de formación a mitad de partido sin perder cohesión.

---

### B. Polivalencia y Posiciones Secundarias

* **Estructura de Posición Secundaria (`pos_secondary`):**
  * Permitir que un futbolista tenga una posición secundaria nativa o entrenada (ejemplo: Posición Principal = Delantero `S`, Posición Secundaria = Medio `M`).
* **Factor de Penalización por Adaptación:**
  * Si un jugador actúa fuera de sus posiciones aprendidas, su fuerza sufre una penalización del $25\%$.
  * Si juega en su posición secundaria, la penalización es de solo el $5\% - 10\%$, reajustable mediante minutos jugados.

---

### C. Rasgos de Personalidad y Química / Relaciones de Equipo

1. **Rasgos Únicos (Traits / Spes):**
   * *Especialista a Parado:* $+15\%$ en efectividad de faltas directas y penaltis.
   * *Canterano Leal:* Mantiene la moral alta incluso con salarios moderados y rinde $+5\%$ en derbis.
   * *Joven Promesa Inestable:* Gran progresión en entrenamientos pero mayor oscilación en partidos decisivos.
2. **Sistema de Química / Relaciones (`team_chemistry`):**
   * Crear un indicador de **Química de Vestuario** (0 - 100) afectado por la barrera del idioma (nacionalidad), tiempo jugando juntos y victorias conseguidas.
   * Una alta química otorga bonificaciones en pases rápidos y reducciones en fallos defensivos.

---

### D. Historial Médico y Propensión a Lesiones

Actualmente, las lesiones se determinan aleatoriamente basándose únicamente en la frescura promedio (`risikoFuerzeVerletzung`). Se propone:

* **Índice de Fragilidad Física (`injury_prone`):** Atributo oculto o visible (1 a 5 estrellas) generado al nacer el personaje.
* **Historial de Lesiones Graves:** Si un jugador sufre un corte de ligamentos o fractura, su índice de fragilidad aumenta permanentemente un $10\%$, requiriendo mayor cuidado de fisioterapia.
* **Módulo de Mantenimiento Físico:** Selección de carga de trabajo individual (Carga Ligera, Normal, Intensiva) para mitigar recaídas.

---

### E. Especialización en Entrenamiento y Tutores / Mentores

1. **Planes de Entrenamiento Individualizados:**
   * En lugar del progreso genérico de `aa_spieler_verbesserung.php`, permitir al mánager enfocar el desarrollo semanal en atributos específicos (ej. Entrenar *Remate* o *Pase*).
2. **Sistema de Tutoría / Mentoring (`tutoring`):**
   * Un veterano con alto liderazgo y talento ($Edad \ge 30$) puede tutelar a un canterano ($Edad \le 20$).
   * **Efecto:** El canterano hereda rasgos de personalidad del veterano y acelera su ganancia de fuerza en un $+15\%$.

---

## 4. Propuestas de Nuevos Features para Clubes

En la actualidad, la gestión del club abarca el estadio, patrocinador principal, finanzas y cuerpo técnico básico. Se proponen las siguientes expansiones para dinamizar la gestión institucional:

```
+-----------------------------------------------------------------------+
|                     NUEVAS FUNCIONALIDADES DE CLUB                    |
+-----------------------------------------------------------------------+
|                                                                       |
|  [ SALUD Y RENDIMIENTO ] ---> Centro Médico y Laboratorio Físico      |
|  [ CANTERA AVANZADA    ] ---> Academias Internacionales y Scouters    |
|  [ STAFF AMPLIADO      ] ---> Director Deportivo, Analista, Nutric.   |
|  [ ESTADIO Y TECNOLOGÍA] ---> Pantallas Gigantes, VAR, Zona Fan       |
|  [ ECONOMÍA DINÁMICA   ] ---> Patrocinios Secundarios por Objetivos   |
|  [ AFICIÓN Y SOCIAL    ] ---> Facciones de Fans y Grado de Presión    |
|                                                                       |
+-----------------------------------------------------------------------+
```

---

### A. Centro de Alto Rendimiento y Departamento Médico

Ampliar las opciones de salud del club con la construcción de infraestructuras dedicadas en la pestaña del estadio/instalaciones:

1. **Clínica del Club (Niveles 1 al 5):**
   * Reduce el tiempo de baja por lesión en un $10\%$ a $50\%$.
2. **Gimnasio y Laboratorio de Biomecánica:**
   * Acelera la recuperación de frescura post-partido en un $+0.5$ a $+2.0$ puntos diarios de forma pasiva.

---

### B. Especialización y Expansión de la Cantera

1. **Academias Internacionales de Cantera (`international_academies`):**
   * Permitir al club fundar filiales de formación en países extranjeros (ej. Sudamérica, África, Asia).
   * Incrementa la variedad cultural de la base de datos de nombres (`man_vNamePool`) y permite captar jóvenes talentos con biotipos físicos diversos.
2. **Opciones Tácticas de Búsqueda:**
   * Configurar al ojeador de la cantera para buscar específicamente ciertas posiciones débiles del primer equipo (`posToSearch`).

---

### C. Cuerpo Técnico Extendido (Staff)

Expandir la plantilla de empleados contratables en `ver_personal.php`:

* **Director Deportivo:** Negocia automáticamente renovaciones secundarias y avisa cuando una cláusula de rescisión está por vencer.
* **Analista de Rivales:** Desbloquea un informe detallado sobre la formación, orientación e historial del próximo oponente de la jornada.
* **Preparador Físico de Elite:** Minimiza el impacto negativo del esfuerzo alto ($100\%$ en táctica) sobre la frescura.
* **Nutricionista Deportiva:** Reduce la frecuencia de lesiones musculares en un $15\%$.

---

### D. Infraestructura Avanzada del Estadio y Tecnología

1. **Instalación de Tecnología (VAR y Pantallas Led):**
   * **VAR (Video Arbitraje):** Reduce en un $80\%$ los penaltis o goles en fuera de juego concedidos por errores arbitrales en casa.
   * **Pantallas Gigantes / U-Television:** Genera ingresos publicitarios extra por jornada.
2. **Zonas Fan y Tiendas Oficiales Ampliadas:**
   * Construcción de *Fan Zone Exterior* que incrementa el aforo real y la satisfacción de la hinchada ante incrementos de precio en los tickets.

---

### E. Patrocinadores Dinámicos y Contratos por Objetivos

Reemplazar el patrocinador estático único con una **Mesa de Negociación de Sponsorships**:

* **Sponsor Principal de Camiseta:** Ofrece pagos fijos por jornada más primas por objetivos (ej. "Clasificar a Copa Internacional" o "Quedar en el Top 3 de Liga").
* **Sponsors Secundarios (Manga, Pantalón, Estadio):** Varios contratos menores concurrentes con diferentes cláusulas de penalización por descenso o incumplimiento.

---

### F. Reputación de Afición y Facciones de Hinchas

* **Nivel de Exigencia de la Afición (Paciencia de la Hinchada):**
  * Equipos grandes tienen aficiones con baja paciencia; perder 3 partidos seguidos reduce drásticamente los ingresos por taquilla y la moral de la directiva.
* **Ambiente de Estadio (Muro de Animación):**
  * La grada de animación otorga un bono directo de $+2\%$ en la posesión de balón durante partidos disputados como local en derbis o copas eliminatorias.

---

## 5. Conclusiones y Recomendaciones para el Desarrollo

1. **Rigor del Motor Actual:** El motor de OpenSoccer posee un fundamento matemático sólido y bien equilibrado basado en distribuciones logarítmicas y cadenas estocásticas de probabilidades condicionales.
2. **Oportunidad de Evolución:** La inclusión de atributos secundarios y rasgos de personalidad permitirá romper la rigidez de la variable unificada `staerke`, brindando un margen táctico mucho mayor sin desestabilizar el simulador.
3. **Sostenibilidad Económica:** La implementación de patrocinadores dinámicos y costes de mantenimiento para el centro médico servirá como un excelente drenaje de dinero (*money sink*), evitando la inflación desmedida en servidores con varias temporadas de recorrido.
