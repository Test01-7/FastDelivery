<?php
require_once __DIR__ . '/../config/conexion.php';

class UsuarioService {
    public const ROLES = ['cliente', 'repartidor', 'administrador'];

    public static function listar(string $rol = '', string $orden = 'id', string $direccion = 'desc', int $page = 1): array {
        $orden = in_array($orden, ['id', 'nombre'], true) ? $orden : 'id';
        $direccion = $direccion === 'asc' ? 'ASC' : 'DESC';
        $where = in_array($rol, self::ROLES, true) ? ' WHERE rol = ?' : '';
        $params = $where ? [$rol] : [];
        $db = getDB();
        $stmt = $db->prepare('SELECT COUNT(*) FROM usuarios' . $where);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();
        $page = min(max(1, $page), max(1, (int)ceil($total / 10)));
        $offset = ($page - 1) * 10;
        $stmt = $db->prepare("SELECT id, nombre, apellido, email, telefono, rol, activo FROM usuarios{$where} ORDER BY {$orden} {$direccion}, id {$direccion} LIMIT 10 OFFSET {$offset}");
        $stmt->execute($params);
        return ['items' => $stmt->fetchAll(), 'total' => $total, 'page' => $page];
    }

    public static function obtener(int $id): ?array {
        $stmt = getDB()->prepare('SELECT id, nombre, apellido, email, telefono, rol, direccion_defecto, activo FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    private static function proteger(array $users, int $id, int $actor, string $rol, bool $activo, bool $delete = false): void {
        $user = null;
        $admins = 0;
        foreach ($users as $row) {
            if ((int)$row['id'] === $id) $user = $row;
            if ($row['rol'] === 'administrador' && $row['activo']) $admins++;
        }
        if (!$user) throw new DomainException('El usuario ya no existe.');
        if (($delete || !$activo || $rol !== 'administrador') &&
            ($id === $actor || ($user['rol'] === 'administrador' && $user['activo'] && $admins <= 1))) {
            throw new DomainException('No puedes eliminar, desactivar ni quitar el rol al administrador conectado o al último administrador activo.');
        }
    }

    public static function guardar(array $data, int $actor): array {
        $db = null;
        try {
            $id = (int)($data['id'] ?? 0);
            $nombre = trim($data['nombre'] ?? '');
            $apellido = trim($data['apellido'] ?? '');
            $email = trim($data['email'] ?? '');
            $telefono = trim($data['telefono'] ?? '');
            $direccion = trim($data['direccion'] ?? '');
            $rol = $data['rol'] ?? 'cliente';
            $activo = ($data['activo'] ?? '1') === '1';
            $password = $data['password'] ?? '';
            if (!$nombre || strlen($nombre) > 100 || strlen($apellido) > 100 || strlen($email) > 150 ||
                !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($rol, self::ROLES, true) ||
                !$telefono || strlen($telefono) > 20 || strlen($direccion) > 255) {
                throw new DomainException('Completa los datos válidos del usuario.');
            }
            if ((!$id || $password !== '') && strlen($password) < 6) throw new DomainException('La contraseña debe tener al menos 6 caracteres.');
            $db = getDB();
            $db->beginTransaction();
            // Orden fijo de bloqueo para proteger el último administrador ante cambios simultáneos.
            $users = $db->query('SELECT id, rol, activo FROM usuarios ORDER BY id FOR UPDATE')->fetchAll();
            if ($id) self::proteger($users, $id, $actor, $rol, $activo);
            $stmt = $db->prepare('SELECT id FROM usuarios WHERE email = ? AND id <> ?');
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) throw new DomainException('Este correo electrónico ya está registrado.');
            $params = [$nombre, $apellido, $email, $telefono, $direccion, $rol, (int)$activo];
            if ($id) {
                $sql = 'UPDATE usuarios SET nombre=?, apellido=?, email=?, telefono=?, direccion_defecto=?, rol=?, activo=?';
                if ($password !== '') { $sql .= ', password_hash=?'; $params[] = password_hash($password, PASSWORD_BCRYPT); }
                $sql .= ' WHERE id=?';
                $params[] = $id;
            } else {
                $sql = 'INSERT INTO usuarios (nombre, apellido, email, telefono, direccion_defecto, rol, activo, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
                $params[] = password_hash($password, PASSWORD_BCRYPT);
            }
            $db->prepare($sql)->execute($params);
            $db->commit();
            return ['success' => true, 'message' => 'Usuario guardado correctamente.'];
        } catch (Throwable $exception) {
            if ($db && $db->inTransaction()) $db->rollBack();
            error_log($exception->getMessage());
            return ['success' => false, 'message' => $exception instanceof DomainException ? $exception->getMessage() : 'No se pudo guardar el usuario. Comprueba la conexión y el correo.'];
        }
    }

    public static function retirar(int $id, int $actor, bool $delete): array {
        $db = null;
        try {
            $db = getDB();
            $db->beginTransaction();
            $users = $db->query('SELECT id, rol, activo FROM usuarios ORDER BY id FOR UPDATE')->fetchAll();
            self::proteger($users, $id, $actor, '', false, $delete);
            if ($delete) {
                $stmt = $db->prepare('SELECT id FROM pedidos WHERE cliente_id = ? OR repartidor_id = ? LIMIT 1');
                $stmt->execute([$id, $id]);
                if ($stmt->fetch()) throw new DomainException('El usuario tiene pedidos vinculados. Desactiva la cuenta para conservar el historial.');
                $db->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);
            } else {
                $db->prepare('UPDATE usuarios SET activo = 0 WHERE id = ?')->execute([$id]);
            }
            $db->commit();
            return ['success' => true, 'message' => $delete ? 'Usuario eliminado de la base de datos.' : 'Cuenta desactivada; sus datos se conservaron.'];
        } catch (Throwable $exception) {
            if ($db && $db->inTransaction()) $db->rollBack();
            error_log($exception->getMessage());
            return ['success' => false, 'message' => $exception instanceof DomainException ? $exception->getMessage() : 'No se pudo realizar la operación.'];
        }
    }
}
