<?php
/**
 * FAST DELIVERY - Panel de Repartidor
 * Pedidos asignados y estado de entrega desde MySQL
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Backend/services/auth.php';
require_once __DIR__ . '/../Backend/services/pedidos.php';

$currentUser = AuthService::requireAuth(['repartidor', 'administrador']);

$successMsg = '';
$errorMsg = '';

// Procesar actualización de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pedidoId = (int)($_POST['pedido_id'] ?? 0);
    $nuevoEstado = $_POST['nuevo_estado'] ?? '';
    
    if ($pedidoId > 0 && !empty($nuevoEstado)) {
        $ok = PedidoService::actualizarEstado($pedidoId, $nuevoEstado, $currentUser['id']);
        if ($ok) {
            $successMsg = "Estado del pedido FD-" . str_pad($pedidoId, 5, '0', STR_PAD_LEFT) . " actualizado a: {$nuevoEstado}.";
        } else {
            $errorMsg = "No se pudo actualizar el estado del pedido.";
        }
    }
}

$pedidos = PedidoService::getPedidos(null);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fast Delivery - Panel de Repartidor</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="background:#edf2f7; min-height:100vh;">

    <header class="admin-topbar">
        <h1 class="admin-topbar-title">PANEL DE REPARTIDOR - ENTREGAS</h1>
        <div style="position: absolute; right: 24px;">
            <a href="logout.php" class="admin-topbar-logout">Cerrar Sesión</a>
        </div>
    </header>

    <main style="max-width: 960px; margin: 80px auto 40px auto; padding: 20px;">
        <div style="background:#fff; border-radius:16px; padding:32px; box-shadow:var(--shadow-soft);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                <div>
                    <h2 style="font-size:20px; font-weight:800; color:#06233d;">
                        Bienvenido, <?= htmlspecialchars($currentUser['nombre']) ?> 🛵
                    </h2>
                    <p style="color:#64748b; font-size:13px;">Gestiona en tiempo real las órdenes registradas en MySQL por los usuarios.</p>
                </div>
                <span style="background:#e0f2fe; color:#0284c7; padding:6px 14px; border-radius:9999px; font-weight:700; font-size:12px;">
                    Estado: Conectado / Activo
                </span>
            </div>

            <?php if (!empty($successMsg)): ?>
                <div class="alert-message alert-success" role="status">
                    <?= htmlspecialchars($successMsg) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($errorMsg)): ?>
                <div class="alert-message alert-error" role="alert">
                    <?= htmlspecialchars($errorMsg) ?>
                </div>
            <?php endif; ?>

            <div style="border:1px solid #e2e8f0; border-radius:12px; overflow:hidden;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead style="background:#f8fafc; border-bottom:1px solid #cbd5e1;">
                        <tr>
                            <th style="padding:12px; text-align:left;">Pedido #</th>
                            <th style="padding:12px; text-align:left;">Cliente / Teléfono</th>
                            <th style="padding:12px; text-align:left;">Dirección Entrega</th>
                            <th style="padding:12px; text-align:left;">Total</th>
                            <th style="padding:12px; text-align:left;">Estado Actual</th>
                            <th style="padding:12px; text-align:center;">Actualizar Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pedidos)): ?>
                            <tr>
                                <td colspan="6" style="padding: 24px; text-align: center; color: #64748b;">
                                    No hay pedidos registrados en la base de datos.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pedidos as $p): ?>
                                <tr style="border-bottom:1px solid #e2e8f0;">
                                    <td style="padding:12px;"><strong><?= htmlspecialchars($p['id_pedido']) ?></strong></td>
                                    <td style="padding:12px;">
                                        <strong><?= htmlspecialchars($p['cliente']) ?></strong><br>
                                        <span style="font-size:11px; color:#64748b;"><?= htmlspecialchars($p['telefono']) ?></span>
                                    </td>
                                    <td style="padding:12px;">
                                        <?= htmlspecialchars($p['direccion']) ?>
                                        <?php if (!empty($p['referencia'])): ?>
                                            <br><small style="color:#64748b;">Ref: <?= htmlspecialchars($p['referencia']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:12px; font-weight:700;">S/ <?= number_format($p['total'], 2) ?></td>
                                    <td style="padding:12px;">
                                        <span style="background:#fef3c7; color:#b45309; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700;">
                                            <?= htmlspecialchars($p['estado']) ?>
                                        </span>
                                    </td>
                                    <td style="padding:12px; text-align:center;">
                                        <form method="POST" action="repartidor.php" style="display:flex; gap:6px; justify-content:center;">
                                            <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
                                            <select name="nuevo_estado" style="font-size:11px; padding:4px; border-radius:4px; border:1px solid #cbd5e1;">
                                                <option value="pendiente" <?= $p['estado_db'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                                <option value="confirmado" <?= $p['estado_db'] === 'confirmado' ? 'selected' : '' ?>>Confirmado</option>
                                                <option value="en_preparacion" <?= $p['estado_db'] === 'en_preparacion' ? 'selected' : '' ?>>Preparando</option>
                                                <option value="en_camino" <?= $p['estado_db'] === 'en_camino' ? 'selected' : '' ?>>En camino</option>
                                                <option value="entregado" <?= $p['estado_db'] === 'entregado' ? 'selected' : '' ?>>Entregado</option>
                                            </select>
                                            <button type="submit" class="btn-admin-action" style="padding:4px 8px; font-size:11px;">
                                                Guardar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top:20px; display:flex; justify-content:space-between;">
                <a href="../Delivery fast/index.php" style="color:#00a8ff; text-decoration:none; font-weight:600; font-size:13px;">
                    ← Ir a la Tienda (Delivery fast)
                </a>
                <a href="logout.php" style="color:#ef4444; text-decoration:none; font-weight:600; font-size:13px;">
                    Cerrar Sesión
                </a>
            </div>
        </div>
    </main>
</body>
</html>
