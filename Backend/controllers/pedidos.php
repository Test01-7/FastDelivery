<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../services/pedidos.php';
header('Content-Type: application/json; charset=utf-8');

function pedidoResponse(array $body, int $status = 200): void {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') pedidoResponse(['status' => 'error', 'mensaje' => 'Método de solicitud no válido.'], 405);
$user = currentUser();
if (!$user) pedidoResponse(['status' => 'error', 'mensaje' => 'Inicia sesión para comprar.'], 401);
$token = $_POST['checkout_token'] ?? '';
if (!is_string($token) || !preg_match('/^[a-f0-9]{32}$/D', $token) || !isset($_SESSION['checkout_requests'][$user['id']][$token])) {
    pedidoResponse(['status' => 'error', 'mensaje' => 'Recarga la página para iniciar una nueva compra.'], 409);
}
$request = $_SESSION['checkout_requests'][$user['id']][$token];
if (is_array($request)) pedidoResponse(['status' => 'success', 'mensaje' => 'Pedido confirmado.', 'pedido' => $request]);

// Lista explícita: nunca se reciben ni persisten campos bancarios.
$data = [];
foreach (['nombre', 'direccion', 'referencia', 'telefono', 'carrito'] as $field) {
    if (isset($_POST[$field]) && !is_string($_POST[$field])) pedidoResponse(['status' => 'error', 'mensaje' => 'Datos de pedido inválidos.'], 422);
    $data[$field] = $_POST[$field] ?? '';
}
$result = PedidoService::crearPedido($data);
if (!$result['success']) pedidoResponse(['status' => 'error', 'mensaje' => $result['message']], 422);
$pedido = $result['pedido'];
$_SESSION['checkout_requests'][$user['id']][$token] = $pedido;
$_SESSION['ultimo_pedido'] = $pedido;
pedidoResponse(['status' => 'success', 'mensaje' => 'Pedido registrado con pago simulado.', 'pedido' => $pedido]);
