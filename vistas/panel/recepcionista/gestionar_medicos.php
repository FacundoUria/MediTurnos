<?php
/*
 * vistas/panel/recepcionista/gestionar_medicos.php
 * ---------------------------------------------------------------
 * Lista de médicos registrados con opción de resetear su
 * contraseña a un valor nuevo y aleatorio (MED#<matricula>#XXXX).
 * ---------------------------------------------------------------
 */
session_start();

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Recepcionista', 'Administrador'])) {
    header('Location: /mediturnos/vistas/autenticacion/login.php');
    exit;
}

require_once __DIR__ . '/../../../configuracion/conexion.php';
require_once __DIR__ . '/../../../controladores/TurnoControlador.php';

$controlador = new TurnoControlador($pdo);
$medicos     = $controlador->obtenerMedicosConDetalles();

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

    /* ── Recuadro de credenciales ── */
    .credenciales-card {
        background: var(--blanco);
        border: 1.5px solid #22c55e;
        border-radius: var(--radio);
        box-shadow: var(--sombra);
        padding: 1.6rem 2rem;
        max-width: 750px;
        margin-bottom: 1.5rem;
    }

    .credenciales-card h3 { font-size: 1rem; font-weight: 600; color: var(--texto); margin-bottom: 1rem; }

    .credenciales-datos {
        background: var(--fondo);
        border-radius: var(--radio);
        padding: 1rem 1.2rem;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.95rem;
        color: var(--texto);
        line-height: 1.8;
    }

    .credenciales-datos strong { color: var(--primario); }

    .credenciales-aviso {
        margin-top: 1rem;
        font-size: 0.85rem;
        color: var(--texto-suave);
    }

    /* ── Tabla de médicos ── */
    .tabla-card {
        background: var(--blanco);
        border-radius: var(--radio);
        box-shadow: var(--sombra);
        padding: 1.6rem 2rem;
        max-width: 1000px;
    }

    .tabla-card h3 { font-size: 1rem; font-weight: 600; color: var(--texto); margin-bottom: 1rem; }

    .tabla-medicos { width: 100%; border-collapse: collapse; font-size: 0.90rem; }

    .tabla-medicos th,
    .tabla-medicos td {
        text-align: left;
        padding: 0.7rem 0.8rem;
        border-bottom: 1px solid var(--borde);
    }

    .tabla-medicos th {
        color: var(--texto-suave);
        font-size: 0.78rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .tabla-medicos tr:last-child td { border-bottom: none; }

    .boton-reset {
        padding: 0.5rem 1rem;
        border-radius: var(--radio);
        border: 1.5px solid var(--primario);
        background: white;
        color: var(--primario);
        font-family: 'DM Sans', sans-serif;
        font-size: 0.85rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }

    .boton-reset:hover { background: var(--primario); color: #fff; }

    .boton-secundario {
        margin-top: 1rem;
        padding: 0.6rem 1.2rem;
        border-radius: var(--radio);
        border: 1.5px solid var(--borde);
        background: white;
        color: var(--texto);
        font-family: 'DM Sans', sans-serif;
        font-size: 0.88rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    .boton-secundario:hover { border-color: var(--primario); color: var(--primario); }
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
        <a href="suspender.php" class="sidebar-link">
            <span class="icono">🚫</span> Suspender agenda
        </a>
        <a href="paciente.php" class="sidebar-link">
            <span class="icono">🔍</span> Buscar paciente
        </a>
        <a href="registrar_medico.php" class="sidebar-link">
            <span class="icono">👨‍⚕️</span> Registrar médico
        </a>
        <a href="gestionar_medicos.php" class="sidebar-link activo">
            <span class="icono">📋</span> Gestionar médicos
        </a>

        <div class="sidebar-footer">
            <a href="/mediturnos/vistas/autenticacion/logout.php" class="sidebar-link">
                <span class="icono">🚪</span> Cerrar sesión
            </a>
        </div>
    </aside>

    <main class="contenido">

        <div class="encabezado-pagina">
            <h1>Gestionar médicos</h1>
            <p>Listado de médicos registrados y reseteo de contraseña de acceso</p>
        </div>

        <div id="credenciales-card" class="credenciales-card" style="display:none;">
            <h3 id="credenciales-titulo"></h3>
            <div class="credenciales-datos">
                <strong>Usuario:</strong> <span id="credenciales-dni"></span><br>
                <strong>Contraseña:</strong> <span id="credenciales-password"></span>
            </div>
            <p class="credenciales-aviso">⚠️ Anotá estas credenciales antes de cerrar.</p>
            <button type="button" class="boton-secundario" onclick="cerrarCredenciales()">Cerrar</button>
        </div>

        <div id="error-card" class="alerta-error" style="display:none; margin-bottom:1.5rem;"></div>

        <div class="tabla-card">
            <h3>Médicos registrados</h3>
            <table class="tabla-medicos">
                <thead>
                    <tr>
                        <th>Matrícula</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Especialidad</th>
                        <th>Usuario (DNI)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($medicos as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['matricula']) ?></td>
                            <td><?= htmlspecialchars($m['nombre']) ?></td>
                            <td><?= htmlspecialchars($m['apellido']) ?></td>
                            <td><?= htmlspecialchars($m['especialidades'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($m['dni_username'] ?? '—') ?></td>
                            <td>
                                <?php if ($m['dni_username']): ?>
                                    <button type="button" class="boton-reset"
                                        onclick="resetearPassword('<?= htmlspecialchars($m['matricula'], ENT_QUOTES) ?>', '<?= htmlspecialchars($m['apellido'], ENT_QUOTES) ?>')">
                                        Resetear contraseña
                                    </button>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

<script>
    function cerrarCredenciales() {
        document.getElementById('credenciales-card').style.display = 'none';
    }

    function cerrarError() {
        document.getElementById('error-card').style.display = 'none';
    }

    async function resetearPassword(matricula, apellido) {
        if (!confirm('¿Resetear la contraseña de Dr/a. ' + apellido + '? Se generará una contraseña nueva.')) {
            return;
        }

        cerrarCredenciales();
        cerrarError();

        try {
            const respuesta = await fetch('ajax/resetear_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'matricula=' + encodeURIComponent(matricula),
            });

            const datos = await respuesta.json();

            if (datos.exito) {
                const ahora = new Date();
                const fecha = ahora.toLocaleDateString();
                const hora  = ahora.toLocaleTimeString();

                document.getElementById('credenciales-titulo').textContent =
                    '✅ Contraseña reseteada — ' + datos.nombre + ' (reseteado el ' + fecha + ' a las ' + hora + ')';
                document.getElementById('credenciales-dni').textContent      = datos.dni;
                document.getElementById('credenciales-password').textContent = datos.password;
                document.getElementById('credenciales-card').style.display   = 'block';
            } else {
                const errorCard = document.getElementById('error-card');
                errorCard.textContent = '❌ ' + (datos.mensaje || 'Error al resetear');
                errorCard.style.display = 'block';
            }
        } catch (e) {
            const errorCard = document.getElementById('error-card');
            errorCard.textContent = '❌ Error de conexión al resetear la contraseña.';
            errorCard.style.display = 'block';
        }
    }
</script>

<?php require_once __DIR__ . '/../../../vistas/plantillas/footer.php'; ?>
