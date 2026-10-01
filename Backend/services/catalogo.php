<?php
/**
 * FAST DELIVERY - Datos y funciones de productos
 * Conectado dinámicamente con MySQL (RDS / Local) a través de Backend/services
 */

require_once __DIR__ . '/productos.php';

function obtenerProductosDB(): array {
    try {
        $dbProds = ProductoService::getProductos(null, null, 100, 0, true);
        $resultado = [];
        foreach ($dbProds as $p) {
            $resultado[] = [
                    "id" => (int)$p['id'],
                    "nombre" => $p['nombre'],
                    "categoria" => strtolower($p['categoria_nombre']),
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
        $catNombre = strtolower($prod['categoria']);
        $coincideCat = ($categoria === 'todos' || $catNombre === strtolower($categoria) || strpos($catNombre, strtolower($categoria)) !== false);
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

