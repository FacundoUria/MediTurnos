<?php
/*
 * vistas/panel/recepcionista/index.php
 * ---------------------------------------------------------------
 * Panel principal del recepcionista.
 * Muestra los KPIs del día y la agenda de turnos.
 * Los datos vienen de la DB a través del TurnoControlador.
 * ---------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../configuracion/conexion.php';
require_once __DIR__ . '/../../../controladores/TurnoControlador.php';

// Creamos el controlador pasándole la conexión PDO
$controlador = new TurnoControlador($pdo);

// Le pedimos los datos al controlador — él se los pide al modelo
$turnos = $controlador->obtenerAgendaHoy();
$kpis   = $controlador->obtenerKpis();

// Extraemos los números para los KPIs
$turnos_hoy        = $kpis['total']      ?? 0;
$turnos_pendientes = $kpis['pendientes'] ?? 0;  
$turnos_cancelados = $kpis['cancelados'] ?? 0;

require_once __DIR__ . '/../../../vistas/plantillas/header.php';
?>

<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    .layout {
        display: flex;
        min-height: 100vh;
        width: 100%;
    }

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
        margin-bottom: 1.5rem;
    }

    .sidebar-logo span {
        font-family: 'DM Serif Display', serif;
        color: #fff;
        font-size: 1.3rem;
    }

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

    .sidebar-link:hover { background: rgba(255,255,255,0.12); color: #fff; }
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
    .kpi-icono.rojo  { background: #fff5f5; }

    .kpi-numero { font-size: 1.8rem; font-weight: 600; color: var(--texto); line-height: 1; }
    .kpi-label  { font-size: 0.82rem; color: var(--texto-suave); margin-top: 0.2rem; }

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

    .boton-reservar {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.6rem 1.1rem;
        background: var(--primario);
        color: white;
        border: none;
        border-radius: 8px;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.88rem;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        transition: background 0.2s;
    }

    .boton-reservar:hover { background: var(--primario-osc); }

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

    .btn-accion {
        padding: 0.35rem 0.8rem;
        border-radius: 6px;
        font-size: 0.80rem;
        font-weight: 500;
        border: 1.5px solid var(--borde);
        background: white;
        color: var(--texto);
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s;
    }

    .btn-accion:hover { border-color: var(--primario); color: var(--primario); }

    /* Mensaje cuando no hay turnos */
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

        <span class="sidebar-seccion">Menú</span>

        <a href="index.php" class="sidebar-link activo">
            <span class="icono">📅</span> Agenda del día
        </a>
        <a href="reservar.php" class="sidebar-link">
            <span class="icono">➕</span> Reservar turno
        </a>
        <a href="cancelar.php" class="sidebar-link">
            <span class="icono">❌</span> Cancelar turno
        </a>
        
        <a href="paciente.php" class="sidebar-link">
    <span class="icono">🔍</span> Buscar paciente
</a>

        <div class="sidebar-footer">
            <a href="/mediturnos/vistas/autenticacion/login.php" class="sidebar-link">
                <span class="icono">🚪</span> Cerrar sesión
            </a>
        </div>
    </aside>

    <main class="contenido">

        <div class="encabezado-pagina">
            <h1>Agenda del día</h1>
            <p><?= strftime('%A %d de %B de %Y') ?? date('d/m/Y') ?></p>
        </div>

        <!-- KPIs con datos reales de la DB -->
        <div class="kpis">
            <div class="kpi">
                <div class="kpi-icono azul">📅</div>
                <div>
                    <div class="kpi-numero"><?= $turnos_hoy ?></div>
                    <div class="kpi-label">Turnos hoy</div>
                </div>
            </div>
            <div class="kpi">
                <div class="kpi-icono verde">⏳</div>
                <div>
                    <div class="kpi-numero"><?= $turnos_pendientes ?></div>
                    <div class="kpi-label">Pendientes</div>
                </div>
            </div>
            <div class="kpi">
                <div class="kpi-icono rojo">❌</div>
                <div>
                    <div class="kpi-numero"><?= $turnos_cancelados ?></div>
                    <div class="kpi-label">Cancelados</div>
                </div>
            </div>
        </div>

        <!-- Tabla con datos reales de la DB -->
        <div class="tabla-card">
            <div class="tabla-header">
                <h2>Turnos de hoy</h2>
                <a href="reservar.php" class="boton-reservar">＋ Nuevo turno</a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Hora</th>
                        <th>Paciente</th>
                        <th>Médico</th>
                        <th>Especialidad</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($turnos)): ?>
                        <tr>
                            <td colspan="6" class="sin-registros">
                                No hay turnos programados para hoy
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($turnos as $turno): ?>
                        <tr>
                            <td class="hora"><?= date('H:i', strtotime($turno['hora'])) ?></td>
                            <td><?= htmlspecialchars($turno['paciente']) ?></td>
                            <td><?= htmlspecialchars($turno['medico']) ?></td>
                            <td><?= htmlspecialchars($turno['especialidad']) ?></td>
                            <td>
                                <span class="estado <?= $turno['estado'] ?>">
                                    <?= $turno['estado'] ?>
                                </span>
                            </td>
                            <td>
                                <a href="cancelar.php?id=<?= $turno['id_turno'] ?>" class="btn-accion">
                                    Gestionar
                                </a>
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