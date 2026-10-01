<?php
require_once __DIR__ . '/../Backend/controllers/consultas_pedidos.php';
$id_pedido = is_string($_GET['id'] ?? '') ? trim($_GET['id'] ?? '') : '';
$pedido = pedidoDelUsuario($id_pedido, $currentUser);

// Estados oficiales
$estados = [
    'Pendiente' => 1,
    'Confirmado' => 2,
    'Preparando' => 3,
    'En camino' => 4,
    'Entregado' => 5
];

$estado_actual_str = $pedido ? $pedido['estado'] : 'Pendiente';
$paso_actual_num = isset($estados[$estado_actual_str]) ? $estados[$estado_actual_str] : 1;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Seguimiento de Pedido - FAST DELIVERY</title>
  <link rel="stylesheet" href="css/estilo.css">
  <style>
    .stepper {
      display: flex;
      justify-content: space-between;
      margin: 2.5rem 0;
      position: relative;
    }
    .stepper::before {
      content: '';
      position: absolute;
      top: 20px;
      left: 8%;
      right: 8%;
      height: 4px;
      background: #e2e8f0;
      z-index: 1;
    }
    .stepper-progress {
      position: absolute;
      top: 20px;
      left: 8%;
      height: 4px;
      background: var(--primary);
      z-index: 1;
      transition: width 0.4s ease;
    }
    .step-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      position: relative;
      z-index: 2;
      width: 20%;
    }
    .step-circle {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background: #ffffff;
      border: 2px solid #cbd5e1;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 0.9rem;
      color: #64748b;
    }
    .step-item.completed .step-circle {
      background: var(--primary);
      border-color: var(--primary);
      color: #ffffff;
    }
    .step-item.active .step-circle {
      border-color: var(--primary);
      color: var(--primary);
      background: var(--primary-light);
      box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.2);
    }
    .step-label {
      font-size: 0.8rem;
      font-weight: 600;
      color: #64748b;
      margin-top: 0.5rem;
      text-align: center;
    }
    .step-item.completed .step-label, .step-item.active .step-label {
      color: var(--text-main);
      font-weight: 700;
    }
  </style>
</head>
<body>

  <!-- Header -->
  <header class="main-header">
    <div class="container header-content">
      <a href="index.php" class="logo">
        <div>FAST<span>DELIVERY</span></div>
      </a>
      <nav class="main-nav">
        <a href="index.php">Inicio</a>
        <a href="mis_pedidos.php">Mis Pedidos</a>
      </nav>
      <div class="header-actions">
        <a href="index.php" class="cart-btn">Volver a la Tienda</a>
      </div>
    </div>
  </header>

  <main class="container" style="padding: 2.5rem 1rem; max-width: 800px;">
    <?php if (!$pedido): ?>
      <div style="background: #ffffff; padding: 3rem; text-align: center; border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
        <h2 style="font-size: 1.2rem; font-weight: 700;">No se encontró el pedido solicitado en la base de datos</h2>
        <p style="color: var(--text-muted); margin-top: 0.5rem;">Verifica el código o realiza un nuevo pedido.</p>
        <a href="index.php" class="btn-add" style="display: inline-block; margin-top: 1.2rem;">Volver al Inicio</a>
      </div>
    <?php else: ?>
      <div style="background: #ffffff; padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
        
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
          <div>
            <h2 style="font-size: 1.3rem; font-weight: 800;">Seguimiento de Pedido</h2>
            <span style="font-size: 0.875rem; color: var(--text-muted);">Código: <strong><?php echo htmlspecialchars($pedido['id_pedido']); ?></strong> | Fecha: <?php echo htmlspecialchars($pedido['fecha']); ?></span>
          </div>
          <div>
            <span style="background: var(--primary-light); color: var(--primary); padding: 0.4rem 0.9rem; border-radius: var(--radius-full); font-size: 0.85rem; font-weight: 700;">
              <?php echo htmlspecialchars($pedido['estado']); ?>
            </span>
          </div>
        </div>

        <!-- Barra de Progreso de 5 Pasos -->
        <?php
        $pct_width = (($paso_actual_num - 1) / 4) * 84;
        ?>
        <?php if ($pedido['estado_db'] !== 'cancelado'): ?>
        <div class="stepper">
          <div class="stepper-progress" style="width: <?php echo $pct_width; ?>%;"></div>

          <div class="step-item <?php echo $paso_actual_num >= 1 ? ($paso_actual_num == 1 ? 'active' : 'completed') : ''; ?>">
            <div class="step-circle">1</div>
            <div class="step-label">Pendiente</div>
          </div>

          <div class="step-item <?php echo $paso_actual_num >= 2 ? ($paso_actual_num == 2 ? 'active' : 'completed') : ''; ?>">
            <div class="step-circle">2</div>
            <div class="step-label">Confirmado</div>
          </div>

          <div class="step-item <?php echo $paso_actual_num >= 3 ? ($paso_actual_num == 3 ? 'active' : 'completed') : ''; ?>">
            <div class="step-circle">3</div>
            <div class="step-label">Preparando</div>
          </div>

          <div class="step-item <?php echo $paso_actual_num >= 4 ? ($paso_actual_num == 4 ? 'active' : 'completed') : ''; ?>">
            <div class="step-circle">4</div>
            <div class="step-label">En camino</div>
          </div>

          <div class="step-item <?php echo $paso_actual_num >= 5 ? ($paso_actual_num == 5 ? 'active' : 'completed') : ''; ?>">
            <div class="step-circle">5</div>
            <div class="step-label">Entregado</div>
          </div>
        </div>

        <?php else: ?><p style="padding:1rem;background:#fef2f2;color:#991b1b;border-radius:10px;margin-top:1rem">Este pedido fue cancelado. No se realizará la entrega.</p><?php endif; ?>
        <!-- Detalle del Pedido -->
        <div style="background: #f8fafc; padding: 1.2rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-top: 2rem;">
          <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.8rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.4rem;">Detalle del pedido</h4>
          <p style="font-size: 0.875rem; margin-bottom: 0.4rem;"><strong>Cliente:</strong> <?php echo htmlspecialchars($pedido['cliente']); ?></p>
          <p style="font-size: 0.875rem; margin-bottom: 0.4rem;"><strong>Dirección:</strong> <?php echo htmlspecialchars($pedido['direccion']); ?> <?php echo !empty($pedido['referencia']) ? '(' . htmlspecialchars($pedido['referencia']) . ')' : ''; ?></p>
          <p style="font-size: 0.875rem; margin-bottom: 0.4rem;"><strong>Método de Pago:</strong> <?php echo htmlspecialchars($pedido['metodo_pago']); ?></p>
          <p style="font-size: 0.875rem; margin-bottom: 0.4rem;"><strong>Productos:</strong> 
            <?php 
            if (is_array($pedido['items'])) {
                $items_str = array_map(function($i) { return (is_array($i) ? $i['cantidad'] . 'x ' . $i['nombre'] : $i); }, $pedido['items']);
                echo htmlspecialchars(implode(', ', $items_str));
            } else {
                echo htmlspecialchars($pedido['items']);
            }
            ?>
          </p>
          <div style="margin-top: 0.8rem; padding-top: 0.6rem; border-top: 1px dashed #cbd5e1; font-size: 1.1rem; font-weight: 800; color: var(--text-main);">
            Total: S/ <?php echo number_format($pedido['total'], 2); ?>
          </div>
        </div>

        <div style="margin-top: 1.5rem; display: flex; gap: 1rem; justify-content: flex-end;">
          <a href="seguimiento.php?id=<?php echo urlencode($pedido['id_pedido']); ?>" class="btn-add" style="display: inline-block;">🔄 Actualizar Estado</a>
          <a href="mis_pedidos.php" class="btn-checkout-primary" style="width: auto; padding: 0.5rem 1.2rem; text-decoration: none;">Ver todos mis pedidos</a>
        </div>

      </div>
    <?php endif; ?>
  </main>

  <footer>
    <div class="container footer-content">
      <div><strong>FAST DELIVERY</strong> &copy; <?php echo date('Y'); ?>. Todos los derechos reservados.</div>
    </div>
  </footer>

</body>
</html>
