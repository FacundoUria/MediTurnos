<?php
/*
 * vistas/panel/recepcionista/paciente.php
 * ---------------------------------------------------------------
 * Pantalla para buscar un paciente y ver todos sus turnos.
 * El recepcionista escribe el nombre o DNI, selecciona el paciente
 * y ve su historial completo de turnos.
 * ---------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../configuracion/conexion.php';
require_once __DIR__ . '/../../../controladores/TurnoControlador.php';

$controlador = new TurnoControlador($pdo);
$pacientes   = $controlador->obtenerDatosFormulario()['pacientes'];

// Si se seleccionó un paciente, traemos sus datos y turnos
$paciente_seleccionado = null;
$turnos_paciente       = [];

if (isset($_GET['id_paciente']) && !empty($_GET['id_paciente'])) {
    $id = (int)$_GET['id_paciente'];
    $paciente_seleccionado = $controlador->obtenerPaciente($id);
    $turnos_paciente       = $controlador->obtenerTurnosPaciente($id);
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

    /* ── Buscador ── */
    .buscador-card {
        background: var(--blanco);
        border-radius: var(--radio);
        box-shadow: var(--sombra);
        padding: 1.4rem 1.6rem;
        margin-bottom: 1.5rem;
        max-width: 750px;
    }

    .buscador-card label {
        display: block;
        font-size: 0.88rem;
        font-weight: 500;
        color: var(--texto);
        margin-bottom: 0.4rem;
    }

    .buscador-wrapper { position: relative; }

    .resultados-lista {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1.5px solid var(--primario);
        border-radius: 0 0 var(--radio) var(--radio);
        max-height: 220px;
        overflow-y: auto;
        z-index: 100;
        display: none;
        box-shadow: var(--sombra);
    }

    .resultado-item {
        padding: 0.75rem 1rem;
        cursor: pointer;
        font-size: 0.90rem;
        border-bottom: 1px solid var(--borde);
        transition: background 0.15s;
        text-decoration: none;
        display: block;
        color: var(--texto);
    }

    .resultado-item:last-child { border-bottom: none; }
    .resultado-item:hover { background: var(--acento); }
    .resultado-item .dni { font-size: 0.78rem; color: var(--texto-suave); margin-top: 0.1rem; }

    /* ── Ficha del paciente ── */
    .ficha-card {
        background: var(--blanco);
        border-radius: var(--radio);
        box-shadow: var(--sombra);
        margin-bottom: 1.5rem;
        overflow: hidden;
        max-width: 750px;
    }

    .ficha-header {
        background: var(--primario);
        padding: 1.2rem 1.6rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .ficha-header h2 { color: white; font-size: 1.1rem; font-weight: 600; }
    .ficha-header p  { color: rgba(255,255,255,0.75); font-size: 0.85rem; margin-top: 0.2rem; }

    .ficha-datos {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0;
        border-bottom: 1px solid var(--borde);
    }

    .ficha-dato {
        padding: 1rem 1.4rem;
        border-right: 1px solid var(--borde);
    }

    .ficha-dato:last-child { border-right: none; }
    .ficha-dato .etiqueta { font-size: 0.75rem; color: var(--texto-suave); text-transform: uppercase; letter-spacing: 0.05em; }
    .ficha-dato .valor    { font-size: 0.92rem; font-weight: 500; color: var(--texto); margin-top: 0.2rem; }

    .boton-reservar-paciente {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 1rem;
        background: rgba(255,255,255,0.15);
        color: white;
        border: 1.5px solid rgba(255,255,255,0.3);
        border-radius: 8px;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.85rem;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s;
    }

    .boton-reservar-paciente:hover { background: rgba(255,255,255,0.25); }

    /* ── Tabla de turnos ── */
    .tabla-card {
        background: var(--blanco);
        border-radius: var(--radio);
        box-shadow: var(--sombra);
        overflow: hidden;
        max-width: 750px;
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

    /* ── Estado vacío inicial ── */
    .estado-vacio {
        text-align: center;
        padding: 4rem 2rem;
        color: var(--texto-suave);
        max-width: 750px;
    }

    .estado-vacio .icono-grande { font-size: 3rem; margin-bottom: 1rem; }
    .estado-vacio h3 { font-size: 1.1rem; color: var(--texto); margin-bottom: 0.5rem; }
    .estado-vacio p  { font-size: 0.90rem; }
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
        <a href="cancelar.php" class="sidebar-link">
            <span class="icono">❌</span> Cancelar turno
        </a>
        <a href="paciente.php" class="sidebar-link activo">
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
            <h1>Buscar paciente</h1>
            <p>Buscá un paciente para ver su historial de turnos</p>
        </div>

        <!-- Buscador -->
        <div class="buscador-card">
            <label for="buscador-paciente">Nombre o DNI</label>
            <div class="buscador-wrapper">
                <input
                    type="text"
                    id="buscador-paciente"
                    class="campo"
                    placeholder="Escribí el nombre o DNI..."
                    autocomplete="off"
                    oninput="filtrarPacientes(this.value)"
                    onfocus="mostrarResultados()"
                >
                <div class="resultados-lista" id="resultados-lista"></div>
            </div>
        </div>

        <?php if ($paciente_seleccionado): ?>

            <!-- Ficha del paciente -->
            <div class="ficha-card">
                <div class="ficha-header">
                    <div>
                        <h2>👤 <?= htmlspecialchars($paciente_seleccionado['apellido'] . ', ' . $paciente_seleccionado['nombre']) ?></h2>
                        <p>DNI: <?= htmlspecialchars($paciente_seleccionado['dni']) ?></p>
                    </div>
                    <a href="reservar.php" class="boton-reservar-paciente">
                        ➕ Reservar turno
                    </a>
                </div>
                <div class="ficha-datos">
                    <div class="ficha-dato">
                        <div class="etiqueta">Teléfono</div>
                        <div class="valor"><?= htmlspecialchars($paciente_seleccionado['telefono'] ?? '—') ?></div>
                    </div>
                    <div class="ficha-dato">
                        <div class="etiqueta">Email</div>
                        <div class="valor"><?= htmlspecialchars($paciente_seleccionado['email'] ?? '—') ?></div>
                    </div>
                    <div class="ficha-dato">
                        <div class="etiqueta">Total de turnos</div>
                        <div class="valor"><?= count($turnos_paciente) ?></div>
                    </div>
                </div>
            </div>

            <!-- Tabla de turnos del paciente -->
            <div class="tabla-card">
                <div class="tabla-header">
                    <h2>Historial de turnos</h2>
                    <span class="badge-total"><?= count($turnos_paciente) ?> turnos</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Médico</th>
                            <th>Especialidad</th>
                            <th>Consultorio</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($turnos_paciente)): ?>
                            <tr>
                                <td colspan="6" class="sin-registros">
                                    Este paciente no tiene turnos registrados
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($turnos_paciente as $turno): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($turno['fecha'])) ?></td>
                                <td><?= date('H:i', strtotime($turno['hora'])) ?></td>
                                <td><?= htmlspecialchars($turno['medico']) ?></td>
                                <td><?= htmlspecialchars($turno['especialidad']) ?></td>
                                <td><?= htmlspecialchars($turno['consultorio']) ?></td>
                                <td>
                                    <span class="estado <?= $turno['estado'] ?>">
                                        <?= $turno['estado'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>

            <!-- Estado inicial sin paciente seleccionado -->
            <div class="estado-vacio">
                <div class="icono-grande">🔍</div>
                <h3>Buscá un paciente para ver su historial</h3>
                <p>Escribí el nombre o DNI en el buscador de arriba</p>
            </div>

        <?php endif; ?>

    </main>
</div>

<script>
    const pacientes = <?= json_encode($pacientes) ?>;

    function filtrarPacientes(texto) {
        const lista = document.getElementById('resultados-lista');

        if (texto.length < 2) {
            lista.style.display = 'none';
            return;
        }

        const busqueda = texto.toLowerCase();
        const filtrados = pacientes.filter(p =>
            p.apellido.toLowerCase().includes(busqueda) ||
            p.nombre.toLowerCase().includes(busqueda)   ||
            p.dni.includes(busqueda)
        );

        if (filtrados.length === 0) {
            lista.innerHTML = '<div class="resultado-item">No se encontraron resultados</div>';
        } else {
            lista.innerHTML = filtrados.map(p => `
                <a href="paciente.php?id_paciente=${p.id_paciente}" class="resultado-item">
                    <div>${p.apellido}, ${p.nombre}</div>
                    <div class="dni">DNI: ${p.dni}</div>
                </a>
            `).join('');
        }

        lista.style.display = 'block';
    }

    function mostrarResultados() {
        const texto = document.getElementById('buscador-paciente').value;
        if (texto.length >= 2) {
            document.getElementById('resultados-lista').style.display = 'block';
        }
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.buscador-wrapper')) {
            document.getElementById('resultados-lista').style.display = 'none';
        }
    });
</script>

<?php require_once __DIR__ . '/../../../vistas/plantillas/footer.php'; ?>