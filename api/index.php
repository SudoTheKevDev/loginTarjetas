<?php
// ============================================================
// ENDPOINT ÚNICO: api/index.php?action=...
//   sesion            GET   -> estado de la sesión actual
//   login             POST  -> inicia sesión (email + password)
//   logout            POST  -> cierra sesión
//   registrar_usuario POST  -> crea un usuario
//   crear_tarjeta     POST  -> (requiere sesión)
//   listar_tarjetas   GET   -> (requiere sesión)
// ============================================================

error_reporting(0);
date_default_timezone_set('America/Mexico_City');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

define('RUTA_DATOS', __DIR__ . '/data/tarjetas.json');
define('SESION_DURACION', 60 * 60 * 24 * 7); // 7 días

// ---- Sesión persistente (cookie de 7 días, HttpOnly) --------
ini_set('session.gc_maxlifetime', (string) SESION_DURACION);
session_set_cookie_params([
    'lifetime' => SESION_DURACION,
    'path'     => '/',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// ---- Utilidades de respuesta --------------------------------
function responder($statusCode, $payload) {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function responderExito($mensaje, $data = null) {
    responder(200, ['success' => true, 'message' => $mensaje, 'data' => $data]);
}

function responderError($mensaje, $statusCode = 400) {
    responder($statusCode, ['success' => false, 'error' => $mensaje]);
}

function soloMetodo($metodo) {
    if ($_SERVER['REQUEST_METHOD'] !== $metodo) {
        responderError("Método no permitido. Usa $metodo.", 405);
    }
}

// ---- Autenticación ------------------------------------------
function usuarioActual() {
    if (empty($_SESSION['usuario_id'])) {
        return null;
    }
    return [
        'id'       => $_SESSION['usuario_id'],
        'nombre'   => $_SESSION['usuario_nombre'],
        'email'    => $_SESSION['usuario_email'],
        'nickname' => $_SESSION['usuario_nickname'],
    ];
}

function requiereAuth() {
    $u = usuarioActual();
    if (!$u) {
        responderError('No autenticado. Inicia sesión.', 401);
    }
    return $u['id'];
}

// ---- Acceso a datos (tarjetas en JSON) ----------------------
function leerTarjetas() {
    if (!file_exists(RUTA_DATOS)) {
        return [];
    }
    $tarjetas = json_decode(file_get_contents(RUTA_DATOS), true);
    return is_array($tarjetas) ? $tarjetas : [];
}

function guardarTarjetas($tarjetas) {
    $carpeta = dirname(RUTA_DATOS);
    if (!is_dir($carpeta)) {
        mkdir($carpeta, 0755, true);
    }
    return file_put_contents(RUTA_DATOS, json_encode($tarjetas, JSON_PRETTY_PRINT), LOCK_EX);
}

// ---- Acciones ------------------------------------------------
function accionSesion() {
    soloMetodo('GET');
    $u = usuarioActual();
    responderExito($u ? 'Sesión activa.' : 'Sin sesión.', ['autenticado' => (bool) $u, 'usuario' => $u]);
}

function accionLogin() {
    soloMetodo('POST');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        responderError('Completa correo y contraseña.', 400);
    }

    try {
        $stmt = getDB()->prepare(
            'SELECT id, nombre_completo, email, nickname, password_hash FROM usuarios WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();
    } catch (Throwable $e) {
        error_log('login: ' . $e->getMessage());
        responderError('No se pudo conectar con la base de datos.', 500);
    }

    if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
        responderError('Credenciales incorrectas.', 401);
    }

    session_regenerate_id(true);
    $_SESSION['usuario_id']       = (int) $usuario['id'];
    $_SESSION['usuario_nombre']   = $usuario['nombre_completo'];
    $_SESSION['usuario_email']    = $usuario['email'];
    $_SESSION['usuario_nickname'] = $usuario['nickname'];

    responderExito('Bienvenido/a, ' . $usuario['nombre_completo'] . '.', ['autenticado' => true, 'usuario' => usuarioActual()]);
}

function accionLogout() {
    soloMetodo('POST');
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    responderExito('Sesión cerrada.');
}

function accionRegistrarUsuario() {
    soloMetodo('POST');

    $nombre     = trim($_POST['nombre'] ?? '');
    $educacion  = trim($_POST['educacion'] ?? '');
    $nacimiento = trim($_POST['nacimiento'] ?? '');
    $telefono   = trim($_POST['telefono'] ?? '');
    $horas      = trim($_POST['horas'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $nickname   = trim($_POST['nickname'] ?? '');
    $password   = $_POST['password'] ?? '';

    if ($nombre === '' || $email === '' || $nickname === '' || $password === '') {
        responderError('Por favor completa todos los campos.', 400);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        responderError('El correo electrónico no es válido.', 400);
    }
    if (strlen($password) < 6) {
        responderError('La contraseña debe tener al menos 6 caracteres.', 400);
    }

    try {
        $db = getDB();

        $check = $db->prepare('SELECT id FROM usuarios WHERE email = ? OR nickname = ? LIMIT 1');
        $check->execute([$email, $nickname]);
        if ($check->fetch()) {
            responderError('Ya existe un usuario con ese correo o nickname.', 409);
        }

        $stmt = $db->prepare(
            'INSERT INTO usuarios (nombre_completo, fecha_nacimiento, nivel_educativo, telefono, horas_programando, email, nickname, password_hash)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $nombre,
            $nacimiento !== '' ? $nacimiento : null,
            $educacion  !== '' ? $educacion  : null,
            $telefono   !== '' ? $telefono   : null,
            $horas      !== '' ? $horas      : null,
            $email,
            $nickname,
            password_hash($password, PASSWORD_DEFAULT),
        ]);
    } catch (Throwable $e) {
        error_log('registro: ' . $e->getMessage());
        responderError('Ocurrió un error al registrar el usuario en la base de datos.', 500);
    }

    responderExito('Usuario registrado correctamente. Ya puedes iniciar sesión.');
}

function accionCrearTarjeta() {
    soloMetodo('POST');
    $usuarioId = requiereAuth();

    $inputJSON    = $_POST['datos_tarjeta'] ?? file_get_contents('php://input');
    $nuevaTarjeta = json_decode($inputJSON, true);

    if (!$nuevaTarjeta || empty($nuevaTarjeta['name'])) {
        responderError('Datos inválidos o incompletos.', 400);
    }

    $nuevaTarjeta['id']         = uniqid('user_');
    $nuevaTarjeta['createdAt']  = date('Y-m-d H:i:s');
    $nuevaTarjeta['usuario_id'] = $usuarioId;

    $tarjetas   = leerTarjetas();
    $tarjetas[] = $nuevaTarjeta;

    if (guardarTarjetas($tarjetas) === false) {
        responderError('Error de permisos. El servidor no permite escribir en api/data/.', 500);
    }
    responderExito('Tarjeta guardada correctamente en el servidor.', $nuevaTarjeta);
}

function accionListarTarjetas() {
    soloMetodo('GET');
    requiereAuth();
    responderExito('Tarjetas obtenidas correctamente.', leerTarjetas());
}

// ---- Router --------------------------------------------------
$action = $_GET['action'] ?? $_POST['action'] ?? null;

switch ($action) {
    case 'sesion':            accionSesion();           break;
    case 'login':             accionLogin();            break;
    case 'logout':            accionLogout();           break;
    case 'registrar_usuario': accionRegistrarUsuario(); break;
    case 'crear_tarjeta':     accionCrearTarjeta();     break;
    case 'listar_tarjetas':   accionListarTarjetas();   break;
    default:
        responderError('Acción no reconocida o no especificada.', 404);
}