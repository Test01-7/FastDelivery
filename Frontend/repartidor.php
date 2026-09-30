<?php
/**
 * FAST DELIVERY - Panel de Repartidor
 * Pedidos asignados y estado de entrega
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Backend/services/auth.php';

$currentUser = AuthService::requireAuth(['repartidor', 'administrador']);
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

    <main style="max-width: 900px; margin: 80px auto 40px auto; padding: 20px;">
        <div style="background:#fff; border-radius:16px; padding:32px; box-shadow:var(--shadow-soft);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                <div>
                    <h2 style="font-size:20px; font-weight:800; color:#06233d;">
                        Bienvenido, <?= htmlspecialchars($currentUser['nombre']) ?> 🛵
                    </h2>
                    <p style="color:#64748b; font-size:13px;">Gestiona las órdenes de entrega de productos de primera necesidad.</p>
                </div>
                <span style="background:#e0f2fe; color:#0284c7; padding:6px 14px; border-radius:9999px; font-weight:700; font-size:12px;">
                    Estado: Conectado / Activo
                </span>
            </div>

            <div style="border:1px solid #e2e8f0; border-radius:12px; overflow:hidden;">
                <table style="width:100%; border-collapse:collapse; font-size:13px;">
                    <thead style="background:#f8fafc; border-bottom:1px solid #cbd5e1;">
                        <tr>
                            <th style="padding:12px; text-align:left;">Pedido #</th>
                            <th style="padding:12px; text-align:left;">Dirección Entrega</th>
                            <th style="padding:12px; text-align:left;">Total</th>
                            <th style="padding:12px; text-align:left;">Estado</th>
                            <th style="padding:12px; text-align:center;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom:1px solid #e2e8f0;">
                            <td style="padding:12px;"><strong>#1001</strong></td>
                            <td style="padding:12px;">Jr. Los Álamos 450, Lima (Dpto 302)</td>
                            <td style="padding:12px; font-weight:700;">S/ 34.50</td>
                            <td style="padding:12px;">
                                <span style="background:#fef3c7; color:#b45309; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700;">
                                    En Preparación
                                </span>
                            </td>
                            <td style="padding:12px; text-align:center;">
                                <button class="btn-admin-action" style="padding:6px 12px; font-size:11px;" onclick="alert('Pedido marcado como: En camino');">
                                    Iniciar Ruta
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:12px;"><strong>#1002</strong></td>
                            <td style="padding:12px;">Av. Primavera 1020, San Borja</td>
                            <td style="padding:12px; font-weight:700;">S/ 58.20</td>
                            <td style="padding:12px;">
                                <span style="background:#d1fae5; color:#065f46; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700;">
                                    Confirmado
                                </span>
                            </td>
                            <td style="padding:12px; text-align:center;">
                                <button class="btn-admin-action" style="padding:6px 12px; font-size:11px;" onclick="alert('Pedido tomado');">
                                    Tomar Pedido
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div style="margin-top:20px; display:flex; justify-content:space-between;">
                <a href="productos.php" style="color:#00a8ff; text-decoration:none; font-weight:600; font-size:13px;">
                    ← Ver Catálogo de Productos
                </a>
                <a href="logout.php" style="color:#ef4444; text-decoration:none; font-weight:600; font-size:13px;">
                    Cerrar Sesión
                </a>
            </div>
        </div>
    </main>
</body>
</html>
