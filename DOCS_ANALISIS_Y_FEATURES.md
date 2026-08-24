# Análisis Estadístico, Simulación de Juego y Propuesta de Nuevos Features para OpenSoccer

Este documento contiene un análisis técnico, matemático y estadístico detallado del funcionamiento interno del motor de simulación de partidos y la determinación de características en **OpenSoccer**, así como una propuesta fundamentada de nuevos *features* para jugadores y clubes.

---

## 1. Análisis Estadístico y Algorítmico de la Simulación de Juego (`aa_spieltag_simulation.php`)

El motor de partidos de OpenSoccer es un sistema estocástico discreto basado en probabilidad condicional y árboles de decisión por eventos en tiempo de partido (de 1 a 90/120 minutos).

### 1.1 Frecuencia y Distribución de Ataques (`get_minutes`)

Un partido consta de un número prefijado de oportunidades o ataques (normalmente $N = 20$ ataques por encuentro).
1. **Intervalo base entre ataques**:
   $$\text{intervall} = \frac{90}{N} = \frac{90}{20} = 4.5 \text{ minutos}$$
2. **Desviación/Jitter aleatorio**:
   Para evitar intervalos fijos, se genera un ruido aleatorio uniforme $J \in [-\text{spielraum}, +\text{spielraum}]$, con $\text{spielraum} = \lceil \text{intervall} \rceil - 1 = 4$.
3. **Asignación de posesión del ataque**:
   La probabilidad de que el Equipo 1 realice el ataque $i$ depende de las oportunidades restantes asignadas según su porcentaje de posesión del balón ($B_1$):
   $$\mathbb{P}(\text{Ataque de Team 1 en paso } i) = \frac{\text{Ataques Restantes}_1}{N - i + 1} \times 100\%$$

### 1.2 Cálculo de Posesión de Balón

La posesión del balón se determina principalmente por la fortaleza relativa de las líneas del mediocampo ($M_1$ y $M_2$) ajustada por la ventaja de local:
$$B_1 = \text{round}\left( \frac{100}{\frac{M_2}{M_1} + 1} \right)$$
En partidos oficiales de liga (excluyendo torneos amistosos o finales de copa en sede neutral), el equipo local recibe un bono de ventaja de $+4\%$ de posesión ($B_1 = B_1 + 4$, $B_2 = 100 - B_1$).

---

### 1.3 Funciones de Ponderación Matemática

Las fuerzas de las líneas (Portería $T$, Defensa $A$, Mediocampo $M$, Delantera $S$) y las configuraciones tácticas se transforman mediante funciones de ponderación continua antes de evaluarse en las probabilidades de los eventos.

#### A. Ponderación de Tácticas (`tactics_weight`)
Las tácticas (que van en escala discreta de 1 a 4) se ponderan mediante la función lineal:
$$f_{\text{tactic}}(w) = 0.25 \cdot w + 0.5$$
* $w=1 \implies f_{\text{tactic}}(1) = 0.75$
* $w=2 \implies f_{\text{tactic}}(2) = 1.00$
* $w=3 \implies f_{\text{tactic}}(3) = 1.25$
* $w=4 \implies f_{\text{tactic}}(4) = 1.50$

#### B. Ponderación de Fuerza (`strengths_weight`)
La fuerza individual o de línea $S \in [0.1, 9.9]$ se escala linealmente mediante:
$$f_{\text{strength}}(S) = 0.125 \cdot S + 0.0625$$
* Para $S = 1.0 \implies f_{\text{strength}}(1.0) = 0.1875$
* Para $S = 5.0 \implies f_{\text{strength}}(5.0) = 0.6875$
* Para $S = 9.9 \implies f_{\text{strength}}(9.9) = 1.3000$

#### C. Evaluación de Rendimiento Periodístico (`staerkeBenoten`)
La calificación de rendimiento (notas tipo prensa alemana de 1 a 6) se determina comparando $f_{\text{strength}}(S)$ contra los umbrales:
$$\text{Umbral}(i) = 1.485626 - 0.2759375 \cdot (i - 1) \quad \text{para } i \in \{1, \dots, 6\}$$

---

### 1.4 Árbol Estocástico de Decisión de un Ataque (`starte_angriff`)

Cada ataque ejecuta un flujo con múltiples ramas condicionales evaluated mediante la función `Chance_Percent(P)`:

1. **Paso del primer tercio del campo enemigo**:
   $$\mathbb{P}(\text{Superar 1er tercio}) = 50\% \times \frac{f_s(M_{\text{att}})}{f_s(M_{\text{def}})} \times \frac{f_t(\text{ausrichtung}_{\text{att}}) \cdot f_t(\text{pass}_{\text{att}})}{f_t(\text{ausrichtung}_{\text{def}}) \cdot f_t(\text{risk\_pass}_{\text{att}})}$$

2. **Ocurrencia de Falta / Infracción**:
   Si se supera el primer tercio, hay una probabilidad de falta defensiva:
   $$\mathbb{P}(\text{Falta Defensiva}) = 25\% \times f_t(\text{aggress}_{\text{def}})$$
   * **Tarjeta Amarilla**: $30\%$ de las faltas. Reduce la fuerza de las líneas del equipo en un $2\%$ ($\text{factor} = 0.98$).
   * **Tarjeta Roja**: $3\%$ de las faltas. Reduce la fuerza del equipo en un $10\%$ ($\text{factor} = 0.90$).

3. **Tiro Libre Indirecto o Fuera de Juego**:
   * Fuera de juego: $\mathbb{P}(\text{Abseits}) = 17\% \times f_t(\text{ausrichtung}_{\text{att}}) \cdot f_t(\text{pass}_{\text{att}})$.
   * Si hay tiro libre indirecto:
     $$\mathbb{P}(\text{Remate a puerta}) = 30\% \times \frac{f_s(S_{\text{att}})}{f_s(A_{\text{def}})}$$
     $$\mathbb{P}(\text{Gol en Tiro Libre Ind.}) = 30\% \times \frac{f_s(S_{\text{att}})}{f_s(T_{\text{def}})}$$

4. **Avance al segundo tercio y Opciones de Gol**:
   Si el ataque continúa hacia el área rival:
   * **Penalti**:
     $$\mathbb{P}(\text{Penalti}) = 19\% \times \frac{f_s(S_{\text{att}})}{f_s(A_{\text{def}})}$$
     $$\mathbb{P}(\text{Gol de Penalti}) = \frac{77\%}{f_s(T_{\text{def}})}$$
   * **Disparo Normal a Puerta**:
     $$\mathbb{P}(\text{Disparo}) = 62\% \times \frac{f_s(S_{\text{att}})}{f_s(A_{\text{def}})} \times f_t(\text{pass}_{\text{att}}) \cdot f_t(\text{risk\_pass}_{\text{att}})$$
     $$\mathbb{P}(\text{Gol en Disparo Normal}) = 30\% \times \frac{f_s(S_{\text{att}})}{f_s(T_{\text{def}})}$$

5. **Contraataque Rápido (`quickCounterAttack`)**:
   Si el ataque es interceptado por la defensa, existe la posibilidad de activar un contragolpe inmediato:
   $$\mathbb{P}(\text{Contrattaque}) = 15\% \times \frac{f_s(A_{\text{def}})}{f_s(M_{\text{att}})} \times f_t(\text{geschw}_{\text{att}}) \cdot f_t(\text{risk\_pass}_{\text{att}}) \cdot f_t(\text{druck}_{\text{def}})$$
   En este caso, se reduce temporalmente la defensa del equipo atacante original al $80\%$ ($S_{\text{att\_A}} = S_{\text{att\_A}} \cdot 0.8$) y se invoca recursivamente `starte_angriff` invirtiendo los roles de atacante y defensor.

---

### 1.5 Asistencia al Estadio e Ingresos

La asistencia ($V$) al estadio depende del aforo ($P$), la notoriedad del rival, el tipo de torneo, el precio de la entrada ($p$) y si es un partido de derbi local:

$$V_{\text{raw}} = \text{fanaufkommen} + \frac{15000}{1.4^{(\text{rank}_{\text{rival}} - 1)}} + \text{Bonus}_{\text{Torneo}} + (70 - p) \times 750 + \text{Bonus}_{\text{Derby}}$$

* $\text{Bonus}_{\text{Torneo}}$: Pokal ($+30.000$), Liga ($+15.000$), Cup ($+10.000$), Test ($+5.000$).
* $\text{Bonus}_{\text{Derby}}$: $+20.000$ espectadores si los nombres de club comparten la misma ciudad.
* Asistencia Final: $V = \min(P, \max(0, V_{\text{raw}}))$.

---

### 1.6 Sistema de Calificación ELO

El cambio de puntos ELO al finalizar el encuentro sigue la fórmula:
$$R_{\text{esperado}} = \frac{1}{10^{\frac{\Delta \text{ELO}}{400}} + 1}$$
$$\Delta \text{ELO}_{\text{partido}} = 40 \times K_{\text{torneo}} \times K_{\text{goles}} \times (R_{\text{real}} - R_{\text{esperado}})$$

* $K_{\text{torneo}}$: Liga ($1.0$), Pokal ($2.0$), Cup ($1.3$), Test ($0.0$).
* $K_{\text{goles}}$: 0 o 1 gol de dif. ($1.0$), 2 goles de dif. ($1.5$), $\ge 3$ goles de dif. ($\frac{11 + \text{dif}}{8}$).

---

## 2. Determinación y Dinámica de Características de Jugadores y Clubes

### 2.1 Jugadores

#### A. Generación de Canteranos y Talento (`aa_spieler_erzeugen.php`)
Los jugadores juveniles se generan mediante una distribución log-normal multiplicativa invertida:
$$R = \exp\left( (\ln(\text{max}) - \ln(\text{min})) \cdot (\text{unif}(0, 1))^{1.15} + \ln(\text{min}) \right)$$
El nivel de las instalaciones de cantera (`jugendarbeit` del 1 al 5) define los rangos de talento $\text{talent}_{\min}$ y $\text{talent}_{\max}$:
* **Nivel 1**: Talento entre $[2.1, 5.9]$, sueldo base \$300.000.
* **Nivel 5**: Talento entre $[4.9, 9.9]$, sueldo base \$1.200.000.
La fuerza inicial es un porcentaje del talento: $\text{fuerza} = \text{round}(\text{talento} \times \text{fuerza\_inicial}, 1)$, donde $\text{fuerza\_inicial} \in [0.5, 0.9]$.

#### B. Progresión y Envejecimiento (`aa_spieler_verbesserung.php`)
* **Progresión por experiencia**: Jugadores con más de 8 partidos acumulan puntos de mejora. Si no han alcanzado su techo de talento, aumentan su fuerza $\Delta S \in [0.1, 1.2]$.
* **Declive por edad**: A partir de los 31 años ($\text{wiealt} \ge 11.315 \text{ días}$):
  $$\Delta S_{\text{declive}} = \text{round}\left(S \cdot \frac{\lfloor \frac{\text{edad\_días}}{365} - 28 \rfloor}{70}, 1\right)$$

#### C. Valor de Mercado (`marktwertAusdruck`)
El valor financiero de un jugador se calcula mediante la fórmula exponencial/potencial:
$$\text{Valor} = \text{round}\left( 1.75^{\text{fuerza}} \times \text{talento} \times 30.000 \times \left( \frac{\lfloor \text{edad}/365 \rfloor}{27} \right)^{-1.7} \right) + \text{Ruido}(100, 1100)$$

#### D. Estimación Determinista del Ojeador (`schaetzungVomScout`)
Para evitar que los usuarios conozcan de forma exacta e instantánea el talento real de un jugador rival, el nivel del ojeador genera una estimación con un margen de error determinista basado en un hash MD5 reproducible (`md5(team + scout_level + player_id)`).

---

### 2.2 Clubes

Los atributos de los clubes comprenden:
1. **Finanzas y Presupuesto**: Cuenta bancaria (`konto`), balance bancario, patrocinios base (`sponsor_a`) y bono por victoria (`sponsor_s`).
2. **Infraestructura del Estadio y Comodidades**: Capacidad en asientos, precio de entrada, plazas de parking, estación de metro, restaurantes, carpas de cerveza, pizzerías, puesto de snacks, museo del club y tienda de fans (`fanshop`).
3. **Personal Especializado**: Nivel de cantera (`jugendarbeit`), coordinador de fans (`fanbetreuer`), ojeador (`scout`).

---

## 3. Propuesta de Nuevos Features (Jugadores y Clubes)

### 3.1 Nuevos Features para JUGADORES

#### Feature 1: Sub-atributos Específicos por Posición (Descomposición de `staerke`)
* **Problema actual**: La fuerza es un único valor escalar ($0.1 - 9.9$). Un delantero de fuerza 7.0 es idéntico a otro en todas sus facetas.
* **Propuesta**: Descomponer la fuerza general en 3 sub-atributos específicos visibles o entrenables:
  * **Físico**: Velocidad (`Geschwindigkeit`), Ausdauer (`Resistencia`), Fuerza física (`Physis`).
  * **Técnico**: Passspiel (`Pase`), Torschuss (`Remate/Tiro`), Zweikampf (`Entrada/Robo`), Dribbling (`Dribling`).
  * **Mental**: Spielübersicht (`Visión táctica`), Disziplin (`Disciplina`), Führungsqualität (`Liderazgo`).
* **Integración con la simulación**:
  * En `starte_angriff`, las probabilidades de pase largo usaran la media de `Pase` y `Visión táctica` del mediocampo; las faltas dependerán de la `Disciplina`.

#### Feature 2: Especializaciones y Rasgos Unicos (*Player Traits*)
* **Atributos cualitativos tipo *Perks***:
  * *Especialista en Faltas Directas*: Aumenta un $+15\%$ la probabilidad de conversión en tiros libres.
  * *Maestro del Penalti*: Reduce el impacto del portero rival en lanzamientos desde los 11 metros.
  * *Líder / Capitán*: Si está en la alineación titular, incrementa la moral del equipo en $+5\%$ y reduce la pérdida de rendimiento tras encajar un gol.
  * *Jugador Clave de Clásicos / Derbis*: Incrementa su fuerza efectiva en $+0.5$ durante encuentros de derbi local.

#### Feature 3: Polivalencia de Posición y Versatilidad
* Permitir **posiciones secundarias** (ej. Delantero que puede jugar de Mediocampista con una penalización menor del $-10\%$ en lugar de usar un jugador amateur o tener un desbalance severo).

#### Feature 4: Historial Médico y Propenso a Lesiones (*Injury Prone Rate*)
* Crear un atributo implícito de resistencia a lesiones basado en el historial del jugador. Los jugadores con múltiples lesiones graves aumentan ligeramente el factor de riesgo en partidos de alta intensidad o presión táctica.

---

### 3.2 Nuevos Features para CLUBES

#### Feature 1: Expansión y Especialización del Cuerpo Técnico (*Staff*)
* **Centro Médico y Fisioterapeutas**:
  * Niveles 1 a 5. Reduce los días de baja por lesión en un $10\%-50\%$ y ralentiza la pérdida de frische (frescura) tras cada partido.
* **Preparador Físico**:
  * Disminuye la tasa de agotamiento (`erschoepungswert`) cuando se utilizan tácticas de alta presión (`druck = 4`).
* **Analista Táctico / Entrenador Asistente**:
  * Genera un informe previo del rival destacando la táctica más probable y permitiendo automatizar la alineación óptima.

#### Feature 2: Academia de Cantera Avanzada (Ligas Juveniles U19)
* Reemplazar la generación directa de juveniles al primer equipo por una **Liga Juvenil Sub-19**.
* El club puede elegir qué áreas entrenar en la cantera (ej. enfoque en formar defensas o delanteros) y promover jugadores al primer equipo cuando alcancen la edad reglamentaria.

#### Feature 3: Acuerdos Comerciales Dinámicos y Sponsors de Camiseta
* **Patrocinadores condicionales**: Negociación de contratos de patrocinio con objetivos específicos (ej. terminar entre los 3 primeros, no descender, avanzar a semifinales de Pokal).
* **Tienda del Club y Merchandising global**: Los ingresos por fanshop escalarán dinámicamente según la popularidad e hitos de los jugadores estrellas del equipo.

#### Feature 4: Medidor de Lealtad de la Hinchada y Dinámica de Rivalidades
* **Barra de Satisfacción de la Afición**: Influenciada por los resultados en derbis, los precios de las entradas y el estilo de juego (ofensivo vs defensivo). Una afición satisfecha incrementa la asistencia base en partidos de menor atractivo.

---

## 4. Esquema DB y Plan de Implementación Recomendado

Para implementar estas mejoras sin romper la compatibilidad con el código heredado PHP/MySQL:

1. **Tabla `man_spieler_attributes`**:
   ```sql
   CREATE TABLE `man_spieler_attributes` (
     `spieler_id` varchar(32) NOT NULL,
     `velocidad` decimal(2,1) unsigned NOT NULL DEFAULT '5.0',
     `remate` decimal(2,1) unsigned NOT NULL DEFAULT '5.0',
     `pase` decimal(2,1) unsigned NOT NULL DEFAULT '5.0',
     `entrada` decimal(2,1) unsigned NOT NULL DEFAULT '5.0',
     `vision` decimal(2,1) unsigned NOT NULL DEFAULT '5.0',
     `liderazgo` decimal(2,1) unsigned NOT NULL DEFAULT '5.0',
     `trait_especial` enum('Ninguno','TirosLibres','Penaltis','Capitan','Derby') NOT NULL DEFAULT 'Ninguno',
     PRIMARY KEY (`spieler_id`)
   ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
   ```

2. **Tabla `man_personal_expanded`**:
   ```sql
   CREATE TABLE `man_personal_expanded` (
     `team_id` varchar(32) NOT NULL,
     `fisioterapeuta` tinyint(1) unsigned NOT NULL DEFAULT '1',
     `preparador_fisico` tinyint(1) unsigned NOT NULL DEFAULT '1',
     `analista_tactico` tinyint(1) unsigned NOT NULL DEFAULT '1',
     PRIMARY KEY (`team_id`)
   ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
   ```

---
*Documento preparado para el equipo de desarrollo de OpenSoccer.*
