<?php
/**
 * Fast Delivery - Servicio de Pedidos y Seguimiento
 * Gestión de pedidos, ítems, pagos e historial de estados en MySQL
 */

require_once __DIR__ . '/conexion.php';

class PedidoService {

    /**
     * Crear un nuevo pedido con sus detalles y pago en la base de datos
     */
    public static function crearPedido(array $data): array {
        try {
            $db = getDB();
            $db->beginTransaction();

            // Si hay sesión iniciada de usuario, usar su ID
            $cliente_id = 2; // Por defecto Juan Pérez (Cliente de prueba en SQL.db)
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (!empty($_SESSION['user_id'])) {
                $cliente_id = (int)$_SESSION['user_id'];
            }

            $direccion = trim($data['direccion'] ?? '');
            $referencia = trim($data['referencia'] ?? '');
            $nombre_contacto = trim($data['nombre'] ?? '');
            $telefono_contacto = trim($data['telefono'] ?? '');
            $metodo_pago = trim($data['metodo_pago'] ?? 'Efectivo');

            // Mapeo de método de pago al ENUM ('efectivo', 'yape', 'plin', 'tarjeta', 'transferencia')
            $metodo_map = [
                'Yape' => 'yape',
                'Plin' => 'plin',
                'Efectivo' => 'efectivo',
                'Tarjeta' => 'tarjeta',
                'Transferencia' => 'transferencia'
            ];
            $metodo_enum = $metodo_map[$metodo_pago] ?? 'efectivo';

            $items_raw = $data['carrito'] ?? $data['items'] ?? '[]';
            $items = is_array($items_raw) ? $items_raw : json_decode($items_raw, true);

            if (empty($items) || !is_array($items)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'El pedido debe contener al menos un producto.'];
            }

            $subtotal = 0;
            $itemsValidos = [];

            foreach ($items as $item) {
                $prodId = (int)($item['id'] ?? 0);
                $cant = max(1, (int)($item['cantidad'] ?? 1));

                // Obtener precio y stock actual de la base de datos
                $stmtProd = $db->prepare("SELECT id, nombre, precio, stock, unidad_medida FROM productos WHERE id = :id LIMIT 1");
                $stmtProd->execute([':id' => $prodId]);
                $prod = $stmtProd->fetch();

                if ($prod) {
                    $precioUnitario = (float)$prod['precio'];
                    $itemSubtotal = $precioUnitario * $cant;
                    $subtotal += $itemSubtotal;

                    $itemsValidos[] = [
                        'producto_id' => $prod['id'],
                        'nombre' => $prod['nombre'],
                        'cantidad' => $cant,
                        'precio_unitario' => $precioUnitario,
                        'subtotal' => $itemSubtotal,
                        'unidad' => $prod['unidad_medida']
                    ];

                    // Actualizar stock en la base de datos
                    $nuevoStock = max(0, (int)$prod['stock'] - $cant);
                    $stmtStock = $db->prepare("UPDATE productos SET stock = :stock WHERE id = :id");
                    $stmtStock->execute([':stock' => $nuevoStock, ':id' => $prod['id']]);
                }
            }

            if (empty($itemsValidos)) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Los productos seleccionados no se encuentran en la base de datos.'];
            }

            $costo_envio = ($subtotal >= 50 || $subtotal === 0) ? 0.00 : 5.00;
            $total = $subtotal + $costo_envio;

            // Insertar en tabla pedidos
            $stmtPed = $db->prepare("
                INSERT INTO pedidos (cliente_id, estado, total, costo_envio, direccion_entrega, referencia_direccion, fecha_pedido)
                VALUES (:cliente_id, 'pendiente', :total, :costo_envio, :direccion, :referencia, NOW())
            ");
            $stmtPed->execute([
                ':cliente_id' => $cliente_id,
                ':total' => $total,
                ':costo_envio' => $costo_envio,
                ':direccion' => $direccion,
                ':referencia' => $referencia
            ]);

            $pedidoId = (int)$db->lastInsertId();
            $codigoPedido = 'FD-' . str_pad($pedidoId, 5, '0', STR_PAD_LEFT);

            // Insertar detalles de productos
            $stmtDet = $db->prepare("
                INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
                VALUES (:pedido_id, :producto_id, :cantidad, :precio_unitario, :subtotal)
            ");
            foreach ($itemsValidos as $item) {
                $stmtDet->execute([
                    ':pedido_id' => $pedidoId,
                    ':producto_id' => $item['producto_id'],
                    ':cantidad' => $item['cantidad'],
                    ':precio_unitario' => $item['precio_unitario'],
                    ':subtotal' => $item['subtotal']
                ]);
            }

            // Insertar registro de pago
            $stmtPago = $db->prepare("
                INSERT INTO pagos (pedido_id, metodo_pago, monto, estado_pago, fecha_pago)
                VALUES (:pedido_id, :metodo_pago, :monto, 'completado', NOW())
            ");
            $stmtPago->execute([
                ':pedido_id' => $pedidoId,
                ':metodo_pago' => $metodo_enum,
                ':monto' => $total
            ]);

            // Insertar historial de estado
            $stmtHist = $db->prepare("
                INSERT INTO historial_estados (pedido_id, estado_anterior, estado_nuevo, observacion)
                VALUES (:pedido_id, NULL, 'pendiente', 'Pedido registrado desde la plataforma Fast Delivery')
            ");
            $stmtHist->execute([':pedido_id' => $pedidoId]);

            $db->commit();

            return [
                'success' => true,
                'pedido' => [
                    'id' => $pedidoId,
                    'id_pedido' => $codigoPedido,
                    'fecha' => date('d/m/Y H:i'),
                    'cliente' => $nombre_contacto ?: 'Cliente',
                    'direccion' => $direccion,
                    'referencia' => $referencia,
                    'telefono' => $telefono_contacto,
                    'metodo_pago' => ucfirst($metodo_enum),
                    'subtotal' => $subtotal,
                    'envio' => $costo_envio,
                    'total' => $total,
                    'estado' => 'Pendiente',
                    'items' => $itemsValidos
                ]
            ];

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Error en PedidoService::crearPedido: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al guardar el pedido: ' . $e->getMessage()];
        }
    }

    /**
     * Obtener un pedido por ID o Código (ej: FD-00001)
     */
    public static function getPedidoById(string $idOrCode): ?array {
        try {
            $db = getDB();
            $numericId = (int)preg_replace('/[^0-9]/', '', $idOrCode);
            if ($numericId <= 0) return null;

            $stmt = $db->prepare("
                SELECT p.*, u.nombre AS cliente_nombre, u.apellido AS cliente_apellido, u.telefono AS cliente_telefono
                FROM pedidos p
                JOIN usuarios u ON p.cliente_id = u.id
                WHERE p.id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $numericId]);
            $ped = $stmt->fetch();

            if (!$ped) return null;

            $estadoMap = [
                'pendiente' => 'Pendiente',
                'confirmado' => 'Confirmado',
                'en_preparacion' => 'Preparando',
                'en_camino' => 'En camino',
                'entregado' => 'Entregado',
                'cancelado' => 'Cancelado'
            ];

            // Obtener detalles del pedido
            $stmtDet = $db->prepare("
                SELECT d.*, pr.nombre AS producto_nombre, pr.imagen_url, pr.unidad_medida
                FROM detalle_pedidos d
                JOIN productos pr ON d.producto_id = pr.id
                WHERE d.pedido_id = :pedido_id
            ");
            $stmtDet->execute([':pedido_id' => $numericId]);
            $detalles = $stmtDet->fetchAll();

            // Obtener pago
            $stmtPago = $db->prepare("SELECT metodo_pago FROM pagos WHERE pedido_id = :pedido_id LIMIT 1");
            $stmtPago->execute([':pedido_id' => $numericId]);
            $pago = $stmtPago->fetch();

            $items = [];
            foreach ($detalles as $d) {
                $items[] = [
                    'id' => $d['producto_id'],
                    'nombre' => $d['producto_nombre'],
                    'cantidad' => (int)$d['cantidad'],
                    'precio' => (float)$d['precio_unitario'],
                    'subtotal' => (float)$d['subtotal'],
                    'unidad' => $d['unidad_medida']
                ];
            }

            return [
                'id' => $ped['id'],
                'id_pedido' => 'FD-' . str_pad($ped['id'], 5, '0', STR_PAD_LEFT),
                'fecha' => date('d/m/Y H:i', strtotime($ped['fecha_pedido'])),
                'cliente' => trim($ped['cliente_nombre'] . ' ' . $ped['cliente_apellido']),
                'telefono' => $ped['cliente_telefono'],
                'direccion' => $ped['direccion_entrega'],
                'referencia' => $ped['referencia_direccion'],
                'metodo_pago' => ucfirst($pago['metodo_pago'] ?? 'Efectivo'),
                'subtotal' => (float)$ped['total'] - (float)$ped['costo_envio'],
                'envio' => (float)$ped['costo_envio'],
                'total' => (float)$ped['total'],
                'estado' => $estadoMap[$ped['estado']] ?? 'Pendiente',
                'estado_db' => $ped['estado'],
                'items' => $items
            ];
        } catch (Exception $e) {
            error_log("Error en getPedidoById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener lista de pedidos de un cliente o todos si clienteId es nulo
     */
    public static function getPedidos(?int $clienteId = null): array {
        try {
            $db = getDB();
            $sql = "
                SELECT p.id
                FROM pedidos p
            ";
            $params = [];
            if ($clienteId !== null && $clienteId > 0) {
                $sql .= " WHERE p.cliente_id = :cliente_id";
                $params[':cliente_id'] = $clienteId;
            }
            $sql .= " ORDER BY p.id DESC LIMIT 50";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $resultado = [];
            foreach ($ids as $id) {
                $ped = self::getPedidoById((string)$id);
                if ($ped) {
                    $resultado[] = $ped;
                }
            }
            return $resultado;
        } catch (Exception $e) {
            error_log("Error en getPedidos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar estado del pedido (para repartidor o administrador)
     */
    public static function actualizarEstado(int $pedidoId, string $nuevoEstado, ?int $repartidorId = null): bool {
        try {
            $db = getDB();

            $validos = ['pendiente', 'confirmado', 'en_preparacion', 'en_camino', 'entregado', 'cancelado'];
            if (!in_array($nuevoEstado, $validos)) {
                return false;
            }

            $stmtCurr = $db->prepare("SELECT estado FROM pedidos WHERE id = :id LIMIT 1");
            $stmtCurr->execute([':id' => $pedidoId]);
            $estadoAnterior = $stmtCurr->fetchColumn();

            $sql = "UPDATE pedidos SET estado = :estado";
            $params = [':estado' => $nuevoEstado, ':id' => $pedidoId];

            if ($repartidorId !== null) {
                $sql .= ", repartidor_id = :repartidor_id";
                $params[':repartidor_id'] = $repartidorId;
            }

            if ($nuevoEstado === 'entregado') {
                $sql .= ", fecha_entrega = NOW()";
            }

            $sql .= " WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            // Guardar en historial
            $stmtHist = $db->prepare("
                INSERT INTO historial_estados (pedido_id, estado_anterior, estado_nuevo, observacion)
                VALUES (:pedido_id, :anterior, :nuevo, 'Cambio de estado desde el panel')
            ");
            $stmtHist->execute([
                ':pedido_id' => $pedidoId,
                ':anterior' => $estadoAnterior ?: 'desconocido',
                ':nuevo' => $nuevoEstado
            ]);

            return true;
        } catch (Exception $e) {
            error_log("Error en actualizarEstado: " . $e->getMessage());
            return false;
        }
    }
}
