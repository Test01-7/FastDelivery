<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/middleware/security.php';
require_once __DIR__ . '/services/auth.php';
require_once __DIR__ . '/middleware/auth.php';

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function flash(string $message, bool $success = true): void {
    $_SESSION['flash'] = ['message' => $message, 'success' => $success];
}

function takeFlash(): ?array {
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $message;
}
