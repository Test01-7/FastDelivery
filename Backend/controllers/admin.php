<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../services/productos.php';
require_once __DIR__ . '/../services/usuarios.php';
require_once __DIR__ . '/../services/pedidos.php';
require_once __DIR__ . '/../services/imagenes.php';
$currentUser = requireUser(['administrador']);
$panel = in_array($_GET['panel'] ?? '', ['productos', 'usuarios', 'pedidos'], true) ? $_GET['panel'] : 'productos';
$filters = ['panel' => $panel];
foreach (['rol', 'orden', 'direccion', 'search', 'page'] as $key) {
    if (isset($_GET[$key]) && is_string($_GET[$key])) $filters[$key] = $_GET[$key];
}
$adminUrl = 'admin.php?' . http_build_query($filters);
$formError = '';
$formData = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireScalarPost();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($panel === 'productos' && in_array($action, ['save', 'delete'], true)) {
        $uploaded = null;
        try {
            $data = $_POST;
            unset($data['imagen_url']);
            if ($action === 'save') {
                $uploaded = ImagenService::subir($_FILES['imagen'] ?? null);
                if ($uploaded) $data['imagen_url'] = $uploaded;
            }
            $result = $action === 'delete' ? ProductoService::eliminarProducto($id) :
                ($id ? ProductoService::actualizarProducto($id, $data) : ProductoService::crearProducto($data));
        } catch (DomainException $exception) {
            $result = ['success' => false, 'message' => $exception->getMessage()];
        }
        if (!$result['success'] && $uploaded) {
            ImagenService::descartar($uploaded);
            $result['message'] .= ' Vuelve a seleccionar la imagen antes de guardar.';
        }
    } elseif ($panel === 'usuarios' && in_array($action, ['save', 'deactivate', 'delete'], true)) {
        $result = $action === 'save' ? UsuarioService::guardar($_POST, (int)$currentUser['id']) :
            UsuarioService::retirar($id, (int)$currentUser['id'], $action === 'delete');
    } elseif ($panel === 'pedidos' && in_array($action, ['save', 'delete'], true)) {
        $result = PedidoService::editarOperacion($id, $action === 'delete' ? ['estado' => 'cancelado'] : $_POST);
    } else {
        $result = ['success' => false, 'message' => 'Acción no válida para este panel.'];
    }
    if ($result['success'] || $action !== 'save') {
        flash($result['message'], $result['success']);
        header('Location: ' . $adminUrl);
        exit;
    }
    $formError = $result['message'];
    $formData = $_POST;
}

$notice = takeFlash();
$dbError = '';
$page = max(1, (int)($_GET['page'] ?? 1));
$total = 0;
$rows = $categorias = $repartidores = [];
$editing = null;
$showForm = isset($_GET['new']) || isset($_GET['edit']) || $formData !== null;
$rol = in_array($_GET['rol'] ?? '', UsuarioService::ROLES, true) ? $_GET['rol'] : '';
$orden = in_array($_GET['orden'] ?? '', ['id', 'nombre'], true) ? $_GET['orden'] : 'id';
$direccion = ($_GET['direccion'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$search = is_string($_GET['search'] ?? '') ? trim($_GET['search'] ?? '') : '';
try {
    getDB();
    if ($panel === 'productos') {
        $total = ProductoService::countProductos(null, $search);
        $page = min($page, max(1, (int)ceil($total / 10)));
        $rows = ProductoService::getProductos(null, $search, 10, ($page - 1) * 10);
        $categorias = ProductoService::getCategorias();
        if (isset($_GET['edit'])) $editing = ProductoService::getProductoById((int)$_GET['edit']);
    } elseif ($panel === 'usuarios') {
        $list = UsuarioService::listar($rol, $orden, $direccion, $page);
        $rows = $list['items']; $total = $list['total']; $page = $list['page'];
        if (isset($_GET['edit'])) $editing = UsuarioService::obtener((int)$_GET['edit']);
    } else {
        $list = PedidoService::listarAdmin($page);
        $rows = $list['items']; $total = $list['total']; $page = $list['page'];
        $repartidores = getDB()->query("SELECT id, nombre, apellido FROM usuarios WHERE rol='repartidor' AND activo=1 ORDER BY nombre, id")->fetchAll();
        if (isset($_GET['edit'])) $editing = PedidoService::getPedidoById((string)(int)$_GET['edit']);
    }
    if (isset($_GET['edit']) && !$editing) { $showForm = false; $dbError = 'El registro solicitado no existe.'; }
    if ($formData !== null) $editing = array_merge($editing ?? [], $formData);
    if ($panel === 'usuarios' && $formData !== null) $editing['direccion_defecto'] = $formData['direccion'] ?? '';
} catch (Throwable $exception) {
    error_log($exception->getMessage());
    $dbError = 'No se pudieron cargar los datos. Comprueba la conexión con MySQL.';
}
$editing = $editing ?? [];
$pages = max(1, (int)ceil($total / 10));

function adminLink(array $changes = []): string {
    global $filters;
    return 'admin.php?' . http_build_query(array_merge($filters, $changes));
}
