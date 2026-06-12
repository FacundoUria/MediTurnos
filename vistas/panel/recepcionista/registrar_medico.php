<?php
/*
 * vistas/panel/recepcionista/registrar_medico.php
 * ---------------------------------------------------------------
 * Alta de médicos: datos personales, especialidad, horario de
 * atención y usuario de acceso al sistema (rol Médico).
 * Todo se guarda en una sola transacción ACID.
 * ---------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../configuracion/conexion.php';
require_once __DIR__ . '/../../../controladores/TurnoControlador.php';

$controlador = new TurnoControlador($pdo);
$datos       = $controlador->obtenerDatosFormulario();

$mensaje     = '';
$tipo_msg    = '';
$credenciales = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $credenciales = $controlador->registrarMedico([
            'matricula'       => $_POST['matricula']       ?? '',
            'nombre'          => trim($_POST['nombre']          ?? ''),
            'apellido'        => trim($_POST['apellido']        ?? ''),
            'telefono'        => trim($_POST['telefono']        ?? ''),
            'email'           => trim($_POST['email']           ?? ''),
            'id_especialidad' => $_POST['id_especialidad'] ?? '',
            'dias'            => $_POST['dias']            ?? [],
            'hora_inicio'     => $_POST['hora_inicio']     ?? '',
            'hora_fin'        => $_POST['hora_fin']        ?? '',
            'id_consultorio'  => $_POST['id_consultorio']  ?? '',
            'dni_username'    => trim($_POST['dni_username'] ?? ''),
        ]);

        $mensaje  = '✅ Médico registrado correctamente.';
        $tipo_msg = 'exito';

    } catch (PDOException $e) {
        $mensaje  = '❌ Error al registrar: ' . $e->getMessage();
        $tipo_msg = 'error';
    } catch (Exception $e) {
        $mensaje  = '❌ ' . $e->getMessage();
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

    /* ── Checkboxes de días ── */
    .dias-grupo {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
    }

    .dia-checkbox {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.6rem 0.9rem;
        border: 1.5px solid var(--borde);
        border-radius: var(--radio);
        font-size: 0.88rem;
        color: var(--texto);
        cursor: pointer;
        transition: all 0.2s;
    }

    .dia-checkbox:hover { border-color: var(--primario); }

    .dia-checkbox input { accent-color: var(--primario); cursor: pointer; }

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
        <a href="registrar_medico.php" class="sidebar-link activo">
            <span class="icono">👨‍⚕️</span> Registrar médico
        </a>
        <a href="gestionar_medicos.php" class="sidebar-link">
            <span class="icono">👨‍⚕️</span> Gestionar médicos
        </a>

        <div class="sidebar-footer">
            <a href="/mediturnos/vistas/autenticacion/logout.php" class="sidebar-link">
                <span class="icono">🚪</span> Cerrar sesión
            </a>
        </div>
    </aside>

    <main class="contenido">

        <div class="encabezado-pagina">
            <h1>Registrar médico</h1>
            <p>Dar de alta un médico nuevo, su especialidad, horario y usuario de acceso</p>
        </div>

        <?php if ($mensaje): ?>
            <div class="alerta-<?= $tipo_msg ?>" style="margin-bottom:1.5rem;">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <?php if ($credenciales): ?>
            <div class="credenciales-card">
                <h3>Credenciales de acceso — entregar al médico</h3>
                <div class="credenciales-datos">
                    <strong>Usuario:</strong> <?= htmlspecialchars($credenciales['dni_username']) ?><br>
                    <strong>Contraseña:</strong> <?= htmlspecialchars($credenciales['password']) ?>
                </div>
                <p class="credenciales-aviso">⚠️ Estas credenciales solo se muestran una vez.</p>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" action="">

                <p class="seccion-titulo">Datos del médico</p>
                <div class="grilla-2">
                    <div class="grupo-campo">
                        <label for="matricula">Matrícula</label>
                        <input type="number" name="matricula" id="matricula" class="campo" required>
                    </div>
                    <div class="grupo-campo">
                        <label for="nombre">Nombre</label>
                        <input type="text" name="nombre" id="nombre" class="campo" required placeholder="Ej: Laura">
                    </div>
                    <div class="grupo-campo">
                        <label for="apellido">Apellido</label>
                        <input type="text" name="apellido" id="apellido" class="campo" required placeholder="Ej: Fernández">
                    </div>
                    <div class="grupo-campo">
                        <label for="telefono">Teléfono</label>
                        <input type="text" name="telefono" id="telefono" class="campo" placeholder="Ej: 0261-4123456">
                    </div>
                    <div class="grupo-campo campo-completo">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" class="campo" placeholder="Ej: laura@gmail.com">
                    </div>
                </div>

                <hr class="separador">

                <p class="seccion-titulo">Especialidad y horario</p>
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

                    <div class="grupo-campo campo-completo">
                        <label>Días de atención</label>
                        <div class="dias-grupo">
                            <?php
                            $nombres_dias = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'];
                            foreach ($nombres_dias as $valor => $nombre): ?>
                                <label class="dia-checkbox">
                                    <input type="checkbox" name="dias[]" value="<?= $valor ?>">
                                    <?= $nombre ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="grupo-campo">
                        <label for="hora_inicio">Hora de inicio</label>
                        <input type="time" name="hora_inicio" id="hora_inicio" class="campo" required>
                    </div>
                    <div class="grupo-campo">
                        <label for="hora_fin">Hora de fin</label>
                        <input type="time" name="hora_fin" id="hora_fin" class="campo" required>
                    </div>
                </div>

                <hr class="separador">

                <p class="seccion-titulo">Acceso al sistema</p>
                <div class="grilla-2">
                    <div class="grupo-campo campo-completo">
                        <label for="dni_username">DNI (usuario)</label>
                        <input type="text" name="dni_username" id="dni_username" class="campo" required placeholder="Ej: 30123456">
                    </div>
                </div>

                <div class="acciones">
                    <a href="index.php" class="boton-secundario">← Volver</a>
                    <button type="submit" class="boton-primario">Registrar médico</button>
                </div>

            </form>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../../../vistas/plantillas/footer.php'; ?>
