<?php
require_once __DIR__ . '/pedidos.php';

class RepartoService {
    private const DISPONIBLES = "repartidor_id IS NULL AND estado IN ('pendiente', 'confirmado', 'en_preparacion')";

    public static function listar(int $repartidor, string $panel, int $page = 1): array {
        $db = getDB();
        $where = self::DISPONIBLES;
        $params = [];
        if ($panel !== 'disponibles') {
            $where = "repartidor_id = ? AND estado " . ($panel === 'historial'
                ? "IN ('entregado', 'cancelado')" : "NOT IN ('entregado', 'cancelado')");
            $params = [$repartidor];
        }
        $count = $db->prepare("SELECT COUNT(*) FROM pedidos WHERE $where");
        $count->execute($params);
        $total = (int)$count->fetchColumn();
        $pages = max(1, (int)ceil($total / 12));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * 12;
        $query = $db->prepare("SELECT id FROM pedidos WHERE $where ORDER BY id " . ($panel === 'historial' ? 'DESC' : 'ASC') . " LIMIT 12 OFFSET $offset");
        $query->execute($params);
        $items = [];
        foreach ($query->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $pedido = PedidoService::getPedidoById((string)$id);
            // Volver a comprobar el alcance si cambió una asignación durante la consulta.
            if (!$pedido) continue;
            if ($panel === 'disponibles' && ($pedido['repartidor_id'] !== null || !in_array($pedido['estado_db'], ['pendiente', 'confirmado', 'en_preparacion'], true))) continue;
            if ($panel !== 'disponibles' && (int)$pedido['repartidor_id'] !== $repartidor) continue;
            if ($panel !== 'disponibles' && (in_array($pedido['estado_db'], ['entregado', 'cancelado'], true) !== ($panel === 'historial'))) continue;
            $items[] = $pedido;
        }
        $summary = $db->prepare("SELECT
            COALESCE(SUM(" . self::DISPONIBLES . "), 0) AS disponibles,
            COALESCE(SUM(repartidor_id = ? AND estado NOT IN ('entregado', 'cancelado')), 0) AS entregas,
            COALESCE(SUM(repartidor_id = ? AND estado = 'entregado'), 0) AS completadas
            FROM pedidos");
        $summary->execute([$repartidor, $repartidor]);
        return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages, 'summary' => $summary->fetch()];
    }

    public static function operar(int $repartidor, int $pedidoId, string $accion): array {
        $db = null;
        try {
            if ($pedidoId < 1 || !in_array($accion, ['tomar', 'enviar', 'entregar'], true)) throw new DomainException('La acción seleccionada no es válida.');
            $db = getDB();
            $db->beginTransaction();
            $actor = $db->prepare("SELECT id FROM usuarios WHERE id = ? AND rol = 'repartidor' AND activo = 1 FOR UPDATE");
            $actor->execute([$repartidor]);
            if (!$actor->fetchColumn()) throw new DomainException('Solo un repartidor activo puede gestionar entregas.');
            $query = $db->prepare('SELECT id, repartidor_id, estado FROM pedidos WHERE id = ? FOR UPDATE');
            $query->execute([$pedidoId]);
            $pedido = $query->fetch();
            if (!$pedido) throw new DomainException('El pedido no existe.');
            $estado = $pedido['estado'];
            if (in_array($estado, ['cancelado', 'entregado'], true)) throw new DomainException('Este pedido ya está finalizado y no se puede modificar.');
            if ($accion === 'tomar') {
                if ($pedido['repartidor_id'] !== null) throw new DomainException('Este pedido ya fue asignado a un repartidor. Actualiza la lista.');
                if (!in_array($estado, ['pendiente', 'confirmado', 'en_preparacion'], true)) throw new DomainException('Este pedido no está disponible para tomarlo.');
                $nuevo = $estado === 'pendiente' ? 'confirmado' : $estado;
                $message = 'Pedido tomado. Ya aparece en Mis entregas.';
                $observation = 'Pedido tomado por el repartidor #' . $repartidor;
            } else {
                if ((int)$pedido['repartidor_id'] !== $repartidor) throw new DomainException('Solo puedes gestionar los pedidos asignados a tu cuenta.');
                if ($accion === 'enviar') {
                    if (!in_array($estado, ['pendiente', 'confirmado', 'en_preparacion'], true)) throw new DomainException('El pedido ya está en camino.');
                    $nuevo = 'en_camino';
                    $message = 'Envío iniciado. El cliente ya puede ver que está en camino.';
                } else {
                    if ($estado !== 'en_camino') throw new DomainException('Inicia el envío antes de marcar el pedido como entregado.');
                    $nuevo = 'entregado';
                    $message = 'Entrega completada. El pedido se guardó en tu historial.';
                }
                $observation = 'Actualizado por el repartidor #' . $repartidor;
            }
            $db->prepare("UPDATE pedidos SET repartidor_id = ?, estado = ?, fecha_entrega = CASE WHEN ? = 'entregado' THEN COALESCE(fecha_entrega, NOW()) ELSE fecha_entrega END WHERE id = ?")
                ->execute([$repartidor, $nuevo, $nuevo, $pedidoId]);
            $db->prepare('INSERT INTO historial_estados (pedido_id, estado_anterior, estado_nuevo, observacion) VALUES (?, ?, ?, ?)')
                ->execute([$pedidoId, $estado, $nuevo, $observation]);
            $db->commit();
            return ['success' => true, 'message' => $message];
        } catch (Throwable $exception) {
            if ($db && $db->inTransaction()) $db->rollBack();
            error_log($exception->getMessage());
            return ['success' => false, 'message' => $exception instanceof DomainException ? $exception->getMessage() : 'No se pudo actualizar la entrega. Inténtalo nuevamente.'];
        }
    }
}
