<?php
/**
 * FAST DELIVERY - Datos y funciones de productos
 * Conectado dinámicamente con MySQL (RDS / Local) a través de Backend/services
 */

require_once __DIR__ . '/productos.php';

function categoriasTienda(string $categoria, string $nombre = ''): array {
    $categoria = strtolower(trim($categoria));
    if (in_array($categoria, ['super', 'supermercado', 'abarrotes'], true)) {
        if (preg_match('/papel higi[eé]n|detergente|limpiador|lej[ií]a/iu', $nombre)) return ['limpieza'];
        return preg_match('/leche|agua|jugo|n[eé]ctar|bebida|gaseosa|refresco/iu', $nombre) ? ['bebidas'] : ['alimentos'];
    }
    // La categoría antigua agrupa ambas secciones; conservar esa pertenencia sin modificar MySQL.
    if ($categoria === 'cuidado y limpieza') return ['limpieza', 'higiene'];
    if (in_array($categoria, ['farmacia', 'medicamentos'], true)) return ['medicamentos'];
    if (str_contains($categoria, 'limpieza')) return ['limpieza'];
    if (str_contains($categoria, 'higiene') || str_contains($categoria, 'cuidado')) return ['higiene'];
    return [$categoria];
}

function obtenerProductosDB(): array {
    try {
        $dbProds = ProductoService::getProductos(null, null, 100, 0, true);
        $resultado = [];
        foreach ($dbProds as $p) {
            $categorias = categoriasTienda($p['categoria_nombre'], $p['nombre']);
            $resultado[] = [
                    "id" => (int)$p['id'],
                    "nombre" => $p['nombre'],
                    "categoria" => $categorias[0],
                    "categorias" => $categorias,
                    "categoria_id" => (int)$p['categoria_id'],
                    "precio" => (float)$p['precio'],
                    "precio_oferta" => null,
                    "es_oferta" => ((int)$p['id'] % 2 === 1), // simula ofertas para productos impares en la UI
                    "destacado" => true,
                    "stock" => (int)$p['stock'],
                    "unidad" => $p['unidad_medida'],
                    "descripcion" => $p['descripcion'] ?: $p['nombre'],
                    "imagen" => !empty($p['imagen_url']) ? $p['imagen_url'] : 'assets/images/default.svg'
            ];
        }
        return $resultado;
    } catch (Exception $e) {
        error_log("Error conectando catálogo con MySQL: " . $e->getMessage());
    }

    return [];
}

function obtenerProductos(): array {
    return obtenerProductosDB();
}

function filtrarProductos($categoria = 'todos', $buscar = '', $soloOfertas = false, $soloDestacados = false): array {
    $todos = obtenerProductos();
    $filtrados = [];

    foreach ($todos as $prod) {
        $coincideCat = $categoria === 'todos' || in_array(strtolower($categoria), $prod['categorias'], true);
        $coincideBusqueda = empty($buscar) || 
                            stripos($prod['nombre'], $buscar) !== false || 
                            stripos($prod['unidad'], $buscar) !== false;
        $coincideOferta = !$soloOfertas || $prod['es_oferta'];
        $coincideDestacado = !$soloDestacados || $prod['destacado'];

        if ($coincideCat && $coincideBusqueda && $coincideOferta && $coincideDestacado) {
            $filtrados[] = $prod;
        }
    }

    return $filtrados;
}

