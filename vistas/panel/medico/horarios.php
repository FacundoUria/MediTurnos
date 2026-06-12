<?php
/*
 * vistas/panel/medico/horarios.php
 * ---------------------------------------------------------------
 * Gestión de horarios de atención del médico logueado.
 * Permite ver, agregar y eliminar sus propios horarios.
 * ---------------------------------------------------------------
 */
session_start();

// Verificamos que esté logueado y sea médico
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Medico') {
    header('Location: /mediturnos/vistas/autenticacion/login.php');
    exit;
}

require_once __DIR__ . '/../../../configuracion/conexion.php';
require_once __DIR__ . '/../../../controladores/TurnoControlador.php';

$controlador = new TurnoControlador($pdo);
$matricula    = $_SESSION['matricula'];

$mensaje  = '';
$tipo_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    try {
        if ($accion === 'agregar') {
            $controlador->agregarHorario([
                'matricula'       => $matricula,
                'id_consultorio'  => $_POST['id_consultorio']  ?? '',
                'id_especialidad' => $_POST['id_especialidad'] ?? '',
                'dia_semana'      => $_POST['dia_semana']      ?? '',
                'hora_inicio'     => $_POST['hora_inicio']     ?? '',
                'hora_fin'        => $_POST['hora_fin']        ?? '',
            ]);
            $mensaje  = '✅ Horario agregado correctamente.';
            $tipo_msg = 'exito';

        } elseif ($accion === 'eliminar') {
            $controlador->eliminarHorario($_POST['id_horario'] ?? '', $matricula);
            $mensaje  = '✅ Horario eliminado correctamente.';
            $tipo_msg = 'exito';
        }
    } catch (Exception $e) {
        $mensaje  = '❌ ' . $e->getMessage();
        $tipo_msg = 'error';
    }
}

$horarios      = $controlador->obtenerHorariosMedico($matricula);
$especialidades = $controlador->obtenerEspecialidadesMedico($matricula);
$consultorios  = $controlador->obtenerDatosFormulario()['consultorios'];

// Traemos el nombre del médico
$sql_med = "SELECT nombre, apellido FROM Medico WHERE matricula = :matricula";
$stmt_med = $pdo->prepare($sql_med);
$stmt_med->execute([':matricula' => $matricula]);
$medico = $stmt_med->fetch();

$nombres_dias = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

require_once __DIR__ . '/../../../vistas/plantillas/header.php';
?>

<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    .layout { display: flex; min-height: 100vh; width: 100%; }

    .sidebar {
        width: 220px;
        min-width: 220px;
        background: var(--primario);
        display: flex;
        flex-direction: column;
        padding: 2rem 1.2rem;
        gap: 0.4rem;
    }

    .sidebar-logo {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        padding: 0.5rem 0.8rem;
        margin-bottom: 1rem;
    }

    .sidebar-logo span { font-family: 'DM Serif Display', serif; color: #fff; font-size: 1.3rem; }

    .sidebar-medico {
        background: rgba(255,255,255,0.1);
        border-radius: 10px;
        padding: 0.8rem;
        margin-bottom: 1rem;
    }

    .sidebar-medico .nombre { color: white; font-size: 0.90rem; font-weight: 600; }
    .sidebar-medico .rol    { color: rgba(255,255,255,0.65); font-size: 0.78rem; margin-top: 0.2rem; }

    .sidebar-seccion {
        font-size: 0.72rem;
        font-weight: 600;
        color: rgba(255,255,255,0.45);
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding: 0.5rem 0.8rem 0.2rem;
        margin-top: 0.5rem;
    }

    .sidebar-link {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        padding: 0.75rem 0.8rem;
        border-radius: 8px;
        color: rgba(255,255,255,0.80);
        text-decoration: none;
        font-size: 0.92rem;
        transition: all 0.2s;
    }

    .sidebar-link:hover  { background: rgba(255,255,255,0.12); color: #fff; }
    .sidebar-link.activo { background: rgba(255,255,255,0.18); color: #fff; font-weight: 500; }
    .sidebar-link .icono { font-size: 1.1rem; width: 22px; text-align: center; }

    .sidebar-footer {
        margin-top: auto;
        padding-top: 1rem;
        border-top: 1px solid rgba(255,255,255,0.15);
    }

    .contenido {
        flex: 1;
        min-width: 0;
        background: var(--fondo);
        padding: 2rem 2.5rem;
        overflow-y: auto;
    }

    .encabezado-pagina { margin-bottom: 2rem; }
    .encabezado-pagina h1 { font-size: 1.5rem; font-weight: 600; color: var(--texto); }
    .encabezado-pagina p  { color: var(--texto-suave); font-size: 0.92rem; margin-top: 0.2rem; }

    .tabla-card {
        background: var(--blanco);
        border-radius: var(--radio);
        box-shadow: var(--sombra);
        overflow: hidden;
        width: 100%;
        margin-bottom: 2rem;
    }

    .tabla-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.2rem 1.6rem;
        border-bottom: 1px solid var(--borde);
    }

    .tabla-header h2 { font-size: 1rem; font-weight: 600; color: var(--texto); }

    .badge-total {
        background: var(--acento);
        color: var(--primario);
        padding: 0.25rem 0.75rem;
        border-radius: 999px;
        font-size: 0.80rem;
        font-weight: 600;
    }

    table { width: 100%; border-collapse: collapse; }

    thead th {
        background: var(--fondo);
        padding: 0.8rem 1.2rem;
        text-align: left;
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--texto-suave);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid var(--borde);
    }

    tbody tr { border-bottom: 1px solid var(--borde); transition: background 0.15s; }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: var(--acento); }
    tbody td { padding: 0.9rem 1.2rem; font-size: 0.90rem; color: var(--texto); }

    .hora { font-weight: 600; color: var(--primario); }

    .sin-registros {
        text-align: center;
        padding: 3rem;
        color: var(--texto-suave);
        font-style: italic;
    }

    .boton-eliminar {
        padding: 0.45rem 0.9rem;
        background: #fff5f5;
        color: #991b1b;
        border: 1.5px solid #fecaca;
        border-radius: 8px;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.82rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    .boton-eliminar:hover { background: #fee2e2; border-color: #fca5a5; }

    /* ── Tarjeta del formulario ── */
    .form-card {
        background: var(--blanco);
        border-radius: var(--radio);
        box-shadow: var(--sombra);
        padding: 2rem;
        max-width: 750px;
    }

    .seccion-titulo {
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--texto-suave);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 1rem;
    }

    .grilla-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; }
    .campo-completo { grid-column: 1 / -1; }

    .grupo-campo { display: flex; flex-direction: column; gap: 0.4rem; }
    .grupo-campo label { font-size: 0.88rem; font-weight: 500; color: var(--texto); }

    select.campo {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7c93' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        cursor: pointer;
    }

    .acciones { display: flex; gap: 1rem; margin-top: 1.8rem; }

    .boton-primario {
        flex: 1;
        padding: 0.9rem;
        background: var(--primario);
        color: var(--blanco);
        border: none;
        border-radius: var(--radio);
        font-family: 'DM Sans', sans-serif;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
    }

    .boton-primario:hover { background: var(--primario-osc); }
</style>

<div class="layout">

    <aside class="sidebar">
        <div class="sidebar-logo">
            <span>🏥</span>
            <span>MediTurnos</span>
        </div>

        <div class="sidebar-medico">
            <div class="nombre">Dr/a. <?= htmlspecialchars($medico['apellido'] ?? '') ?></div>
            <div class="rol">Médico — Matrícula <?= $matricula ?></div>
        </div>

        <span class="sidebar-seccion">Menú</span>

        <a href="index.php" class="sidebar-link">
            <span class="icono">📅</span> Mi agenda
        </a>
        <a href="horarios.php" class="sidebar-link activo">
            <span class="icono">🕐</span> Mis horarios
        </a>

        <div class="sidebar-footer">
            <a href="/mediturnos/vistas/autenticacion/logout.php" class="sidebar-link">
                <span class="icono">🚪</span> Cerrar sesión
            </a>
        </div>
    </aside>

    <main class="contenido">

        <div class="encabezado-pagina">
            <h1>Mis horarios de atención</h1>
            <p>Gestioná los días, horarios y consultorios en los que atendés</p>
        </div>

        <?php if ($mensaje): ?>
            <div class="alerta-<?= $tipo_msg ?>" style="margin-bottom:1.5rem;">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <div class="tabla-card">
            <div class="tabla-header">
                <h2>Horarios actuales</h2>
                <span class="badge-total"><?= count($horarios) ?> horario(s)</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Día</th>
                        <th>Hora inicio</th>
                        <th>Hora fin</th>
                        <th>Consultorio</th>
                        <th>Especialidad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($horarios)): ?>
                        <tr>
                            <td colspan="6" class="sin-registros">
                                No tenés horarios de atención configurados
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($horarios as $h): ?>
                        <tr>
                            <td><?= htmlspecialchars($nombres_dias[(int) $h['dia_semana']] ?? $h['dia_semana']) ?></td>
                            <td class="hora"><?= htmlspecialchars(substr($h['hora_inicio'], 0, 5)) ?></td>
                            <td class="hora"><?= htmlspecialchars(substr($h['hora_fin'], 0, 5)) ?></td>
                            <td>Consultorio <?= htmlspecialchars($h['consultorio_numero']) ?> — <?= htmlspecialchars($h['consultorio_piso']) ?> piso</td>
                            <td><?= htmlspecialchars($h['especialidad']) ?></td>
                            <td>
                                <form method="POST" action="" onsubmit="return confirm('¿Eliminar este horario?')">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id_horario" value="<?= $h['id_horario'] ?>">
                                    <button type="submit" class="boton-eliminar">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="form-card">
            <form method="POST" action="">
                <input type="hidden" name="accion" value="agregar">

                <p class="seccion-titulo">Agregar nuevo horario</p>
                <div class="grilla-2">
                    <div class="grupo-campo">
                        <label for="dia_semana">Día</label>
                        <select name="dia_semana" id="dia_semana" class="campo" required>
                            <option value="">— Seleccioná —</option>
                            <?php for ($d = 1; $d <= 6; $d++): ?>
                                <option value="<?= $d ?>"><?= $nombres_dias[$d] ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="grupo-campo">
                        <label for="id_especialidad">Especialidad</label>
                        <select name="id_especialidad" id="id_especialidad" class="campo" required>
                            <option value="">— Seleccioná —</option>
                            <?php foreach ($especialidades as $e): ?>
                                <option value="<?= $e['id_especialidad'] ?>">
                                    <?= htmlspecialchars($e['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grupo-campo">
                        <label for="hora_inicio">Hora de inicio</label>
                        <input type="time" name="hora_inicio" id="hora_inicio" class="campo" required>
                    </div>
                    <div class="grupo-campo">
                        <label for="hora_fin">Hora de fin</label>
                        <input type="time" name="hora_fin" id="hora_fin" class="campo" required>
                    </div>
                    <div class="grupo-campo campo-completo">
                        <label for="id_consultorio">Consultorio</label>
                        <select name="id_consultorio" id="id_consultorio" class="campo" required>
                            <option value="">— Seleccioná —</option>
                            <?php foreach ($consultorios as $c): ?>
                                <option value="<?= $c['id_consultorio'] ?>">
                                    Consultorio <?= htmlspecialchars($c['numero']) ?> — <?= htmlspecialchars($c['piso']) ?> piso
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="acciones">
                    <button type="submit" class="boton-primario">Agregar horario</button>
                </div>

            </form>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../../vistas/plantillas/footer.php'; ?>
