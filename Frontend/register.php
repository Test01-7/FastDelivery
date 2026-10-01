<?php
require_once __DIR__ . '/../Backend/controllers/auth.php';
$return = returnPage($_POST['return'] ?? $_GET['return'] ?? null);
$result = handleAuth('register');
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Crear cuenta · Fast Delivery</title><link rel="stylesheet" href="css/pages.css"></head>
<body>
<header class="page-header"><a class="brand" href="index.php">FAST<span>DELIVERY</span></a><a href="index.php">← Volver a la tienda</a></header>
<main class="auth-wrap">
 <section class="auth-story"><span class="eyebrow">Empieza aquí</span><h1>Tus compras, más fáciles.</h1><p>Crea tu cuenta para pedir tus productos favoritos y consultar tus entregas.</p><div class="auth-art"><strong>Menos vueltas. Más tiempo.</strong><span class="muted">Todo lo que necesitas, en Fast Delivery.</span></div></section>
 <section class="card auth-card"><span class="eyebrow">Únete a Fast Delivery</span><h1>Crear cuenta</h1><p class="muted">Completa tus datos para registrarte.</p>
 <?php if ($result): ?><div class="notice error" role="alert"><?= e($result['message']) ?></div><?php endif; ?>
 <form method="post" action="register.php">
  <input type="hidden" name="return" value="<?= e($return) ?>">
  <div class="form-grid">
   <label class="field">Nombre<input name="nombre" autocomplete="given-name" required maxlength="100" value="<?= e($_POST['nombre'] ?? '') ?>"></label>
   <label class="field">Apellido<input name="apellido" autocomplete="family-name" required maxlength="100" value="<?= e($_POST['apellido'] ?? '') ?>"></label>
  </div>
  <label class="field">Correo electrónico<input type="email" name="email" autocomplete="email" required maxlength="150" value="<?= e($_POST['email'] ?? '') ?>"></label>
  <label class="field">Teléfono<input type="tel" name="telefono" autocomplete="tel" required maxlength="20" value="<?= e($_POST['telefono'] ?? '') ?>"></label>
  <label class="field">Dirección<input name="direccion" autocomplete="street-address" maxlength="255" value="<?= e($_POST['direccion'] ?? '') ?>"></label>
  <label class="field">Contraseña<input type="password" name="password" autocomplete="new-password" minlength="6" required><small>Usa al menos 6 caracteres.</small></label>
  <button class="btn full" type="submit">Crear cuenta</button>
 </form>
 <p class="auth-links">¿Ya tienes cuenta? <a href="login.php?return=<?= e($return) ?>">Inicia sesión</a></p>
 </section>
</main></body></html>
