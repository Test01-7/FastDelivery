<?php
session_start();
require_once __DIR__ . '/includes/productos.php';
require_once __DIR__ . '/../Backend/services/auth.php';

$authError = '';
$authSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_auth'])) {
    $action = $_POST['action_auth'];
    if ($action === 'login') {
        $res = AuthService::login($_POST['email'] ?? '', $_POST['password'] ?? '');
        if ($res['success']) {
            $authSuccess = '¡Sesión iniciada correctamente!';
            if (!empty($res['redirect']) && $res['rol'] !== 'cliente') {
                header("Location: ../Frontend/" . $res['redirect']);
                exit;
            }
        } else {
            $authError = $res['message'];
        }
    } elseif ($action === 'register') {
        $res = AuthService::register($_POST);
        if ($res['success']) {
            $authSuccess = $res['message'];
        } else {
            $authError = $res['message'];
        }
    }
}

$currentUser = !empty($_SESSION['user_id']) ? [
    'id' => $_SESSION['user_id'],
    'nombre' => $_SESSION['user_nombre'] ?? 'Usuario',
    'rol' => $_SESSION['user_rol'] ?? 'cliente'
] : null;

$cat_actual = isset($_GET['categoria']) ? $_GET['categoria'] : 'todos';
$buscar_actual = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

$productos_oferta = filtrarProductos('todos', '', true, false);
$productos_destacados = filtrarProductos('todos', '', false, true);
$productos_filtrados = filtrarProductos($cat_actual, $buscar_actual);
$todos_productos = obtenerProductos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FAST DELIVERY - Tu Comida y Productos a Domicilio</title>
  <meta name="description" content="Pide alimentos, bebidas, productos de limpieza e higiene a domicilio rápido.">
  <link rel="stylesheet" href="css/estilo.css">
</head>
<body>

  <!-- Barra Superior Informativa -->
  <div class="top-bar">
    <div class="container top-bar-content">
      <div class="top-bar-location">
        <span>Ubicación actual:</span>
        <strong style="color: #ffffff;" id="top-location-text">San Miguel, Lima</strong>
      </div>
      <div class="top-bar-info">
        <span>Atención hoy hasta las 10:00 PM</span>
        <span>Envío gratis desde S/ 50</span>
        <span>Central: (01) 700-3000</span>
      </div>
    </div>
  </div>

  <!-- 1. NAVBAR & HEADER -->
  <header class="main-header">
    <div class="container header-content">
      <!-- Logo -->
      <a href="index.php" class="logo">
        <div>FAST<span>DELIVERY</span></div>
      </a>

      <!-- Menú de Navegación -->
      <nav class="main-nav">
        <a href="#catalogo">Categorías</a>
        <a href="#ofertas">Ofertas</a>
        <a href="#destacados">Destacados</a>
        <a href="mis_pedidos.php">Mis pedidos</a>
      </nav>

      <!-- Acciones de Usuario y Carrito -->
      <div class="header-actions">
        <?php if ($currentUser): ?>
          <?php if ($currentUser['rol'] === 'administrador'): ?>
            <a href="../Frontend/admin.php" class="btn-add" style="background:#06233d; color:#fff; border-color:#06233d; font-size:0.75rem;">
              ⚙️ Admin
            </a>
          <?php elseif ($currentUser['rol'] === 'repartidor'): ?>
            <a href="../Frontend/repartidor.php" class="btn-add" style="background:#0284c7; color:#fff; border-color:#0284c7; font-size:0.75rem;">
              🛵 Repartidor
            </a>
          <?php endif; ?>

          <div style="display:flex; align-items:center; gap:8px; background:#f1f5f9; padding:4px 10px; border-radius:var(--radius-full); border:1px solid #cbd5e1;">
            <span style="font-size:0.8rem; font-weight:700; color:#1e293b;">👤 <?= htmlspecialchars($currentUser['nombre']) ?></span>
            <a href="../Frontend/logout.php" title="Cerrar Sesión" style="color:#ef4444; font-size:0.75rem; font-weight:700;">✕</a>
          </div>
        <?php else: ?>
          <button class="user-btn" onclick="document.getElementById('modal-auth').classList.add('active')">
            <span>Iniciar Sesión / Registro</span>
          </button>
        <?php endif; ?>

        <button class="cart-btn" id="cart-btn">
          <span>Carrito</span>
          <span class="cart-count" id="cart-count">0</span>
        </button>
      </div>
    </div>
  </header>

  <main class="container">

    <!-- BANNER / HERO -->
    <section class="banner-section">
      <div class="promo-banner">
        <div>
          <span class="promo-tag">Fast Delivery Express</span>
          <h2 class="promo-title">Tus productos esenciales a la puerta de tu casa</h2>
          <p class="promo-sub">Alimentos, bebidas, limpieza e higiene personal entregados en menos de 30 minutos.</p>
          <div class="promo-badges">
            <div class="promo-badge-item">Entrega en 30 min</div>
            <div class="promo-badge-item">Base de datos MySQL RDS</div>
            <div class="promo-badge-item">Yape, Plin o Efectivo</div>
          </div>
        </div>
      </div>
    </section>

    <!-- BUSCADOR -->
    <section style="margin-bottom: 2rem;">
      <form action="index.php#catalogo" method="GET" class="header-search" style="max-width: 100%;">
        <input type="text" name="buscar" id="search-input" value="<?php echo htmlspecialchars($buscar_actual); ?>" placeholder="Buscar producto por nombre (ej. arroz, leche, agua, detergente...)" style="width: 100%; border-radius: var(--radius-md);">
        <?php if (!empty($cat_actual) && $cat_actual !== 'todos'): ?>
          <input type="hidden" name="categoria" value="<?php echo htmlspecialchars($cat_actual); ?>">
        <?php endif; ?>
      </form>
    </section>

    <!-- CATEGORÍAS (Alimentos, Bebidas, Limpieza, Higiene) -->
    <section id="catalogo" style="margin-bottom: 2.5rem;">
      <h3 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem;">Categorías principales</h3>
      <nav class="categories-bar">
        <?php
        $categorias = [
            'todos' => 'Todas las categorías',
            'alimentos' => 'Alimentos',
            'bebidas' => 'Bebidas',
            'limpieza' => 'Limpieza',
            'higiene' => 'Higiene',
            'medicamentos' => 'Farmacia'
        ];
        foreach ($categorias as $key => $label):
            $active_class = ($cat_actual === $key) ? 'active' : '';
            $url = "index.php?categoria=" . $key . (!empty($buscar_actual) ? "&buscar=" . urlencode($buscar_actual) : "") . "#catalogo";
        ?>
          <a href="<?php echo $url; ?>" class="cat-chip <?php echo $active_class; ?>" data-cat="<?php echo $key; ?>">
            <?php echo $label; ?>
          </a>
        <?php endforeach; ?>
      </nav>
    </section>

    <!-- 2. PRODUCTOS: LISTA Y FILTRADOS -->
    <section style="margin-bottom: 3rem;">
      <div class="products-section-title">
        <span>Catálogo de productos <?php echo (!empty($buscar_actual) ? ' - Búsqueda: "' . htmlspecialchars($buscar_actual) . '"' : ''); ?></span>
      </div>

      <div class="products-grid" id="products-grid">
        <?php if (empty($productos_filtrados)): ?>
          <div style="grid-column: 1 / -1; text-align: center; padding: 3rem 1rem; color: #64748b;">
            <p style="font-weight: 600; margin-bottom: 0.3rem;">No se encontraron productos en esta categoría o búsqueda.</p>
            <p style="font-size: 0.85rem;">Prueba buscando otra palabra clave.</p>
          </div>
        <?php else: ?>
          <?php foreach ($productos_filtrados as $prod): ?>
            <div class="card-product" data-id="<?php echo $prod['id']; ?>">
              <div class="product-img-box" onclick="verDetalleProducto(<?php echo $prod['id']; ?>)" style="cursor: pointer;">
                <img src="<?php echo htmlspecialchars($prod['imagen']); ?>" alt="<?php echo htmlspecialchars($prod['nombre']); ?>">
              </div>
              <div class="product-unit-tag"><?php echo htmlspecialchars($prod['unidad']); ?></div>
              <h4 class="product-name" onclick="verDetalleProducto(<?php echo $prod['id']; ?>)" style="cursor: pointer;"><?php echo htmlspecialchars($prod['nombre']); ?></h4>
              <div style="font-size: 0.75rem; color: #16a34a; font-weight: 600; margin-bottom: 0.4rem;">
                Stock: <?php echo $prod['stock']; ?> unidades
              </div>
              <div class="product-footer">
                <div class="product-price-val">S/ <?php echo number_format($prod['precio'], 2); ?></div>
                <button class="btn-add" onclick="agregarAlCarrito(<?php echo $prod['id']; ?>)">+ Agregar</button>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <!-- OFERTAS -->
    <section id="ofertas" style="margin-bottom: 3rem;">
      <h3 class="section-heading accent">Ofertas especiales</h3>
      <div class="products-grid">
        <?php foreach ($productos_oferta as $prod): ?>
          <div class="card-product" data-id="<?php echo $prod['id']; ?>">
            <div class="offer-chip">Oferta</div>
            <div class="product-img-box" onclick="verDetalleProducto(<?php echo $prod['id']; ?>)" style="cursor: pointer;">
              <img src="<?php echo htmlspecialchars($prod['imagen']); ?>" alt="<?php echo htmlspecialchars($prod['nombre']); ?>">
            </div>
            <div class="product-unit-tag"><?php echo htmlspecialchars($prod['unidad']); ?></div>
            <h4 class="product-name" onclick="verDetalleProducto(<?php echo $prod['id']; ?>)" style="cursor: pointer;"><?php echo htmlspecialchars($prod['nombre']); ?></h4>
            <div class="product-footer">
              <div>
                <span class="price-offer">S/ <?php echo number_format($prod['precio'], 2); ?></span>
              </div>
              <button class="btn-add" onclick="agregarAlCarrito(<?php echo $prod['id']; ?>)">+ Agregar</button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- PRODUCTOS DESTACADOS -->
    <section id="destacados" style="margin-bottom: 3rem;">
      <h3 class="section-heading">Productos destacados</h3>
      <div class="products-grid">
        <?php foreach ($productos_destacados as $prod): ?>
          <div class="card-product" data-id="<?php echo $prod['id']; ?>">
            <div class="product-img-box" onclick="verDetalleProducto(<?php echo $prod['id']; ?>)" style="cursor: pointer;">
              <img src="<?php echo htmlspecialchars($prod['imagen']); ?>" alt="<?php echo htmlspecialchars($prod['nombre']); ?>">
            </div>
            <div class="product-unit-tag"><?php echo htmlspecialchars($prod['unidad']); ?></div>
            <h4 class="product-name" onclick="verDetalleProducto(<?php echo $prod['id']; ?>)" style="cursor: pointer;"><?php echo htmlspecialchars($prod['nombre']); ?></h4>
            <div class="product-footer">
              <div class="product-price-val">S/ <?php echo number_format($prod['precio'], 2); ?></div>
              <button class="btn-add" onclick="agregarAlCarrito(<?php echo $prod['id']; ?>)">+ Agregar</button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

  </main>

  <!-- 3. CARRITO (DRAWER) -->
  <div class="cart-overlay" id="cart-overlay">
    <div class="cart-drawer">
      <div class="drawer-head">
        <h3>Carrito de Compras</h3>
        <button class="close-icon" id="close-cart-btn">✕</button>
      </div>
      <div class="drawer-body-content" id="drawer-body-content">
        <!-- Generado dinámicamente -->
      </div>
      <div class="drawer-foot">
        <div class="shipping-progress-box">
          <span id="shipping-progress-text">Te faltan S/ 50.00 para Envío Gratis</span>
          <div class="progress-bar-bg">
            <div class="progress-bar-fill" id="shipping-progress-bar" style="width: 0%;"></div>
          </div>
        </div>

        <div class="foot-row">
          <span>Subtotal</span>
          <span id="cart-subtotal">S/ 0.00</span>
        </div>
        <div class="foot-row">
          <span>Costo de delivery</span>
          <span id="cart-envio">S/ 0.00</span>
        </div>
        <div class="foot-row total-row">
          <span>Total</span>
          <span id="cart-total">S/ 0.00</span>
        </div>
        <button class="btn-checkout-primary" id="btn-checkout">
          Continuar compra
        </button>
      </div>
    </div>
  </div>

  <!-- MODAL DETALLE DE PRODUCTO (VER PRODUCTO) -->
  <div class="modal-bg" id="modal-detalle-producto">
    <div class="modal-card">
      <div class="modal-head">
        <h3 style="font-size: 1.1rem;" id="modal-prod-nombre">Detalle de Producto</h3>
        <button class="close-icon" onclick="document.getElementById('modal-detalle-producto').classList.remove('active')">✕</button>
      </div>
      <div class="modal-body-content">
        <div style="display: flex; gap: 1.2rem; margin-bottom: 1.2rem; flex-wrap: wrap;">
          <img id="modal-prod-img" src="" alt="" style="width: 140px; height: 140px; object-fit: cover; border-radius: var(--radius-md); background: #f8fafc;">
          <div style="flex: 1;">
            <p style="font-size: 0.8rem; color: var(--text-muted);" id="modal-prod-unidad"></p>
            <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0.4rem 0;" id="modal-prod-desc"></p>
            <div style="font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.4rem;" id="modal-prod-precio"></div>
            <div style="font-size: 0.8rem; color: #16a34a; font-weight: 700;" id="modal-prod-stock">Stock disponible: -</div>
          </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; pt-2; border-top: 1px solid var(--border-color); padding-top: 1rem;">
          <div style="display: flex; align-items: center; gap: 0.6rem;">
            <span style="font-size: 0.85rem; font-weight: 600;">Cantidad:</span>
            <div class="inline-qty-control" style="padding: 0.3rem 0.6rem;">
              <button class="inline-qty-btn" onclick="cambiarCantModal(-1)">-</button>
              <span class="inline-qty-num" id="modal-prod-cant">1</span>
              <button class="inline-qty-btn" onclick="cambiarCantModal(1)">+</button>
            </div>
          </div>

          <button class="btn-checkout-primary" style="width: auto; padding: 0.6rem 1.2rem;" id="btn-modal-agregar">
            Agregar al carrito
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- 4. CHECKOUT -->
  <div class="modal-bg" id="modal-checkout">
    <div class="modal-card">
      <div class="modal-head">
        <h3 style="font-size: 1.1rem;">Checkout - Datos de Envío</h3>
        <button class="close-icon" id="close-checkout-modal">✕</button>
      </div>
      <div class="modal-body-content">
        <form id="checkout-form" action="procesar_pedido.php" method="POST">
          <input type="hidden" name="carrito" id="input-carrito-json">

          <!-- Dirección -->
          <div style="margin-bottom: 1.2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
              <label class="form-label" style="margin: 0;">Dirección de entrega</label>
              <button type="button" onclick="obtenerUbicacionActual()" style="font-size: 0.75rem; color: var(--primary); font-weight: 700;">
                Usar ubicación actual
              </button>
            </div>
            <input type="text" class="form-field" name="direccion" id="input-direccion" placeholder="Ingresar dirección manual (Ej. Av. Marina 1450, Dpto 302)" value="<?= htmlspecialchars($_SESSION['user_direccion'] ?? '') ?>" required>
            <input type="text" class="form-field" name="referencia" id="input-referencia" placeholder="Referencia (Ej. Frente al parque, puerta color verde)" style="margin-top: -0.5rem;">
          </div>

          <!-- Datos de Contacto -->
          <label class="form-label">Nombre completo</label>
          <input type="text" class="form-field" name="nombre" id="input-nombre" placeholder="Ej. Carlos Mendoza" value="<?= htmlspecialchars(isset($_SESSION['user_nombre']) ? $_SESSION['user_nombre'] . ' ' . ($_SESSION['user_apellido'] ?? '') : '') ?>" required>

          <label class="form-label">Teléfono de contacto</label>
          <input type="tel" class="form-field" name="telefono" id="input-telefono" placeholder="Ej. 987 654 321" value="<?= htmlspecialchars($_SESSION['user_telefono'] ?? '') ?>" required>

          <!-- Método de Pago (Yape, Plin, Efectivo) -->
          <label class="form-label">Método de pago</label>
          <input type="hidden" name="metodo_pago" id="input-metodo-pago" value="Yape">
          <div class="payment-grid">
            <div class="payment-box active" data-pago="Yape">
              <span>Yape</span>
            </div>
            <div class="payment-box" data-pago="Plin">
              <span>Plin</span>
            </div>
            <div class="payment-box" data-pago="Efectivo">
              <span>Efectivo</span>
            </div>
          </div>

          <!-- Resumen del Pedido -->
          <div style="background: #f8fafc; padding: 0.8rem; border-radius: var(--radius-md); border: 1px solid var(--border-color); font-size: 0.85rem; margin-bottom: 1rem;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem;">
              <span>Subtotal:</span>
              <span id="checkout-subtotal">S/ 0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem;">
              <span>Delivery:</span>
              <span id="checkout-envio">S/ 0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-weight: 800; font-size: 0.95rem; border-top: 1px dashed #cbd5e1; padding-top: 0.3rem;">
              <span>Total a Pagar:</span>
              <span id="checkout-total">S/ 0.00</span>
            </div>
          </div>

          <button type="submit" class="btn-checkout-primary" style="margin-top: 0.5rem;">
            Confirmar pedido (Guardar en MySQL)
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- MODAL USUARIO / INICIAR SESION O REGISTRARSE -->
  <div class="modal-bg <?= (!empty($authError) || !empty($authSuccess)) ? 'active' : '' ?>" id="modal-auth">
    <div class="modal-card" style="max-width: 420px;">
      <div class="modal-head">
        <h3 style="font-size: 1rem;">Cuenta de Usuario</h3>
        <button class="close-icon" onclick="document.getElementById('modal-auth').classList.remove('active')">✕</button>
      </div>
      <div class="modal-body-content">
        
        <?php if (!empty($authError)): ?>
          <div style="background:#fee2e2; border:1px solid #f87171; color:#991b1b; padding:8px 12px; border-radius:6px; font-size:0.8rem; margin-bottom:12px; text-align:center;">
            <?= htmlspecialchars($authError) ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($authSuccess)): ?>
          <div style="background:#d1fae5; border:1px solid #34d399; color:#065f46; padding:8px 12px; border-radius:6px; font-size:0.8rem; margin-bottom:12px; text-align:center;">
            <?= htmlspecialchars($authSuccess) ?>
          </div>
        <?php endif; ?>

        <div style="display: flex; gap: 0.5rem; margin-bottom: 1.2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
          <button type="button" id="tab-login" class="btn-add" style="flex: 1;" onclick="switchAuthTab('login')">Iniciar sesión</button>
          <button type="button" id="tab-register" class="btn-add" style="flex: 1; opacity: 0.6;" onclick="switchAuthTab('register')">Registrarse</button>
        </div>

        <form id="form-auth" action="index.php" method="POST">
          <input type="hidden" name="action_auth" id="input-action-auth" value="login">

          <div id="auth-register-name" style="display: none;">
            <label class="form-label">Nombre *</label>
            <input type="text" name="nombre" class="form-field" placeholder="Ej. María">

            <label class="form-label">Apellido *</label>
            <input type="text" name="apellido" class="form-field" placeholder="Ej. Ramos">

            <label class="form-label">Teléfono *</label>
            <input type="text" name="telefono" class="form-field" placeholder="Ej. 987 654 321">
          </div>

          <label class="form-label">Correo Electrónico (Usuario) *</label>
          <input type="email" name="email" id="auth-email-field" class="form-field" placeholder="ejemplo@correo.com" required>

          <label class="form-label">Contraseña *</label>
          <input type="password" name="password" id="auth-pass-field" class="form-field" placeholder="••••••••" required>

          <div id="auth-register-rol" style="display: none;">
            <label class="form-label">Tipo de Cuenta</label>
            <select name="rol" class="form-field">
              <option value="cliente" selected>Cliente</option>
              <option value="repartidor">Repartidor</option>
              <option value="administrador">Administrador</option>
            </select>
          </div>

          <button type="submit" class="btn-checkout-primary" style="margin-top: 0.5rem;" id="btn-auth-submit">
            Iniciar sesión
          </button>
        </form>

        <div style="margin-top:16px; border-top:1px dashed #cbd5e1; padding-top:12px; font-size:0.75rem; color:#64748b; text-align:center;">
          <strong>Acceso directo de prueba:</strong><br>
          <div style="display:flex; gap:6px; justify-content:center; margin-top:6px;">
            <button type="button" onclick="fillAuthForm('admin@fastdelivery.com', 'admin123')" style="font-size:11px; background:#e0f2fe; color:#0284c7; border:none; padding:4px 8px; border-radius:4px; font-weight:700;">👑 Admin</button>
            <button type="button" onclick="fillAuthForm('cliente@fastdelivery.com', 'cliente123')" style="font-size:11px; background:#dcfce7; color:#15803d; border:none; padding:4px 8px; border-radius:4px; font-weight:700;">🛒 Cliente</button>
            <button type="button" onclick="fillAuthForm('repartidor@fastdelivery.com', 'repartidor123')" style="font-size:11px; background:#fef3c7; color:#b45309; border:none; padding:4px 8px; border-radius:4px; font-weight:700;">🛵 Repartidor</button>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- FOOTER -->
  <footer>
    <div class="footer-top container">
      <div class="footer-brand">
        <h4 class="section-heading">FAST DELIVERY</h4>
        <p>Entregamos tus productos esenciales rápidamente y sin complicaciones.</p>
      </div>
      <div class="footer-col">
        <h4>Catálogo</h4>
        <ul>
          <li><a href="index.php#catalogo">Alimentos</a></li>
          <li><a href="index.php#catalogo">Bebidas</a></li>
          <li><a href="index.php#catalogo">Limpieza</a></li>
          <li><a href="index.php#catalogo">Higiene</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Cuenta</h4>
        <ul>
          <li><a href="#modal-auth" onclick="document.getElementById('modal-auth').classList.add('active')">Iniciar sesión</a></li>
          <li><a href="#modal-auth" onclick="document.getElementById('modal-auth').classList.add('active')">Registrarse</a></li>
          <li><a href="mis_pedidos.php">Mis pedidos</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Contacto</h4>
        <ul>
          <li>Tel: (01) 700-3000</li>
          <li>WhatsApp: 987 654 321</li>
          <li>Email: soporte@fastdelivery.pe</li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">&copy; <?php echo date('Y'); ?> FAST DELIVERY. Todos los derechos reservados.</div>
  </footer>

  <script>
    const PRODUCTOS = <?php echo json_encode($todos_productos); ?>;

    function fillAuthForm(user, pass) {
      document.getElementById('auth-email-field').value = user;
      document.getElementById('auth-pass-field').value = pass;
    }

    function switchAuthTab(tab) {
      const tabLogin = document.getElementById('tab-login');
      const tabRegister = document.getElementById('tab-register');
      const regName = document.getElementById('auth-register-name');
      const regRol = document.getElementById('auth-register-rol');
      const actionInput = document.getElementById('input-action-auth');
      const btnSubmit = document.getElementById('btn-auth-submit');

      if (tab === 'login') {
        tabLogin.style.opacity = '1';
        tabRegister.style.opacity = '0.6';
        regName.style.display = 'none';
        regRol.style.display = 'none';
        actionInput.value = 'login';
        btnSubmit.textContent = 'Iniciar sesión';
      } else {
        tabLogin.style.opacity = '0.6';
        tabRegister.style.opacity = '1';
        regName.style.display = 'block';
        regRol.style.display = 'block';
        actionInput.value = 'register';
        btnSubmit.textContent = 'Registrarse';
      }
    }
  </script>
  <script src="js/app.js"></script>
</body>
</html>