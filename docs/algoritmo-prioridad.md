# Algoritmo de prioridad de recuperación

Es el componente innovador del proyecto. Cada donación recibe un puntaje de 0 a 100 que suma tres factores. El código está en `apps/api/src/Domain/Priority.php` y las pruebas en `apps/api/tests/unit/PriorityTest.php`.

| Factor | Máximo | Cálculo |
| --- | --- | --- |
| Urgencia | 50 | Con 6 horas o menos para vencer vale 50. Después baja en línea recta hasta 0 a las 72 horas. Fórmula 50 × (72 − horas) / 66 |
| Cercanía | 30 | 30 × (1 − km / 80). A 80 km o más vale 0. Si no hay zona de referencia vale 15 |
| Volumen | 20 | 20 × kilos / 40. Con 40 kg o más vale 20 |

Cada factor se redondea a un decimal y el total a entero. Si dos donaciones empatan va primero la que vence antes.

## De dónde salen los datos

- Las horas que faltan se calculan con `expiresAt` y la hora del servidor.
- La distancia es la línea recta entre el centro de la zona del receptor y el de la donación, con la fórmula de Haversine. Las coordenadas están en `Catalog.php`.
- Los kilos usan equivalencias fijas. Un kilo o un litro valen 1 kg, una pieza 0.25 kg y una porción 0.4 kg.
- Si no hay sesión, el visitante puede elegir una zona en "Calcular cercanía desde".

## Ejemplo resuelto a mano

5 kg de pan en Guanajuato capital que vencen en 24 horas, vistos por un receptor de la misma zona.

| Factor | Operación | Puntos |
| --- | --- | --- |
| Urgencia | 50 × (72 − 24) / 66 = 36.36 | 36.4 |
| Cercanía | 30 × (1 − 0 / 80) | 30.0 |
| Volumen | 20 × 5 / 40 | 2.5 |
| Total | 36.4 + 30 + 2.5 = 68.9 | 69 |

Si el receptor está en Silao, a 19.5 km, la cercanía baja a 22.7 y el total queda en 62. La prueba "Ejemplo a mano" comprueba los tres factores y el 69.

## Por qué así

- La urgencia pesa más porque la comida que vence pronto se pierde si nadie va.
- La cercanía importa porque una organización no puede cruzar el estado por 2 kg.
- El volumen pesa menos para no dejar fuera las donaciones pequeñas.

## Limitaciones

- La distancia es entre centros de zona, no entre direcciones reales.
- Los pesos 50, 30 y 20 son una decisión del equipo y se pueden ajustar en las constantes de `Priority.php`.
- No toma en cuenta el tráfico ni el horario de cada organización.
