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

    // Trae los turnos activos (no realizados) para cancelar
public function obtenerTurnosActivos() {
    $sql = "SELECT
                t.id_turno,
                t.fecha,
                t.hora,
                t.estado,
                CONCAT(p.apellido, ', ', p.nombre) AS paciente,
                p.dni,
                CONCAT('Dr/a. ', m.apellido)       AS medico,
                e.nombre                           AS especialidad
            FROM Turno t
            JOIN Paciente     p ON t.id_paciente    = p.id_paciente
            JOIN Medico       m ON t.matricula       = m.matricula
            JOIN Especialidad e ON t.id_especialidad = e.id_especialidad
            WHERE t.estado NOT IN ('Realizado')
            AND   t.fecha >= CURDATE()
            ORDER BY t.fecha ASC, t.hora ASC";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Cancela un turno llamando al SP CancelarTurno
public function cancelarTurno($id_turno) {
    $stmt = $this->pdo->prepare("CALL CancelarTurno(:id)");
    $stmt->execute([':id' => $id_turno]);
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

}