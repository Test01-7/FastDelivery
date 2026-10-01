<?php
require_once __DIR__ . '/../Backend/controllers/auth.php';
$return = returnPage($_POST['return'] ?? $_GET['return'] ?? null);
$result = handleAuth('login');
$notice = takeFlash();
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Iniciar sesión · Fast Delivery</title><link rel="stylesheet" href="css/pages.css"></head>
<body>
<header class="page-header"><a class="brand" href="index.php">FAST<span>DELIVERY</span></a><a href="index.php">← Volver a la tienda</a></header>
<main class="auth-wrap">
 <section class="auth-story"><span class="eyebrow">Tu tienda, más cerca</span><h1>Lo esencial, a la puerta de tu casa.</h1><p>Entra a tu cuenta, continúa tu compra y sigue tus pedidos en un solo lugar.</p><div class="auth-art"><strong>Todo listo para tu día.</strong><span class="muted">Alimentos, bebidas, limpieza y cuidado personal.</span></div></section>
 <section class="card auth-card"><span class="eyebrow">Bienvenido de nuevo</span><h1>Iniciar sesión</h1><p class="muted">Ingresa tus datos para continuar.</p>
 <?php if ($notice): ?><div class="notice" role="status"><?= e($notice['message']) ?></div><?php endif; ?>
 <?php if ($result): ?><div class="notice error" role="alert"><?= e($result['message']) ?></div><?php endif; ?>
 <?php if (DEMO_MODE): ?>
 <section class="demo-access"><span class="eyebrow">Modo presentación</span><p class="muted">Entra con un clic para mostrar cada panel.</p>
  <form method="post" action="login.php" class="demo-roles">
   <input type="hidden" name="return" value="<?= e($return) ?>">
   <button class="btn" name="demo_role" value="administrador" type="submit">Administrador</button>
   <button class="btn secondary" name="demo_role" value="cliente" type="submit">Cliente</button>
   <button class="btn secondary" name="demo_role" value="repartidor" type="submit">Repartidor</button>
  </form>
 </section>
 <?php endif; ?>
 <form method="post" action="login.php">
  <input type="hidden" name="return" value="<?= e($return) ?>">
  <label class="field">Correo o nombre de usuario<input name="email" autocomplete="username" required maxlength="150" value="<?= e($_POST['email'] ?? '') ?>" placeholder="tu@correo.com"></label>
  <label class="field">Contraseña<input type="password" name="password" autocomplete="current-password" required></label>
  <button class="btn full" type="submit">Iniciar sesión</button>
 </form>
 <p class="auth-links">¿Aún no tienes cuenta? <a href="register.php?return=<?= e($return) ?>">Regístrate</a></p>
 </section>
</main></body></html>
