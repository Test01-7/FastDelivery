<?php
/**
 * FAST DELIVERY - Datos y funciones de productos
 * Conectado dinámicamente con MySQL (RDS / Local) a través de Backend/services
 */

require_once __DIR__ . '/../../Backend/services/productos.php';

function obtenerProductosDB(): array {
    try {
        $dbProds = ProductoService::getProductos(null, null, 100, 0);
        if (!empty($dbProds)) {
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
                    "imagen" => !empty($p['imagen_url']) ? $p['imagen_url'] : 'https://images.unsplash.com/photo-1586201375761-83865001e31c?auto=format&fit=crop&w=500&q=80'
                ];
            }
            return $resultado;
        }
    } catch (Exception $e) {
        error_log("Error conectando catálogo con MySQL: " . $e->getMessage());
    }

    return obtenerProductosFallback();
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

function obtenerProductosFallback(): array {
    return [
        [
            "id" => 1,
            "nombre" => "Arroz Extra Superior Faraón 5kg",
            "categoria" => "alimentos",
            "precio" => 21.90,
            "precio_oferta" => 19.90,
            "es_oferta" => true,
            "destacado" => true,
            "stock" => 25,
            "unidad" => "Bolsa de 5 kg",
            "descripcion" => "Arroz de grano largo, graneado perfecto para los almuerzos de la semana.",
            "imagen" => "https://images.unsplash.com/photo-1586201375761-83865001e31c?auto=format&fit=crop&w=500&q=80"
        ],
        [
            "id" => 2,
            "nombre" => "Aceite Vegetal Primor 1L",
            "categoria" => "alimentos",
            "precio" => 10.50,
            "precio_oferta" => null,
            "es_oferta" => false,
            "destacado" => true,
            "stock" => 18,
            "unidad" => "Botella de 1 L",
            "descripcion" => "Aceite vegetal 100% puro para freír y cocinar diariamente.",
            "imagen" => "https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?auto=format&fit=crop&w=500&q=80"
        ],
        [
            "id" => 3,
            "nombre" => "Agua Mineral San Luis Sin Gas 2.5L",
            "categoria" => "bebidas",
            "precio" => 4.20,
            "precio_oferta" => 3.50,
            "es_oferta" => true,
            "destacado" => true,
            "stock" => 30,
            "unidad" => "Botella 2.5 L",
            "descripcion" => "Agua de mesa purificada y refrescante para hidratarte todo el día.",
            "imagen" => "https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&w=500&q=80"
        ],
        [
            "id" => 4,
            "nombre" => "Jugo Néctar Frugos del Valle Naranja 1L",
            "categoria" => "bebidas",
            "precio" => 6.50,
            "precio_oferta" => null,
            "es_oferta" => false,
            "destacado" => false,
            "stock" => 20,
            "unidad" => "Caja de 1 L",
            "descripcion" => "Delicioso néctar con jugo de naranja natural sin preservantes.",
            "imagen" => "https://images.unsplash.com/photo-1613478223719-2ab802602423?auto=format&fit=crop&w=500&q=80"
        ],
        [
            "id" => 5,
            "nombre" => "Detergente Ariel Multiacción 2kg",
            "categoria" => "limpieza",
            "precio" => 24.50,
            "precio_oferta" => 21.90,
            "es_oferta" => true,
            "destacado" => true,
            "stock" => 15,
            "unidad" => "Bolsa de 2 kg",
            "descripcion" => "Remueve las manchas más difíciles dejando tus prendas con aroma fresco.",
            "imagen" => "https://images.unsplash.com/photo-1585837575652-267c041d77d4?auto=format&fit=crop&w=500&q=80"
        ],
        [
            "id" => 6,
            "nombre" => "Papel Higiénico Suave Pack x12 Rollos",
            "categoria" => "limpieza",
            "precio" => 16.90,
            "precio_oferta" => null,
            "es_oferta" => false,
            "destacado" => false,
            "stock" => 40,
            "unidad" => "Pack de 12 rollos",
            "descripcion" => "Doble hoja de máxima suavidad y alto rendimiento para el hogar.",
            "imagen" => "https://images.unsplash.com/photo-1584556812952-905ffd0c611a?auto=format&fit=crop&w=500&q=80"
        ],
        [
            "id" => 7,
            "nombre" => "Shampoo Head & Shoulders Limpieza 375ml",
            "categoria" => "higiene",
            "precio" => 17.50,
            "precio_oferta" => 14.90,
            "es_oferta" => true,
            "destacado" => true,
            "stock" => 12,
            "unidad" => "Frasco 375 ml",
            "descripcion" => "Fórmula con control de caspa y limpieza profunda desde la raíz.",
            "imagen" => "https://images.unsplash.com/photo-1535585209827-a15fcdbc4c2d?auto=format&fit=crop&w=500&q=80"
        ],
        [
            "id" => 8,
            "nombre" => "Jabón de Tocador Camay Suave 3x125g",
            "categoria" => "higiene",
            "precio" => 9.20,
            "precio_oferta" => null,
            "es_oferta" => false,
            "destacado" => false,
            "stock" => 22,
            "unidad" => "Pack 3 unidades",
            "descripcion" => "Jabón perfumado con aceites esenciales para la piel suave.",
            "imagen" => "https://images.unsplash.com/photo-1607006482182-3d90615993e1?auto=format&fit=crop&w=500&q=80"
        ]
    ];
}
