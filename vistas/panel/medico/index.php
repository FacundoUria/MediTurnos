<?php
/*
 * vistas/panel/medico/index.php
 * ---------------------------------------------------------------
 * Panel del médico — muestra su agenda personal.
 * Solo lectura — no puede modificar nada.
 * Filtra los turnos por la matrícula guardada en la sesión.
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
$matricula   = $_SESSION['matricula'];

$filtro      = $_GET['filtro']      ?? 'hoy';
$fecha_desde = date('Y-m-d');
$fecha_hasta = date('Y-m-d');

if ($filtro === 'mes') {
    $fecha_desde = date('Y-m-01');
    $fecha_hasta = date('Y-m-t');
} elseif ($filtro === 'personalizado') {
    $fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-d');
    $fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
}

$turnos = $controlador->obtenerTurnosPorMedico($matricula, $fecha_desde, $fecha_hasta);
$kpis   = $controlador->obtenerKpisMedico($matricula);
$medico = $controlador->obtenerMedico($matricula);

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

    .kpis {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.2rem;
        margin-bottom: 2rem;
    }

    .kpi {
        background: var(--blanco);
        border-radius: var(--radio);
        padding: 1.4rem 1.6rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: var(--sombra);
    }

    .kpi-icono {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }

    .kpi-icono.azul  { background: #e8f4fd; }
    .kpi-icono.verde { background: #f0fff4; }
    .kpi-icono.amarillo { background: #fefce8; }

    .kpi-numero { font-size: 1.8rem; font-weight: 600; color: var(--texto); line-height: 1; }
    .kpi-label  { font-size: 0.82rem; color: var(--texto-suave); margin-top: 0.2rem; }

    .filtro-card {
        background: var(--blanco);
        border-radius: var(--radio);
        box-shadow: var(--sombra);
        padding: 1.2rem 1.6rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .filtro-tabs { display: flex; gap: 0.4rem; }

    .filtro-btn {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        border: 1.5px solid var(--borde);
        background: white;
        color: var(--texto-suave);
        font-family: 'DM Sans', sans-serif;
        font-size: 0.88rem;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s;
    }

    .filtro-btn:hover  { border-color: var(--primario); color: var(--primario); }
    .filtro-btn.activo { background: var(--primario); color: white; border-color: var(--primario); }

    .filtro-separador { width: 1px; height: 32px; background: var(--borde); }

    .filtro-personalizado { display: flex; align-items: center; gap: 0.6rem; }
    .filtro-personalizado input {
        padding: 0.5rem 0.8rem;
        border: 1.5px solid var(--borde);
        border-radius: 8px;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.88rem;
        color: var(--texto);
        outline: none;
        transition: border 0.2s;
    }
    .filtro-personalizado input:focus { border-color: var(--primario); }
    .filtro-personalizado span { font-size: 0.85rem; color: var(--texto-suave); }

    .btn-aplicar {
        padding: 0.5rem 1rem;
        background: var(--primario);
        color: white;
        border: none;
        border-radius: 8px;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.88rem;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.2s;
    }

    .btn-aplicar:hover { background: var(--primario-osc); }

    .tabla-card {
        background: var(--blanco);
        border-radius: var(--radio);
        box-shadow: var(--sombra);
        overflow: hidden;
        width: 100%;
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

    .paciente-nombre { font-weight: 500; }
    .paciente-dni    { font-size: 0.78rem; color: var(--texto-suave); margin-top: 0.1rem; }

    .estado {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 500;
    }

    .estado.Pendiente  { background: #fefce8; color: #854d0e; }
    .estado.Confirmado { background: #eff6ff; color: #1d4ed8; }
    .estado.Realizado  { background: #f0fff4; color: #166534; }
    .estado.Cancelado  { background: #fff5f5; color: #991b1b; }
    .estado.Ausente    { background: #f5f3ff; color: #5b21b6; }

    .sin-registros {
        text-align: center;
        padding: 3rem;
        color: var(--texto-suave);
        font-style: italic;
    }
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

        <a href="index.php" class="sidebar-link activo">
            <span class="icono">📅</span> Mi agenda
        </a>
        <a href="horarios.php" class="sidebar-link">
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
            <h1>Mi agenda</h1>
            <p><?= date('d/m/Y') ?></p>
        </div>

        <div class="kpis">
            <div class="kpi">
                <div class="kpi-icono azul">📅</div>
                <div>
                    <div class="kpi-numero"><?= $kpis['total'] ?? 0 ?></div>
                    <div class="kpi-label">Turnos hoy</div>
                </div>
            </div>
            <div class="kpi">
                <div class="kpi-icono amarillo">⏳</div>
                <div>
                    <div class="kpi-numero"><?= $kpis['pendientes'] ?? 0 ?></div>
                    <div class="kpi-label">Pendientes hoy</div>
                </div>
            </div>
            <div class="kpi">
                <div class="kpi-icono verde">✅</div>
                <div>
                    <div class="kpi-numero"><?= $kpis['confirmados'] ?? 0 ?></div>
                    <div class="kpi-label">Confirmados hoy</div>
                </div>
            </div>
        </div>

        <div class="filtro-card">
            <div class="filtro-tabs">
                <a href="?filtro=hoy" class="filtro-btn <?= $filtro === 'hoy' ? 'activo' : '' ?>">Hoy</a>
                <a href="?filtro=mes" class="filtro-btn <?= $filtro === 'mes' ? 'activo' : '' ?>">Este mes</a>
            </div>
            <div class="filtro-separador"></div>
            <form method="GET" action="" class="filtro-personalizado">
                <input type="hidden" name="filtro" value="personalizado">
                <span>Desde</span>
                <input type="date" name="fecha_desde" value="<?= $filtro === 'personalizado' ? $fecha_desde : date('Y-m-d') ?>">
                <span>hasta</span>
                <input type="date" name="fecha_hasta" value="<?= $filtro === 'personalizado' ? $fecha_hasta : date('Y-m-d') ?>">
                <button type="submit" class="btn-aplicar">Aplicar</button>
            </form>
        </div>

        <div class="tabla-card">
            <div class="tabla-header">
                <h2>
                    <?php if ($filtro === 'hoy'): ?>Turnos de hoy
                    <?php elseif ($filtro === 'mes'): ?>Turnos de <?= date('F Y') ?>
                    <?php else: ?>Turnos del <?= date('d/m/Y', strtotime($fecha_desde)) ?> al <?= date('d/m/Y', strtotime($fecha_hasta)) ?>
                    <?php endif; ?>
                </h2>
                <span class="badge-total"><?= count($turnos) ?> turnos</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <?php if ($filtro !== 'hoy'): ?><th>Fecha</th><?php endif; ?>
                        <th>Hora</th>
                        <th>Paciente</th>
                        <th>Especialidad</th>
                        <th>Consultorio</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($turnos)): ?>
                        <tr>
                            <td colspan="<?= $filtro !== 'hoy' ? 6 : 5 ?>" class="sin-registros">
                                No tenés turnos para el período seleccionado
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($turnos as $t): ?>
                        <tr>
                            <?php if ($filtro !== 'hoy'): ?>
                                <td><?= date('d/m/Y', strtotime($t['fecha'])) ?></td>
                            <?php endif; ?>
                            <td class="hora"><?= date('H:i', strtotime($t['hora'])) ?></td>
                            <td>
                                <div class="paciente-nombre"><?= htmlspecialchars($t['paciente']) ?></div>
                                <div class="paciente-dni">DNI: <?= htmlspecialchars($t['dni']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($t['especialidad']) ?></td>
                            <td>Consultorio <?= htmlspecialchars($t['consultorio']) ?></td>
                            <td>
                                <span class="estado <?= $t['estado'] ?>"><?= $t['estado'] ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../../vistas/plantillas/footer.php'; ?>