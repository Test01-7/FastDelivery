<?php
$titles = ['disponibles' => 'Pedidos disponibles', 'entregas' => 'Mis entregas', 'historial' => 'Historial de entregas'];
$descriptions = ['disponibles' => 'Elige un pedido sin repartidor y tómalo para realizar la entrega.', 'entregas' => 'Consulta tus pedidos asignados y actualiza el progreso del envío.', 'historial' => 'Consulta las entregas finalizadas y los pedidos cancelados de tu cuenta.'];
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e($titles[$panel]) ?> · Fast Delivery</title><link rel="stylesheet" href="css/pages.css"></head>
<body>
<div class="admin-shell">
 <aside class="admin-sidebar" id="delivery-sidebar">
  <a class="brand" href="repartidor.php">FAST<span>DELIVERY</span></a>
  <span class="sidebar-label">Panel de repartidor</span>
  <button type="button" class="btn secondary mobile-menu" id="delivery-menu" aria-controls="delivery-sidebar" aria-expanded="true">Menú</button>
  <nav class="admin-nav" aria-label="Panel de repartidor">
   <?php foreach ($titles as $key => $title): ?><a href="repartidor.php?panel=<?= e($key) ?>" class="<?= $panel === $key ? 'active' : '' ?>" <?= $panel === $key ? 'aria-current="page"' : '' ?>><?= e($title) ?></a><?php endforeach; ?>
  </nav>
  <div class="sidebar-footer"><a href="index.php">← Ver tienda</a><a href="logout.php">Cerrar sesión</a></div>
 </aside>
 <main class="admin-content">
  <header class="admin-top"><span class="eyebrow">Centro de entregas</span><span class="admin-profile"><?= e(trim($currentUser['nombre'] . ' ' . $currentUser['apellido'])) ?></span></header>
  <section class="delivery-stats" aria-label="Resumen de pedidos">
   <a class="card" href="repartidor.php?panel=disponibles"><span>Disponibles</span><strong><?= e($data['summary']['disponibles']) ?></strong></a>
   <a class="card" href="repartidor.php?panel=entregas"><span>Mis entregas en curso</span><strong><?= e($data['summary']['entregas']) ?></strong></a>
   <a class="card" href="repartidor.php?panel=historial"><span>Entregas completadas</span><strong><?= e($data['summary']['completadas']) ?></strong></a>
  </section>
  <div class="panel-title"><div><h1><?= e($titles[$panel]) ?></h1><p><?= e($descriptions[$panel]) ?></p></div><a class="btn secondary" href="repartidor.php?panel=<?= e($panel) ?>&amp;page=<?= e($data['page']) ?>">Actualizar</a></div>
  <?php if ($notice): ?><div class="notice <?= $notice['success'] ? '' : 'error' ?>" role="status"><?= e($notice['message']) ?></div><?php endif; ?>
  <?php if (!$data['items']): ?><div class="card empty-state"><h2><?= $panel === 'disponibles' ? 'No hay pedidos disponibles' : ($panel === 'entregas' ? 'No tienes entregas en curso' : 'Tu historial está vacío') ?></h2><p><?= $panel === 'disponibles' ? 'Actualiza la lista cuando haya nuevas compras.' : ($panel === 'entregas' ? 'Ve a Pedidos disponibles para tomar tu primera entrega.' : 'Tus entregas finalizadas aparecerán aquí.') ?></p><?php if ($panel === 'entregas'): ?><a class="btn" href="repartidor.php">Ver pedidos disponibles</a><?php endif; ?></div><?php endif; ?>
  <section class="delivery-grid" aria-label="Lista de pedidos">
  <?php foreach ($data['items'] as $pedido): ?>
   <article class="card delivery-order">
    <div class="delivery-order-head"><div><h2><?= e($pedido['id_pedido']) ?></h2><span class="muted"><?= e($pedido['fecha']) ?></span></div><span class="badge <?= $pedido['estado_db'] === 'cancelado' ? 'off' : '' ?>"><?= e($pedido['estado']) ?></span></div>
    <dl class="delivery-info"><dt>Cliente / contacto</dt><dd><?= e($pedido['cliente']) ?></dd><dt>Teléfono</dt><dd><?= e($pedido['telefono']) ?></dd><dt>Dirección de entrega</dt><dd><?= e($pedido['direccion']) ?></dd><?php if ($pedido['referencia']): ?><dt>Referencia</dt><dd><?= e($pedido['referencia']) ?></dd><?php endif; ?></dl>
    <details class="delivery-products"><summary>Productos del pedido (<?= count($pedido['items']) ?>)</summary><ul><?php foreach ($pedido['items'] as $item): ?><li><?= e($item['cantidad']) ?> × <?= e($item['nombre']) ?></li><?php endforeach; ?></ul></details>
    <div class="delivery-total"><span>Total del pedido · <?= e($pedido['metodo_pago']) ?></span><strong>S/ <?= number_format($pedido['total'], 2) ?></strong></div>
    <div class="actions">
     <?php if ($panel !== 'historial'): ?>
      <form method="post" action="repartidor.php?panel=<?= e($panel) ?>" data-delivery-action>
       <input type="hidden" name="pedido_id" value="<?= e($pedido['id']) ?>">
       <input type="hidden" name="accion" value="<?= $panel === 'disponibles' ? 'tomar' : ($pedido['estado_db'] === 'en_camino' ? 'entregar' : 'enviar') ?>">
       <button class="btn" type="submit"><?= $panel === 'disponibles' ? 'Tomar pedido' : ($pedido['estado_db'] === 'en_camino' ? 'Marcar como entregado' : 'Iniciar envío') ?></button>
      </form>
     <?php endif; ?>
     <?php if ($panel !== 'disponibles'): ?><a class="btn secondary" href="seguimiento.php?id=<?= e($pedido['id_pedido']) ?>">Ver seguimiento</a><?php endif; ?>
    </div>
   </article>
  <?php endforeach; ?>
  </section>
  <div class="pagination"><span><?= e($data['total']) ?> pedidos · Página <?= e($data['page']) ?> de <?= e($data['pages']) ?></span><div class="actions"><?php if ($data['page'] > 1): ?><a class="btn secondary small" href="repartidor.php?panel=<?= e($panel) ?>&amp;page=<?= e($data['page'] - 1) ?>">← Anterior</a><?php endif; ?><?php if ($data['page'] < $data['pages']): ?><a class="btn secondary small" href="repartidor.php?panel=<?= e($panel) ?>&amp;page=<?= e($data['page'] + 1) ?>">Siguiente →</a><?php endif; ?></div></div>
 </main>
</div>
<script src="js/repartidor.js"></script>
</body></html>
