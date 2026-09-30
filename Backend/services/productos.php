<?php
/**
 * Fast Delivery - Servicio de Productos y Categorías
 * Consultas y operaciones CRUD para clientes y panel de administración
 */

require_once __DIR__ . '/conexion.php';

class ProductoService {

    /**
     * Obtener listado de categorías activas
     */
    public static function getCategorias(): array {
        try {
            $db = getDB();
            $stmt = $db->query("SELECT id, nombre, descripcion FROM categorias WHERE activo = 1 ORDER BY id ASC");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error en getCategorias: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener productos con filtros de búsqueda y categoría
     */
    public static function getProductos(?int $categoriaId = null, ?string $busqueda = null, int $limit = 50, int $offset = 0): array {
        try {
            $db = getDB();
            $sql = "
                SELECT p.id, p.categoria_id, c.nombre AS categoria_nombre, p.nombre, p.descripcion, 
                       p.precio, p.stock, p.unidad_medida, p.imagen_url, p.disponible, p.creado_en
                FROM productos p
                JOIN categorias c ON p.categoria_id = c.id
                WHERE 1=1
            ";
            $params = [];

            if ($categoriaId !== null && $categoriaId > 0) {
                $sql .= " AND p.categoria_id = :categoria_id";
                $params[':categoria_id'] = $categoriaId;
            }

            if (!empty($busqueda)) {
                $sql .= " AND (p.nombre LIKE :busqueda OR p.descripcion LIKE :busqueda OR c.nombre LIKE :busqueda)";
                $params[':busqueda'] = "%{$busqueda}%";
            }

            $sql .= " ORDER BY p.id ASC LIMIT :limit OFFSET :offset";

            $stmt = $db->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error en getProductos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar total de productos según filtros (para paginación)
     */
    public static function countProductos(?int $categoriaId = null, ?string $busqueda = null): int {
        try {
            $db = getDB();
            $sql = "SELECT COUNT(*) FROM productos p JOIN categorias c ON p.categoria_id = c.id WHERE 1=1";
            $params = [];

            if ($categoriaId !== null && $categoriaId > 0) {
                $sql .= " AND p.categoria_id = :categoria_id";
                $params[':categoria_id'] = $categoriaId;
            }

            if (!empty($busqueda)) {
                $sql .= " AND (p.nombre LIKE :busqueda OR p.descripcion LIKE :busqueda)";
                $params[':busqueda'] = "%{$busqueda}%";
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Obtener un producto por ID
     */
    public static function getProductoById(int $id): ?array {
        try {
            $db = getDB();
            $stmt = $db->prepare("
                SELECT p.*, c.nombre AS categoria_nombre 
                FROM productos p 
                JOIN categorias c ON p.categoria_id = c.id 
                WHERE p.id = :id 
                LIMIT 1
            ");
            $stmt->execute([':id' => $id]);
            $res = $stmt->fetch();
            return $res ?: null;
        } catch (Exception $e) {
            error_log("Error en getProductoById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear un nuevo producto
     */
    public static function crearProducto(array $data): array {
        try {
            $db = getDB();

            $categoria_id = (int)($data['categoria_id'] ?? 1);
            $nombre = trim($data['nombre'] ?? '');
            $descripcion = trim($data['descripcion'] ?? '');
            $precio = (float)($data['precio'] ?? 0);
            $stock = (int)($data['stock'] ?? 0);
            $unidad_medida = trim($data['unidad_medida'] ?? 'unidad');
            $imagen_url = trim($data['imagen_url'] ?? 'assets/images/default.png');
            $disponible = isset($data['disponible']) ? (int)$data['disponible'] : 1;

            if (empty($nombre) || $precio <= 0) {
                return ['success' => false, 'message' => 'El título y el precio son obligatorios.'];
            }

            $stmt = $db->prepare("
                INSERT INTO productos (categoria_id, nombre, descripcion, precio, stock, unidad_medida, imagen_url, disponible)
                VALUES (:categoria_id, :nombre, :descripcion, :precio, :stock, :unidad_medida, :imagen_url, :disponible)
            ");
            $stmt->execute([
                ':categoria_id' => $categoria_id,
                ':nombre' => $nombre,
                ':descripcion' => $descripcion,
                ':precio' => $precio,
                ':stock' => $stock,
                ':unidad_medida' => $unidad_medida,
                ':imagen_url' => $imagen_url,
                ':disponible' => $disponible
            ]);

            return ['success' => true, 'id' => $db->lastInsertId(), 'message' => 'Producto creado con éxito.'];
        } catch (Exception $e) {
            error_log("Error en crearProducto: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al guardar el producto: ' . $e->getMessage()];
        }
    }

    /**
     * Actualizar un producto existente
     */
    public static function actualizarProducto(int $id, array $data): array {
        try {
            $db = getDB();

            $categoria_id = (int)($data['categoria_id'] ?? 1);
            $nombre = trim($data['nombre'] ?? '');
            $descripcion = trim($data['descripcion'] ?? '');
            $precio = (float)($data['precio'] ?? 0);
            $stock = (int)($data['stock'] ?? 0);
            $unidad_medida = trim($data['unidad_medida'] ?? 'unidad');
            $imagen_url = trim($data['imagen_url'] ?? '');
            $disponible = isset($data['disponible']) ? (int)$data['disponible'] : 1;

            if (empty($nombre) || $precio <= 0) {
                return ['success' => false, 'message' => 'El título y el precio son obligatorios.'];
            }

            $sql = "
                UPDATE productos 
                SET categoria_id = :categoria_id, nombre = :nombre, descripcion = :descripcion,
                    precio = :precio, stock = :stock, unidad_medida = :unidad_medida, disponible = :disponible
            ";
            $params = [
                ':id' => $id,
                ':categoria_id' => $categoria_id,
                ':nombre' => $nombre,
                ':descripcion' => $descripcion,
                ':precio' => $precio,
                ':stock' => $stock,
                ':unidad_medida' => $unidad_medida,
                ':disponible' => $disponible
            ];

            if (!empty($imagen_url)) {
                $sql .= ", imagen_url = :imagen_url";
                $params[':imagen_url'] = $imagen_url;
            }

            $sql .= " WHERE id = :id";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            return ['success' => true, 'message' => 'Producto actualizado correctamente.'];
        } catch (Exception $e) {
            error_log("Error en actualizarProducto: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()];
        }
    }

    /**
     * Eliminar un producto
     */
    public static function eliminarProducto(int $id): array {
        try {
            $db = getDB();
            
            // Verificar si el producto tiene pedidos asociados
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM detalle_pedidos WHERE producto_id = :id");
            $checkStmt->execute([':id' => $id]);
            if ($checkStmt->fetchColumn() > 0) {
                // Si tiene pedidos, hacer soft delete
                $stmt = $db->prepare("UPDATE productos SET disponible = 0 WHERE id = :id");
                $stmt->execute([':id' => $id]);
                return ['success' => true, 'message' => 'El producto tiene pedidos asociados; se marcó como no disponible.'];
            }

            $stmt = $db->prepare("DELETE FROM productos WHERE id = :id");
            $stmt->execute([':id' => $id]);

            return ['success' => true, 'message' => 'Producto eliminado de la base de datos con éxito.'];
        } catch (Exception $e) {
            error_log("Error en eliminarProducto: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al eliminar producto: ' . $e->getMessage()];
        }
    }
}
