<?php
require_once __DIR__ . '/../config/conexion.php';

trait OperacionesPedidos {
    public const ESTADOS = ['pendiente', 'confirmado', 'en_preparacion', 'en_camino', 'entregado', 'cancelado'];

    public static function crearPedido(array $data): array {
        $db = null;
        try {
            $cliente = (int)($_SESSION['user_id'] ?? 0);
            if (!$cliente) throw new DomainException('Inicia sesión para completar la compra.');
            foreach (['nombre', 'telefono', 'direccion'] as $campo) {
                if (!trim($data[$campo] ?? '')) throw new DomainException('Completa los datos de entrega y contacto.');
            }
            if (strlen($data['direccion']) > 255 || strlen($data['referencia'] ?? '') > 255 || strlen($data['telefono']) > 20 || strlen($data['nombre']) > 200) {
                throw new DomainException('Revisa la longitud de los datos de entrega.');
            }
            $raw = $data['carrito'] ?? [];
            $items = is_array($raw) ? $raw : json_decode($raw, true);
            if (!is_array($items) || !$items || count($items) > 100) throw new DomainException('El carrito debe contener entre 1 y 100 productos.');
            $cantidades = [];
            foreach ($items as $item) {
                if (!is_array($item)) throw new DomainException('El carrito no es válido.');
                $id = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                $cantidad = filter_var($item['cantidad'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100000]]);
                if (!$id || !$cantidad) throw new DomainException('Hay cantidades o productos inválidos.');
                $cantidades[$id] = ($cantidades[$id] ?? 0) + $cantidad;
            }
            ksort($cantidades);
            $db = getDB();
            $db->beginTransaction();
            $user = $db->prepare('SELECT id FROM usuarios WHERE id = ? AND activo = 1 FOR UPDATE');
            $user->execute([$cliente]);
            if (!$user->fetch()) throw new DomainException('La cuenta no está disponible.');
            $subtotal = 0;
            $validos = [];
            $select = $db->prepare('SELECT id, nombre, precio, stock, unidad_medida, disponible FROM productos WHERE id = ? FOR UPDATE');
            foreach ($cantidades as $id => $cantidad) {
                $select->execute([$id]);
                $producto = $select->fetch();
                if (!$producto || !$producto['disponible']) throw new DomainException('Un producto ya no está disponible. Revisa tu carrito.');
                if ($cantidad > (int)$producto['stock']) throw new DomainException('Stock insuficiente para ' . $producto['nombre'] . '.');
                $precio = (float)$producto['precio'];
                $importe = round($precio * $cantidad, 2);
                $subtotal += $importe;
                $validos[] = ['id' => $id, 'producto_id' => $id, 'nombre' => $producto['nombre'], 'cantidad' => $cantidad,
                    'precio' => $precio, 'precio_unitario' => $precio, 'subtotal' => $importe, 'unidad' => $producto['unidad_medida']];
            }
            $subtotal = round($subtotal, 2);
            $envio = $subtotal >= 50 ? 0 : 5;
            $total = $subtotal + $envio;
            if ($subtotal <= 0 || $total > 99999999.99) throw new DomainException('El importe del pedido no es válido.');
            // Metadatos de entrega en el historial existente, sin añadir columnas ni datos bancarios.
            $contacto = 'FD:' . json_encode(['n' => trim($data['nombre']), 't' => trim($data['telefono']), 'sim' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            if (preg_match_all('/./us', $contacto) > 255) throw new DomainException('El nombre de contacto es demasiado largo.');
            $db->prepare("INSERT INTO pedidos (cliente_id, estado, total, costo_envio, direccion_entrega, referencia_direccion) VALUES (?, 'pendiente', ?, ?, ?, ?)")
                ->execute([$cliente, $total, $envio, trim($data['direccion']), trim($data['referencia'] ?? '')]);
            $id = (int)$db->lastInsertId();
            $detalle = $db->prepare('INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)');
            $stock = $db->prepare('UPDATE productos SET stock = stock - ? WHERE id = ?');
            foreach ($validos as $item) {
                $detalle->execute([$id, $item['id'], $item['cantidad'], $item['precio'], $item['subtotal']]);
                $stock->execute([$item['cantidad'], $item['id']]);
            }
            $db->prepare("INSERT INTO pagos (pedido_id, metodo_pago, monto, estado_pago, numero_operacion) VALUES (?, 'tarjeta', ?, 'completado', ?)")
                ->execute([$id, $total, 'SIM-' . bin2hex(random_bytes(8))]);
            $db->prepare("INSERT INTO historial_estados (pedido_id, estado_anterior, estado_nuevo, observacion) VALUES (?, NULL, 'pendiente', ?)")->execute([$id, $contacto]);
            $db->commit();
            return ['success' => true, 'pedido' => ['id' => $id, 'id_pedido' => 'FD-' . str_pad($id, 5, '0', STR_PAD_LEFT),
                'fecha' => date('d/m/Y H:i'), 'cliente' => trim($data['nombre']), 'telefono' => trim($data['telefono']),
                'direccion' => trim($data['direccion']), 'referencia' => trim($data['referencia'] ?? ''), 'cliente_id' => $cliente,
                'metodo_pago' => 'Tarjeta', 'subtotal' => $subtotal, 'envio' => $envio, 'total' => $total, 'estado' => 'Pendiente', 'items' => $validos]];
        } catch (Throwable $exception) {
            if ($db && $db->inTransaction()) $db->rollBack();
            error_log($exception->getMessage());
            return ['success' => false, 'message' => $exception instanceof DomainException ? $exception->getMessage() : 'No se pudo guardar el pedido. Intenta nuevamente.'];
        }
    }

    public static function listarAdmin(int $page = 1): array {
        $db = getDB();
        $total = (int)$db->query('SELECT COUNT(*) FROM pedidos')->fetchColumn();
        $page = min(max(1, $page), max(1, (int)ceil($total / 10)));
        $offset = ($page - 1) * 10;
        $items = $db->query("SELECT p.*, CONCAT(u.nombre, ' ', u.apellido) AS cliente FROM pedidos p JOIN usuarios u ON p.cliente_id = u.id ORDER BY p.id DESC LIMIT 10 OFFSET {$offset}")->fetchAll();
        return ['items' => $items, 'total' => $total, 'page' => $page];
    }

    public static function editarOperacion(int $id, array $data): array {
        $db = null;
        try {
            $estado = $data['estado'] ?? '';
            if (!in_array($estado, self::ESTADOS, true)) throw new DomainException('Selecciona un estado válido.');
            $db = getDB();
            $db->beginTransaction();
            // Usuarios antes que pedidos, igual que creación y gestión de cuentas.
            $usuarios = $db->query('SELECT id, rol, activo FROM usuarios ORDER BY id FOR UPDATE')->fetchAll();
            $stmt = $db->prepare('SELECT * FROM pedidos WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $pedido = $stmt->fetch();
            if (!$pedido) throw new DomainException('El pedido no existe.');
            if ($pedido['estado'] === 'cancelado') throw new DomainException('El pedido ya está cancelado y no se puede modificar.');
            if ($pedido['estado'] === 'entregado' && $estado !== 'entregado') throw new DomainException('Un pedido entregado no se puede cancelar ni volver a un estado anterior.');
            $direccion = trim($data['direccion'] ?? $pedido['direccion_entrega']);
            $referencia = trim($data['referencia'] ?? $pedido['referencia_direccion'] ?? '');
            if (!$direccion || strlen($direccion) > 255 || strlen($referencia) > 255) throw new DomainException('Introduce una dirección y referencia válidas.');
            $repartidor = array_key_exists('repartidor_id', $data) ? (int)$data['repartidor_id'] : (int)$pedido['repartidor_id'];
            if ($repartidor) {
                $valido = false;
                foreach ($usuarios as $usuario) {
                    if ((int)$usuario['id'] === $repartidor && $usuario['rol'] === 'repartidor' && $usuario['activo']) $valido = true;
                }
                if (!$valido && !($estado === 'cancelado' && $repartidor === (int)$pedido['repartidor_id'])) throw new DomainException('Selecciona un repartidor activo.');
            }
            if ($estado === 'cancelado') {
                $stmt = $db->prepare('SELECT producto_id, cantidad FROM detalle_pedidos WHERE pedido_id = ? ORDER BY producto_id');
                $stmt->execute([$id]);
                $update = $db->prepare('UPDATE productos SET stock = stock + ? WHERE id = ?');
                foreach ($stmt->fetchAll() as $item) $update->execute([$item['cantidad'], $item['producto_id']]);
            }
            $db->prepare("UPDATE pedidos SET direccion_entrega=?, referencia_direccion=?, repartidor_id=?, estado=?, fecha_entrega=CASE WHEN ?='entregado' THEN COALESCE(fecha_entrega, NOW()) ELSE fecha_entrega END WHERE id=?")
                ->execute([$direccion, $referencia, $repartidor ?: null, $estado, $estado, $id]);
            $db->prepare('INSERT INTO historial_estados (pedido_id, estado_anterior, estado_nuevo, observacion) VALUES (?, ?, ?, ?)')
                ->execute([$id, $pedido['estado'], $estado, $estado === 'cancelado' ? 'Cancelación; stock restaurado, sin reembolso' : 'Actualización desde administración']);
            $db->commit();
            return ['success' => true, 'message' => $estado === 'cancelado' ? 'Pedido cancelado. Se restauró el stock; no se realizó un reembolso.' : 'Pedido actualizado correctamente.'];
        } catch (Throwable $exception) {
            if ($db && $db->inTransaction()) $db->rollBack();
            error_log($exception->getMessage());
            return ['success' => false, 'message' => $exception instanceof DomainException ? $exception->getMessage() : 'No se pudo actualizar el pedido.'];
        }
    }

    public static function actualizarEstado(int $id, string $estado, ?int $repartidor = null): bool {
        $data = ['estado' => $estado];
        if ($repartidor !== null) $data['repartidor_id'] = $repartidor;
        return self::editarOperacion($id, $data)['success'];
    }
}
