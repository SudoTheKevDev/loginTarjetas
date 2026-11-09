<?php
// ============================================================
//  RutaPc – Barra de navegación
//  includes/navbar.php
// ============================================================
$usuarioNombre = sesion('usuario_nombre') ?? '';
$usuarioRol    = sesion('usuario_rol')    ?? '';
$currentFile   = basename($_SERVER['PHP_SELF']);
$baseUrl       = defined('APP_URL') ? APP_URL : '';
?>
<header class="navbar">
    <div class="navbar-inner">

        <a href="<?= $baseUrl ?>/index.php" class="navbar-brand">
            <span class="brand-icon">
                <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="28" height="28" rx="7" fill="#00d4ff"/>
                    <path d="M7 8h8a4 4 0 0 1 0 8H7V8Z" fill="#0a1628"/>
                    <path d="M15 16l5 6" stroke="#0a1628" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
            </span>
            RutaPc
        </a>

        <nav class="navbar-nav" id="navMenu">
            <a href="<?= $baseUrl ?>/index.php"
               class="nav-link <?= $currentFile === 'index.php' ? 'active' : '' ?>">
                Dashboard
            </a>
            <?php if ($usuarioRol === 'administrador'): ?>
            <a href="#" class="nav-link nav-badge">
                Usuarios
                <span class="badge">Admin</span>
            </a>
            <?php endif; ?>
        </nav>

        <div class="navbar-end">
            <div class="user-menu" id="userMenu">

                    <div class="dropdown-divider"></div>
                    <a href="<?= $baseUrl ?>/logout.php" class="dropdown-item dropdown-item--danger">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        Cerrar sesión
                    </a>
                </div>
            </div>

            <button class="hamburger" id="hamburger" aria-label="Menú">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</header>
