<?php
/*
 * controladores/TurnoControlador.php
 * ---------------------------------------------------------------
 * Es el intermediario entre la Vista y el Modelo de Turnos.
 * La vista le pide datos, él se los pide al Modelo y se los
 * devuelve listos para mostrar. No genera HTML nunca.
 * ---------------------------------------------------------------
 */

require_once __DIR__ . '/../configuracion/conexion.php';
require_once __DIR__ . '/../modelos/TurnoModelo.php';

class TurnoControlador {

    private $modelo;
    private $pdo;

    public function __construct($pdo) {
        $this->pdo    = $pdo;
        $this->modelo = new TurnoModelo($pdo);
    }

    // Devuelve los turnos de hoy para mostrar en la tabla
    public function obtenerAgendaHoy() {
        return $this->modelo->obtenerTurnosHoy();
    }

    // Devuelve los números para los KPIs
    public function obtenerKpis() {
        return $this->modelo->contarTurnosHoy();
    }

    // Trae los datos para llenar los selects del formulario
    public function obtenerDatosFormulario() {
        return [
            'pacientes'      => $this->modelo->obtenerPacientes(),
            'especialidades' => $this->modelo->obtenerEspecialidades(),
            'medicos'        => $this->modelo->obtenerMedicos(),
            'consultorios'   => $this->modelo->obtenerConsultorios(),
        ];
    }

    // Registra un paciente nuevo y devuelve su id
    public function registrarPaciente($datos) {
        return $this->modelo->insertarPaciente(
            $datos['nombre'],
            $datos['apellido'],
            $datos['dni'],
            $datos['telefono'],
            $datos['email']
        );
    }

    // Llama al SP ReservarTurno con los datos del formulario
    public function reservarTurno($id_paciente, $matricula, $id_consultorio, $id_especialidad, $fecha, $hora) {
        $stmt = $this->pdo->prepare(
            "CALL ReservarTurno(:paciente, :matricula, :consultorio, :especialidad, :fecha, :hora)"
        );
        $stmt->execute([
            ':paciente'      => $id_paciente,
            ':matricula'     => $matricula,
            ':consultorio'   => $id_consultorio,
            ':especialidad'  => $id_especialidad,
            ':fecha'         => $fecha,
            ':hora'          => $hora,
        ]);
    }
    // Trae los turnos de un paciente específico
public function obtenerTurnosPaciente($id_paciente) {
    return $this->modelo->obtenerTurnosPorPaciente($id_paciente);
}

// Trae los datos de un paciente por su id
public function obtenerPaciente($id_paciente) {
    return $this->modelo->obtenerPacientePorId($id_paciente);
}
// Devuelve turnos por rango de fechas
public function obtenerTurnosPorFecha($fecha_desde, $fecha_hasta) {
    return $this->modelo->obtenerTurnosPorFecha($fecha_desde, $fecha_hasta);
}

// Trae los turnos activos para la pantalla de cancelar
public function obtenerTurnosActivos() {
    return $this->modelo->obtenerTurnosActivos();
}

// Cancela un turno llamando al SP
public function cancelarTurno($id_turno) {
    $this->modelo->cancelarTurno($id_turno);
}

// Cambia el estado de un turno
public function cambiarEstado($id_turno, $estado_nuevo) {
    $this->modelo->cambiarEstadoTurno($id_turno, $estado_nuevo);
}

}
