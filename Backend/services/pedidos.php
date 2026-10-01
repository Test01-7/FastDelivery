<?php
/**
 * Fast Delivery - Servicio de Pedidos y Seguimiento
 * Gestión de pedidos, ítems, pagos e historial de estados en MySQL
 */

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/operaciones_pedidos.php';

class PedidoService {

    use OperacionesPedidos;

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

            $stmtContacto = $db->prepare('SELECT observacion FROM historial_estados WHERE pedido_id = ? AND estado_anterior IS NULL ORDER BY id LIMIT 1');
            $stmtContacto->execute([$numericId]);
            $observacion = $stmtContacto->fetchColumn();
            $contacto = is_string($observacion) && str_starts_with($observacion, 'FD:') ? json_decode(substr($observacion, 3), true) : [];

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
                'cliente_id' => (int)$ped['cliente_id'],
                'repartidor_id' => $ped['repartidor_id'],
                'id_pedido' => 'FD-' . str_pad($ped['id'], 5, '0', STR_PAD_LEFT),
                'fecha' => date('d/m/Y H:i', strtotime($ped['fecha_pedido'])),
                'cliente' => $contacto['n'] ?? trim($ped['cliente_nombre'] . ' ' . $ped['cliente_apellido']),
                'telefono' => $contacto['t'] ?? $ped['cliente_telefono'],
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
    public static function getPedidos(?int $clienteId = null, ?int $repartidorId = null): array {
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
            if ($repartidorId !== null) {
                $sql .= ($clienteId !== null && $clienteId > 0 ? ' AND' : ' WHERE') . ' p.repartidor_id = :repartidor_id';
                $params[':repartidor_id'] = $repartidorId;
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

}
