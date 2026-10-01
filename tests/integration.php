<?php
// Crea un esquema de pruebas independiente; nunca ejecuta SQL.db ni modifica delivery_db.
$runtime = __DIR__ . '/../.runtime';
if (!is_dir($runtime)) mkdir($runtime, 0777, true);
ini_set('session.save_path', $runtime);
putenv('DEMO_MODE=false');
require_once __DIR__ . '/../config.php';
$original = DB_NAME;
$testName = 'fastdelivery_test_' . bin2hex(random_bytes(6));
$root = new PDO(sprintf('mysql:host=%s;port=%s;charset=%s', DB_HOST, DB_PORT, DB_CHARSET), DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
if (!preg_match('/^[a-zA-Z0-9_]+$/D', $original)) throw new RuntimeException('Nombre de base no válido.');
$root->exec("CREATE DATABASE `{$testName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$created = true;
function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
    echo 'PASS: ' . $message . "\n";
}
try {
    foreach (['usuarios', 'categorias', 'productos', 'pedidos', 'detalle_pedidos', 'pagos', 'historial_estados'] as $table) {
        $schema = $root->query("SHOW CREATE TABLE `{$original}`.`{$table}`")->fetch()['Create Table'];
        $root->exec("USE `{$testName}`");
        $root->exec(preg_replace('/ AUTO_INCREMENT=[0-9]+/', '', $schema));
    }
    require_once __DIR__ . '/../Backend/bootstrap.php';
    require_once __DIR__ . '/../Backend/services/usuarios.php';
    require_once __DIR__ . '/../Backend/services/productos.php';
    require_once __DIR__ . '/../Backend/services/pedidos.php';
    // La conexión singleton apunta exclusivamente al esquema recién creado.
    $db = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, $testName, DB_CHARSET), DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
    $property = new ReflectionProperty(Database::class, 'instance');
    $property->setValue(null, $db);
    $user = $db->prepare('INSERT INTO usuarios (nombre, apellido, email, telefono, rol, password_hash) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ([['Admin', 'administrador'], ['Cliente', 'cliente'], ['Repartidor', 'repartidor'], ['Otro', 'cliente']] as $i => $data) {
        $user->execute([$data[0], 'Prueba', strtolower($data[0]) . '@test.local', '999999999', $data[1], password_hash('prueba123', PASSWORD_BCRYPT)]);
    }
    $db->exec("INSERT INTO categorias (nombre) VALUES ('Alimentos')");
    $db->exec("INSERT INTO productos (categoria_id,nombre,precio,stock,unidad_medida,imagen_url) VALUES (1,'Arroz de prueba',20,100,'bolsa','assets/images/arroz.svg'),(1,'Leche de prueba',10,50,'unidad','assets/images/leche.svg')");
    $valid = ['id'=>1, 'nombre'=>'Admin', 'apellido'=>'Prueba', 'email'=>'admin@test.local', 'telefono'=>'999999999', 'rol'=>'cliente', 'activo'=>'1'];
    check(!UsuarioService::guardar($valid, 1)['success'], 'No se puede quitar rol al administrador conectado');
    check(!UsuarioService::retirar(1, 1, false)['success'], 'No se puede desactivar al administrador conectado');
    check(!UsuarioService::retirar(1, 1, true)['success'], 'No se puede eliminar al administrador conectado');
    check(!UsuarioService::retirar(1, 99, true)['success'], 'No se puede eliminar al último administrador');
    $new = ['nombre'=>'Zeta', 'apellido'=>'Prueba', 'email'=>'zeta@test.local', 'telefono'=>'999999999', 'rol'=>'cliente', 'activo'=>'1', 'password'=>'secreto123'];
    check(UsuarioService::guardar($new, 1)['success'], 'Crear usuario');
    check(!UsuarioService::guardar($new, 1)['success'], 'Rechazar correo duplicado');
    $id = (int)$db->query("SELECT id FROM usuarios WHERE email='zeta@test.local'")->fetchColumn();
    $hash = $db->query("SELECT password_hash FROM usuarios WHERE id={$id}")->fetchColumn();
    $new['id']=$id; $new['nombre']='Alfa'; $new['password']='';
    check(UsuarioService::guardar($new, 1)['success'] && $hash === $db->query("SELECT password_hash FROM usuarios WHERE id={$id}")->fetchColumn(), 'Editar usuario conserva contraseña vacía');
    $new['password']='nueva123';
    check(UsuarioService::guardar($new, 1)['success'] && password_verify('nueva123', $db->query("SELECT password_hash FROM usuarios WHERE id={$id}")->fetchColumn()), 'Cambiar contraseña guarda hash');
    for ($i=0;$i<12;$i++) $user->execute(['Persona '.$i, 'Prueba', "persona{$i}@test.local", '999999999', 'cliente', password_hash('prueba123',PASSWORD_BCRYPT)]);
    $list = UsuarioService::listar('cliente', 'nombre', 'asc', 2);
    check($list['page']===2 && count($list['items'])>0 && count(array_filter($list['items'], fn($u)=>$u['rol']!=='cliente'))===0, 'Filtro, orden por nombre y paginación');
    $list = UsuarioService::listar('', 'inyeccion', 'inyeccion', 1);
    check($list['items'][0]['id'] > $list['items'][1]['id'], 'Orden inválido usa ID descendente');
    check(UsuarioService::retirar($id, 1, false)['success'] && !UsuarioService::obtener($id)['activo'], 'Desactivar conserva cuenta');
    check(UsuarioService::retirar($id, 1, true)['success'] && !UsuarioService::obtener($id), 'Eliminar cuenta sin pedidos');
    $_SESSION['user_id']=2;
    $pedidoData=['nombre'=>'Destinatario Prueba','direccion'=>'Av. Prueba 123','telefono'=>'988887777','carrito'=>[['id'=>1,'cantidad'=>1,'precio'=>0.01]]];
    $result=PedidoService::crearPedido($pedidoData);
    check($result['success'] && $result['pedido']['total']===25.0, 'Pedido recalcula precio y envío desde MySQL');
    $pedidoId=$result['pedido']['id'];
    $saved = PedidoService::getPedidoById((string)$pedidoId);
    check($saved['cliente']==='Destinatario Prueba' && $saved['telefono']==='988887777', 'Conservar contacto de entrega sin cambiar esquema');
    check((int)$db->query('SELECT stock FROM productos WHERE id=1')->fetchColumn()===99, 'Compra descuenta stock');
    check(!UsuarioService::retirar(2, 1, true)['success'], 'Usuario con pedidos no se elimina');
    $pedidoData['carrito']=[['id'=>1,'cantidad'=>3]];
    $result=PedidoService::crearPedido($pedidoData);
    check($result['success'] && $result['pedido']['envio']===0, 'Envío gratis desde S/ 50');
    $count=(int)$db->query('SELECT COUNT(*) FROM pedidos')->fetchColumn();
    foreach ([[], [['id'=>9999,'cantidad'=>1]], [['id'=>1,'cantidad'=>0]], [['id'=>1,'cantidad'=>100000]], [['id'=>1,'cantidad'=>1],['id'=>9999,'cantidad'=>1]]] as $bad) {
        $pedidoData['carrito']=$bad;
        check(!PedidoService::crearPedido($pedidoData)['success'], 'Rechazar carrito inválido o stock insuficiente');
    }
    check((int)$db->query('SELECT COUNT(*) FROM pedidos')->fetchColumn()===$count && (int)$db->query('SELECT stock FROM productos WHERE id=1')->fetchColumn()===96, 'Fallos no dejan pedidos ni descuentos parciales');
    check(PedidoService::editarOperacion($pedidoId, ['estado'=>'en_camino','direccion'=>'Av. Nueva 456','repartidor_id'=>3])['success'], 'Editar dirección, asignar repartidor y estado');
    check(PedidoService::getPedidoById((string)$pedidoId)['direccion']==='Av. Nueva 456', 'Seguimiento consulta cambios actuales');
    check(!PedidoService::editarOperacion($pedidoId, ['estado'=>'pendiente','repartidor_id'=>2])['success'], 'Rechazar asignación a un cliente');
    $db->exec('UPDATE usuarios SET activo=0 WHERE id=3');
    check(!PedidoService::editarOperacion($pedidoId, ['estado'=>'pendiente','repartidor_id'=>3])['success'], 'Rechazar repartidor inactivo');
    check(PedidoService::editarOperacion($pedidoId, ['estado'=>'cancelado'])['success'], 'Cancelar pedido conserva historial');
    check((int)$db->query('SELECT stock FROM productos WHERE id=1')->fetchColumn()===97, 'Cancelar restaura stock');
    check(!PedidoService::editarOperacion($pedidoId, ['estado'=>'cancelado'])['success'] && (int)$db->query('SELECT stock FROM productos WHERE id=1')->fetchColumn()===97, 'Cancelación repetida no duplica stock');
    check(!PedidoService::editarOperacion($pedidoId, ['estado'=>'pendiente'])['success'], 'No reactivar cancelado');
    $secondId=$result['pedido']['id'];
    check(PedidoService::editarOperacion($secondId,['estado'=>'entregado'])['success'] && !PedidoService::editarOperacion($secondId,['estado'=>'cancelado'])['success'], 'No cancelar pedido entregado');
    check(ProductoService::eliminarProducto(2)['success'] && !ProductoService::getProductoById(2)['disponible'], 'Eliminar producto desactiva sin borrar');
    check(count(ProductoService::getProductos(null,null,50,0,true))===1, 'Tienda excluye productos desactivados');
    check(count(ProductoService::getProductos(null,'Alimentos'))===2 && ProductoService::countProductos(null,'Alimentos')===2, 'Búsqueda y conteo por categoría');
    $productData = ['categoria_id'=>1,'nombre'=>'Papel de prueba','precio'=>'12.50','stock'=>'10','unidad_medida'=>'paquete','imagen_url'=>'assets/images/papel_super.svg'];
    $productResult = ProductoService::crearProducto($productData);
    check($productResult['success'], 'Crear producto desde servicio');
    $productId = (int)$productResult['id'];
    $productData['precio'] = '15.00';
    check(ProductoService::actualizarProducto($productId, $productData)['success'] && (float)ProductoService::getProductoById($productId)['precio']===15.0, 'Editar producto desde servicio');
    $productData['stock']='-1';
    check(!ProductoService::actualizarProducto($productId, $productData)['success'], 'Rechazar stock negativo');
    check(ProductoService::eliminarProducto($productId)['success'] && !ProductoService::getProductoById($productId)['disponible'], 'Desactivar producto conserva registro');
    $db->prepare('DELETE FROM productos WHERE id=?')->execute([$productId]);
    $_SESSION['user_id']=3;
    check(currentUser()===null, 'Sesión de usuario desactivado pierde acceso');
    $_SESSION['user_id']=2;
    $newPublic=['nombre'=>'Publico','apellido'=>'Prueba','email'=>'publico@test.local','telefono'=>'999999999','password'=>'prueba123','rol'=>'administrador'];
    check(AuthService::register($newPublic)['success'] && $db->query("SELECT rol FROM usuarios WHERE email='publico@test.local'")->fetchColumn()==='cliente', 'Registro público no otorga rol administrador');
    check(!AuthService::demo('administrador')['success'], 'Modo normal impide acceso sin contraseña');
    echo "ALL INTEGRATION CHECKS PASSED\n";
    if (in_array('--keep', $argv, true)) {
        $db->exec('UPDATE usuarios SET activo=1 WHERE id=3');
        $db->exec('UPDATE productos SET disponible=1 WHERE id=2');
        file_put_contents($runtime . '/test-db.json', json_encode(['database' => $testName]));
        $created = false;
        echo "Esquema de pruebas conservado para verificación HTTP y visual.\n";
    }
} finally {
    if ($created && preg_match('/^fastdelivery_test_[a-f0-9]{12}$/D', $testName)) $root->exec("DROP DATABASE `{$testName}`");
}
