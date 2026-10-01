<?php
function currentUser(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    try {
        $stmt = getDB()->prepare('SELECT id, nombre, apellido, email, telefono, direccion_defecto AS direccion, rol, activo FROM usuarios WHERE id = ?');
        $stmt->execute([(int)$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if (!$user || !$user['activo']) {
            unset($_SESSION['user_id'], $_SESSION['user_rol']);
            return null;
        }
        foreach (['nombre', 'apellido', 'email', 'telefono', 'direccion', 'rol'] as $field) {
            $_SESSION['user_' . $field] = $user[$field];
        }
        return $user;
    } catch (Throwable $exception) {
        error_log($exception->getMessage());
        return null;
    }
}

function requireUser(array $roles = [], ?string $return = null): array {
    $user = currentUser();
    if (!$user) {
        $return = returnPage($return ?? basename($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
        header('Location: login.php?return=' . urlencode($return));
        exit;
    }
    if ($roles && !in_array($user['rol'], $roles, true)) {
        header('Location: ' . AuthService::getRedirectUrlForRole($user['rol']) . '?error=acceso_no_autorizado');
        exit;
    }
    return $user;
}
