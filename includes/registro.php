<?php

// ============================================================
//  RutaPc – Registro de usuarios
//  registro.php
// ============================================================
ob_start(); // <-- TRUCO CLAVE: Guarda el HTML en memoria y evita errores de cabeceras en InfinityFree
require_once __DIR__ . '/includes/auth.php';

redirigirSiAutenticado();

$pageTitle = 'Crear cuenta';
$mensaje   = '';
$tipoMsg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'nombre'   => filter_input(INPUT_POST, 'nombre',   FILTER_UNSAFE_RAW) ?? '',
        'apellido' => filter_input(INPUT_POST, 'apellido', FILTER_UNSAFE_RAW) ?? '',
        'email'    => filter_input(INPUT_POST, 'email',    FILTER_SANITIZE_EMAIL) ?? '',
        'password' => filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW) ?? '',
        'confirm'  => filter_input(INPUT_POST, 'confirm',  FILTER_UNSAFE_RAW) ?? '',
        'rol'      => filter_input(INPUT_POST, 'rol',      FILTER_UNSAFE_RAW) ?? 'cliente',
    ];

    $resultado = registrarUsuario($datos);
    $mensaje   = $resultado['mensaje'];
    $tipoMsg   = $resultado['ok'] ? 'success' : 'error';

    if ($resultado['ok']) {
        header('https://rutapc.site.je/RutaPc/login.php?registered=1');
        exit;
    }
}

$old = [
    'nombre'   => htmlspecialchars($_POST['nombre']   ?? '', ENT_QUOTES, 'UTF-8'),
    'apellido' => htmlspecialchars($_POST['apellido'] ?? '', ENT_QUOTES, 'UTF-8'),
    'email'    => htmlspecialchars($_POST['email']    ?? '', ENT_QUOTES, 'UTF-8'),
    'rol'      => $_POST['rol'] ?? 'cliente',
];

$baseUrl = APP_URL;
?>

<?php require_once __DIR__ . '/includes/header.php'; ?>
<link rel="stylesheet" href="https://rutapc.site.je/RutaPc/CSS/form-registro.css">
</head>
<body>
    <header>
        <div class="home-container">
            <a href="https://rutapc.site.je/RutaPc/index.php" class="home-link">
                <p>Página principal</p>
                <img src="https://rutapc.site.je/RutaPc/img/home.png" alt="Home">
            </a>
        </div>
    </header>
<main class="auth-wrap">
    <div class="auth-box">
    <div class="containter-head">
            <div class="auth-logo">
                <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                    <path d="M5 6h10a5 5 0 0 1 0 10H5V6Z" fill="#00d4ff"/>
                    <path d="M15 16l6 8" stroke="#00d4ff" stroke-width="2.8" stroke-linecap="round" opacity=".7"/>
                </svg>
                <span>RutaPc</span>
            </div>
            <h1 class="auth-title">Crear una cuenta</h1>
        </div>

        <p class="auth-desc">Completa el formulario para registrarte en el sistema.</p>

        <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipoMsg ?>" data-autohide="6000">
            <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="" id="formRegistro" class="grid-form" novalidate>

            <div class="form-group">
                <label class="form-label" for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" class="form-control"
                    placeholder="Juan" value="<?= $old['nombre'] ?>" autocomplete="given-name" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="apellido">Apellido</label>
                <input type="text" id="apellido" name="apellido" class="form-control"
                    placeholder="Pérez" value="<?= $old['apellido'] ?>" autocomplete="family-name" required>
            </div>

            <div class="form-group full-width">
                <label class="form-label" for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" class="form-control"
                    placeholder="correo@ejemplo.com" value="<?= $old['email'] ?>" autocomplete="email" required>
            </div>

            <div class="form-group password">
                <label class="form-label" for="password">Contraseña</label>
                <div style="position:relative;">
                    <input type="password" id="password" name="password" class="form-control"
                        placeholder="Mín. 8 caracteres" autocomplete="new-password" style="padding-right:46px; width: 100%;" required minlength="8">
                    <button type="button" data-toggle-pw="password" title="Mostrar / ocultar"
                        style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:white;display:flex;align-items:center;padding:4px; width:auto; margin:0;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="form-group password">
                <label class="form-label" for="confirm">Confirmar contraseña</label>
                <div style="position:relative;">
                    <input type="password" id="confirm" name="confirm" class="form-control"
                        placeholder="Repite tu contraseña" autocomplete="new-password" style="padding-right:46px; width: 100%;" required>
                    <button type="button" data-toggle-pw="confirm" title="Mostrar / ocultar"
                        style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:white;display:flex;align-items:center;padding:4px; width:auto; margin:0;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>


            <div class="full-width">
                <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px;">
                    Crear cuenta
                </button>
            </div>

        </form>

        <div class="divider full-width">o</div>

        <p class="text-center full-width">
            ¿Ya tienes cuenta?
            <a href="<?= $baseUrl ?>/login.php">Iniciar sesión</a>
        </p>
    </div>
</main>
</body>