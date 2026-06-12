<?php
/*
 * vistas/autenticacion/login.php
 * ---------------------------------------------------------------
 * Pantalla de login con backend real.
 * Verifica DNI y contraseña contra la DB.
 * Redirige según el rol del usuario.
 * ---------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../configuracion/conexion.php';
require_once __DIR__ . '/../../controladores/AuthControlador.php';

$error = '';

// Si ya está logueado lo redirigimos directamente
if (isset($_SESSION['rol'])) {
    $destino = match($_SESSION['rol']) {
        'Administrador', 'Recepcionista' => '/mediturnos/vistas/panel/recepcionista/index.php',
        'Medico'                         => '/mediturnos/vistas/panel/medico/index.php',
        'Paciente'                       => '/mediturnos/vistas/panel/paciente/index.php',
        default                          => ''
    };
    if ($destino) {
        header("Location: $destino");
        exit;
    }
}

$tab_activo = 'paciente';

// Procesamos el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? 'paciente';
    $controlador = new AuthControlador($pdo);

    if ($tipo === 'paciente') {
        $tab_activo = 'paciente';
        $dni = trim($_POST['dni'] ?? '');

        if (empty($dni)) {
            $error = 'Ingresá tu DNI.';
        } else {
            $resultado = $controlador->loginPaciente($dni);

            if ($resultado['exito']) {
                header("Location: " . $resultado['destino']);
                exit;
            } else {
                $error = $resultado['mensaje'];
            }
        }
    } else {
        $tab_activo = 'staff';
        $dni      = trim($_POST['dni']      ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($dni) || empty($password)) {
            $error = 'Completá todos los campos.';
        } else {
            $resultado = $controlador->login($dni, $password);

            if ($resultado['exito']) {
                header("Location: " . $resultado['destino']);
                exit;
            } else {
                $error = $resultado['mensaje'];
            }
        }
    }
}

require_once __DIR__ . '/../../vistas/plantillas/header.php';
?>

<style>
    .pantalla-login {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        background: linear-gradient(135deg, #e8f4fd 0%, #f0f5f9 60%, #dce6f0 100%);
    }

    .contenedor-login {
        display: flex;
        width: 100%;
        max-width: 900px;
        background: var(--blanco);
        border-radius: 20px;
        box-shadow: var(--sombra);
        overflow: hidden;
    }

    .panel-marca {
        width: 45%;
        background: linear-gradient(160deg, var(--primario) 0%, var(--primario-osc) 100%);
        padding: 3rem 2.5rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 1.5rem;
    }

    .panel-marca .icono-sistema {
        width: 64px;
        height: 64px;
        background: rgba(255,255,255,0.15);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
    }

    .panel-marca h1 {
        font-family: 'DM Serif Display', serif;
        color: #ffffff;
        font-size: 2rem;
        line-height: 1.2;
    }

    .panel-marca p { color: rgba(255,255,255,0.75); font-size: 0.95rem; line-height: 1.6; }

    .lista-roles {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-top: 0.5rem;
    }

    .lista-roles li {
        color: rgba(255,255,255,0.85);
        font-size: 0.88rem;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .lista-roles li::before {
        content: '✓';
        background: rgba(255,255,255,0.2);
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        flex-shrink: 0;
    }

    .panel-formulario {
        width: 55%;
        padding: 3rem 2.5rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .panel-formulario h2 { font-size: 1.5rem; font-weight: 600; color: var(--texto); margin-bottom: 0.4rem; }
    .panel-formulario .subtitulo { color: var(--texto-suave); font-size: 0.92rem; margin-bottom: 2rem; }

    .grupo-campo { margin-bottom: 1.2rem; }
    .grupo-campo label { display: block; font-size: 0.88rem; font-weight: 500; color: var(--texto); margin-bottom: 0.4rem; }

    .pie-formulario { margin-top: 1.5rem; text-align: center; color: var(--texto-suave); font-size: 0.82rem; }

    /* ── Tabs de login ── */
    .tabs-login {
        display: flex;
        gap: 0;
        margin-bottom: 1.5rem;
        background: var(--fondo);
        border-radius: var(--radio);
        padding: 0.4rem;
    }

    .tab-login {
        flex: 1;
        padding: 0.7rem 1rem;
        border-radius: 8px;
        border: none;
        background: transparent;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.92rem;
        font-weight: 500;
        color: var(--texto-suave);
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }

    .tab-login.activo { background: var(--primario); color: #fff; }

    .panel-login { display: none; }
    .panel-login.visible { display: block; }

    @media (max-width: 640px) {
        .panel-marca { display: none; }
        .panel-formulario { width: 100%; padding: 2rem 1.5rem; }
    }
</style>

<div class="pantalla-login">
    <div class="contenedor-login">

        <div class="panel-marca">
            <div class="icono-sistema">🏥</div>
            <h1>MediTurnos</h1>
            <p>Sistema de gestión de agenda médica para clínicas y consultorios.</p>
            <ul class="lista-roles">
                <li>Recepcionista — gestión completa</li>
                <li>Médico — agenda personal</li>
                <li>Paciente — estado de turnos</li>
            </ul>
        </div>

        <div class="panel-formulario">
            <h2>Bienvenido</h2>
            <p class="subtitulo">Elegí cómo querés ingresar</p>

            <div class="tabs-login">
                <button type="button" class="tab-login <?= $tab_activo === 'paciente' ? 'activo' : '' ?>" onclick="cambiarTabLogin('paciente', this)">
                    Soy paciente
                </button>
                <button type="button" class="tab-login <?= $tab_activo === 'staff' ? 'activo' : '' ?>" onclick="cambiarTabLogin('staff', this)">
                    Soy staff
                </button>
            </div>

            <?php if ($error): ?>
                <div class="alerta-error">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- ── Tab: paciente ── -->
            <div id="panel-paciente" class="panel-login <?= $tab_activo === 'paciente' ? 'visible' : '' ?>">
                <form method="POST" action="">
                    <input type="hidden" name="tipo" value="paciente">
                    <div class="grupo-campo">
                        <label for="dni-paciente">DNI</label>
                        <input
                            type="text"
                            id="dni-paciente"
                            name="dni"
                            class="campo"
                            placeholder="Ej: 35987654"
                            required
                            maxlength="10"
                            value="<?= $tab_activo === 'paciente' ? htmlspecialchars($_POST['dni'] ?? '') : '' ?>"
                        >
                    </div>
                    <button type="submit" class="boton-primario">Ver mis turnos</button>
                </form>
            </div>

            <!-- ── Tab: staff ── -->
            <div id="panel-staff" class="panel-login <?= $tab_activo === 'staff' ? 'visible' : '' ?>">
                <form method="POST" action="">
                    <input type="hidden" name="tipo" value="staff">
                    <div class="grupo-campo">
                        <label for="dni-staff">DNI</label>
                        <input
                            type="text"
                            id="dni-staff"
                            name="dni"
                            class="campo"
                            placeholder="Ej: 35987654"
                            required
                            maxlength="10"
                            value="<?= $tab_activo === 'staff' ? htmlspecialchars($_POST['dni'] ?? '') : '' ?>"
                        >
                    </div>
                    <div class="grupo-campo">
                        <label for="password">Contraseña</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="campo"
                            placeholder="Tu contraseña"
                            required
                        >
                    </div>
                    <button type="submit" class="boton-primario">Ingresar al sistema</button>
                </form>
            </div>

            <p class="pie-formulario">Universidad Champagnat · Sistema Académico MediTurnos</p>
        </div>

    </div>
</div>

<script>
    function cambiarTabLogin(tipo, boton) {
        document.querySelectorAll('.tab-login').forEach(t => t.classList.remove('activo'));
        boton.classList.add('activo');
        document.querySelectorAll('.panel-login').forEach(p => p.classList.remove('visible'));
        document.getElementById('panel-' + tipo).classList.add('visible');
    }
</script>

<?php require_once __DIR__ . '/../../vistas/plantillas/footer.php'; ?>