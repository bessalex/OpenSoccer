# Propuesta de Nuevas Funcionalidades (Features) para Jugadores y Clubes en OpenSoccer

Este documento presenta una propuesta integral de diseño conceptual y técnico de nuevas características (*features*) para expandir la profundidad estratégica, la inmersión y la sostenibilidad económica en **OpenSoccer**.

---

## 1. Nuevas Funcionalidades para Jugadores (Player Features)

Actualmente, los jugadores en OpenSoccer poseen 4 posiciones básicas (`T`, `A`, `M`, `S`), un valor general de fuerza (`staerke`), talento (`talent`), frescura (`frische`) y moral (`moral`). A continuación se proponen nuevos subsistemas para diversificar a los personajes:

### A. Desglose de Atributos Específicos por Posición

En lugar de un único valor escalar de `staerke`, se propone desglosar la capacidad del jugador en **6 atributos secundarios (0-100)**:

1. **Atributos Físicos:**
   * **Velocidad / Aceleración (`speed`):** Influye en la probabilidad de desmarque y contragolpes.
   * **Resistencia / Estamina (`stamina`):** Atenúa la velocidad de pérdida de frescura durante el partido.
2. **Atributos Técnicos:**
   * **Pase / Visión (`passing`):** Incrementa la probabilidad de éxito en el paso del 1er al 2º tercio de campo.
   * **Remate / Definición (`shooting`):** Aumenta el porcentaje de conversión de gol frente al portero.
   * **Entrada / Robos (`tackling`):** Mejora la recuperación de balón en defensa sin cometer falta.
3. **Atributos Mentales:**
   * **Liderazgo / Personalidad (`leadership`):** Aporta un bono de moral a los compañeros adyacentes cuando el jugador es nombrado Capitán.

```
Estructura Sugerida en DB (man_spieler):
ALTER TABLE man_spieler ADD COLUMN attr_speed TINYINT UNSIGNED DEFAULT 50;
ALTER TABLE man_spieler ADD COLUMN attr_stamina TINYINT UNSIGNED DEFAULT 50;
ALTER TABLE man_spieler ADD COLUMN attr_passing TINYINT UNSIGNED DEFAULT 50;
ALTER TABLE man_spieler ADD COLUMN attr_shooting TINYINT UNSIGNED DEFAULT 50;
ALTER TABLE man_spieler ADD COLUMN attr_tackling TINYINT UNSIGNED DEFAULT 50;
ALTER TABLE man_spieler ADD COLUMN attr_leadership TINYINT UNSIGNED DEFAULT 50;
```

---

### B. Sistema de Especialización de Roles y Sub-Posiciones

Permite asignar roles tácticos específicos a los jugadores para obtener bonificaciones de rendimiento según el esquema empleado:

* **Defensas:** Central Correoso, Lateral Ofensivo, Carrilero.
* **Mediocampistas:** Pivote Defensivo (5), Mediapunta Creativo (10), Interior Mixto (8).
* **Delanteros:** Ariete de Área (9), Segundo Delantero, Extremo Regateador.

#### Mecánica de Adaptación:
* Si un jugador juega fuera de su rol natural o posición secundaria, sufre una penalización temporal del **15% en su fuerza efectiva**.

---

### C. Rasgos Únicos de Personalidad y Química (`Traits`)

Cada jugador puede poseer hasta **2 rasgos pasivos** asignados al nacer en la cantera o mediante eventos especiales:

* **"Especialista en Penaltis":** +25% de efectividad desde los 11 metros.
* **"Líder del Vestuario":** Reduce las pérdidas diarias de moral de toda la plantilla en un 0.5%.
* **"Frágil / Cristal":** Mayor probabilidad de sufrir lesiones de mediana/larga duración.
* **"Jugador de Clásicos":** +1.0 de fuerza en partidos decisivos o derbis locales.
* **"Hombre de Un Solo Club" (*One-Club Man*):** Bono masivo de moral y lealtad si permanece más de 5 temporadas en el mismo equipo.

---

### D. Gestión Físico-Médica Avanzada (Fatiga Acumulada y Lesiones)

Actualmente la frescura (`frische`) se recupera linealmente. Se propone introducir la **Fatiga Acumulada (Micro-traumas)**:

* Jugar más de 3 partidos en 7 días genera carga de fatiga crónica.
* Si la fatiga crónica superase el 60%, el riesgo de lesión muscular se multiplica por $3\times$.
* **Manguera de Descanso Racional:** Obliga a rotaciones de plantilla en semanas con múltiples competiciones (Liga + Copa + Torneo Internacional).

---

## 2. Nuevas Funcionalidades para Clubes (Club Features)

Para enriquecer el ecosistema de gestión del mánager y las infraestructuras del club:

### A. Especialización del Centro de Formación (Cantera 2.0)

Ampliar la gestión del centro de formación juvenil (`jugendarbeit`):

* **Escuelas Temáticas de Cantera:**
  * *Academia de Defensas (Estilo Rígido)*: Mayor probabilidad de generar defensas centrales con alto talento.
  * *Academia de Regateadores*: Mayor probabilidad de extremos y delanteros creativos.
* **Red de Ojeadores / Scouting Internacional (`scouting`):**
  * Enviar ojeadores a regiones específicas (Sudamérica, Europa del Este, África) para descubrir talentos jóvenes ocultos a menor coste salarial.

---

### B. Departamento Médico y Centro de Alto Rendimiento

Introducción de nuevas instalaciones ampliables en la infraestructura del club (`man_stadien` / `man_teams`):

1. **Centro de Fisioterapia y Crioterapia (Nivel 1 al 5):**
   * Acelera la recuperación diaria de frescura entre partidos ($+2\%$ al $+10\%$ adicional).
   * Reduce el tiempo de baja de los lesionados en un $20\%$ por nivel del centro.
2. **Gimnasio de Alto Rendimiento:**
   * Permite realizar entrenamientos específicos enfocados en atributos individuales (ej. Fuerza, Resistencia).

---

### C. Sistema de Acuerdos Multiclub y Clubes Satélite (Farm Clubs)

Permite a los clubes de máxima categoría (Liga 1) firmar acuerdos de colaboración con clubes de divisiones inferiores (Liga 3 o 4):

* **Cesión Preferente:** Enviar jóvenes promesas a clubes satélite sin coste de ficha.
* **Derecho de Tanteo:** Opción de compra prioritaria sobre las promesas que despunten en el club afiliado.
* **Beneficio Mutuo:** El club pequeño recibe financiación directa para sus instalaciones y jugadores cedidos de mayor nivel.

---

### D. Patrocinios Dinámicos y Contratos por Objetivos

Evolución del sistema de patrocinadores (`sponsoren.php`):

* **Sponsors con Variables por Rendimiento:**
  * Bonos económicos por no encajar goles en 3 partidos seguidos.
  * Primas extraordinarias por clasificar a torneos internacionales.
* **Patrocinador Técnico (Marcas de Camiseta):**
  * Contratos multianuales con marcas de equipamiento que aportan ingresos fijos y porcentaje de venta de camisetas a través de la tienda oficial del club (*Fanshop*).

---

## 3. Matriz de Prioridad e Impacto de Implementación

| Funcionalidad | Complejidad Técnica | Impacto en la Jugabilidad | Prioridad Recomendada |
| :--- | :---: | :---: | :---: |
| **Atributos Específicos por Posición** | Media | Muy Alto | Alta (Fase 1) |
| **Centro Médico y Fisioterapia** | Baja | Alto | Alta (Fase 1) |
| **Rasgos de Personalidad (`Traits`)** | Media | Alto | Media (Fase 2) |
| **Patrocinios Dinámicos** | Baja | Medio | Media (Fase 2) |
| **Red de Ojeadores (Scouting)** | Alta | Alto | Media (Fase 3) |
| **Acuerdos Multiclub / Satélites** | Alta | Muy Alto | Baja (Fase 3) |

---

## 4. Conclusión

La incorporación gradual de estas características dotará a **OpenSoccer** de mayor profundidad táctica y estratégica, fomentando decisiones de gestión a largo plazo y mejorando la retención de usuarios mánagers.
