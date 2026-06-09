# 🏥 Proyecto MediTurnos - Sistema de Gestión de Turnos

¡Buenas! Este es mi proyecto final de Base de Datos. Es un sistema para manejar los turnos de una clínica, con médicos, pacientes, obras sociales y consultorios. Lo armé usando **PHP** con el patrón **MVC** (Modelo-Vista-Controlador) y **phpMyAdmin (MySQL)** para la base de datos.

---

## 📂 ¿Cómo está armada la Base de Datos?

La base de datos tiene **13 tablas** en total. Al principio arranqué con las 6 o 7 básicas (Paciente, Medico, Turno, etc.), pero después le agregué las tablas intermedias y de historial que pedía el alcance del proyecto para que quede completo y real.

Para probar que todo funcione rápido y no se tilde con pocos datos, **poblé las tablas con más de 1.000 registros de prueba** (con nombres y datos argentinos para que quede más prolijo).

### Cosas clave que metí en la BD:
* **Seguridad:** Creé una tabla de `Rol` (Admin, Recepcionista, Médico, Paciente) y una tabla de `Usuario` con las contraseñas encriptadas en hash.
* **Historial:** Una tabla llamada `Historial_Turno` que registra cada vez que un turno cambia de estado.
* **Relaciones N:M:** Tablas intermedias como `Medico_Especialidad` (porque un médico puede tener más de una especialidad) y `Paciente_Plan` (para las obras sociales).

---

## ⚡ Automatización y Rendimiento (Lo que hice en MySQL)

Para la entrega de rendimiento y programación, metí mano directo adentro de phpMyAdmin:

### 1. Stored Procedure (`CancelarTurno`)
En vez de armar el código de cancelación adentro de PHP, creé este procedimiento en la base de datos. Vos le pasás el ID del turno y el procedimiento se encarga de cambiar el estado a "Cancelado". Si el turno no existe o ya estaba cancelado, te tira un error automático para que no rompas nada.

### 2. Trigger (`LogTurno`)
Este es un "vigilante" automático. Lo programé para que, cada vez que se actualice un estado en la tabla `Turno`, salte solo y guarde el cambio en `Historial_Turno`. No hace falta llamarlo desde PHP; la base de datos lo hace sola y así nos aseguramos de que nunca se pierda el historial.

### 3. Índices (Optimización)
Como cargué más de 1.000 datos, usé el comando `EXPLAIN` para ver cómo buscaba la base de datos. Vi que en los `JOIN` tardaba bastante porque hacía un *Full Table Scan* (revisaba todas las filas una por una). 
Para solucionarlo, creé índices en las claves foráneas y en los DNI:
```sql
