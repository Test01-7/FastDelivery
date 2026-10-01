<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Panel de <?= e($panel) ?> · Fast Delivery</title><link rel="stylesheet" href="css/pages.css"></head>
<body>
<div class="admin-shell">
 <aside class="admin-sidebar" id="admin-sidebar">
  <a class="brand" href="index.php">FAST<span>DELIVERY</span></a><span class="sidebar-label">Administración</span>
  <button class="btn secondary small mobile-menu" type="button" id="toggle-menu" aria-expanded="true" aria-controls="admin-nav">Menú</button>
  <nav class="admin-nav" id="admin-nav" aria-label="Administración">
   <?php foreach (['productos' => ['▦', 'Panel de productos'], 'usuarios' => ['♙', 'Panel de usuarios'], 'pedidos' => ['▤', 'Panel de pedidos']] as $key => $nav): ?>
    <a href="admin.php?panel=<?= e($key) ?>" class="<?= $panel === $key ? 'active' : '' ?>" <?= $panel === $key ? 'aria-current="page"' : '' ?>><span class="nav-icon" aria-hidden="true"><?= e($nav[0]) ?></span><?= e($nav[1]) ?></a>
   <?php endforeach; ?>
  </nav>
  <div class="sidebar-footer"><a href="index.php">← Ver tienda</a><a href="logout.php">Cerrar sesión</a></div>
 </aside>
 <main class="admin-content">
  <header class="admin-top"><span class="eyebrow">Tu centro de gestión</span><div class="admin-profile">● <?= e($currentUser['nombre']) ?> <span class="muted">· Admin</span></div></header>
  <div class="panel-title"><div><h1>Panel de <?= e($panel) ?></h1><p><?= e(['productos' => 'Organiza tu catálogo y disponibilidad.', 'usuarios' => 'Gestiona cuentas, roles y accesos.', 'pedidos' => 'Consulta las compras y coordina las entregas.'][$panel]) ?></p></div>
   <?php if ($panel !== 'pedidos'): ?><a class="btn" href="<?= e(adminLink(['new' => '1'])) ?>">+ <?= $panel === 'productos' ? 'Nuevo producto' : 'Nuevo usuario' ?></a><?php endif; ?>
  </div>
  <?php if ($notice): ?><div class="notice <?= $notice['success'] ? '' : 'error' ?>" role="status"><?= e($notice['message']) ?></div><?php endif; ?>
  <?php if ($dbError): ?><div class="notice error" role="alert"><?= e($dbError) ?></div><?php endif; ?>
  <?php if ($formError): ?><div class="notice error" role="alert"><?= e($formError) ?></div><?php endif; ?>
  <section class="card">
  <?php if ($showForm): ?>
   <section class="editor" id="editor"><h2><?= $panel === 'pedidos' ? 'Detalle y gestión del pedido' : (!empty($editing['id']) ? 'Editar ' : 'Crear ') . ($panel === 'productos' ? 'producto' : 'usuario') ?></h2>
    <form method="post" action="<?= e(adminLink()) ?>">
     <input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= e($editing['id'] ?? 0) ?>">
     <div class="form-grid">
     <?php if ($panel === 'productos'): ?>
      <label class="field">Nombre<input name="nombre" required maxlength="150" value="<?= e($editing['nombre'] ?? '') ?>"></label>
      <label class="field">Categoría<select name="categoria_id" required><option value="">Seleccionar categoría</option><?php foreach ($categorias as $category): ?><option value="<?= e($category['id']) ?>" <?= ($editing['categoria_id'] ?? '') == $category['id'] ? 'selected' : '' ?>><?= e($category['nombre']) ?></option><?php endforeach; ?></select></label>
      <label class="field">Precio (S/)<input type="number" name="precio" required min="0.01" max="99999999.99" step="0.01" value="<?= e($editing['precio'] ?? '') ?>"></label>
      <label class="field">Stock<input type="number" name="stock" required min="0" step="1" value="<?= e($editing['stock'] ?? 0) ?>"></label>
      <label class="field">Unidad de medida<input name="unidad_medida" required maxlength="20" value="<?= e($editing['unidad_medida'] ?? 'unidad') ?>"></label>
      <label class="field">Disponibilidad<select name="disponible"><option value="1" <?= ($editing['disponible'] ?? 1) ? 'selected' : '' ?>>Disponible</option><option value="0" <?= isset($editing['disponible']) && !$editing['disponible'] ? 'selected' : '' ?>>Desactivado</option></select></label>
      <label class="field wide">Imagen (URL o ruta)<input name="imagen_url" maxlength="255" value="<?= e($editing['imagen_url'] ?? 'assets/images/default.svg') ?>"></label>
      <label class="field wide">Descripción<textarea name="descripcion"><?= e($editing['descripcion'] ?? '') ?></textarea></label>
     <?php elseif ($panel === 'usuarios'): ?>
      <label class="field">Nombre<input name="nombre" required maxlength="100" value="<?= e($editing['nombre'] ?? '') ?>"></label>
      <label class="field">Apellido<input name="apellido" maxlength="100" value="<?= e($editing['apellido'] ?? '') ?>"></label>
      <label class="field">Correo<input type="email" name="email" required maxlength="150" value="<?= e($editing['email'] ?? '') ?>"></label>
      <label class="field">Teléfono<input name="telefono" type="tel" required maxlength="20" value="<?= e($editing['telefono'] ?? '') ?>"></label>
      <label class="field">Rol<select name="rol"><?php foreach (UsuarioService::ROLES as $role): ?><option value="<?= e($role) ?>" <?= ($editing['rol'] ?? 'cliente') === $role ? 'selected' : '' ?>><?= e(ucfirst($role)) ?></option><?php endforeach; ?></select></label>
      <label class="field">Estado<select name="activo"><option value="1" <?= ($editing['activo'] ?? 1) ? 'selected' : '' ?>>Activo</option><option value="0" <?= isset($editing['activo']) && !$editing['activo'] ? 'selected' : '' ?>>Desactivado</option></select></label>
      <label class="field wide">Dirección<input name="direccion" maxlength="255" value="<?= e($editing['direccion_defecto'] ?? '') ?>"></label>
      <label class="field wide">Contraseña<input type="password" name="password" autocomplete="new-password" minlength="6" <?= empty($editing['id']) ? 'required' : '' ?>><small><?= empty($editing['id']) ? 'Mínimo 6 caracteres.' : 'Déjala vacía para mantener la contraseña actual.' ?></small></label>
     <?php else: ?>
      <div class="detail-list wide"><strong><?= e($editing['id_pedido'] ?? '') ?> · <?= e($editing['cliente'] ?? '') ?></strong><p class="muted"><?= e($editing['fecha'] ?? '') ?> · <?= e($editing['metodo_pago'] ?? '') ?> · Total S/ <?= number_format((float)($editing['total'] ?? 0), 2) ?></p>
       <?php foreach ($editing['items'] ?? [] as $item): ?><div class="summary-line"><span><?= e($item['nombre']) ?> × <?= e($item['cantidad']) ?></span><strong>S/ <?= number_format((float)$item['subtotal'], 2) ?></strong></div><?php endforeach; ?>
      </div>
      <label class="field wide">Dirección de entrega<input name="direccion" required maxlength="255" value="<?= e($editing['direccion'] ?? '') ?>" <?= ($editing['estado_db'] ?? '') === 'cancelado' ? 'disabled' : '' ?>></label>
      <label class="field wide">Referencia<input name="referencia" maxlength="255" value="<?= e($editing['referencia'] ?? '') ?>" <?= ($editing['estado_db'] ?? '') === 'cancelado' ? 'disabled' : '' ?>></label>
      <label class="field">Repartidor<select name="repartidor_id" <?= ($editing['estado_db'] ?? '') === 'cancelado' ? 'disabled' : '' ?>><option value="">Sin asignar</option><?php foreach ($repartidores as $driver): ?><option value="<?= e($driver['id']) ?>" <?= ($editing['repartidor_id'] ?? '') == $driver['id'] ? 'selected' : '' ?>><?= e($driver['nombre'] . ' ' . $driver['apellido']) ?></option><?php endforeach; ?></select></label>
      <label class="field">Estado<select name="estado" <?= ($editing['estado_db'] ?? '') === 'cancelado' ? 'disabled' : '' ?>><?php foreach (PedidoService::ESTADOS as $state): ?><option value="<?= e($state) ?>" <?= ($editing['estado_db'] ?? '') === $state ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $state))) ?></option><?php endforeach; ?></select></label>
      <?php if (($editing['estado_db'] ?? '') === 'cancelado'): ?><p class="wide muted">Este pedido está cancelado y conserva su historial.</p><?php endif; ?>
     <?php endif; ?>
     </div>
     <div class="actions"><?php if ($panel !== 'pedidos' || ($editing['estado_db'] ?? '') !== 'cancelado'): ?><button type="submit" class="btn">Guardar cambios</button><?php endif; ?><a class="btn secondary" href="<?= e(adminLink()) ?>">Cerrar</a></div>
    </form>
   </section>
  <?php endif; ?>
  <?php if ($panel !== 'pedidos'): ?>
   <form class="toolbar" method="get" action="admin.php">
    <input type="hidden" name="panel" value="<?= e($panel) ?>">
    <?php if ($panel === 'productos'): ?>
     <label class="field">Buscar producto<input name="search" value="<?= e($search) ?>" placeholder="Nombre, descripción o categoría"></label>
    <?php else: ?>
     <label class="field">Filtrar por rol<select name="rol"><option value="">Todos los roles</option><?php foreach (UsuarioService::ROLES as $role): ?><option value="<?= e($role) ?>" <?= $rol === $role ? 'selected' : '' ?>><?= e(ucfirst($role)) ?></option><?php endforeach; ?></select></label>
     <label class="field">Ordenar por<select name="orden"><option value="id" <?= $orden === 'id' ? 'selected' : '' ?>>ID</option><option value="nombre" <?= $orden === 'nombre' ? 'selected' : '' ?>>Nombre</option></select></label>
     <label class="field">Dirección<select name="direccion"><option value="desc" <?= $direccion === 'desc' ? 'selected' : '' ?>>Descendente</option><option value="asc" <?= $direccion === 'asc' ? 'selected' : '' ?>>Ascendente</option></select></label>
    <?php endif; ?>
    <button class="btn" type="submit">Aplicar</button><a class="btn secondary" href="admin.php?panel=<?= e($panel) ?>">Limpiar</a>
   </form>
  <?php endif; ?>
  <div class="table-wrap"><table><thead><tr>
   <?php foreach (['productos' => ['ID', 'Producto', 'Precio', 'Stock', 'Estado', 'Acciones'], 'usuarios' => ['ID', 'Usuario', 'Rol', 'Estado', 'Acciones'], 'pedidos' => ['Pedido', 'Cliente', 'Fecha', 'Total', 'Estado', 'Acciones']][$panel] as $label): ?><th scope="col"><?= e($label) ?></th><?php endforeach; ?>
  </tr></thead><tbody>
  <?php foreach ($rows as $row): ?><tr>
   <?php if ($panel === 'productos'): ?>
    <td>#<?= e($row['id']) ?></td><td><div class="product-cell"><img src="<?= e($row['imagen_url'] ?: 'assets/images/default.svg') ?>" alt=""><div><strong><?= e($row['nombre']) ?></strong><span class="sub"><?= e($row['categoria_nombre']) ?> · <?= e($row['unidad_medida']) ?></span></div></div></td><td>S/ <?= number_format((float)$row['precio'], 2) ?></td><td><?= e($row['stock']) ?></td><td><span class="badge <?= $row['disponible'] ? '' : 'off' ?>"><?= $row['disponible'] ? 'Disponible' : 'Desactivado' ?></span></td>
   <?php elseif ($panel === 'usuarios'): ?>
    <td>#<?= e($row['id']) ?></td><td><strong><?= e($row['nombre'] . ' ' . $row['apellido']) ?></strong><span class="sub"><?= e($row['email']) ?></span></td><td><?= e(ucfirst($row['rol'])) ?></td><td><span class="badge <?= $row['activo'] ? '' : 'off' ?>"><?= $row['activo'] ? 'Activo' : 'Desactivado' ?></span></td>
   <?php else: ?>
    <td><strong>FD-<?= e(str_pad($row['id'], 5, '0', STR_PAD_LEFT)) ?></strong></td><td><?= e($row['cliente']) ?></td><td><?= e(date('d/m/Y H:i', strtotime($row['fecha_pedido']))) ?></td><td>S/ <?= number_format((float)$row['total'], 2) ?></td><td><span class="badge <?= $row['estado'] === 'cancelado' ? 'off' : '' ?>"><?= e(ucfirst(str_replace('_', ' ', $row['estado']))) ?></span></td>
   <?php endif; ?>
   <td><div class="actions"><a class="btn secondary small" href="<?= e(adminLink(['edit' => $row['id']])) ?>"><?= $panel === 'pedidos' ? 'Ver / gestionar' : 'Editar' ?></a>
    <?php if ($panel === 'usuarios' && $row['activo']): ?><form method="post" action="<?= e(adminLink()) ?>" data-confirm="Desactivar esta cuenta impedirá que el usuario vuelva a acceder. Sus datos se conservarán."><input type="hidden" name="action" value="deactivate"><input type="hidden" name="id" value="<?= e($row['id']) ?>"><button class="btn secondary small" type="submit">Desactivar cuenta</button></form><?php endif; ?>
    <?php if ($panel !== 'pedidos' || !in_array($row['estado'], ['cancelado', 'entregado'], true)): ?>
     <form method="post" action="<?= e(adminLink()) ?>" data-confirm="<?= e(['productos' => 'El producto se desactivará y dejará de aparecer en la tienda. Se conservará su historial.', 'usuarios' => 'El usuario se eliminará definitivamente si no tiene pedidos vinculados. Esta acción no se puede deshacer.', 'pedidos' => 'El pedido se cancelará y se devolverá el stock. Se conservará el historial y no se realizará un reembolso.'][$panel]) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($row['id']) ?>"><button class="btn danger small" type="submit"><?= e(['productos' => 'Eliminar', 'usuarios' => 'Eliminar usuario', 'pedidos' => 'Eliminar pedido'][$panel]) ?></button></form>
    <?php endif; ?>
   </div></td>
  </tr><?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="6"><div class="empty-state">No hay registros para mostrar.</div></td></tr><?php endif; ?>
  </tbody></table></div>
  <div class="pagination"><span><?= e($total) ?> registros · Página <?= e($page) ?> de <?= e($pages) ?></span><div class="actions"><?php if ($page > 1): ?><a class="btn secondary small" href="<?= e(adminLink(['page' => $page - 1])) ?>">← Anterior</a><?php endif; ?><?php if ($page < $pages): ?><a class="btn secondary small" href="<?= e(adminLink(['page' => $page + 1])) ?>">Siguiente →</a><?php endif; ?></div></div>
  </section>
 </main>
</div>
<dialog id="confirm-dialog"><h2>Confirmar acción</h2><p id="confirm-text"></p><div class="actions"><button type="button" class="btn secondary" id="confirm-cancel">Volver</button><button type="button" class="btn danger" id="confirm-accept">Confirmar</button></div></dialog>
<script src="js/admin.js"></script>
</body></html>
