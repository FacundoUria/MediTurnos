<?php
/*
 * vistas/panel/recepcionista/suspender.php
 * ---------------------------------------------------------------
 * Módulo de suspensión de agenda médica.
 * El recepcionista elige un médico y una fecha.
 * El sistema cancela todos los turnos pendientes de ese día
 * en una sola transacción ACID — todo o nada.
 * ---------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../configuracion/conexion.php';
require_once __DIR__ . '/../../../controladores/TurnoControlador.php';

$controlador = new TurnoControlador($pdo);
$medicos     = $controlador->obtenerDatosFormulario()['medicos'];

$mensaje      = '';
$tipo_msg     = '';
$turnos_afectados = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricula = $_POST['matricula'] ?? '';
    $fecha     = $_POST['fecha']     ?? '';

    if (empty($matricula) || empty($fecha)) {
        $mensaje  = '⚠️ Completá todos los campos.';
        $tipo_msg = 'error';
    } else {
        try {
            $turnos_afectados = $controlador->suspenderAgenda($matricula, $fecha);
            $mensaje  = "✅ Agenda suspendida correctamente. Se cancelaron {$turnos_afectados} turno(s) y se bloqueó la fecha.";
            $tipo_msg = 'exito';
        } catch (Exception $e) {
            $mensaje  = '❌ ' . $e->getMessage();
            $tipo_msg = 'error';
        }
    }
}

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

    .encabezado-pagina { margin-bottom: 2rem; }
    .encabezado-pagina h1 { font-size: 1.5rem; font-weight: 600; color: var(--texto); }
    .encabezado-pagina p  { color: var(--texto-suave); font-size: 0.92rem; margin-top: 0.2rem; }

    /* ── Aviso importante ── */
    .aviso-critico {
        background: #fff7ed;
        border: 1.5px solid #fed7aa;
        border-radius: var(--radio);
        padding: 1rem 1.4rem;
        margin-bottom: 1.5rem;
        font-size: 0.90rem;
        color: #9a3412;
        max-width: 700px;
    }

    .aviso-critico strong { display: block; margin-bottom: 0.3rem; font-size: 0.95rem; }

    /* ── Tarjeta del formulario ── */
    .form-card {
        background: var(--blanco);
        border-radius: var(--radio);
        box-shadow: var(--sombra);
        padding: 2rem;
        max-width: 700px;
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

    .grupo-campo { display: flex; flex-direction: column; gap: 0.4rem; }
    .grupo-campo label { font-size: 0.88rem; font-weight: 500; color: var(--texto); }

    select.campo {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7c93' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        cursor: pointer;
    }

    .separador { border: none; border-top: 1px solid var(--borde); margin: 1.5rem 0; }

    .acciones { display: flex; gap: 1rem; margin-top: 1.8rem; }

    .boton-secundario {
        padding: 0.85rem 1.5rem;
        border-radius: var(--radio);
        border: 1.5px solid var(--borde);
        background: white;
        color: var(--texto);
        font-family: 'DM Sans', sans-serif;
        font-size: 0.95rem;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s;
    }

    .boton-secundario:hover { border-color: var(--primario); color: var(--primario); }

    /* Botón de suspender — rojo para indicar acción crítica */
    .boton-peligro {
        flex: 1;
        padding: 0.9rem;
        background: #dc2626;
        color: white;
        border: none;
        border-radius: var(--radio);
        font-family: 'DM Sans', sans-serif;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
    }

    .boton-peligro:hover { background: #b91c1c; }

    /* ── Resumen de resultado ── */
    .resultado-card {
        background: var(--blanco);
        border-radius: var(--radio);
        box-shadow: var(--sombra);
        padding: 1.6rem 2rem;
        max-width: 700px;
        margin-top: 1.5rem;
        border-left: 4px solid #22c55e;
    }

    .resultado-card h3 { font-size: 1rem; font-weight: 600; color: var(--texto); margin-bottom: 0.5rem; }
    .resultado-card p  { font-size: 0.90rem; color: var(--texto-suave); }
    .resultado-numero  { font-size: 2rem; font-weight: 700; color: #16a34a; margin: 0.5rem 0; }
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
        <a href="paciente.php" class="sidebar-link">
            <span class="icono">🔍</span> Buscar paciente
        </a>
        <a href="suspender.php" class="sidebar-link activo">
            <span class="icono">🚫</span> Suspender agenda
        </a>

        <div class="sidebar-footer">
            <a href="/mediturnos/vistas/autenticacion/logout.php" class="sidebar-link">
                <span class="icono">🚪</span> Cerrar sesión
            </a>
        </div>
    </aside>

    <main class="contenido">

        <div class="encabezado-pagina">
            <h1>Suspender agenda médica</h1>
            <p>Cancelá todos los turnos de un médico en una fecha específica</p>
        </div>

        <!-- Aviso importante -->
        <div class="aviso-critico">
            <strong>⚠️ Acción crítica e irreversible</strong>
            Al confirmar, se cancelarán <strong>todos los turnos pendientes</strong> del médico
            en la fecha seleccionada y se bloqueará esa fecha para nuevas reservas.
            Esta operación usa una transacción ACID — si algo falla, nada se modifica.
        </div>

        <?php if ($mensaje): ?>
            <div class="alerta-<?= $tipo_msg ?>" style="margin-bottom:1.5rem;max-width:700px;">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <!-- Formulario -->
        <div class="form-card">
            <form method="POST" action=""
                  onsubmit="return confirm('¿Confirmar suspensión de agenda?\n\nEsta acción cancelará TODOS los turnos pendientes del médico en la fecha seleccionada.\n\n¿Estás seguro?')">

                <p class="seccion-titulo">Datos de la suspensión</p>

                <div class="grilla-2">
                    <div class="grupo-campo">
                        <label for="matricula">Médico</label>
                        <select name="matricula" id="matricula" class="campo" required>
                            <option value="">— Seleccioná un médico —</option>
                            <?php foreach ($medicos as $m): ?>
                                <option value="<?= $m['matricula'] ?>">
                                    Dr/a. <?= htmlspecialchars($m['apellido'] . ', ' . $m['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grupo-campo">
                        <label for="fecha">Fecha a suspender</label>
                        <input
                            type="date"
                            id="fecha"
                            name="fecha"
                            class="campo"
                            required
                            min="<?= date('Y-m-d') ?>"
                        >
                    </div>
                </div>

                <hr class="separador">

                <div class="acciones">
                    <a href="index.php" class="boton-secundario">← Volver</a>
                    <button type="submit" class="boton-peligro">
                        🚫 Confirmar suspensión
                    </button>
                </div>

            </form>
        </div>

        <!-- Resultado si hubo éxito -->
        <?php if ($tipo_msg === 'exito' && $turnos_afectados > 0): ?>
        <div class="resultado-card">
            <h3>Resumen de la operación</h3>
            <div class="resultado-numero"><?= $turnos_afectados ?></div>
            <p>
                turno(s) cancelado(s) correctamente.<br>
                Todos los cambios fueron registrados en el historial.<br>
                La fecha quedó bloqueada para nuevas reservas.
            </p>
        </div>
        <?php endif; ?>

    </main>
</div>

<?php require_once __DIR__ . '/../../../vistas/plantillas/footer.php'; ?>