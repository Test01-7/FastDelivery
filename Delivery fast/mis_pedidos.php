<?php
session_start();
require_once __DIR__ . '/includes/productos.php';
require_once __DIR__ . '/../Backend/services/pedidos.php';

$cliente_id = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$pedidosDB = PedidoService::getPedidos($cliente_id);

$pedidosSession = isset($_SESSION['pedidos']) ? $_SESSION['pedidos'] : [];

$mapaPedidos = [];
foreach ($pedidosDB as $p) {
    $mapaPedidos[$p['id_pedido']] = $p;
}
foreach ($pedidosSession as $p) {
    if (!isset($mapaPedidos[$p['id_pedido']])) {
        $mapaPedidos[$p['id_pedido']] = $p;
    }
}
$pedidos = array_values($mapaPedidos);

$pedidos_actuales = array_filter($pedidos, function($p) {
    $st = strtolower($p['estado']);
    return $st !== 'entregado' && $st !== 'cancelado';
});

$historial = array_filter($pedidos, function($p) {
    $st = strtolower($p['estado']);
    return $st === 'entregado' || $st === 'cancelado';
});
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mis Pedidos - FAST DELIVERY</title>
  <link rel="stylesheet" href="css/estilo.css">
</head>
<body>

  <!-- Barra Superior -->
  <div class="top-bar">
    <div class="container top-bar-content">
      <div class="top-bar-location">
        <span>Ubicación:</span>
        <strong style="color: #ffffff;">San Miguel, Lima</strong>
      </div>
      <div class="top-bar-info">
        <span>Atención: Lun-Dom 8:00 AM - 10:00 PM</span>
        <span>Central: (01) 700-3000</span>
      </div>
    </div>
  </div>

  <!-- Header -->
  <header class="main-header">
    <div class="container header-content">
      <a href="index.php" class="logo">
        <div>FAST<span>DELIVERY</span></div>
      </a>
      <nav class="main-nav">
        <a href="index.php">Inicio</a>
        <a href="index.php#catalogo">Categorías</a>
        <a href="mis_pedidos.php" class="active">Mis Pedidos</a>
      </nav>
      <div class="header-actions">
        <a href="index.php" class="cart-btn">Volver a la Tienda</a>
      </div>
    </div>
  </header>

  <main class="container" style="padding: 2rem 1rem;">
    <h2 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 1.5rem;">Mis Pedidos</h2>

    <!-- Pedidos Actuales -->
    <section style="margin-bottom: 2.5rem;">
      <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin-bottom: 1rem; padding-bottom: 0.4rem; border-bottom: 2px solid var(--primary);">
        Pedidos en curso (Sincronizados con MySQL RDS)
      </h3>

      <?php if (empty($pedidos_actuales)): ?>
        <div style="background: #ffffff; padding: 2rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); text-align: center; color: var(--text-muted);">
          <p style="font-weight: 600;">No tienes pedidos pendientes en este momento.</p>
          <a href="index.php" style="display: inline-block; margin-top: 0.8rem; color: var(--primary); font-weight: 700;">Ir a realizar un pedido ➔</a>
        </div>
      <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 1rem;">
          <?php foreach ($pedidos_actuales as $p): ?>
            <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.2rem;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.8rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.6rem;">
                <div>
                  <strong style="font-size: 1rem; color: var(--text-main);"><?php echo htmlspecialchars($p['id_pedido']); ?></strong>
                  <span style="font-size: 0.8rem; color: var(--text-muted); margin-left: 0.5rem;"><?php echo htmlspecialchars($p['fecha']); ?></span>
                </div>
                <div>
                  <span style="background: #fef3c7; color: #d97706; padding: 0.25rem 0.6rem; border-radius: var(--radius-full); font-size: 0.75rem; font-weight: 700;">
                    Estado: <?php echo htmlspecialchars($p['estado']); ?>
                  </span>
                </div>
              </div>

              <div style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.8rem;">
                <p><strong>Dirección:</strong> <?php echo htmlspecialchars($p['direccion']); ?> <?php echo !empty($p['referencia']) ? '(' . htmlspecialchars($p['referencia']) . ')' : ''; ?></p>
                <p><strong>Pago:</strong> <?php echo htmlspecialchars($p['metodo_pago']); ?></p>
                <p><strong>Productos:</strong> 
                  <?php 
                  if (is_array($p['items'])) {
                      $nombres = array_map(function($i) {
                          return (is_array($i) ? $i['cantidad'] . 'x ' . $i['nombre'] : $i);
                      }, $p['items']);
                      echo htmlspecialchars(implode(', ', $nombres));
                  } else {
                      echo htmlspecialchars($p['items']);
                  }
                  ?>
                </p>
              </div>

              <div style="display: flex; justify-content: space-between; align-items: center; pt-2; border-top: 1px dashed var(--border-color); padding-top: 0.6rem;">
                <span style="font-size: 1.05rem; font-weight: 800; color: var(--text-main);">
                  Total: S/ <?php echo number_format($p['total'], 2); ?>
                </span>
                <a href="seguimiento.php?id=<?php echo urlencode($p['id_pedido']); ?>" class="btn-add" style="display: inline-block;">
                  Ver Seguimiento en Vivo ➔
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- Historial -->
    <section>
      <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin-bottom: 1rem; padding-bottom: 0.4rem; border-bottom: 2px solid var(--border-color);">
        Historial de Pedidos Entregados / Completados
      </h3>

      <?php if (empty($historial)): ?>
        <div style="background: #ffffff; padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); text-align: center; color: var(--text-muted); font-size: 0.875rem;">
          Aún no tienes historial de pedidos completados.
        </div>
      <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 1rem;">
          <?php foreach ($historial as $p): ?>
            <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem; opacity: 0.9;">
              <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                  <strong><?php echo htmlspecialchars($p['id_pedido']); ?></strong> - <?php echo htmlspecialchars($p['fecha']); ?>
                </div>
                <span style="background: #dcfce7; color: #15803d; padding: 0.2rem 0.6rem; border-radius: var(--radius-full); font-size: 0.75rem; font-weight: 700;">
                  <?php echo htmlspecialchars($p['estado']); ?>
                </span>
              </div>
              <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.4rem;">
                Total: S/ <?php echo number_format($p['total'], 2); ?> | Dirección: <?php echo htmlspecialchars($p['direccion']); ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <footer>
    <div class="container footer-content">
      <div><strong>FAST DELIVERY</strong> &copy; <?php echo date('Y'); ?>. Todos los derechos reservados.</div>
    </div>
  </footer>

</body>
</html>
