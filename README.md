# 🏥 MediTurnos

Sistema de gestión de agenda médica desarrollado como proyecto académico para la **Universidad Champagnat** — Licenciatura en Sistemas de Información.

---

## 📋 Descripción

MediTurnos es una aplicación web para la gestión de turnos médicos en clínicas y consultorios. Está orientada al rol del recepcionista como administrador central del sistema, con paneles diferenciados para médicos y pacientes.

---

## 🏗️ Arquitectura

El sistema implementa el patrón **MVC (Modelo-Vista-Controlador)**:

```
mediturnos/
├── configuracion/
│   └── conexion.php          # Conexión PDO a MySQL
├── controladores/
│   ├── AuthControlador.php   # Login, logout y sesiones
│   └── TurnoControlador.php  # Lógica de negocio principal
├── modelos/
│   ├── TurnoModelo.php       # Consultas SQL de turnos y médicos
│   └── UsuarioModelo.php     # Consultas SQL de usuarios
└── vistas/
    ├── autenticacion/        # Login y logout
    ├── panel/
    │   ├── recepcionista/    # Panel principal del sistema
    │   │   └── ajax/         # Endpoints JSON para selects encadenados
    │   ├── medico/           # Agenda y horarios del médico
    │   └── paciente/         # Estado de turnos del paciente
    └── plantillas/           # Header y footer reutilizables
```

---

## ⚙️ Stack tecnológico

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.2 |
| Base de datos | MySQL / MariaDB |
| Frontend | HTML, CSS, JavaScript vanilla |
| Servidor | XAMPP (Apache) |
| Patrón | MVC en español |

---

## 👥 Roles del sistema

### 🧑‍💼 Recepcionista
- Ver agenda del día con KPIs (total, pendientes, cancelados)
- Filtrar turnos por hoy, mes o rango personalizado
- Reservar turnos con selects encadenados (especialidad → médico → fecha → hora)
- Buscar pacientes y gestionar el estado de sus turnos
- Suspender agenda de un médico en una fecha específica
- Registrar médicos nuevos con usuario de acceso automático
- Gestionar médicos y resetear contraseñas

### 👨‍⚕️ Médico
- Ver su agenda personal filtrable por fecha
- Gestionar sus propios horarios de atención (agregar y eliminar)

### 🧑‍🤝‍🧑 Paciente
- Ver el estado de sus turnos sin contraseña — solo con DNI

---

## 🗄️ Base de datos

### Tablas principales
| Tabla | Descripción |
|---|---|
| `Medico` | Datos de los médicos |
| `Especialidad` | Especialidades médicas (6 activas) |
| `Medico_Especialidad` | Relación N:M entre médicos y especialidades |
| `Consultorio` | 5 consultorios físicos |
| `Horario_Atencion` | Días y horarios de atención por médico |
| `Paciente` | Datos de los pacientes |
| `Turno` | Turnos reservados |
| `Historial_Turno` | Auditoría de cambios de estado |
| `Agenda_Bloqueada` | Fechas suspendidas por médico |
| `Usuario` | Acceso al sistema con roles |
| `Rol` | Administrador, Recepcionista, Médico, Paciente |

### Stored Procedures
- `ReservarTurno` — inserta un turno validando disponibilidad
- `CancelarTurno` — cancela un turno con validaciones

### Trigger
- `LogTurno` — registra automáticamente en `Historial_Turno` cada cambio de estado en la tabla `Turno`

---

## 🔒 Características técnicas

### Transacciones ACID
El sistema implementa transacciones PDO en dos operaciones críticas:

**Suspender agenda** — cancela todos los turnos pendientes de un médico en una fecha, registra el historial de cada uno y bloquea la fecha. Todo o nada.

**Registrar médico** — inserta el médico, su especialidad, sus horarios y su usuario de acceso en una sola operación atómica.

### Seguridad
- Contraseñas hasheadas con `password_hash()` (bcrypt)
- `password_verify()` para autenticación
- `session_regenerate_id()` al hacer login — previene Session Fixation
- Prepared statements en todas las consultas — previene SQL Injection
- `htmlspecialchars()` en todas las salidas — previene XSS
- Verificación de rol en todas las vistas protegidas

### AJAX
Los selects encadenados del formulario de reserva funcionan via AJAX — cuando el recepcionista elige una especialidad, el sistema consulta los médicos disponibles sin recargar la página. Lo mismo para días disponibles, verificación de agenda bloqueada y slots de hora.

---

## 🚀 Instalación

### Requisitos
- XAMPP con PHP 8.2 y MySQL/MariaDB
- Ubuntu 24.04 (o compatible)

### Pasos
```bash
# 1. Clonar el repositorio en htdocs
git clone https://github.com/FacundoUria/MediTurnos.git /opt/lampp/htdocs/mediturnos

# 2. Importar la base de datos en phpMyAdmin
# Importar el archivo SQL de estructura y datos

# 3. Iniciar XAMPP
sudo /opt/lampp/lampp start

# 4. Acceder al sistema
http://localhost/mediturnos/vistas/autenticacion/login.php
```

---

## 🔑 Credenciales de prueba

| Rol | DNI | Contraseña |
|---|---|---|
| Recepcionista | 11111111 | REC#2026 |
| Dr. Fernández | 22222222 | MED#10142 |
| Dr. Benítez | 400060000 | MED#10413 |
| Dr. Cabrera | 400070000 | MED#10512 |
| Dr. Torres | 22691211 | MED#10806 |
| Dr. Sánchez | 66666666 | MED#20001 |
| Dr. Molina | 77777777 | MED#20002 |
| Dr. Ramírez | 88888888 | MED#20003 |
| Dr. Herrera | 300010000 | MED#30001 |
| Dr. Acosta | 300020000 | MED#30002 |
| Dr. Peralta | 300030000 | MED#30003 |
| Dr. Aguirre | 300040000 | MED#30004 |
| Dr. Ibáñez | 300050000 | MED#30005 |

**Paciente** — ingresar con cualquier DNI de la tabla Paciente (ej: 44537978) desde el tab "Soy paciente". No requiere contraseña.

---

## 👨‍💻 Autor

**Facundo Uría** — Licenciatura en Sistemas de Información  
Universidad Champagnat — Mendoza, Argentina  
2026