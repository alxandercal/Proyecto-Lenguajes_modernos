# Historias de usuario

| # | Como | Quiero | Para | Criterio de aceptación | Revisión |
| --- | --- | --- | --- | --- | --- |
| 1 | Organización | registrarme como donador o receptor con mi zona | usar la plataforma con el rol correcto | Se crea `users/{uid}` con el rol elegido. No se puede elegir ADMIN | R1 |
| 2 | Donador | publicar comida con tipo, cantidad, unidad, vencimiento y punto de recogida | que alguien la aproveche | La donación queda Publicada y aparece en el listado. Cantidad 0 o fecha pasada dan error | R1 |
| 3 | Cualquier persona | ver las donaciones disponibles y filtrarlas por categoría, zona y vigencia | encontrar lo que me sirve | Los filtros cambian el listado sin recargar la página | R1 |
| 4 | Receptor | reservar una donación | asegurar que nadie más llegue por ella | La donación pasa a Reservada. Una segunda reserva recibe 409 | R2 |
| 5 | Receptor | liberar mi reserva si ya no puedo ir | que otra organización la aproveche | La donación vuelve a Publicada y la reserva se borra | R2 |
| 6 | Donador | editar o cancelar mis donaciones | corregir datos o avisar que ya no está | Solo se edita si sigue Publicada. Cancelar una reservada cancela la reserva | R2 |
| 7 | Receptor | ver las donaciones ordenadas por prioridad y saber por qué | ir primero por lo más urgente | Cada donación muestra su puntaje y los puntos de urgencia, cercanía y volumen | R2 |
| 8 | Donador | confirmar la entrega con evidencia | dejar constancia de quién recibió y cuánto | La donación pasa a Entregada y se guarda la evidencia | R3 |
| 9 | Administrador | ver indicadores de entregas y kilos recuperados | medir el impacto de la plataforma | El total sube en los kilos exactos de cada entrega | R3 |
