<?php
/*
 * vistas/autenticacion/logout.php
 * ---------------------------------------------------------------
 * Cierra la sesión completamente y redirige al login.
 * ---------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../configuracion/conexion.php';
require_once __DIR__ . '/../../controladores/AuthControlador.php';

$controlador = new AuthControlador($pdo);
$controlador->logout();