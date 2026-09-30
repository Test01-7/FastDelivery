<?php
/**
 * FAST DELIVERY - Catálogo y Selección de Productos (Cliente)
 * Basado en wireframe (Imagen 2) y conectado a MySQL RDS
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Backend/services/auth.php';
require_once __DIR__ . '/../Backend/services/productos.php';

// Validar que el usuario esté autenticado
$currentUser = AuthService::requireAuth(['cliente', 'administrador', 'repartidor']);

// Obtener categorías y filtros
$categoriaFiltro = isset($_GET['cat']) ? (int)$_GET['cat'] : null;
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : null;

$categorias = ProductoService::getCategorias();
$productos = ProductoService::getProductos($categoriaFiltro, $busqueda, 40, 0);

// Helper para obtener imagen o fallback
function getImgSrc(string $imgUrl): string {
    // Si existe archivo con extensión svg
    $svgAlternative = preg_replace('/\.(png|jpg|jpeg)$/i', '.svg', $imgUrl);
    if (file_exists(__DIR__ . '/' . $svgAlternative)) {
        return $svgAlternative;
    }
    if (file_exists(__DIR__ . '/' . $imgUrl)) {
        return $imgUrl;
    }
    return 'assets/images/default.svg';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fast Delivery - Seleccionar Productos</title>
    <meta name="description" content="Catálogo de productos de primera necesidad. Realiza tu pedido con entrega a domicilio rápida.">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .cart-floating-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #06233d;
            color: #ffffff;
            border: 2px solid #00a8ff;
            border-radius: var(--radius-pill);
            padding: 14px 22px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 10px 25px rgba(6, 35, 61, 0.4);
            cursor: pointer;
            z-index: 100;
            font-weight: 700;
            transition: all 0.25s ease;
        }
        .cart-floating-btn:hover {
            transform: scale(1.05);
            background: #0b345b;
        }
        .cart-count-badge {
            background: #ef4444;
            color: #ffffff;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 12px;
            font-weight: 800;
        }
    </style>
</head>
<body>

    <!-- Header Superior (Logo Fast Delivery a la izquierda, Usuario a la derecha) -->
    <header class="client-header">
        <a href="productos.php" class="client-logo" style="text-decoration:none;">FAST DELIVERY</a>

        <div class="client-nav-right">
            <?php if ($currentUser['rol'] === 'administrador'): ?>
                <a href="admin.php" class="btn-admin-action" style="padding: 6px 14px; font-size: 11px;">
                    ⚙️ Ir a Panel Admin
                </a>
            <?php endif; ?>

            <!-- Perfil de Usuario con Menú -->
            <div class="user-profile-badge" id="userMenuBtn" onclick="toggleUserDropdown()" title="Opciones de cuenta">
                <div class="user-avatar-icon">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 20c0-4 4-6 8-6s8 2 8 6"/>
                    </svg>
                </div>
                <span class="user-name-text"><?= htmlspecialchars($currentUser['nombre']) ?></span>
                <span style="font-size: 10px; color: #64748b;">▼</span>

                <div id="userDropdown" style="display:none; position:absolute; top:46px; right:0; background:#fff; border:1px solid #cbd5e1; border-radius:8px; box-shadow:0 8px 16px rgba(0,0,0,0.1); width:180px; z-index:200;">
                    <div style="padding:10px 14px; border-bottom:1px solid #e2e8f0; font-size:11px; color:#64748b;">
                        Rol: <strong style="text-transform:capitalize; color:#06233d;"><?= htmlspecialchars($currentUser['rol']) ?></strong>
                    </div>
                    <?php if ($currentUser['rol'] === 'administrador'): ?>
                        <a href="admin.php" style="display:block; padding:10px 14px; color:#1e293b; text-decoration:none; font-size:13px; font-weight:600;">⚙️ Panel Admin</a>
                    <?php endif; ?>
                    <a href="logout.php" style="display:block; padding:10px 14px; color:#ef4444; text-decoration:none; font-size:13px; font-weight:600;">🚪 Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="client-main-content">

        <!-- Banner de Filtro de Búsqueda -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
            <div>
                <h2 style="font-size: 16px; font-weight: 700; color: #1e293b;">
                    Hola, <?= htmlspecialchars($currentUser['nombre']) ?> 👋
                </h2>
                <p style="font-size: 13px; color: #64748b;">Selecciona los productos de primera necesidad para tu pedido.</p>
            </div>

            <form method="GET" action="productos.php" class="search-pill-box" style="width: 280px;">
                <?php if ($categoriaFiltro): ?>
                    <input type="hidden" name="cat" value="<?= $categoriaFiltro ?>">
                <?php endif; ?>
                <input type="text" name="q" placeholder="Buscar productos..." value="<?= htmlspecialchars($busqueda ?? '') ?>">
                <button type="submit" style="background:none; border:none; cursor:pointer;">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </button>
            </form>
        </div>

        <!-- SECCIÓN CATEGORÍAS (ENMARCADA EN VIOLETA SEGÚN WIREFRAME 2) -->
        <section class="categories-container" aria-label="Categorías de productos">
            <h2 class="categories-title">CATEGORIAS</h2>
            <div class="categories-grid">
                
                <!-- Categoría: Todos -->
                <a href="productos.php" class="category-card <?= empty($categoriaFiltro) ? 'active' : '' ?>">
                    <div class="category-img-wrapper" style="background:#e0f2fe;">
                        <svg viewBox="0 0 60 40" style="width: 48px; height: 32px;" fill="none" stroke="#0284c7" stroke-width="2">
                            <rect x="5" y="5" width="22" height="12" rx="2"/>
                            <rect x="33" y="5" width="22" height="12" rx="2"/>
                            <rect x="5" y="22" width="22" height="12" rx="2"/>
                            <rect x="33" y="22" width="22" height="12" rx="2"/>
                        </svg>
                    </div>
                    <span class="category-label">Todos</span>
                </a>

                <!-- Categoría 1: Medicamentos -->
                <a href="productos.php?cat=1" class="category-card <?= ($categoriaFiltro === 1) ? 'active' : '' ?>">
                    <div class="category-img-wrapper">
                        <img src="assets/images/cat_medicamentos.svg" alt="Medicamentos y Farmacia">
                    </div>
                    <span class="category-label">medicamentos</span>
                </a>

                <!-- Categoría 2: Super -->
                <a href="productos.php?cat=2" class="category-card <?= ($categoriaFiltro === 2) ? 'active' : '' ?>">
                    <div class="category-img-wrapper">
                        <img src="assets/images/cat_super.svg" alt="Supermercado y Abarrotes">
                    </div>
                    <span class="category-label">Super</span>
                </a>

                <!-- Categoría 3: Cuidado y Limpieza -->
                <a href="productos.php?cat=3" class="category-card <?= ($categoriaFiltro === 3) ? 'active' : '' ?>">
                    <div class="category-img-wrapper">
                        <img src="assets/images/jabon.svg" alt="Cuidado y Limpieza">
                    </div>
                    <span class="category-label">Limpieza</span>
                </a>

            </div>
        </section>

        <!-- SECCIÓN DE PRODUCTOS (GRID DE TARJETAS BLANCAS) -->
        <section aria-label="Listado de productos">
            <div class="products-section-header">
                <h3 style="font-size: 18px; font-weight: 800; color: #06233d;">
                    <?= $categoriaFiltro ? 'Productos de la categoría' : 'Catálogo Disponible' ?>
                    <span style="font-size: 13px; font-weight: 500; color: #64748b;">(<?= count($productos) ?> items)</span>
                </h3>

                <?php if ($categoriaFiltro || $busqueda): ?>
                    <a href="productos.php" style="font-size: 13px; color: #00a8ff; text-decoration: none; font-weight: 600;">
                        ✕ Limpiar filtros
                    </a>
                <?php endif; ?>
            </div>

            <?php if (empty($productos)): ?>
                <div style="background:#fff; border-radius:12px; padding:40px; text-align:center; box-shadow:var(--shadow-soft);">
                    <p style="font-size:16px; color:#64748b; margin-bottom:12px;">No se encontraron productos con el filtro aplicado.</p>
                    <a href="productos.php" class="btn-primary-action" style="display:inline-block; width:auto; padding:10px 20px;">
                        Ver todos los productos
                    </a>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($productos as $p): ?>
                        <article class="product-card" id="product-card-<?= $p['id'] ?>">
                            <span class="product-category-tag"><?= htmlspecialchars($p['categoria_nombre']) ?></span>
                            
                            <div class="product-img-box">
                                <img src="<?= htmlspecialchars(getImgSrc($p['imagen_url'])) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>" loading="lazy">
                            </div>

                            <h4 class="product-title"><?= htmlspecialchars($p['nombre']) ?></h4>
                            <p class="product-desc"><?= htmlspecialchars($p['descripcion']) ?></p>

                            <div class="product-footer-row">
                                <div class="product-price">
                                    S/ <?= number_format($p['precio'], 2) ?>
                                </div>
                                <button 
                                    type="button" 
                                    class="btn-add-cart" 
                                    onclick="addToCart(<?= $p['id'] ?>, '<?= addslashes(htmlspecialchars($p['nombre'])) ?>', <?= $p['precio'] ?>)"
                                >
                                    <span>+ Añadir</span>
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <!-- Botón Flotante del Carrito -->
    <button class="cart-floating-btn" id="btnOpenCart" onclick="openCartModal()">
        <span>🛒 Mi Pedido</span>
        <span class="cart-count-badge" id="cartCountBadge">0</span>
        <span id="cartTotalText" style="font-size: 13px; color: #38bdf8;">S/ 0.00</span>
    </button>

    <!-- Modal del Carrito de Compras -->
    <div class="modal-overlay" id="cartModal" role="dialog" aria-modal="true" aria-labelledby="cartTitle">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modal-title" id="cartTitle">🛒 Resumen de tu Pedido</h3>
                <button type="button" class="modal-close" onclick="closeCartModal()">&times;</button>
            </div>
            
            <div id="cartItemsContainer" style="max-height: 280px; overflow-y: auto; margin-bottom: 20px;">
                <p style="text-align: center; color: #64748b; padding: 20px;">Tu carrito está vacío.</p>
            </div>

            <div style="border-top: 1px solid #e2e8f0; padding-top: 14px; margin-bottom: 20px;">
                <div style="display:flex; justify-content:space-between; margin-bottom: 6px; font-size:13px; color:#64748b;">
                    <span>Subtotal:</span>
                    <span id="cartSubtotal">S/ 0.00</span>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom: 6px; font-size:13px; color:#64748b;">
                    <span>Costo de Envío:</span>
                    <span style="color:#10b981; font-weight:600;">S/ 0.00 (Gratis)</span>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:18px; font-weight:800; color:#06233d;">
                    <span>Total a Pagar:</span>
                    <span id="cartFinalTotal">S/ 0.00</span>
                </div>
            </div>

            <div class="form-group">
                <label for="orderAddress">Dirección de Entrega:</label>
                <input type="text" id="orderAddress" class="form-control" value="<?= htmlspecialchars($currentUser['direccion'] ?? 'Av. Principal 123, Lima') ?>">
            </div>

            <div class="form-actions">
                <button type="button" class="btn-admin-action" onclick="closeCartModal()" style="background:#64748b;">Seguir Comprando</button>
                <button type="button" class="btn-admin-action" onclick="confirmOrder()" style="background:#10b981;">Confirmar y Enviar Pedido</button>
            </div>
        </div>
    </div>

    <!-- Barra Inferior Azul Oscuro (Wireframe 2) -->
    <footer class="client-footer-bar"></footer>

    <script>
        let cart = [];

        function addToCart(id, name, price) {
            const existing = cart.find(item => item.id === id);
            if (existing) {
                existing.qty += 1;
            } else {
                cart.push({ id, name, price, qty: 1 });
            }
            updateCartUI();

            // Animación feedback en botón
            const btn = event.currentTarget;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span>✓ Añadido</span>';
            btn.style.backgroundColor = '#10b981';
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.style.backgroundColor = '';
            }, 900);
        }

        function updateCartUI() {
            const countBadge = document.getElementById('cartCountBadge');
            const totalText = document.getElementById('cartTotalText');
            const subtotalText = document.getElementById('cartSubtotal');
            const finalTotalText = document.getElementById('cartFinalTotal');
            const container = document.getElementById('cartItemsContainer');

            const totalCount = cart.reduce((sum, item) => sum + item.qty, 0);
            const totalPrice = cart.reduce((sum, item) => sum + (item.qty * item.price), 0);

            countBadge.innerText = totalCount;
            totalText.innerText = 'S/ ' + totalPrice.toFixed(2);
            subtotalText.innerText = 'S/ ' + totalPrice.toFixed(2);
            finalTotalText.innerText = 'S/ ' + totalPrice.toFixed(2);

            if (cart.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 20px;">Tu carrito está vacío.</p>';
                return;
            }

            let html = '<div style="display:flex; flex-direction:column; gap:10px;">';
            cart.forEach((item, index) => {
                html += `
                    <div style="display:flex; justify-content:space-between; align-items:center; background:#f8fafc; padding:8px 12px; border-radius:8px;">
                        <div>
                            <div style="font-weight:700; font-size:13px; color:#0f172a;">${item.name}</div>
                            <div style="font-size:12px; color:#64748b;">S/ ${item.price.toFixed(2)} c/u</div>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <button onclick="changeQty(${index}, -1)" style="width:24px; height:24px; border:1px solid #cbd5e1; background:#fff; border-radius:4px; cursor:pointer;">-</button>
                            <span style="font-weight:700; font-size:13px;">${item.qty}</span>
                            <button onclick="changeQty(${index}, 1)" style="width:24px; height:24px; border:1px solid #cbd5e1; background:#fff; border-radius:4px; cursor:pointer;">+</button>
                            <span style="font-weight:800; font-size:13px; margin-left:8px; min-width:60px; text-align:right;">S/ ${(item.qty * item.price).toFixed(2)}</span>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        }

        function changeQty(index, delta) {
            cart[index].qty += delta;
            if (cart[index].qty <= 0) {
                cart.splice(index, 1);
            }
            updateCartUI();
        }

        function openCartModal() {
            document.getElementById('cartModal').classList.add('active');
        }

        function closeCartModal() {
            document.getElementById('cartModal').classList.remove('active');
        }

        function confirmOrder() {
            if (cart.length === 0) {
                alert('El carrito está vacío. Agrega productos primero.');
                return;
            }
            const address = document.getElementById('orderAddress').value;
            alert('¡Gracias por tu compra! Tu pedido de ' + cart.length + ' producto(s) ha sido registrado y será enviado a: ' + address);
            cart = [];
            updateCartUI();
            closeCartModal();
        }

        function toggleUserDropdown() {
            const dd = document.getElementById('userDropdown');
            dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
        }

        window.addEventListener('click', function(e) {
            const btn = document.getElementById('userMenuBtn');
            const dd = document.getElementById('userDropdown');
            if (btn && dd && !btn.contains(e.target)) {
                dd.style.display = 'none';
            }
        });
    </script>
</body>
</html>
