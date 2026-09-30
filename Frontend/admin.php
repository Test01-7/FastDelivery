<?php
/**
 * FAST DELIVERY - Panel de Administrador / Gestión de Productos
 * Basado en wireframe (Imagen 3) y conectado a MySQL RDS
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Backend/services/auth.php';
require_once __DIR__ . '/../Backend/services/productos.php';

// Validar que el usuario sea estrictamente 'administrador'
$currentUser = AuthService::requireAuth(['administrador']);

$successMsg = '';
$errorMsg = '';

// Procesar acciones CRUD de administración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $res = ProductoService::crearProducto($_POST);
        if ($res['success']) {
            $successMsg = '¡Producto agregado correctamente al catálogo!';
        } else {
            $errorMsg = $res['message'];
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $res = ProductoService::actualizarProducto($id, $_POST);
        if ($res['success']) {
            $successMsg = '¡Producto actualizado correctamente!';
        } else {
            $errorMsg = $res['message'];
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $res = ProductoService::eliminarProducto($id);
        if ($res['success']) {
            $successMsg = $res['message'];
        } else {
            $errorMsg = $res['message'];
        }
    }
}

// Búsqueda y paginación
$busqueda = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 6; // Para simular paginación < 1 2 3 4 > según wireframe
$offset = ($page - 1) * $limit;

$totalCount = ProductoService::countProductos(null, $busqueda);
$totalPages = max(1, (int)ceil($totalCount / $limit));
$productos = ProductoService::getProductos(null, $busqueda, $limit, $offset);
$categorias = ProductoService::getCategorias();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fast Delivery - Panel de Administrador</title>
    <meta name="description" content="Panel de administración de catálogo de productos de Fast Delivery.">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .sidebar-menu-tip {
            display: none;
            position: absolute;
            left: 80px;
            background: #0f172a;
            color: #fff;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            white-space: nowrap;
            z-index: 300;
        }
        .sidebar-icon-btn:hover .sidebar-menu-tip {
            display: block;
        }
    </style>
</head>
<body class="admin-layout">

    <!-- BARRA SUPERIOR (PANEL DE ADMINISTRADOR) -->
    <header class="admin-topbar">
        <h1 class="admin-topbar-title">PANEL DE ADMINISTRADOR</h1>
        <div style="position: absolute; left: 24px; display:flex; align-items:center; gap: 8px;">
            <span style="font-size: 13px; color: #94a3b8;">Admin:</span>
            <strong style="font-size: 13px; color: #ffffff;"><?= htmlspecialchars($currentUser['nombre']) ?></strong>
        </div>
        <div style="position: absolute; right: 24px; display:flex; align-items:center; gap:12px;">
            <a href="../Delivery fast/index.php" class="admin-topbar-logout" style="border-color:#38bdf8; color:#38bdf8;">
                🛒 Ver Tienda
            </a>
            <a href="logout.php" class="admin-topbar-logout">
                Cerrar Sesión
            </a>
        </div>
    </header>

    <!-- BARRA LATERAL IZQUIERDA (NAVY SIDEBAR CON ICONOS) -->
    <nav class="admin-sidebar" aria-label="Navegación del Administrador">
        
        <!-- Icono 1: Menú Hamburguesa con borde violeta (Wireframe 3) -->
        <a href="admin.php" class="sidebar-icon-btn menu-toggle" title="Menú Principal">
            <svg viewBox="0 0 24 24">
                <line x1="3" y1="6" x2="21" y2="6" stroke-width="2.5" stroke-linecap="round"/>
                <line x1="3" y1="12" x2="21" y2="12" stroke-width="2.5" stroke-linecap="round"/>
                <line x1="3" y1="18" x2="21" y2="18" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
            <span class="sidebar-menu-tip">Menú Principal</span>
        </a>

        <!-- Icono 2: Home / Inicio -->
        <a href="../Delivery fast/index.php" class="sidebar-icon-btn" title="Inicio / Catálogo de Clientes">
            <svg viewBox="0 0 24 24">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke-width="2" stroke-linejoin="round"/>
                <polyline points="9 22 9 12 15 12 15 22" stroke-width="2"/>
            </svg>
            <span class="sidebar-menu-tip">Vista Cliente</span>
        </a>

        <!-- Icono 3: Usuarios con engranaje -->
        <a href="#usuarios" class="sidebar-icon-btn" onclick="alert('Módulo de Gestión de Usuarios y Repartidores activo.'); return false;" title="Gestión de Usuarios">
            <svg viewBox="0 0 24 24">
                <circle cx="9" cy="7" r="4" stroke-width="2"/>
                <path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2" stroke-width="2"/>
                <circle cx="18" cy="18" r="3" stroke-width="1.8"/>
                <path d="M18 13.5v1M18 21.5v1M13.5 18h1M21.5 18h1" stroke-width="1.5"/>
            </svg>
            <span class="sidebar-menu-tip">Gestión de Usuarios</span>
        </a>

        <!-- Icono 4: Canasta / Carrito con engranaje (ACTIVO - GESTIÓN DE PRODUCTOS) -->
        <a href="admin.php" class="sidebar-icon-btn active" title="Gestión de Productos">
            <svg viewBox="0 0 24 24">
                <circle cx="9" cy="21" r="1" stroke-width="2"/>
                <circle cx="20" cy="21" r="1" stroke-width="2"/>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" stroke-width="2"/>
                <circle cx="15" cy="11" r="2.5" stroke-width="1.5"/>
                <path d="M15 7.5v1M15 13.5v1M11.5 11h1M17.5 11h1" stroke-width="1.2"/>
            </svg>
            <span class="sidebar-menu-tip">Panel de Productos</span>
        </a>

        <!-- Salir al final de la barra -->
        <div style="margin-top: auto; margin-bottom: 20px;">
            <a href="logout.php" class="sidebar-icon-btn" title="Cerrar Sesión" style="color: #f87171;">
                <svg viewBox="0 0 24 24">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" stroke-width="2"/>
                    <polyline points="16 17 21 12 16 7" stroke-width="2"/>
                    <line x1="21" y1="12" x2="9" y2="12" stroke-width="2"/>
                </svg>
                <span class="sidebar-menu-tip">Cerrar Sesión</span>
            </a>
        </div>
    </nav>

    <!-- ÁREA DE CONTENIDO PRINCIPAL -->
    <main class="admin-main-wrap">
        <section class="admin-panel-card">
            
            <!-- Título Central -->
            <h2 class="admin-panel-title">PANEL DE PRODUCTOS</h2>

            <!-- Alertas / Feedback -->
            <?php if (!empty($successMsg)): ?>
                <div class="alert-message alert-success" role="status">
                    <?= htmlspecialchars($successMsg) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($errorMsg)): ?>
                <div class="alert-message alert-error" role="alert">
                    <?= htmlspecialchars($errorMsg) ?>
                </div>
            <?php endif; ?>

            <!-- Barra de búsqueda superior -->
            <div class="admin-toolbar-row">
                <form method="GET" action="admin.php" class="search-pill-box" id="searchForm">
                    <input 
                        type="text" 
                        name="search" 
                        id="searchInput" 
                        placeholder="Buscar producto..." 
                        value="<?= htmlspecialchars($busqueda) ?>"
                    >
                    <button type="submit" style="background:none; border:none; cursor:pointer;" aria-label="Buscar">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </button>
                </form>

                <div style="font-size: 13px; color: #64748b;">
                    Total: <strong><?= $totalCount ?></strong> productos registrados en RDS/MySQL
                </div>
            </div>

            <!-- Estructura Dividida: Tabla a la izquierda, Botones de acción a la derecha -->
            <div class="admin-content-split">
                
                <!-- TABLA DE PRODUCTOS (ID | Titulo | Descripcion) -->
                <div class="admin-table-container">
                    <table class="admin-table" id="tablaProductos">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th style="width: 200px;">Titulo</th>
                                <th>Descripcion</th>
                                <th style="width: 90px;">Precio</th>
                                <th style="width: 80px;">Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($productos)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: #64748b; padding: 30px;">
                                        No se encontraron productos registrados.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($productos as $p): ?>
                                    <tr 
                                        onclick="selectRow(this, <?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>)" 
                                        data-id="<?= $p['id'] ?>"
                                    >
                                        <td><strong><?= $p['id'] ?></strong></td>
                                        <td><?= htmlspecialchars($p['nombre']) ?></td>
                                        <td><?= htmlspecialchars($p['descripcion']) ?></td>
                                        <td>S/ <?= number_format($p['precio'], 2) ?></td>
                                        <td><?= $p['stock'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- COLUMNA DE BOTONES DE ACCIÓN (WIRE FRAME 3) -->
                <aside class="admin-actions-column" aria-label="Acciones de catálogo">
                    <button type="button" class="btn-admin-action" onclick="openAddModal()">
                        Añadir Producto
                    </button>
                    
                    <a href="admin.php" class="btn-admin-action" style="background:#0b345b;">
                        Reiniciar Lista
                    </a>

                    <button type="button" class="btn-admin-action" id="btnEditar" onclick="openEditModal()">
                        Editar Producto
                    </button>

                    <button type="button" class="btn-admin-action danger" id="btnBorrar" onclick="confirmDelete()">
                        Borrar Producto
                    </button>
                </aside>

            </div>

            <!-- PAGINACIÓN (< 1 2 3 4 > EN AZUL CELESTE SCREENSHOT 3) -->
            <nav class="admin-pagination" aria-label="Paginación de productos">
                <?php if ($page > 1): ?>
                    <a href="admin.php?page=<?= $page - 1 ?>&search=<?= urlencode($busqueda) ?>">&lt;</a>
                <?php else: ?>
                    <span style="opacity: 0.4;">&lt;</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= max(4, $totalPages); $i++): ?>
                    <?php if ($i <= $totalPages): ?>
                        <a href="admin.php?page=<?= $i ?>&search=<?= urlencode($busqueda) ?>" class="<?= ($page === $i) ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php else: ?>
                        <span style="opacity: 0.3;"><?= $i ?></span>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="admin.php?page=<?= $page + 1 ?>&search=<?= urlencode($busqueda) ?>">&gt;</a>
                <?php else: ?>
                    <span style="opacity: 0.4;">&gt;</span>
                <?php endif; ?>
            </nav>

        </section>
    </main>

    <!-- MODAL 1: AÑADIR PRODUCTO -->
    <div class="modal-overlay" id="addModal" role="dialog" aria-modal="true" aria-labelledby="addTitle">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modal-title" id="addTitle">Añadir Nuevo Producto</h3>
                <button type="button" class="modal-close" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST" action="admin.php">
                <input type="hidden" name="action" value="create">

                <div class="form-group">
                    <label for="add_categoria">Categoría *</label>
                    <select name="categoria_id" id="add_categoria" class="form-control" required>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="add_nombre">Título / Nombre del Producto *</label>
                    <input type="text" name="nombre" id="add_nombre" class="form-control" placeholder="Ej: Alcohol Medicinal 70°" required>
                </div>

                <div class="form-group">
                    <label for="add_descripcion">Descripción</label>
                    <textarea name="descripcion" id="add_descripcion" class="form-control" rows="3" placeholder="Detalles del producto..."></textarea>
                </div>

                <div style="display:flex; gap:12px;">
                    <div class="form-group" style="flex:1;">
                        <label for="add_precio">Precio (S/) *</label>
                        <input type="number" step="0.10" name="precio" id="add_precio" class="form-control" placeholder="0.00" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label for="add_stock">Stock *</label>
                        <input type="number" name="stock" id="add_stock" class="form-control" value="20" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="add_unidad">Unidad de Medida</label>
                    <select name="unidad_medida" id="add_unidad" class="form-control">
                        <option value="unidad">unidad</option>
                        <option value="paquete">paquete</option>
                        <option value="kg">kg</option>
                        <option value="litro">litro</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="add_imagen">Ruta de Imagen</label>
                    <input type="text" name="imagen_url" id="add_imagen" class="form-control" value="assets/images/default.svg">
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-admin-action" onclick="closeModal('addModal')" style="background:#64748b;">Cancelar</button>
                    <button type="submit" class="btn-admin-action">Guardar en Base de Datos</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: EDITAR PRODUCTO -->
    <div class="modal-overlay" id="editModal" role="dialog" aria-modal="true" aria-labelledby="editTitle">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modal-title" id="editTitle">Editar Producto</h3>
                <button type="button" class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST" action="admin.php">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-group">
                    <label for="edit_categoria">Categoría *</label>
                    <select name="categoria_id" id="edit_categoria" class="form-control" required>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_nombre">Título / Nombre *</label>
                    <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="edit_descripcion">Descripción</label>
                    <textarea name="descripcion" id="edit_descripcion" class="form-control" rows="3"></textarea>
                </div>

                <div style="display:flex; gap:12px;">
                    <div class="form-group" style="flex:1;">
                        <label for="edit_precio">Precio (S/) *</label>
                        <input type="number" step="0.10" name="precio" id="edit_precio" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label for="edit_stock">Stock *</label>
                        <input type="number" name="stock" id="edit_stock" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_unidad">Unidad de Medida</label>
                    <select name="unidad_medida" id="edit_unidad" class="form-control">
                        <option value="unidad">unidad</option>
                        <option value="paquete">paquete</option>
                        <option value="kg">kg</option>
                        <option value="litro">litro</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-admin-action" onclick="closeModal('editModal')" style="background:#64748b;">Cancelar</button>
                    <button type="submit" class="btn-admin-action">Actualizar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <!-- FORMULARIO OCULTO PARA BORRAR PRODUCTO -->
    <form id="deleteForm" method="POST" action="admin.php" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>

    <script>
        let selectedProduct = null;

        function selectRow(tr, productData) {
            // Remover selección previa
            document.querySelectorAll('#tablaProductos tbody tr').forEach(r => r.classList.remove('selected'));
            tr.classList.add('selected');
            selectedProduct = productData;
        }

        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }

        function openEditModal() {
            if (!selectedProduct) {
                alert('Por favor haga clic en una fila de la tabla para seleccionar el producto a editar.');
                return;
            }

            document.getElementById('edit_id').value = selectedProduct.id;
            document.getElementById('edit_categoria').value = selectedProduct.categoria_id;
            document.getElementById('edit_nombre').value = selectedProduct.nombre;
            document.getElementById('edit_descripcion').value = selectedProduct.descripcion;
            document.getElementById('edit_precio').value = selectedProduct.precio;
            document.getElementById('edit_stock').value = selectedProduct.stock;
            document.getElementById('edit_unidad').value = selectedProduct.unidad_medida;

            document.getElementById('editModal').classList.add('active');
        }

        function confirmDelete() {
            if (!selectedProduct) {
                alert('Por favor seleccione un producto en la tabla haciendo clic en su fila.');
                return;
            }

            if (confirm(`¿Está seguro de que desea eliminar el producto "${selectedProduct.nombre}" (ID: ${selectedProduct.id}) de la base de datos?`)) {
                document.getElementById('delete_id').value = selectedProduct.id;
                document.getElementById('deleteForm').submit();
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.classList.remove('active');
            }
        });
    </script>
</body>
</html>
