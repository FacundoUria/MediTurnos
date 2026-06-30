<?php
/*
 * modelos/TurnoModelo.php
 * ---------------------------------------------------------------
 * Su único trabajo es consultar la tabla Turno en la DB.
 * No sabe nada de HTML ni de sesiones.
 * El Controlador lo llama y él devuelve los datos limpios.
 * ---------------------------------------------------------------
 */

class TurnoModelo {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Trae todos los turnos del día de hoy con 
    public function obtenerTurnosHoy() {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM vista_agenda_hoy ORDER BY hora ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Cuenta los turnos de hoy por estado para los KPIs
    public function contarTurnosHoy() {
        $sql = "SELECT
                    COUNT(*)                  AS total,
                    SUM(estado = 'Pendiente') AS pendientes,
                    SUM(estado = 'Cancelado') AS cancelados
                FROM Turno
                WHERE fecha = CURDATE()";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Trae turnos por rango de fechas
    public function obtenerTurnosPorFecha($fecha_desde, $fecha_hasta) {
        $sql = "SELECT
                    t.id_turno,
                    t.id_paciente,
                    t.fecha,
                    t.hora,
                    t.estado,
                    CONCAT(p.apellido, ', ', p.nombre) AS paciente,
                    CONCAT('Dr/a. ', m.apellido)       AS medico,
                    e.nombre                           AS especialidad,
                    c.numero                           AS consultorio
                FROM Turno t
                JOIN Paciente     p ON t.id_paciente    = p.id_paciente
                JOIN Medico       m ON t.matricula       = m.matricula
                JOIN Especialidad e ON t.id_especialidad = e.id_especialidad
                JOIN Consultorio  c ON t.id_consultorio  = c.id_consultorio
                WHERE t.fecha BETWEEN :desde AND :hasta
                ORDER BY t.fecha ASC, t.hora ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':desde' => $fecha_desde, ':hasta' => $fecha_hasta]);
        return $stmt->fetchAll();
    }

    // Trae todos los pacientes para el select
    public function obtenerPacientes() {
        $stmt = $this->pdo->prepare(
            "SELECT id_paciente, nombre, apellido, dni
             FROM Paciente
             ORDER BY apellido ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Trae todas las especialidades
    public function obtenerEspecialidades() {
        $stmt = $this->pdo->prepare(
            "SELECT id_especialidad, nombre FROM Especialidad ORDER BY nombre ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Trae todos los médicos
    public function obtenerMedicos() {
        $stmt = $this->pdo->prepare(
            "SELECT matricula, nombre, apellido FROM Medico ORDER BY apellido ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Trae todos los consultorios
    public function obtenerConsultorios() {
        $stmt = $this->pdo->prepare(
            "SELECT id_consultorio, numero, piso FROM Consultorio ORDER BY numero ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Inserta un paciente nuevo y devuelve su id
    public function insertarPaciente($nombre, $apellido, $dni, $telefono, $email) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO Paciente (nombre, apellido, dni, telefono, email)
             VALUES (:nombre, :apellido, :dni, :telefono, :email)"
        );
        $stmt->execute([
            ':nombre'   => $nombre,
            ':apellido' => $apellido,
            ':dni'      => $dni,
            ':telefono' => $telefono,
            ':email'    => $email,
        ]);
        return $this->pdo->lastInsertId();
    }

    // Trae todos los turnos de un paciente específico
    public function obtenerTurnosPorPaciente($id_paciente) {
        $sql = "SELECT
                    t.id_turno,
                    t.fecha,
                    t.hora,
                    t.estado,
                    CONCAT('Dr/a. ', m.apellido) AS medico,
                    e.nombre                     AS especialidad,
                    c.numero                     AS consultorio
                FROM Turno t
                JOIN Medico       m ON t.matricula        = m.matricula
                JOIN Especialidad e ON t.id_especialidad  = e.id_especialidad
                JOIN Consultorio  c ON t.id_consultorio   = c.id_consultorio
                WHERE t.id_paciente = :id_paciente
                ORDER BY t.fecha DESC, t.hora DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_paciente' => $id_paciente]);
        return $stmt->fetchAll();
    }

    // Trae los datos de un paciente por su id
    public function obtenerPacientePorId($id_paciente) {
        $stmt = $this->pdo->prepare(
            "SELECT id_paciente, nombre, apellido, dni, telefono, email
             FROM Paciente
             WHERE id_paciente = :id"
        );
        $stmt->execute([':id' => $id_paciente]);
        return $stmt->fetch();
    }

    



// Cambia el estado de un turno
// El trigger LogTurno registra el cambio automáticamente
public function cambiarEstadoTurno($id_turno, $estado_nuevo) {
    $stmt = $this->pdo->prepare(
        "UPDATE Turno SET estado = :estado WHERE id_turno = :id"
    );
    $stmt->execute([
        ':estado' => $estado_nuevo,
        ':id'     => $id_turno,
    ]);
}   



// Verifica si hay turnos pendientes para un médico en una fecha
public function contarTurnosPendientes($matricula, $fecha) {
    $stmt = $this->pdo->prepare(
        "SELECT COUNT(*) AS total FROM Turno
         WHERE matricula = :matricula
         AND   fecha     = :fecha
         AND   estado    = 'Pendiente'"
    );
    $stmt->execute([':matricula' => $matricula, ':fecha' => $fecha]);
    return $stmt->fetch()['total'];
}

// Trae los turnos pendientes de un médico en una fecha, con datos del paciente
public function obtenerTurnosPendientesPorFecha($matricula, $fecha) {
    $stmt = $this->pdo->prepare(
        "SELECT t.id_turno, t.hora, p.nombre, p.apellido, p.dni
         FROM Turno t
         JOIN Paciente p ON t.id_paciente = p.id_paciente
         WHERE t.matricula = :matricula
         AND   t.fecha     = :fecha
         AND   t.estado    = 'Pendiente'
         ORDER BY t.hora ASC"
    );
    $stmt->execute([':matricula' => $matricula, ':fecha' => $fecha]);
    return $stmt->fetchAll();
}

// Suspende la agenda — transacción ACID completa
public function suspenderAgenda($matricula, $fecha) {

    // Iniciamos la transacción — todo o nada
    $this->pdo->beginTransaction();

    try {
        // PASO 1: Traemos los turnos pendientes para registrar el historial
        $turnos = $this->obtenerTurnosPendientesPorFecha($matricula, $fecha);

        // PASO 2: Cancelamos todos los turnos pendientes de ese día
        $stmt = $this->pdo->prepare(
            "UPDATE Turno SET estado = 'Cancelado'
             WHERE matricula = :matricula
             AND   fecha     = :fecha
             AND   estado    = 'Pendiente'"
        );
        $stmt->execute([':matricula' => $matricula, ':fecha' => $fecha]);

        // PASO 3: Bloqueamos la agenda para esa fecha
        $stmt_blq = $this->pdo->prepare(
            "INSERT INTO Agenda_Bloqueada (matricula, fecha, motivo)
             VALUES (:matricula, :fecha, 'Ausencia de profesional')"
        );
        $stmt_blq->execute([':matricula' => $matricula, ':fecha' => $fecha]);

        // Todo ok — confirmamos los cambios
        $this->pdo->commit();

        return [
            'count'  => count($turnos),
            'turnos' => $turnos,
        ];

    } catch (Exception $e) {
        // Algo falló — revertimos todo como si nada hubiera pasado
        $this->pdo->rollBack();
        throw $e;
    }
}

// Trae médicos por especialidad
public function obtenerMedicosPorEspecialidad($id_especialidad) {
    $stmt = $this->pdo->prepare(
        "SELECT m.matricula, m.nombre, m.apellido
         FROM Medico m
         JOIN Medico_Especialidad me ON m.matricula = me.matricula
         WHERE me.id_especialidad = :id
         ORDER BY m.apellido ASC"
    );
    $stmt->execute([':id' => $id_especialidad]);
    return $stmt->fetchAll();
}

// Trae los días que atiende un médico (opcionalmente filtrando por especialidad)
public function obtenerDiasMedico($matricula, $id_especialidad = null) {
    $sql = "SELECT DISTINCT dia_semana FROM Horario_Atencion
            WHERE matricula = :matricula AND activo = 1";
    $params = [':matricula' => $matricula];

    if ($id_especialidad !== null) {
        $sql .= " AND id_especialidad = :id_especialidad";
        $params[':id_especialidad'] = $id_especialidad;
    }

    $sql .= " ORDER BY dia_semana ASC";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Trae los bloques de horario de un médico en un día específico (opcionalmente por especialidad)
public function obtenerHorasMedico($matricula, $dia_semana, $id_especialidad = null) {
    $sql = "SELECT hora_inicio, hora_fin, id_consultorio
            FROM Horario_Atencion
            WHERE matricula = :matricula
            AND   dia_semana = :dia
            AND   activo = 1";
    $params = [':matricula' => $matricula, ':dia' => $dia_semana];

    if ($id_especialidad !== null) {
        $sql .= " AND id_especialidad = :id_especialidad";
        $params[':id_especialidad'] = $id_especialidad;
    }

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Trae los slots de 30 min del horario del médico ese día, marcando cuáles ya están ocupados
public function obtenerDisponibilidad($matricula, $fecha, $dia_semana, $id_especialidad = null) {
    $bloques = $this->obtenerHorasMedico($matricula, $dia_semana, $id_especialidad);

    $stmt = $this->pdo->prepare(
        "SELECT hora FROM Turno
         WHERE matricula = :matricula
         AND   fecha     = :fecha
         AND   estado NOT IN ('Cancelado', 'Ausente')"
    );
    $stmt->execute([':matricula' => $matricula, ':fecha' => $fecha]);
    $ocupadas = array_map(fn($t) => substr($t['hora'], 0, 5), $stmt->fetchAll());

    $slots = [];
    foreach ($bloques as $bloque) {
        $inicio = strtotime($bloque['hora_inicio']);
        $fin    = strtotime($bloque['hora_fin']);

        for ($t = $inicio; $t < $fin; $t += 1800) {
            $hora = date('H:i', $t);
            $slots[] = [
                'hora'           => $hora,
                'disponible'     => !in_array($hora, $ocupadas, true),
                'id_consultorio' => $bloque['id_consultorio'],
            ];
        }
    }

    return $slots;
}

// Verifica si una fecha está bloqueada para un médico
public function fechaBloqueada($matricula, $fecha) {
    $stmt = $this->pdo->prepare(
        "SELECT COUNT(*) AS total FROM Agenda_Bloqueada
         WHERE matricula = :matricula AND fecha = :fecha"
    );
    $stmt->execute([':matricula' => $matricula, ':fecha' => $fecha]);
    return $stmt->fetch()['total'] > 0;
}

// Registra un médico nuevo: datos personales, especialidad, horarios y usuario — transacción ACID
public function insertarMedico($datos) {

    $this->pdo->beginTransaction();

    try {
        // PASO 1: Datos del médico
        $stmt = $this->pdo->prepare(
            "INSERT INTO Medico (matricula, nombre, apellido, telefono, email)
             VALUES (:matricula, :nombre, :apellido, :telefono, :email)"
        );
        $stmt->execute([
            ':matricula' => $datos['matricula'],
            ':nombre'    => $datos['nombre'],
            ':apellido'  => $datos['apellido'],
            ':telefono'  => $datos['telefono'],
            ':email'     => $datos['email'],
        ]);

        // PASO 2: Especialidad del médico
        $stmt = $this->pdo->prepare(
            "INSERT INTO Medico_Especialidad (matricula, id_especialidad)
             VALUES (:matricula, :id_especialidad)"
        );
        $stmt->execute([
            ':matricula'       => $datos['matricula'],
            ':id_especialidad' => $datos['id_especialidad'],
        ]);

        // PASO 3: Un registro de horario por cada día seleccionado
        $stmt = $this->pdo->prepare(
            "INSERT INTO Horario_Atencion
                (matricula, id_consultorio, id_especialidad, dia_semana, hora_inicio, hora_fin, activo)
             VALUES
                (:matricula, :id_consultorio, :id_especialidad, :dia_semana, :hora_inicio, :hora_fin, 1)"
        );
        foreach ($datos['dias'] as $dia) {
            $stmt->execute([
                ':matricula'       => $datos['matricula'],
                ':id_consultorio'  => $datos['id_consultorio'],
                ':id_especialidad' => $datos['id_especialidad'],
                ':dia_semana'      => $dia,
                ':hora_inicio'     => $datos['hora_inicio'],
                ':hora_fin'        => $datos['hora_fin'],
            ]);
        }

        // PASO 4: Usuario para que el médico pueda loguearse — contraseña autogenerada
        $password = 'MED#' . $datos['matricula'];
        $hash     = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare(
            "INSERT INTO Usuario (dni_username, password_hash, id_rol, id_paciente, matricula_medico)
             VALUES (:dni_username, :password_hash, 3, NULL, :matricula)"
        );
        $stmt->execute([
            ':dni_username'  => $datos['dni_username'],
            ':password_hash' => $hash,
            ':matricula'     => $datos['matricula'],
        ]);

        $this->pdo->commit();

        return $password;

    } catch (Exception $e) {
        $this->pdo->rollBack();
        throw $e;
    }
}

// Trae todos los médicos con su especialidad y usuario de acceso
public function obtenerMedicosConDetalles() {
    $stmt = $this->pdo->prepare(
        "SELECT m.matricula, m.nombre, m.apellido,
                GROUP_CONCAT(DISTINCT e.nombre SEPARATOR ', ') AS especialidades,
                u.dni_username
         FROM Medico m
         LEFT JOIN Medico_Especialidad me ON m.matricula = me.matricula
         LEFT JOIN Especialidad e         ON me.id_especialidad = e.id_especialidad
         LEFT JOIN Usuario u              ON u.matricula_medico = m.matricula
         GROUP BY m.matricula, m.nombre, m.apellido, u.dni_username
         ORDER BY m.apellido ASC"
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

// Resetea la contraseña de un médico a MED#<matricula> y devuelve la nueva contraseña en texto plano
public function resetearPasswordMedico($matricula) {
    $suffix   = str_pad((string) rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    $password = 'MED#' . $matricula . '#' . $suffix;
    $hash     = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $this->pdo->prepare(
        "UPDATE Usuario SET password_hash = :hash WHERE matricula_medico = :matricula"
    );
    $stmt->execute([':hash' => $hash, ':matricula' => $matricula]);

    return $password;
}

// Trae los horarios de atención de un médico, con especialidad y consultorio
public function obtenerHorariosMedico($matricula) {
    $stmt = $this->pdo->prepare(
        "SELECT h.id_horario, h.dia_semana, h.hora_inicio, h.hora_fin,
                h.id_consultorio, h.id_especialidad,
                e.nombre AS especialidad,
                c.numero AS consultorio_numero, c.piso AS consultorio_piso
         FROM Horario_Atencion h
         JOIN Especialidad e ON h.id_especialidad = e.id_especialidad
         JOIN Consultorio  c ON h.id_consultorio  = c.id_consultorio
         WHERE h.matricula = :matricula
         AND   h.activo = 1
         ORDER BY h.dia_semana ASC, h.hora_inicio ASC"
    );
    $stmt->execute([':matricula' => $matricula]);
    return $stmt->fetchAll();
}

// Trae las especialidades que dicta un médico
public function obtenerEspecialidadesMedico($matricula) {
    $stmt = $this->pdo->prepare(
        "SELECT e.id_especialidad, e.nombre
         FROM Medico_Especialidad me
         JOIN Especialidad e ON me.id_especialidad = e.id_especialidad
         WHERE me.matricula = :matricula
         ORDER BY e.nombre ASC"
    );
    $stmt->execute([':matricula' => $matricula]);
    return $stmt->fetchAll();
}

// Verifica si el médico ya tiene un horario ese día en ese consultorio
public function existeHorario($matricula, $dia_semana, $id_consultorio) {
    $stmt = $this->pdo->prepare(
        "SELECT COUNT(*) AS total FROM Horario_Atencion
         WHERE matricula = :matricula
         AND   dia_semana = :dia
         AND   id_consultorio = :id_consultorio
         AND   activo = 1"
    );
    $stmt->execute([
        ':matricula'      => $matricula,
        ':dia'            => $dia_semana,
        ':id_consultorio' => $id_consultorio,
    ]);
    return $stmt->fetch()['total'] > 0;
}

// Agrega un nuevo horario de atención para el médico
public function agregarHorario($matricula, $id_consultorio, $id_especialidad, $dia_semana, $hora_inicio, $hora_fin) {
    $stmt = $this->pdo->prepare(
        "INSERT INTO Horario_Atencion
            (matricula, id_consultorio, id_especialidad, dia_semana, hora_inicio, hora_fin, activo)
         VALUES
            (:matricula, :id_consultorio, :id_especialidad, :dia_semana, :hora_inicio, :hora_fin, 1)"
    );
    $stmt->execute([
        ':matricula'       => $matricula,
        ':id_consultorio'  => $id_consultorio,
        ':id_especialidad' => $id_especialidad,
        ':dia_semana'      => $dia_semana,
        ':hora_inicio'     => $hora_inicio,
        ':hora_fin'        => $hora_fin,
    ]);
}

// Elimina un horario, verificando que pertenezca al médico
public function eliminarHorario($id_horario, $matricula) {
    $stmt = $this->pdo->prepare(
        "DELETE FROM Horario_Atencion WHERE id_horario = :id AND matricula = :matricula"
    );
    $stmt->execute([':id' => $id_horario, ':matricula' => $matricula]);
    return $stmt->rowCount() > 0;
}

    public function obtenerTurnosPorMedico($matricula, $fecha_desde, $fecha_hasta) {
        $stmt = $this->pdo->prepare(
            "SELECT
                t.id_turno,
                t.fecha,
                t.hora,
                t.estado,
                CONCAT(p.apellido, ', ', p.nombre) AS paciente,
                p.dni,
                p.telefono,
                e.nombre AS especialidad,
                c.numero AS consultorio
             FROM Turno t
             JOIN Paciente     p ON t.id_paciente    = p.id_paciente
             JOIN Especialidad e ON t.id_especialidad = e.id_especialidad
             JOIN Consultorio  c ON t.id_consultorio  = c.id_consultorio
             WHERE t.matricula = :matricula
             AND   t.fecha BETWEEN :desde AND :hasta
             ORDER BY t.fecha ASC, t.hora ASC"
        );
        $stmt->execute([
            ':matricula' => $matricula,
            ':desde'     => $fecha_desde,
            ':hasta'     => $fecha_hasta,
        ]);
        return $stmt->fetchAll();
    }

    public function obtenerKpisMedico($matricula) {
        $stmt = $this->pdo->prepare(
            "SELECT
                COUNT(*)                   AS total,
                SUM(estado = 'Pendiente')  AS pendientes,
                SUM(estado = 'Confirmado') AS confirmados
             FROM Turno
             WHERE matricula = :matricula
             AND   fecha = CURDATE()"
        );
        $stmt->execute([':matricula' => $matricula]);
        return $stmt->fetch();
    }

    public function obtenerMedico($matricula) {
        $stmt = $this->pdo->prepare(
            "SELECT nombre, apellido FROM Medico WHERE matricula = :matricula"
        );
        $stmt->execute([':matricula' => $matricula]);
        return $stmt->fetch();
    }

    public function obtenerDatosPaciente($id_paciente) {
        $stmt = $this->pdo->prepare(
            "SELECT nombre, apellido, dni FROM Paciente WHERE id_paciente = :id"
        );
        $stmt->execute([':id' => $id_paciente]);
        return $stmt->fetch();
    }

    public function obtenerTurnosCompletoPaciente($id_paciente) {
        $stmt = $this->pdo->prepare(
            "SELECT
                t.id_turno,
                t.fecha,
                t.hora,
                t.estado,
                CONCAT('Dr/a. ', m.apellido) AS medico,
                e.nombre                     AS especialidad,
                c.numero                     AS consultorio
             FROM Turno t
             JOIN Medico       m ON t.matricula        = m.matricula
             JOIN Especialidad e ON t.id_especialidad  = e.id_especialidad
             JOIN Consultorio  c ON t.id_consultorio   = c.id_consultorio
             WHERE t.id_paciente = :id
             ORDER BY t.fecha DESC, t.hora DESC"
        );
        $stmt->execute([':id' => $id_paciente]);
        return $stmt->fetchAll();
    }

    public function obtenerTurnosPendientes() {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM vista_turnos_pendientes"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerMedicosHorarios() {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM vista_medicos_horarios"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}