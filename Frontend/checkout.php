<?php
require_once __DIR__ . '/../Backend/bootstrap.php';
require_once __DIR__ . '/../Backend/services/catalogo.php';
$user = requireUser([], 'checkout.php');
$products = obtenerProductos();
$token = bin2hex(random_bytes(16));
$requests = $_SESSION['checkout_requests'][$user['id']] ?? [];
if (count($requests) >= 30) $requests = array_slice($requests, -29, null, true);
$requests[$token] = false;
$_SESSION['checkout_requests'][$user['id']] = $requests;
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Finalizar compra · Fast Delivery</title><link rel="stylesheet" href="css/pages.css"></head>
<body>
<header class="page-header"><a class="brand" href="index.php">FAST<span>DELIVERY</span></a><span class="muted">Hola, <?= e($user['nombre']) ?></span><a href="index.php">← Seguir comprando</a></header>
<main class="checkout-main">
 <div id="checkout-content"><span class="eyebrow">Último paso</span><h1>Finaliza tu compra</h1><p class="muted">Revisa tus productos y completa los datos para tu entrega.</p>
  <div class="checkout-grid">
   <section class="card"><h2>Tu carrito <span class="badge" id="checkout-count">0</span></h2><div id="checkout-items"></div>
    <div class="summary-line"><span>Subtotal</span><strong id="checkout-subtotal"></strong></div>
    <div class="summary-line"><span>Delivery</span><strong id="checkout-envio"></strong></div>
    <div class="summary-line summary-total"><span>Total</span><span id="checkout-total"></span></div>
    <p class="muted" style="font-size:12px;margin-top:16px">Envío gratis desde S/ 50.00.</p>
   </section>
   <section class="card"><h2>Entrega y pago</h2><div id="payment-error" class="notice error" role="alert" hidden></div>
    <form id="checkout-form" action="procesar_pedido.php" method="post">
     <?php if (DEMO_MODE): ?><button type="button" class="btn secondary small demo-fill" id="fill-demo-payment">Rellenar datos de prueba</button><?php endif; ?>
     <input type="hidden" name="checkout_token" value="<?= e($token) ?>">
     <label class="field">Nombre de contacto<input name="nombre" autocomplete="name" required maxlength="200" value="<?= e(trim($user['nombre'] . ' ' . $user['apellido'])) ?>"></label>
     <label class="field">Teléfono<input name="telefono" type="tel" autocomplete="tel" required maxlength="20" value="<?= e($user['telefono']) ?>"></label>
     <label class="field">Dirección de entrega<input name="direccion" autocomplete="street-address" required maxlength="255" value="<?= e($user['direccion']) ?>" placeholder="Avenida, número y departamento"></label>
     <label class="field">Referencia <small>(opcional)</small><input name="referencia" maxlength="255" placeholder="Frente al parque, puerta verde..."></label>
     <div class="payment-section"><h2>Datos de tu tarjeta</h2>
      <label class="field">Titular de la tarjeta<input id="card-holder" autocomplete="off" required maxlength="100" placeholder="Nombre como aparece en la tarjeta"></label>
      <label class="field">Número de tarjeta<input id="card-number" inputmode="numeric" autocomplete="off" required minlength="13" maxlength="23" placeholder="0000 0000 0000 0000"></label>
      <div class="form-grid">
       <label class="field">Vencimiento<input id="card-expiry" inputmode="numeric" autocomplete="off" required maxlength="5" pattern="(0[1-9]|1[0-2])/[0-9]{2}" placeholder="MM/AA"></label>
       <label class="field">CVV<input id="card-cvv" type="password" inputmode="numeric" autocomplete="off" required pattern="[0-9]{3,4}" maxlength="4" placeholder="•••"></label>
      </div>
     </div>
     <button type="submit" class="btn full" id="pay-button">Hacer pago</button>
     <p class="demo-label">Pago simulado. No se realizará ningún cargo. Usa datos de prueba.</p>
    </form>
   </section>
  </div>
 </div>
 <section class="card success-card" id="checkout-success" hidden aria-live="polite"><div class="success-mark">✓</div><span class="eyebrow">Compra confirmada</span><h1>Pago realizado</h1><p class="muted">Tu pedido está registrado y listo para su preparación.</p><h2 id="success-code"></h2><p id="success-total"></p><div class="actions" style="justify-content:center"><a class="btn" id="success-tracking" href="mis_pedidos.php">Seguir mi pedido</a><a class="btn secondary" href="index.php">Volver a la tienda</a></div><p class="demo-label">Confirmación de un pago simulado.</p></section>
</main>
<script>const PRODUCTOS = <?= json_encode($products, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="js/cart.js"></script><script src="js/checkout.js"></script>
</body></html>
