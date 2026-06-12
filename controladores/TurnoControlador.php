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

    // Devuelve turnos por rango de fechas
    public function obtenerTurnosPorFecha($fecha_desde, $fecha_hasta) {
        return $this->modelo->obtenerTurnosPorFecha($fecha_desde, $fecha_hasta);
    }

    // Trae los turnos de un paciente específico
    public function obtenerTurnosPaciente($id_paciente) {
        return $this->modelo->obtenerTurnosPorPaciente($id_paciente);
    }

    // Trae los datos de un paciente por su id
    public function obtenerPaciente($id_paciente) {
        return $this->modelo->obtenerPacientePorId($id_paciente);
    }

    // Cambia el estado de un turno
    public function cambiarEstado($id_turno, $estado_nuevo) {
        $this->modelo->cambiarEstadoTurno($id_turno, $estado_nuevo);
    }

    // Trae los médicos que dictan una especialidad
    public function obtenerMedicosPorEspecialidad($id_especialidad) {
        return $this->modelo->obtenerMedicosPorEspecialidad($id_especialidad);
    }

    // Trae los días de la semana que atiende un médico
    public function obtenerDiasMedico($matricula, $id_especialidad = null) {
        return $this->modelo->obtenerDiasMedico($matricula, $id_especialidad);
    }

    // Trae los bloques de horario de un médico en un día específico
    public function obtenerHorasMedico($matricula, $dia_semana, $id_especialidad = null) {
        return $this->modelo->obtenerHorasMedico($matricula, $dia_semana, $id_especialidad);
    }

    // Verifica si una fecha está bloqueada para un médico
    public function fechaBloqueada($matricula, $fecha) {
        return $this->modelo->fechaBloqueada($matricula, $fecha);
    }

    // Trae los slots de 30 min de un médico en una fecha, marcando disponibilidad real
    public function obtenerDisponibilidad($matricula, $fecha, $dia_semana, $id_especialidad = null) {
        return $this->modelo->obtenerDisponibilidad($matricula, $fecha, $dia_semana, $id_especialidad);
    }

    // Registra un médico nuevo con su especialidad, horarios y usuario de acceso
    public function registrarMedico($datos) {

        if (empty($datos['dias'])) {
            throw new Exception('Tenés que seleccionar al menos un día de atención.');
        }

        if ($datos['hora_inicio'] >= $datos['hora_fin']) {
            throw new Exception('La hora de inicio debe ser anterior a la hora de fin.');
        }

        $password = $this->modelo->insertarMedico($datos);

        return [
            'dni_username' => $datos['dni_username'],
            'password'     => $password,
        ];
    }

    // Trae todos los médicos con especialidad y usuario, para gestión
    public function obtenerMedicosConDetalles() {
        return $this->modelo->obtenerMedicosConDetalles();
    }

    // Resetea la contraseña de un médico y devuelve las nuevas credenciales
    public function resetearPassword($matricula) {
        $password = $this->modelo->resetearPasswordMedico($matricula);

        $stmt = $this->pdo->prepare(
            "SELECT dni_username FROM Usuario WHERE matricula_medico = :matricula"
        );
        $stmt->execute([':matricula' => $matricula]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            throw new Exception('Ese médico no tiene un usuario de acceso asociado.');
        }

        return [
            'dni'      => $usuario['dni_username'],
            'password' => $password,
        ];
    }

    // Suspende la agenda de un médico en una fecha — transacción ACID
    public function suspenderAgenda($matricula, $fecha) {

        // Validación 1: la fecha no puede ser del pasado
        if ($fecha < date('Y-m-d')) {
            throw new Exception('No podés suspender una agenda de una fecha pasada.');
        }

        // Validación 2: verificamos que haya turnos pendientes
        $total = $this->modelo->contarTurnosPendientes($matricula, $fecha);
        if ($total == 0) {
            throw new Exception('No hay turnos pendientes para ese médico en esa fecha.');
        }

        // Todo ok — ejecutamos la transacción
        return $this->modelo->suspenderAgenda($matricula, $fecha);
    }
}