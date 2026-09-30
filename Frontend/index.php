<?php
/**
 * FAST DELIVERY - Pantalla de Inicio de Sesión
 * Basado en wireframe (Imagen 1) y base de datos SQL.db
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Backend/services/auth.php';

// Si ya tiene sesión activa y no está cerrando sesión, redirigir según su rol
if (!empty($_SESSION['user_id']) && !isset($_GET['logout'])) {
    $redirect = AuthService::getRedirectUrlForRole($_SESSION['user_rol'] ?? 'cliente');
    header("Location: {$redirect}");
    exit;
}

$errorMessage = '';
$successMessage = '';

// Procesar cierre de sesión
if (isset($_GET['logout'])) {
    AuthService::logout();
    $successMessage = 'Has cerrado sesión correctamente.';
}

// Procesar mensajes por URL
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'requiere_login') {
        $errorMessage = 'Debe iniciar sesión para acceder a esa sección.';
    } elseif ($_GET['error'] === 'acceso_no_autorizado') {
        $errorMessage = 'No tiene permisos para acceder al módulo solicitado.';
    }
}

// Procesar formulario POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'login') {
        $usuario = $_POST['usuario'] ?? '';
        $password = $_POST['password'] ?? '';

        $result = AuthService::login($usuario, $password);
        if ($result['success']) {
            header("Location: " . $result['redirect']);
            exit;
        } else {
            $errorMessage = $result['message'];
        }
    } elseif ($action === 'register') {
        $result = AuthService::register($_POST);
        if ($result['success']) {
            $successMessage = $result['message'];
        } else {
            $errorMessage = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fast Delivery - Iniciar Sesión</title>
    <meta name="description" content="Plataforma de delivery de productos de primera necesidad. Inicia sesión para comprar o administrar el catálogo.">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page-body">

    <!-- Logotipo Superior Izquierdo -->
    <header>
        <h1 class="login-top-brand">FAST DELIVERY</h1>
    </header>

    <!-- Contenedor Central de Login -->
    <main class="login-container">
        <div class="login-card">

            <!-- Avatar Circular -->
            <div class="login-avatar-circle" title="Fast Delivery Usuario">
                <img src="assets/images/user-avatar.svg" alt="Usuario Avatar" style="width: 100%; height: 100%;">
            </div>

            <!-- Alertas / Notificaciones -->
            <?php if (!empty($errorMessage)): ?>
                <div class="alert-message alert-error" role="alert">
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($successMessage)): ?>
                <div class="alert-message alert-success" role="status">
                    <?= htmlspecialchars($successMessage) ?>
                </div>
            <?php endif; ?>

            <!-- Formulario de Login -->
            <form method="POST" action="index.php" class="login-form" id="loginForm">
                <input type="hidden" name="action" value="login">

                <div>
                    <label for="inputUsuario" class="sr-only" style="display:none;">Usuario o Correo</label>
                    <input 
                        type="text" 
                        name="usuario" 
                        id="inputUsuario" 
                        class="login-input" 
                        placeholder="INGRESE SU USUARIO" 
                        required 
                        autocomplete="username"
                        value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>"
                    >
                </div>

                <div>
                    <label for="inputPassword" class="sr-only" style="display:none;">Contraseña</label>
                    <input 
                        type="password" 
                        name="password" 
                        id="inputPassword" 
                        class="login-input" 
                        placeholder="INGRESE CONTRASEÑA" 
                        required 
                        autocomplete="current-password"
                    >
                </div>

                <button type="submit" class="btn-primary-action btn-login-submit" id="btnLoginSubmit">
                    INICIAR SESIÓN
                </button>

                <button type="button" class="btn-primary-action" id="btnCrearCuenta" onclick="openRegisterModal()">
                    CREAR CUENTA
                </button>
            </form>

            <!-- Acceso Rápido para Pruebas / Demostración -->
            <aside class="demo-credentials-box" aria-label="Cuentas de Demostración">
                <div class="demo-credentials-title">
                    <span>⚡ Cuentas de Prueba (SQL.db):</span>
                </div>
                <div class="demo-account-pills">
                    <button type="button" class="demo-pill" onclick="fillCredentials('admin@fastdelivery.com', 'admin123')" title="Rol Administrador -> Abre Panel de Productos">
                        👑 Administrador
                    </button>
                    <button type="button" class="demo-pill" onclick="fillCredentials('cliente@fastdelivery.com', 'cliente123')" title="Rol Cliente -> Abre Selección de Productos">
                        🛒 Cliente
                    </button>
                    <button type="button" class="demo-pill" onclick="fillCredentials('repartidor@fastdelivery.com', 'repartidor123')" title="Rol Repartidor">
                        🛵 Repartidor
                    </button>
                </div>
            </aside>

        </div>
    </main>

    <!-- Modal de Registro de Nueva Cuenta -->
    <div class="modal-overlay" id="registerModal" role="dialog" aria-modal="true" aria-labelledby="regTitle">
        <div class="modal-card">
            <div class="modal-header">
                <h2 class="modal-title" id="regTitle">Crear Nueva Cuenta</h2>
                <button type="button" class="modal-close" onclick="closeRegisterModal()" aria-label="Cerrar">&times;</button>
            </div>
            <form method="POST" action="index.php">
                <input type="hidden" name="action" value="register">

                <div class="form-group">
                    <label for="reg_nombre">Nombre *</label>
                    <input type="text" name="nombre" id="reg_nombre" class="form-control" placeholder="Ej: María" required>
                </div>

                <div class="form-group">
                    <label for="reg_apellido">Apellido *</label>
                    <input type="text" name="apellido" id="reg_apellido" class="form-control" placeholder="Ej: Ramos" required>
                </div>

                <div class="form-group">
                    <label for="reg_email">Correo Electrónico (Usuario) *</label>
                    <input type="email" name="email" id="reg_email" class="form-control" placeholder="ejemplo@correo.com" required>
                </div>

                <div class="form-group">
                    <label for="reg_password">Contraseña *</label>
                    <input type="password" name="password" id="reg_password" class="form-control" placeholder="Mínimo 6 caracteres" minlength="6" required>
                </div>

                <div class="form-group">
                    <label for="reg_telefono">Teléfono / WhatsApp *</label>
                    <input type="text" name="telefono" id="reg_telefono" class="form-control" placeholder="+51 987 654 321" required>
                </div>

                <div class="form-group">
                    <label for="reg_direccion">Dirección de Entrega</label>
                    <input type="text" name="direccion" id="reg_direccion" class="form-control" placeholder="Calle, Av., Nro de casa o dpto">
                </div>

                <div class="form-group">
                    <label for="reg_rol">Tipo de Usuario</label>
                    <select name="rol" id="reg_rol" class="form-control">
                        <option value="cliente" selected>Cliente (Comprar productos)</option>
                        <option value="repartidor">Repartidor (Entregar pedidos)</option>
                        <option value="administrador">Administrador (Gestión de catálogo)</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-admin-action" onclick="closeRegisterModal()" style="background:#64748b;">Cancelar</button>
                    <button type="submit" class="btn-admin-action" style="background:#06233d;">Registrar Cuenta</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function fillCredentials(user, pass) {
            document.getElementById('inputUsuario').value = user;
            document.getElementById('inputPassword').value = pass;
            document.getElementById('btnLoginSubmit').focus();
        }

        function openRegisterModal() {
            document.getElementById('registerModal').classList.add('active');
        }

        function closeRegisterModal() {
            document.getElementById('registerModal').classList.remove('active');
        }

        // Cerrar modal al hacer click fuera
        window.addEventListener('click', function(e) {
            const modal = document.getElementById('registerModal');
            if (e.target === modal) {
                closeRegisterModal();
            }
        });
    </script>
</body>
</html>
