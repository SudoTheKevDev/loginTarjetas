<?php
// ============================================================
//  RutaPc – Funciones de autenticación
//  includes/auth.php
// ============================================================

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Helpers ─────────────────────────────────────────────────

function estaAutenticado(): bool
{
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Redirige al login si NO hay sesión activa.
 */
function requerirLogin(): void
{
    if (!estaAutenticado() || $_SESSION['usuario_rol'] !== 'administrador') {
        header('Location: https://rutapc.site.je/RutaPc/index.php');
        exit;
    }
}

/**
 * Redirige al dashboard si YA hay sesión activa.
 */
function redirigirSiAutenticado(): void
{
    if (estaAutenticado()) {
        header('Location: https://rutapc.site.je/RutaPc/index.php');
        exit;
    }

}

function sesion(string $clave): mixed
{
    return $_SESSION[$clave] ?? null;
}


function cerrarSesion(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

// ── Registro ─────────────────────────────────────────────────

function registrarUsuario(array $datos): array
{
    $nombre   = trim($datos['nombre']   ?? '');
    $apellido = trim($datos['apellido'] ?? '');
    $email    = trim($datos['email']    ?? '');
    $password = $datos['password']      ?? '';
    $confirm  = $datos['confirm']       ?? '';
    $rol      = $datos['rol']           ?? 'empleado';

    if (empty($nombre) || empty($apellido) || empty($email) || empty($password)) {
        return ['ok' => false, 'mensaje' => 'Todos los campos son obligatorios.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'mensaje' => 'El correo electrónico no es válido.'];
    }
    if (strlen($password) < 8) {
        return ['ok' => false, 'mensaje' => 'La contraseña debe tener al menos 8 caracteres.'];
    }
    if ($password !== $confirm) {
        return ['ok' => false, 'mensaje' => 'Las contraseñas no coinciden.'];
    }
    if (!in_array($rol, ['administrador', 'empleado'], true)) {
        $rol = 'empleado';
    }

    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        return ['ok' => false, 'mensaje' => 'Ya existe una cuenta con ese correo.'];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (nombre, apellido, email, password, rol)
         VALUES (:nombre, :apellido, :email, :password, :rol)'
    );
    $stmt->execute([
        ':nombre'   => htmlspecialchars($nombre,   ENT_QUOTES, 'UTF-8'),
        ':apellido' => htmlspecialchars($apellido, ENT_QUOTES, 'UTF-8'),
        ':email'    => $email,
        ':password' => $hash,
        ':rol'      => $rol,
    ]);

    return ['ok' => true, 'mensaje' => '¡Cuenta creada exitosamente! Ya puedes iniciar sesión.'];
}

// ── Login ─────────────────────────────────────────────────────

function iniciarSesion(string $email, string $password): array
{
    $email    = trim($email);
    $password = trim($password);

    if (empty($email) || empty($password)) {
        return ['ok' => false, 'mensaje' => 'Completa todos los campos.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'mensaje' => 'Correo electrónico no válido.'];
    }

    $pdo  = getDB();
    $stmt = $pdo->prepare(
        'SELECT id, nombre, apellido, email, password, rol, activo
         FROM usuarios WHERE email = :email LIMIT 1'
    );
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($password, $usuario['password'])) {
        return ['ok' => false, 'mensaje' => '¡Credenciales incorrectas!'];
    }
    if (!$usuario['activo']) {
        return ['ok' => false, 'mensaje' => 'Tu cuenta está desactivada. Contacta al administrador.'];
    }
    
// Solo regenera el ID en localhost (XAMPP). En InfinityFree causa bucles con el firewall.
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if ($host === 'localhost' || $host === '127.0.0.1') {
        session_regenerate_id(true);
    }
 
    $_SESSION['usuario_id']       = $usuario['id'];
    $_SESSION['usuario_nombre']   = $usuario['nombre'];
    $_SESSION['usuario_apellido'] = $usuario['apellido'];
    $_SESSION['usuario_email']    = $usuario['email'];
    $_SESSION['usuario_rol']      = $usuario['rol'];

    return ['ok' => true, 'mensaje' => 'Bienvenido/a, ' . htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') . '.'];
}
