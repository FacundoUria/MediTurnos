<?php
/*
 * vistas/plantillas/header.php
 * ---------------------------------------------------------------
 * Plantilla reutilizable de cabecera HTML.
 * Se incluye al inicio de TODAS las vistas con require_once.
 * Contiene: declaración HTML, fuentes, variables CSS y estilos globales.
 * ---------------------------------------------------------------
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediTurnos</title>
 
    <!-- Fuente principal desde Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display&display=swap" rel="stylesheet">
 
    <style>
        /* ── Variables globales del sistema ── */
        :root {
            --primario:     #1a6fa8;
            --primario-osc: #145d8e;
            --acento:       #e8f4fd;
            --fondo:        #f0f5f9;
            --blanco:       #ffffff;
            --texto:        #1e2d3d;
            --texto-suave:  #6b7c93;
            --borde:        #dce6f0;
            --error:        #e53e3e;
            --exito:        #2f855a;
            --sombra:       0 4px 24px rgba(26, 111, 168, 0.10);
            --radio:        12px;
        }
 
        /* ── Reset básico ── */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
 
        body {
            font-family: 'DM Sans', sans-serif;
            background-color: var(--fondo);
            color: var(--texto);
            min-height: 100vh;
        }
 
        /* ── Inputs corporativos reutilizables ── */
        .campo {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1.5px solid var(--borde);
            border-radius: var(--radio);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            color: var(--texto);
            background: var(--blanco);
            transition: all 0.3s ease;
            outline: none;
        }
 
        .campo:focus {
            border-color: var(--primario);
            box-shadow: 0 0 0 3px rgba(26, 111, 168, 0.12);
        }
 
        /* ── Botón principal ── */
        .boton-primario {
            width: 100%;
            padding: 0.9rem;
            background: var(--primario);
            color: var(--blanco);
            border: none;
            border-radius: var(--radio);
            font-family: 'DM Sans', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.1s ease;
        }
 
        .boton-primario:hover {
            background: var(--primario-osc);
            transform: translateY(-1px);
        }
 
        .boton-primario:active {
            transform: translateY(0);
        }
 
        /* ── Alertas ── */
        .alerta-error {
            background: #fff5f5;
            border: 1.5px solid #feb2b2;
            color: var(--error);
            padding: 0.85rem 1rem;
            border-radius: var(--radio);
            font-size: 0.9rem;
            margin-bottom: 1.2rem;
        }
 
        .alerta-exito {
            background: #f0fff4;
            border: 1.5px solid #9ae6b4;
            color: var(--exito);
            padding: 0.85rem 1rem;
            border-radius: var(--radio);
            font-size: 0.9rem;
            margin-bottom: 1.2rem;
        }
    </style>
</head>
<body>
