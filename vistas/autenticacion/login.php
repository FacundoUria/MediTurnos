<?php
/*
 * vistas/autenticacion/login.php
 * ---------------------------------------------------------------
 * Vista del formulario de inicio de sesión.
 * Su único trabajo es MOSTRAR el formulario y los mensajes.
 * NO verifica contraseñas ni consulta la DB — eso lo hace
 * el AuthControlador cuando conectemos el backend.
 * ---------------------------------------------------------------
 */

// Iniciamos la sesión para poder usar $_SESSION más adelante
session_start();

// Incluimos la cabecera HTML reutilizable
require_once __DIR__ . '/../../vistas/plantillas/header.php';
?>

<style>
    /* ── Layout centrado de la pantalla de login ── */
    .pantalla-login {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        background: linear-gradient(135deg, #e8f4fd 0%, #f0f5f9 60%, #dce6f0 100%);
    }

    /* ── Contenedor principal dividido en dos columnas ── */
    .contenedor-login {
        display: flex;
        width: 100%;
        max-width: 900px;
        background: var(--blanco);
        border-radius: 20px;
        box-shadow: var(--sombra);
        overflow: hidden;
    }

    /* ── Panel izquierdo — marca del sistema ── */
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

    .panel-marca p {
        color: rgba(255,255,255,0.75);
        font-size: 0.95rem;
        line-height: 1.6;
    }

    /* ── Lista de características ── */
    .lista-features {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-top: 0.5rem;
    }

    .lista-features li {
        color: rgba(255,255,255,0.85);
        font-size: 0.88rem;
        display: flex;
        align-items: center;
        gap: 0.6rem;
    }

    .lista-features li::before {
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

    /* ── Panel derecho — formulario ── */
    .panel-formulario {
        width: 55%;
        padding: 3rem 2.5rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .panel-formulario h2 {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--texto);
        margin-bottom: 0.4rem;
    }

    .panel-formulario .subtitulo {
        color: var(--texto-suave);
        font-size: 0.92rem;
        margin-bottom: 2rem;
    }

    /* ── Grupos de campos del formulario ── */
    .grupo-campo {
        margin-bottom: 1.2rem;
    }

    .grupo-campo label {
        display: block;
        font-size: 0.88rem;
        font-weight: 500;
        color: var(--texto);
        margin-bottom: 0.4rem;
    }

    /* ── Pie del formulario ── */
    .pie-formulario {
        margin-top: 1.5rem;
        text-align: center;
        color: var(--texto-suave);
        font-size: 0.82rem;
    }

    /* ── Responsivo para móvil ── */
    @media (max-width: 640px) {
        .panel-marca {
            display: none;
        }
        .panel-formulario {
            width: 100%;
            padding: 2rem 1.5rem;
        }
    }
</style>

<div class="pantalla-login">
    <div class="contenedor-login">

        <!-- Panel izquierdo: marca del sistema -->
        <div class="panel-marca">
            <div class="icono-sistema">🏥</div>
            <h1>MediTurnos</h1>
            <p>Sistema de gestión de agenda médica para clínicas y consultorios.</p>
           
        </div>

        <!-- Panel derecho: formulario de login -->
        <div class="panel-formulario">
            <h2>Bienvenido</h2>
            <p class="subtitulo">Ingresá con tu DNI y contraseña para continuar</p>

            <?php
            // Mostramos el mensaje de error si existe
            // Esta variable la va a setear el AuthControlador cuando conectemos el backend
            if (isset($error)): ?>
                <div class="alerta-error">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Formulario — cuando conectemos el backend apunta al controlador -->
            <form method="POST" action="">

                <div class="grupo-campo">
                    <label for="dni">DNI</label>
                    <input
                        type="text"
                        id="dni"
                        name="dni"
                        class="campo"
                        placeholder="Ej: 35987654"
                        required
                        maxlength="10"
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

                <button type="submit" class="boton-primario">
                    Ingresar al sistema
                </button>

            </form>

            <p class="pie-formulario">
                Universidad Champagnat · Sistema Académico MediTurnos
            </p>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../../vistas/plantillas/footer.php'; ?>