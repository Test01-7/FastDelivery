<?php
/**
 * Fast Delivery - Servicio de Autenticación y Control de Acceso (RBAC)
 * Manejo de sesiones, hash de contraseñas y redirección según rol
 */

require_once __DIR__ . '/../config/conexion.php';

class AuthService {
    
    /**
     * Iniciar sesión con email o nombre de usuario y contraseña
     */
    public static function login(string $identificador, string $password): array {
        $identificador = trim($identificador);
        // Las contraseñas conservan sus espacios originales.

        if (empty($identificador) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Por favor ingrese su usuario/email y contraseña.'
            ];
        }

        try {
            $db = getDB();
            // Permite ingresar con email o con nombre de usuario
            $stmt = $db->prepare("
                SELECT id, nombre, apellido, email, password_hash, telefono, rol, direccion_defecto, activo
                FROM usuarios 
                WHERE email = :email OR nombre = :nombre
                LIMIT 1
            ");
            $stmt->execute([
                ':email' => $identificador,
                ':nombre' => $identificador
            ]);
            $user = $stmt->fetch();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'El usuario o correo electrónico no se encuentra registrado.'
                ];
            }

            if (!$user['activo']) {
                return [
                    'success' => false,
                    'message' => 'Esta cuenta se encuentra desactivada. Contacte al administrador.'
                ];
            }

            if (!password_verify($password, $user['password_hash'])) {
                return [
                    'success' => false,
                    'message' => 'Contraseña incorrecta. Verifique sus credenciales.'
                ];
            }

            return self::crearSesion($user);

        } catch (Exception $e) {
            error_log("Error en AuthService::login: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Ocurrió un error en el servidor. Intente nuevamente.'
            ];
        }
    }

    private static function crearSesion(array $user): array {
        // Regenerar ID de sesión para prevenir Session Fixation si las cabeceras no se han enviado
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!headers_sent()) {
            session_regenerate_id(true);
        }

        // Guardar datos en la sesión
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_nombre'] = $user['nombre'];
        $_SESSION['user_apellido'] = $user['apellido'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_rol'] = $user['rol'];
        $_SESSION['user_telefono'] = $user['telefono'];
        $_SESSION['user_direccion'] = $user['direccion_defecto'];
        $_SESSION['logged_in_time'] = time();

        // Determinar la URL de redirección según el rol de la base de datos
        $redirectUrl = self::getRedirectUrlForRole($user['rol']);

        return [
            'success' => true,
            'message' => 'Inicio de sesión exitoso.',
            'rol' => $user['rol'],
            'user' => [
                'id' => $user['id'],
                'nombre' => $user['nombre'] . ' ' . $user['apellido'],
                'email' => $user['email'],
                'rol' => $user['rol']
            ],
            'redirect' => $redirectUrl
        ];
    }

    /**
     * Registro de nuevo cliente
     */
    public static function register(array $data): array {
        $nombre = trim($data['nombre'] ?? '');
        $apellido = trim($data['apellido'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $telefono = trim($data['telefono'] ?? '');
        $direccion = trim($data['direccion'] ?? '');
        $rol = 'cliente'; // El registro público no asigna privilegios.

        if (empty($nombre) || empty($apellido) || empty($email) || empty($password) || empty($telefono) || strlen($nombre) > 100 || strlen($apellido) > 100 || strlen($email) > 150 || strlen($telefono) > 20 || strlen($direccion) > 255) {
            return [
                'success' => false,
                'message' => 'Por favor complete todos los campos obligatorios.'
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'El correo electrónico ingresado no tiene un formato válido.'
            ];
        }

        if (strlen($password) < 6) {
            return [
                'success' => false,
                'message' => 'La contraseña debe tener al menos 6 caracteres.'
            ];
        }

        try {
            $db = getDB();

            // Verificar si el email ya está registrado
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'El correo electrónico ya está registrado. Inicie sesión.'
                ];
            }

            // Encriptar contraseña
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            $insertStmt = $db->prepare("
                INSERT INTO usuarios (nombre, apellido, email, password_hash, telefono, rol, direccion_defecto, activo)
                VALUES (:nombre, :apellido, :email, :password_hash, :telefono, :rol, :direccion, 1)
            ");

            $insertStmt->execute([
                ':nombre' => $nombre,
                ':apellido' => $apellido,
                ':email' => $email,
                ':password_hash' => $passwordHash,
                ':telefono' => $telefono,
                ':rol' => $rol,
                ':direccion' => $direccion
            ]);

            $newUserId = $db->lastInsertId();

            return [
                'success' => true,
                'message' => 'Usuario registrado exitosamente. Ahora puede iniciar sesión.',
                'userId' => $newUserId
            ];

        } catch (Exception $e) {
            error_log("Error en AuthService::register: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'No se pudo registrar el usuario. Intente nuevamente.'
            ];
        }
    }

    /**
     * Devuelve la página de destino según el rol
     */
    public static function getRedirectUrlForRole(string $rol): string {
        switch ($rol) {
            case 'administrador':
                return 'admin.php';
            case 'cliente':
                return 'index.php';
            case 'repartidor':
                return 'repartidor.php';
            default:
                return 'index.php';
        }
    }

    /**
     * Verificar si el usuario tiene sesión activa y los roles requeridos
     */
    public static function requireAuth(array $allowedRoles = []): array {
        require_once __DIR__ . '/../middleware/auth.php';
        return requireUser($allowedRoles);
    }

    /**
     * Cerrar sesión de manera segura
     */
    public static function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies") && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
    }
}
