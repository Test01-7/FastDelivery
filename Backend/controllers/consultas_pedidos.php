<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../services/pedidos.php';
$currentUser = requireUser();

function pedidosDelUsuario(array $user): array {
    if ($user['rol'] === 'administrador') return PedidoService::getPedidos();
    if ($user['rol'] === 'repartidor') return PedidoService::getPedidos(null, (int)$user['id']);
    return PedidoService::getPedidos((int)$user['id']);
}

function pedidoDelUsuario(string $code, array $user): ?array {
    if (!preg_match('/^(?:FD-)?[0-9]+$/D', $code)) return null;
    $pedido = PedidoService::getPedidoById($code);
    if (!$pedido) return null;
    if ($user['rol'] === 'administrador' ||
        ($user['rol'] === 'cliente' && (int)$pedido['cliente_id'] === (int)$user['id']) ||
        ($user['rol'] === 'repartidor' && (int)$pedido['repartidor_id'] === (int)$user['id'])) return $pedido;
    return null;
}
