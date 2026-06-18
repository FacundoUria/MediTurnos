<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../../configuracion/conexion.php';
require_once __DIR__ . '/../../../../controladores/TurnoControlador.php';

$matricula = $_POST['matricula'] ?? '';

if (empty($matricula)) {
    echo json_encode(['exito' => false, 'mensaje' => 'Matrícula requerida']);
    exit;
}

$controlador = new TurnoControlador($pdo);

try {
    $resultado = $controlador->resetearPassword((int)$matricula);

    $stmt = $pdo->prepare("SELECT nombre, apellido FROM Medico WHERE matricula = :matricula");
    $stmt->execute([':matricula' => (int)$matricula]);
    $medico = $stmt->fetch();

    echo json_encode([
        'exito'    => true,
        'dni'      => $resultado['dni'],
        'password' => $resultado['password'],
        'nombre'   => 'Dr/a. ' . ($medico['apellido'] ?? '') . ', ' . ($medico['nombre'] ?? ''),
    ]);
} catch (Exception $e) {
    echo json_encode(['exito' => false, 'mensaje' => $e->getMessage()]);
}
