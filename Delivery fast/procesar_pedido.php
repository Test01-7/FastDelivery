<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../Backend/services/pedidos.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $direccion = isset($_POST['direccion']) ? trim($_POST['direccion']) : '';
    $referencia = isset($_POST['referencia']) ? trim($_POST['referencia']) : '';
    $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
    $metodo_pago = isset($_POST['metodo_pago']) ? trim($_POST['metodo_pago']) : 'Efectivo';
    $carrito_raw = isset($_POST['carrito']) ? $_POST['carrito'] : '[]';

    if (empty($nombre) || empty($direccion) || empty($telefono)) {
        echo json_encode([
            'status' => 'error',
            'mensaje' => 'Por favor completa todos los campos obligatorios.'
        ]);
        exit;
    }

    $res = PedidoService::crearPedido([
        'nombre' => $nombre,
        'direccion' => $direccion,
        'referencia' => $referencia,
        'telefono' => $telefono,
        'metodo_pago' => $metodo_pago,
        'carrito' => $carrito_raw
    ]);

    if ($res['success']) {
        $nuevo_pedido = $res['pedido'];
        if (!isset($_SESSION['pedidos']) || !is_array($_SESSION['pedidos'])) {
            $_SESSION['pedidos'] = [];
        }
        array_unshift($_SESSION['pedidos'], $nuevo_pedido);
        $_SESSION['ultimo_pedido'] = $nuevo_pedido;

        echo json_encode([
            'status' => 'success',
            'mensaje' => '¡Pedido registrado exitosamente en la base de datos MySQL!',
            'pedido' => $nuevo_pedido
        ]);
        exit;
    } else {
        echo json_encode([
            'status' => 'error',
            'mensaje' => $res['message']
        ]);
        exit;
    }
}

echo json_encode([
    'status' => 'error',
    'mensaje' => 'Método de solicitud no válido.'
]);
