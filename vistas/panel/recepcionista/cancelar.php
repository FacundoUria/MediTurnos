<?php
/*
 * vistas/panel/recepcionista/cancelar.php
 * ---------------------------------------------------------------
 * Pantalla para buscar y cancelar un turno.
 * Por ahora los datos son de ejemplo (hardcodeados).
 * Cuando conectemos el backend van a venir de la DB.
 * ---------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../vistas/plantillas/header.php';

$turnos = [
    ['id' => 1, 'hora' => '08:00', 'paciente' => 'García, Juan',     'medico' => 'Dr. Pérez',   'especialidad' => 'Cardiología',    'estado' => 'Confirmado'],
    ['id' => 2, 'hora' => '08:30', 'paciente' => 'López, María',     'medico' => 'Dra. Romero', 'especialidad' => 'Pediatría',      'estado' => 'Pendiente'],
    ['id' => 3, 'hora' => '09:00', 'paciente' => 'Martínez, Carlos', 'medico' => 'Dr. Gómez',   'especialidad' => 'Clínica Médica', 'estado' => 'Pendiente'],
    ['id' => 4, 'hora' => '10:00', 'paciente' => 'Fernández, Laura', 'medico' => 'Dra. Torres', 'especialidad' => 'Dermatología',   'estado' => 'Confirmado'],
    ['id' => 5, 'hora' => '10:30', 'paciente' => 'Rodríguez, Pablo', 'medico' => 'Dr. Gómez',   'especialidad' => 'Clínica Médica', 'estado' => 'Cancelado'],
];
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

    .buscador-card label {
        font-size: 0.88rem;
        font-weight: 500;
        color: var(--texto);
    }

    .boton-buscar {
        padding: 0.85rem 1.4rem;
        background: var(--primario);
        color: white;
        border: none;
        border-radius: var(--radio);
        font-family: 'DM Sans', sans-serif;
        font-size: 0.92rem;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.2s;
        white-space: nowrap;
    }

    .boton-buscar:hover { background: var(--primario-osc); }

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
    }

    .tabla-header h2 { font-size: 1rem; font-weight: 600; color: var(--texto); }

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

        <div class="aviso">
            ℹ️ Al cancelar un turno el registro <strong>no se elimina</strong> — queda guardado en el historial con estado <strong>Cancelado</strong>.
        </div>

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
            <button class="boton-buscar">🔍 Buscar</button>
        </div>

        <div class="tabla-card">
            <div class="tabla-header">
                <h2>Turnos activos</h2>
            </div>
            <table id="tabla-turnos">
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
                    <?php foreach ($turnos as $turno): ?>
                    <tr>
                        <td class="hora"><?= $turno['hora'] ?></td>
                        <td><?= htmlspecialchars($turno['paciente']) ?></td>
                        <td><?= htmlspecialchars($turno['medico']) ?></td>
                        <td><?= htmlspecialchars($turno['especialidad']) ?></td>
                        <td>
                            <span class="estado <?= $turno['estado'] ?>">
                                <?= $turno['estado'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($turno['estado'] !== 'Cancelado' && $turno['estado'] !== 'Realizado'): ?>
                                <button
                                    class="btn-cancelar"
                                    onclick="confirmarCancelacion(<?= $turno['id'] ?>, '<?= htmlspecialchars($turno['paciente']) ?>')"
                                >
                                    Cancelar
                                </button>
                            <?php else: ?>
                                <button class="btn-cancelar deshabilitado">Cancelar</button>
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
    function filtrarTabla(texto) {
        const filas = document.querySelectorAll('#tabla-turnos tbody tr');
        const busqueda = texto.toLowerCase();
        filas.forEach(fila => {
            fila.style.display = fila.textContent.toLowerCase().includes(busqueda) ? '' : 'none';
        });
    }

    function confirmarCancelacion(id, paciente) {
        if (confirm(`¿Cancelar el turno de ${paciente}?\n\nEsta acción quedará registrada en el historial.`)) {
            alert('Turno cancelado. (Conectar con backend)');
        }
    }
</script>

<?php require_once __DIR__ . '/../../../vistas/plantillas/footer.php'; ?>