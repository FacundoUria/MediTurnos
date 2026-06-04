<?php
/*
 * vistas/panel/recepcionista/cancelar.php
 * ---------------------------------------------------------------
 * Pantalla para buscar y cancelar un turno.
 * Los datos vienen de la DB a través del TurnoControlador.
 * Al cancelar llama al SP CancelarTurno que también dispara
 * el trigger LogTurno para registrar el cambio en el historial.
 * ---------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../configuracion/conexion.php';
require_once __DIR__ . '/../../../controladores/TurnoControlador.php';

$controlador = new TurnoControlador($pdo);

$mensaje  = '';
$tipo_msg = '';

// Si se envió una cancelación la procesamos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_turno'])) {
    try {
        $controlador->cancelarTurno((int)$_POST['id_turno']);
        $mensaje  = '✅ Turno cancelado correctamente.';
        $tipo_msg = 'exito';
    } catch (PDOException $e) {
        $mensaje  = '❌ ' . $e->getMessage();
        $tipo_msg = 'error';
    }
}

// Traemos los turnos activos de la DB
$turnos = $controlador->obtenerTurnosActivos();

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
        margin-bottom: 1.5rem;
    }

    .sidebar-logo span { font-family: 'DM Serif Display', serif; color: #fff; font-size: 1.3rem; }

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

    .encabezado-pagina { margin-bottom: 1.5rem; }
    .encabezado-pagina h1 { font-size: 1.5rem; font-weight: 600; color: var(--texto); }
    .encabezado-pagina p  { color: var(--texto-suave); font-size: 0.92rem; margin-top: 0.2rem; }

    .aviso {
        background: #eff6ff;
        border: 1.5px solid #bfdbfe;
        border-radius: var(--radio);
        padding: 0.9rem 1.2rem;
        font-size: 0.88rem;
        color: #1d4ed8;
        margin-bottom: 1.5rem;
    }

    .buscador-card {
        background: var(--blanco);
        border-radius: var(--radio);
        box-shadow: var(--sombra);
        padding: 1.2rem 1.6rem;
        margin-bottom: 1.5rem;
        display: flex;
        gap: 1rem;
        align-items: flex-end;
        width: 100%;
    }

    .buscador-card .grupo-campo {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }

    .buscador-card label { font-size: 0.88rem; font-weight: 500; color: var(--texto); }

    .tabla-card {
        background: var(--blanco);
        border-radius: var(--radio);
        box-shadow: var(--sombra);
        overflow: hidden;
        width: 100%;
    }

    .tabla-header {
        padding: 1.2rem 1.6rem;
        border-bottom: 1px solid var(--borde);
        display: flex;
        align-items: center;
        justify-content: space-between;
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

    .estado {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 500;
    }

    .estado.Pendiente  { background: #fefce8; color: #854d0e; }
    .estado.Confirmado { background: #eff6ff; color: #1d4ed8; }
    .estado.Cancelado  { background: #fff5f5; color: #991b1b; }

    .btn-cancelar {
        padding: 0.35rem 0.8rem;
        border-radius: 6px;
        font-size: 0.80rem;
        font-weight: 500;
        border: 1.5px solid #feb2b2;
        background: white;
        color: var(--error);
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-cancelar:hover { background: #fff5f5; }

    .btn-cancelar.deshabilitado {
        opacity: 0.35;
        cursor: not-allowed;
        pointer-events: none;
    }

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

        <a href="index.php" class="sidebar-link">
            <span class="icono">📅</span> Agenda del día
        </a>
        <a href="reservar.php" class="sidebar-link">
            <span class="icono">➕</span> Reservar turno
        </a>
        <a href="cancelar.php" class="sidebar-link activo">
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
            <h1>Cancelar turno</h1>
            <p>Buscá el turno que querés cancelar</p>
        </div>

        <?php if ($mensaje): ?>
            <div class="alerta-<?= $tipo_msg ?>" style="margin-bottom:1.5rem;">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <div class="aviso">
            ℹ️ Al cancelar un turno el registro <strong>no se elimina</strong> — queda guardado en el historial con estado <strong>Cancelado</strong>.
        </div>

        <!-- Buscador -->
        <div class="buscador-card">
            <div class="grupo-campo">
                <label for="buscar">Buscar por paciente o DNI</label>
                <input
                    type="text"
                    id="buscar"
                    class="campo"
                    placeholder="Ej: García o 35987654"
                    oninput="filtrarTabla(this.value)"
                >
            </div>
        </div>

        <!-- Tabla -->
        <div class="tabla-card">
            <div class="tabla-header">
                <h2>Turnos activos</h2>
                <span class="badge-total" id="badge-total"><?= count($turnos) ?> turnos</span>
            </div>
            <table id="tabla-turnos">
                <thead>
                    <tr>
                        <th>Fecha</th>
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
                            <td colspan="7" class="sin-registros">
                                No hay turnos activos
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($turnos as $turno): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($turno['fecha'])) ?></td>
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
                                <?php if ($turno['estado'] !== 'Cancelado'): ?>
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="id_turno" value="<?= $turno['id_turno'] ?>">
                                        <button
                                            type="submit"
                                            class="btn-cancelar"
                                            onclick="return confirm('¿Cancelar el turno de <?= htmlspecialchars($turno['paciente']) ?>?\n\nEsta acción quedará registrada en el historial.')"
                                        >
                                            Cancelar
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn-cancelar deshabilitado">Cancelar</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

<script>
    function filtrarTabla(texto) {
        const filas = document.querySelectorAll('#tabla-turnos tbody tr');
        const busqueda = texto.toLowerCase();
        let visibles = 0;

        filas.forEach(fila => {
            const contenido = fila.textContent.toLowerCase();
            const visible = contenido.includes(busqueda);
            fila.style.display = visible ? '' : 'none';
            if (visible) visibles++;
        });

        document.getElementById('badge-total').textContent = visibles + ' turnos';
    }
</script>

<?php require_once __DIR__ . '/../../../vistas/plantillas/footer.php'; ?>