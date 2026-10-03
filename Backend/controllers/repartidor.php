<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../services/repartos.php';
$currentUser = requireUser(['repartidor']);
$panel = in_array($_GET['panel'] ?? '', ['disponibles', 'entregas', 'historial'], true) ? $_GET['panel'] : 'disponibles';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireScalarPost();
    $id = filter_var($_POST['pedido_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $result = RepartoService::operar((int)$currentUser['id'], $id ?: 0, $_POST['accion'] ?? '');
    flash($result['message'], $result['success']);
    $destination = $result['success'] ? (($_POST['accion'] ?? '') === 'entregar' ? 'historial' : 'entregas') : $panel;
    header('Location: repartidor.php?panel=' . $destination);
    exit;
}

$notice = takeFlash();
try {
    $data = RepartoService::listar((int)$currentUser['id'], $panel, max(1, (int)($_GET['page'] ?? 1)));
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    $notice = ['message' => 'No se pudieron cargar los pedidos. Inténtalo nuevamente.', 'success' => false];
    $data = ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'summary' => ['disponibles' => 0, 'entregas' => 0, 'completadas' => 0]];
}
