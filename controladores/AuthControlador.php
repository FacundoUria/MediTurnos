<?php
/*
 * controladores/AuthControlador.php
 * ---------------------------------------------------------------
 * Maneja la lógica de login y logout.
 * Verifica DNI y contraseña, crea la sesión y redirige según rol.
 * ---------------------------------------------------------------
 */

require_once __DIR__ . '/../configuracion/conexion.php';
require_once __DIR__ . '/../modelos/UsuarioModelo.php';

class AuthControlador {

    private $modelo;

    public function __construct($pdo) {
        $this->modelo = new UsuarioModelo($pdo);
    }

    public function login($dni, $password) {

        // 1. Buscamos el usuario en la DB por DNI
        $usuario = $this->modelo->buscarPorDni($dni);

        // 2. Si no existe o la contraseña no coincide, devolvemos error
        if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
            return ['exito' => false, 'mensaje' => 'DNI o contraseña incorrectos'];
        }

        // 3. Todo ok — regeneramos el ID de sesión por seguridad
        session_regenerate_id(true);

        // 4. Guardamos los datos del usuario en la sesión
        $_SESSION['id_usuario']  = $usuario['id_usuario'];
        $_SESSION['rol']         = $usuario['rol'];
        $_SESSION['dni']         = $usuario['dni_username'];
        $_SESSION['id_paciente'] = $usuario['id_paciente'];
        $_SESSION['matricula']   = $usuario['matricula_medico'];

        // 5. Redirigimos según el rol
        $destino = match($usuario['rol']) {
            'Administrador' => '/mediturnos/vistas/panel/recepcionista/index.php',
            'Recepcionista' => '/mediturnos/vistas/panel/recepcionista/index.php',
            'Medico'        => '/mediturnos/vistas/panel/medico/index.php',
            'Paciente'      => '/mediturnos/vistas/panel/paciente/index.php',
            default         => '/mediturnos/vistas/autenticacion/login.php'
        };

        return ['exito' => true, 'destino' => $destino];
    }

    public function logout() {
        // Paso 1: Vaciamos el array de sesión
        $_SESSION = [];

        // Paso 2: Eliminamos la cookie del navegador
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(), '',
                time() - 42000,
                $p['path'], $p['domain'],
                $p['secure'], $p['httponly']
            );
        }

        // Paso 3: Destruimos la sesión en el servidor
        session_destroy();

        header('Location: /mediturnos/vistas/autenticacion/login.php');
        exit;
    }
}