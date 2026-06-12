<?php
/*
 * modelos/UsuarioModelo.php
 * ---------------------------------------------------------------
 * Consultas a la DB relacionadas con usuarios y autenticación.
 * ---------------------------------------------------------------
 */

class UsuarioModelo {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Busca un usuario por DNI junto con su rol
    // Devuelve los datos del usuario o false si no existe
    public function buscarPorDni($dni) {
        $sql = "SELECT u.id_usuario,
                       u.dni_username,
                       u.password_hash,
                       u.activo,
                       u.id_paciente,
                       u.matricula_medico,
                       r.nombre AS rol
                FROM   Usuario u
                JOIN   Rol r ON u.id_rol = r.id_rol
                WHERE  u.dni_username = :dni
                AND    u.activo = 1
                LIMIT  1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':dni' => $dni]);
        return $stmt->fetch();
    }

    // Busca un paciente por DNI — usado para el acceso sin contraseña
    public function buscarPacientePorDni($dni) {
        $stmt = $this->pdo->prepare(
            "SELECT id_paciente, nombre, apellido, dni
             FROM Paciente
             WHERE dni = :dni
             LIMIT 1"
        );
        $stmt->execute([':dni' => $dni]);
        return $stmt->fetch();
    }
}