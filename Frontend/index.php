<?php
require_once __DIR__ . '/../Backend/bootstrap.php';
require_once __DIR__ . '/../Backend/services/catalogo.php';

$currentUser = currentUser();

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
            <a href="admin.php" class="btn-add" style="background:#06233d; color:#fff; border-color:#06233d; font-size:0.75rem;">
              ⚙️ Admin
            </a>
          <?php elseif ($currentUser['rol'] === 'repartidor'): ?>
            <a href="mis_pedidos.php" class="btn-add" style="background:#0284c7; color:#fff; border-color:#0284c7; font-size:0.75rem;">
              🛵 Pedidos
            </a>
          <?php endif; ?>

          <div style="display:flex; align-items:center; gap:8px; background:#f1f5f9; padding:4px 10px; border-radius:var(--radius-full); border:1px solid #cbd5e1;">
            <span style="font-size:0.8rem; font-weight:700; color:#1e293b;">👤 <?= htmlspecialchars($currentUser['nombre']) ?></span>
            <a href="logout.php" title="Cerrar Sesión" style="color:#ef4444; font-size:0.75rem; font-weight:700;">✕</a>
          </div>
        <?php else: ?>
          <a class="user-btn" href="login.php">Iniciar sesión</a>
          <a class="user-btn" href="register.php">Registrarse</a>
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
            <div class="promo-badge-item">Compra fácil y rápida</div>
            <div class="promo-badge-item">Pago con tarjeta</div>
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
          <li><a href="login.php">Iniciar sesión</a></li>
          <li><a href="register.php">Registrarse</a></li>
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
    const PRODUCTOS = <?php echo json_encode($todos_productos, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

  </script>
  <script src="js/cart.js"></script>
  <script src="js/app.js"></script>
</body>
</html>