<?php
/*
 * vistas/panel/paciente/index.php
 * ---------------------------------------------------------------
 * Panel del paciente — solo puede ver el estado de sus turnos.
 * Solo lectura — no puede modificar nada.
 * ---------------------------------------------------------------
 */
session_start();

// Verificamos que esté logueado y sea paciente
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Paciente') {
    header('Location: /mediturnos/vistas/autenticacion/login.php');
    exit;
}

require_once __DIR__ . '/../../../configuracion/conexion.php';

$id_paciente = $_SESSION['id_paciente'];

// Traemos los datos del paciente
$sql_pac = "SELECT nombre, apellido, dni FROM Paciente WHERE id_paciente = :id";
$stmt    = $pdo->prepare($sql_pac);
$stmt->execute([':id' => $id_paciente]);
$paciente = $stmt->fetch();

// Traemos sus turnos ordenados por fecha
$sql = "SELECT
            t.id_turno,
            t.fecha,
            t.hora,
            t.estado,
            CONCAT('Dr/a. ', m.apellido) AS medico,
            e.nombre                     AS especialidad,
            c.numero                     AS consultorio
        FROM Turno t
        JOIN Medico       m ON t.matricula        = m.matricula
        JOIN Especialidad e ON t.id_especialidad  = e.id_especialidad
        JOIN Consultorio  c ON t.id_consultorio   = c.id_consultorio
        WHERE t.id_paciente = :id
        ORDER BY t.fecha DESC, t.hora DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id_paciente]);
$turnos = $stmt->fetchAll();

// Separamos próximos y pasados
$proximos = array_filter($turnos, fn($t) => $t['fecha'] >= date('Y-m-d') && $t['estado'] !== 'Cancelado');
$pasados  = array_filter($turnos, fn($t) => $t['fecha'] < date('Y-m-d')  || $t['estado'] === 'Cancelado');

require_once __DIR__ . '/../../../vistas/plantillas/header.php';
?>

<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body { background: var(--fondo); }

    .pagina {
        max-width: 680px;
        margin: 0 auto;
        padding: 2rem 1.5rem;
    }

    /* ── Header del paciente ── */
    .header-paciente {
        background: linear-gradient(135deg, var(--primario) 0%, var(--primario-osc) 100%);
        border-radius: var(--radio);
        padding: 1.8rem 2rem;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .header-paciente h1 { color: white; font-size: 1.3rem; font-weight: 600; }
    .header-paciente p  { color: rgba(255,255,255,0.75); font-size: 0.88rem; margin-top: 0.3rem; }

    .btn-cerrar {
        padding: 0.5rem 1rem;
        background: rgba(255,255,255,0.15);
        color: white;
        border: 1.5px solid rgba(255,255,255,0.3);
        border-radius: 8px;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.85rem;
        text-decoration: none;
        transition: all 0.2s;
    }

    .btn-cerrar:hover { background: rgba(255,255,255,0.25); }

    /* ── Sección ── */
    .seccion-titulo {
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--texto-suave);
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 1rem;
        margin-top: 2rem;
    }

    /* ── Tarjeta de turno ── */
    .turno-card {
        background: var(--blanco);
        border-radius: var(--radio);
        box-shadow: var(--sombra);
        padding: 1.4rem 1.6rem;
        margin-bottom: 1rem;
        border-left: 4px solid var(--borde);
        transition: transform 0.15s;
    }

    .turno-card:hover { transform: translateX(3px); }

    .turno-card.proximo   { border-left-color: var(--primario); }
    .turno-card.realizado { border-left-color: #22c55e; }
    .turno-card.cancelado { border-left-color: #ef4444; opacity: 0.7; }
    .turno-card.ausente   { border-left-color: #a855f7; opacity: 0.7; }

    .turno-fecha-hora {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        margin-bottom: 0.8rem;
    }

    .turno-fecha {
        font-size: 1rem;
        font-weight: 600;
        color: var(--texto);
    }

    .turno-hora {
        font-size: 0.88rem;
        color: var(--texto-suave);
        background: var(--fondo);
        padding: 0.2rem 0.6rem;
        border-radius: 6px;
    }

    .turno-detalle {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.4rem;
        margin-bottom: 0.8rem;
    }

    .turno-detalle span {
        font-size: 0.88rem;
        color: var(--texto-suave);
    }

    .turno-detalle strong {
        font-size: 0.88rem;
        color: var(--texto);
    }

    .estado {
        display: inline-block;
        padding: 0.3rem 0.9rem;
        border-radius: 999px;
        font-size: 0.80rem;
        font-weight: 600;
    }

    .estado.Pendiente  { background: #fefce8; color: #854d0e; }
    .estado.Confirmado { background: #eff6ff; color: #1d4ed8; }
    .estado.Realizado  { background: #f0fff4; color: #166534; }
    .estado.Cancelado  { background: #fff5f5; color: #991b1b; }
    .estado.Ausente    { background: #f5f3ff; color: #5b21b6; }

    .sin-turnos {
        text-align: center;
        padding: 2rem;
        color: var(--texto-suave);
        font-style: italic;
        background: var(--blanco);
        border-radius: var(--radio);
        box-shadow: var(--sombra);
    }
</style>

<div class="pagina">

    <!-- Header del paciente -->
    <div class="header-paciente">
        <div>
            <h1>👋 Hola, <?= htmlspecialchars($paciente['nombre'] ?? '') ?>!</h1>
            <p>DNI: <?= htmlspecialchars($paciente['dni'] ?? '') ?> · <?= count($turnos) ?> turnos en total</p>
        </div>
        <a href="/mediturnos/vistas/autenticacion/logout.php" class="btn-cerrar">
            🚪 Salir
        </a>
    </div>

    <!-- Próximos turnos -->
    <p class="seccion-titulo" style="margin-top:0">Próximos turnos</p>

    <?php if (empty($proximos)): ?>
        <div class="sin-turnos">No tenés turnos próximos</div>
    <?php else: ?>
        <?php foreach ($proximos as $t): ?>
        <div class="turno-card proximo">
            <div class="turno-fecha-hora">
                <span class="turno-fecha"><?= date('d/m/Y', strtotime($t['fecha'])) ?></span>
                <span class="turno-hora"><?= date('H:i', strtotime($t['hora'])) ?></span>
                <span class="estado <?= $t['estado'] ?>"><?= $t['estado'] ?></span>
            </div>
            <div class="turno-detalle">
                <span>Médico</span>
                <strong><?= htmlspecialchars($t['medico']) ?></strong>
                <span>Especialidad</span>
                <strong><?= htmlspecialchars($t['especialidad']) ?></strong>
                <span>Consultorio</span>
                <strong>Nº <?= htmlspecialchars($t['consultorio']) ?></strong>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Historial -->
    <?php if (!empty($pasados)): ?>
        <p class="seccion-titulo">Historial</p>
        <?php foreach ($pasados as $t):
            $clase = strtolower($t['estado']);
        ?>
        <div class="turno-card <?= $clase ?>">
            <div class="turno-fecha-hora">
                <span class="turno-fecha"><?= date('d/m/Y', strtotime($t['fecha'])) ?></span>
                <span class="turno-hora"><?= date('H:i', strtotime($t['hora'])) ?></span>
                <span class="estado <?= $t['estado'] ?>"><?= $t['estado'] ?></span>
            </div>
            <div class="turno-detalle">
                <span>Médico</span>
                <strong><?= htmlspecialchars($t['medico']) ?></strong>
                <span>Especialidad</span>
                <strong><?= htmlspecialchars($t['especialidad']) ?></strong>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../../../vistas/plantillas/footer.php'; ?>