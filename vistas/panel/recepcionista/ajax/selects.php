<?php
/*
 * vistas/panel/recepcionista/ajax/selects.php
 * ---------------------------------------------------------------
 * Endpoint AJAX que responde en JSON para los selects encadenados
 * del formulario de reserva de turnos. No genera HTML.
 * ---------------------------------------------------------------
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../../configuracion/conexion.php';
require_once __DIR__ . '/../../../../controladores/TurnoControlador.php';

$controlador = new TurnoControlador($pdo);
$accion      = $_GET['accion'] ?? '';

switch ($accion) {

    case 'medicos_por_especialidad':
        $id_especialidad = $_GET['id_especialidad'] ?? '';
        $medicos = $controlador->obtenerMedicosPorEspecialidad($id_especialidad);
        echo json_encode(['medicos' => $medicos]);
        break;

    case 'dias_medico':
        $matricula        = $_GET['matricula'] ?? '';
        $id_especialidad  = $_GET['id_especialidad'] ?? null;
        $dias = $controlador->obtenerDiasMedico($matricula, $id_especialidad);
        echo json_encode(['dias' => array_map(fn($d) => (int) $d['dia_semana'], $dias)]);
        break;

    case 'horas_medico':
        $matricula       = $_GET['matricula'] ?? '';
        $fecha           = $_GET['fecha'] ?? '';
        $dia_semana      = $_GET['dia_semana'] ?? '';
        $id_especialidad = $_GET['id_especialidad'] ?? null;

        if ($controlador->fechaBloqueada($matricula, $fecha)) {
            echo json_encode([
                'bloqueada' => true,
                'motivo'    => 'El médico no atiende en la fecha seleccionada (agenda suspendida).',
            ]);
            break;
        }

        $bloques = $controlador->obtenerHorasMedico($matricula, $dia_semana, $id_especialidad);

        $slots          = [];
        $id_consultorio = null;

        foreach ($bloques as $bloque) {
            if ($id_consultorio === null) {
                $id_consultorio = $bloque['id_consultorio'];
            }

            $inicio = strtotime($bloque['hora_inicio']);
            $fin    = strtotime($bloque['hora_fin']);

            for ($t = $inicio; $t < $fin; $t += 1800) {
                $slots[] = date('H:i', $t);
            }
        }

        echo json_encode([
            'bloqueada'      => false,
            'slots'          => $slots,
            'id_consultorio' => $id_consultorio,
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida']);
        break;
}
