<?php
/*
 * vistas/panel/recepcionista/reservar.php
 * ---------------------------------------------------------------
 * Formulario para reservar un turno nuevo.
 * Permite buscar paciente existente en tiempo real o registrar uno nuevo.
 * Los selects se cargan con datos reales de la DB.
 * Al enviar llama al SP ReservarTurno.
 * ---------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../configuracion/conexion.php';
require_once __DIR__ . '/../../../controladores/TurnoControlador.php';

$controlador = new TurnoControlador($pdo);
$datos       = $controlador->obtenerDatosFormulario();

$mensaje  = '';
$tipo_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_paciente = $_POST['tipo_paciente'] ?? '';
    try {
        if ($tipo_paciente === 'nuevo') {
            $id_paciente = $controlador->registrarPaciente([
                'nombre'   => trim($_POST['nuevo_nombre']   ?? ''),
                'apellido' => trim($_POST['nuevo_apellido'] ?? ''),
                'dni'      => trim($_POST['nuevo_dni']      ?? ''),
                'telefono' => trim($_POST['nuevo_telefono'] ?? ''),
                'email'    => trim($_POST['nuevo_email']    ?? ''),
            ]);
        } else {
            $id_paciente = $_POST['id_paciente'] ?? '';
        }

        $controlador->reservarTurno(
            $id_paciente,
            $_POST['matricula']       ?? '',
            $_POST['id_consultorio']  ?? '',
            $_POST['id_especialidad'] ?? '',
            $_POST['fecha']           ?? '',
            $_POST['hora']            ?? ''
        );

        $mensaje  = '✅ Turno reservado correctamente.';
        $tipo_msg = 'exito';

    } catch (PDOException $e) {
        $mensaje  = '❌ Error al reservar: ' . $e->getMessage();
        $tipo_msg = 'error';
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

    /* ── Tabs ── */
    .tabs {
        display: flex;
        gap: 0;
        margin-bottom: 1.5rem;
        background: var(--blanco);
        border-radius: var(--radio);
        padding: 0.4rem;
        box-shadow: var(--sombra);
        width: fit-content;
    }

    .tab {
        padding: 0.6rem 1.4rem;
        border-radius: 8px;
        border: none;
        background: transparent;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.92rem;
        font-weight: 500;
        color: var(--texto-suave);
        cursor: pointer;
        transition: all 0.2s;
    }

    .tab.activo { background: var(--primario); color: white; }

    /* ── Tarjeta del formulario ── */
    .form-card {
        background: var(--blanco);
        border-radius: var(--radio);
        box-shadow: var(--sombra);
        padding: 2rem;
        max-width: 750px;
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

    .separador { border: none; border-top: 1px solid var(--borde); margin: 1.5rem 0; }

    .seccion-titulo {
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--texto-suave);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 1rem;
    }

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

    .panel-paciente { display: none; }
    .panel-paciente.visible { display: block; }

    /* ── Buscador de pacientes ── */
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
    }

    .resultado-item:last-child { border-bottom: none; }
    .resultado-item:hover { background: var(--acento); }

    .resultado-item .dni {
        font-size: 0.78rem;
        color: var(--texto-suave);
        margin-top: 0.1rem;
    }

    /* Paciente seleccionado */
    .paciente-seleccionado {
        display: none;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 1rem;
        background: var(--acento);
        border: 1.5px solid var(--primario);
        border-radius: var(--radio);
        margin-top: 0.5rem;
    }

    .paciente-seleccionado.visible { display: flex; }

    .paciente-seleccionado span { font-size: 0.92rem; font-weight: 500; color: var(--primario); }

    .btn-limpiar {
        background: none;
        border: none;
        color: var(--texto-suave);
        cursor: pointer;
        font-size: 1rem;
        padding: 0.2rem 0.4rem;
        border-radius: 4px;
        transition: all 0.2s;
    }

    .btn-limpiar:hover { color: var(--error); background: #fff5f5; }
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
        <a href="reservar.php" class="sidebar-link activo">
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
            <h1>Reservar turno</h1>
            <p>Completá los datos para registrar un nuevo turno</p>
        </div>

        <?php if ($mensaje): ?>
            <div class="alerta-<?= $tipo_msg ?>" style="margin-bottom:1.5rem;">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab activo" onclick="cambiarTab('existente', this)">
                👤 Paciente existente
            </button>
            <button class="tab" onclick="cambiarTab('nuevo', this)">
                ➕ Paciente nuevo
            </button>
        </div>

        <div class="form-card">
            <form method="POST" action="">

                <input type="hidden" name="tipo_paciente" id="tipo_paciente" value="existente">
                <input type="hidden" name="id_paciente"   id="id_paciente_hidden" value="">

                <!-- ── Panel: paciente existente con buscador ── -->
                <div id="panel-existente" class="panel-paciente visible">
                    <p class="seccion-titulo">Buscá el paciente</p>
                    <div class="grupo-campo">
                        <label for="buscador-paciente">Nombre o DNI</label>
                        <div class="buscador-wrapper">
                            <input
                                type="text"
                                id="buscador-paciente"
                                class="campo"
                                placeholder="Escribí el nombre o DNI del paciente..."
                                autocomplete="off"
                                oninput="filtrarPacientes(this.value)"
                                onfocus="mostrarResultados()"
                            >
                            <div class="resultados-lista" id="resultados-lista"></div>
                        </div>
                        <div class="paciente-seleccionado" id="paciente-seleccionado">
                            <span id="nombre-seleccionado"></span>
                            <button type="button" class="btn-limpiar" onclick="limpiarPaciente()">✕</button>
                        </div>
                    </div>
                </div>

                <!-- ── Panel: paciente nuevo ── -->
                <div id="panel-nuevo" class="panel-paciente">
                    <p class="seccion-titulo">Datos del paciente nuevo</p>
                    <div class="grilla-2">
                        <div class="grupo-campo">
                            <label>Nombre</label>
                            <input type="text" name="nuevo_nombre" class="campo" placeholder="Ej: Juan">
                        </div>
                        <div class="grupo-campo">
                            <label>Apellido</label>
                            <input type="text" name="nuevo_apellido" class="campo" placeholder="Ej: García">
                        </div>
                        <div class="grupo-campo">
                            <label>DNI</label>
                            <input type="text" name="nuevo_dni" class="campo" placeholder="Ej: 35987654">
                        </div>
                        <div class="grupo-campo">
                            <label>Teléfono</label>
                            <input type="text" name="nuevo_telefono" class="campo" placeholder="Ej: 0261-4123456">
                        </div>
                        <div class="grupo-campo campo-completo">
                            <label>Email</label>
                            <input type="email" name="nuevo_email" class="campo" placeholder="Ej: juan@gmail.com">
                        </div>
                    </div>
                </div>

                <hr class="separador">

                <p class="seccion-titulo">Médico y especialidad</p>
                <div class="grilla-2">
                    <div class="grupo-campo">
                        <label for="id_especialidad">Especialidad</label>
                        <select name="id_especialidad" id="id_especialidad" class="campo" required>
                            <option value="">— Seleccioná —</option>
                            <?php foreach ($datos['especialidades'] as $e): ?>
                                <option value="<?= $e['id_especialidad'] ?>">
                                    <?= htmlspecialchars($e['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grupo-campo">
                        <label for="matricula">Médico</label>
                        <select name="matricula" id="matricula" class="campo" required>
                            <option value="">— Seleccioná —</option>
                            <?php foreach ($datos['medicos'] as $m): ?>
                                <option value="<?= $m['matricula'] ?>">
                                    Dr/a. <?= htmlspecialchars($m['apellido'] . ', ' . $m['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <hr class="separador">

                <p class="seccion-titulo">Fecha y lugar</p>
                <div class="grilla-2">
                    <div class="grupo-campo">
                        <label for="fecha">Fecha</label>
                        <input type="date" id="fecha" name="fecha" class="campo" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="grupo-campo">
                        <label for="hora">Hora</label>
                        <select name="hora" id="hora" class="campo" required>
                            <option value="">— Seleccioná —</option>
                            <?php
                            $horas = ['07:00','07:30','08:00','08:30','09:00','09:30','10:00','10:30',
                                      '11:00','11:30','12:00','14:00','14:30','15:00','15:30','16:00','16:30','17:00'];
                            foreach ($horas as $h): ?>
                                <option value="<?= $h ?>"><?= $h ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grupo-campo campo-completo">
                        <label for="id_consultorio">Consultorio</label>
                        <select name="id_consultorio" id="id_consultorio" class="campo" required>
                            <option value="">— Seleccioná —</option>
                            <?php foreach ($datos['consultorios'] as $c): ?>
                                <option value="<?= $c['id_consultorio'] ?>">
                                    Consultorio <?= htmlspecialchars($c['numero']) ?> — <?= htmlspecialchars($c['piso']) ?> piso
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="acciones">
                    <a href="index.php" class="boton-secundario">← Volver</a>
                    <button type="submit" class="boton-primario">Confirmar reserva</button>
                </div>

            </form>
        </div>

    </main>
</div>

<script>
    // Lista de pacientes cargada desde PHP para filtrar en el cliente
    const pacientes = <?= json_encode($datos['pacientes']) ?>;

    function filtrarPacientes(texto) {
        const lista = document.getElementById('resultados-lista');

        if (texto.length < 2) {
            lista.style.display = 'none';
            return;
        }

        const busqueda = texto.toLowerCase();
        const filtrados = pacientes.filter(p =>
            p.apellido.toLowerCase().includes(busqueda) ||
            p.nombre.toLowerCase().includes(busqueda) ||
            p.dni.includes(busqueda)
        );

        if (filtrados.length === 0) {
            lista.innerHTML = '<div class="resultado-item">No se encontraron resultados</div>';
        } else {
            lista.innerHTML = filtrados.map(p => `
                <div class="resultado-item" onclick="seleccionarPaciente(${p.id_paciente}, '${p.apellido}, ${p.nombre}', '${p.dni}')">
                    <div>${p.apellido}, ${p.nombre}</div>
                    <div class="dni">DNI: ${p.dni}</div>
                </div>
            `).join('');
        }

        lista.style.display = 'block';
    }

    function seleccionarPaciente(id, nombre, dni) {
        // Guardamos el id en el campo oculto
        document.getElementById('id_paciente_hidden').value = id;

        // Mostramos el nombre seleccionado
        document.getElementById('nombre-seleccionado').textContent = nombre + ' — DNI: ' + dni;
        document.getElementById('paciente-seleccionado').classList.add('visible');

        // Ocultamos la lista y limpiamos el buscador
        document.getElementById('resultados-lista').style.display = 'none';
        document.getElementById('buscador-paciente').value = '';
    }

    function limpiarPaciente() {
        document.getElementById('id_paciente_hidden').value = '';
        document.getElementById('paciente-seleccionado').classList.remove('visible');
        document.getElementById('buscador-paciente').value = '';
    }

    function mostrarResultados() {
        const texto = document.getElementById('buscador-paciente').value;
        if (texto.length >= 2) {
            document.getElementById('resultados-lista').style.display = 'block';
        }
    }

    // Cerrar la lista si clickeás afuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.buscador-wrapper')) {
            document.getElementById('resultados-lista').style.display = 'none';
        }
    });

    function cambiarTab(tipo, boton) {
        document.getElementById('tipo_paciente').value = tipo;
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('activo'));
        boton.classList.add('activo');
        document.getElementById('panel-existente').classList.remove('visible');
        document.getElementById('panel-nuevo').classList.remove('visible');
        document.getElementById('panel-' + tipo).classList.add('visible');
    }
</script>

<?php require_once __DIR__ . '/../../../vistas/plantillas/footer.php'; ?>