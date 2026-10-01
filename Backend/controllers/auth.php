<?php
require_once __DIR__ . '/../bootstrap.php';

function handleAuth(string $action): array {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return [];
    requireScalarPost();
    if ($action === 'register') {
        $data = $_POST;
        $data['rol'] = 'cliente';
        $result = AuthService::register($data);
        if ($result['success']) {
            flash($result['message']);
            header('Location: login.php?return=' . urlencode(returnPage($_POST['return'] ?? null)));
            exit;
        }
    } else {
        $result = isset($_POST['demo_role'])
            ? AuthService::demo($_POST['demo_role'])
            : AuthService::login($_POST['email'] ?? '', $_POST['password'] ?? '');
        if ($result['success']) {
            $destination = $result['rol'] === 'cliente' ? returnPage($_POST['return'] ?? null) : $result['redirect'];
            header('Location: ' . $destination);
            exit;
        }
    }
    return $result;
}
