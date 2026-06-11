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

    // Trae todos los turnos del día de hoy con JOINs
    public function obtenerTurnosHoy() {
        $sql = "SELECT 
                    t.id_turno,
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
                WHERE t.fecha = CURDATE()
                ORDER BY t.hora ASC";

        $stmt = $this->pdo->prepare($sql);
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

        // PASO 3: Insertamos un registro en Historial_Turno por cada turno
        $stmt_hist = $this->pdo->prepare(
            "INSERT INTO Historial_Turno
                (id_turno, estado_anterior, estado_nuevo, fecha_cambio, observacion)
             VALUES
                (:id_turno, 'Pendiente', 'Cancelado', NOW(), 'Ausencia de profesional')"
        );

        foreach ($turnos as $turno) {
            $stmt_hist->execute([':id_turno' => $turno['id_turno']]);
        }

        // PASO 4: Bloqueamos la agenda para esa fecha
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

// Verifica si una fecha está bloqueada para un médico
public function fechaBloqueada($matricula, $fecha) {
    $stmt = $this->pdo->prepare(
        "SELECT COUNT(*) AS total FROM Agenda_Bloqueada
         WHERE matricula = :matricula AND fecha = :fecha"
    );
    $stmt->execute([':matricula' => $matricula, ':fecha' => $fecha]);
    return $stmt->fetch()['total'] > 0;
}

}