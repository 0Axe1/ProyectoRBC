<?php
session_start();

// --- ¡CAMBIO! UTILIZAR LA CONEXIÓN CENTRALIZADA ---
// 1. Incluir el archivo de conexión
require_once __DIR__ . '/../config/db_connection.php';

// 2. Conectar a la BD
$pdo = null;
try {
     $pdo = conectarDB(); // ¡Usar tu función!
} catch (\Exception $e) {
    // Registrar el error para depuración
    error_log('Error de conexión en inventario_actions: ' . $e->getMessage());
    header('Location: ../index.php?page=inventario&error=db_conn');
    exit;
}
// --- FIN DEL CAMBIO ---

// 1. Verificar si ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die("Acceso no autorizado.");
}

// 2. ¡NUEVO! VERIFICACIÓN DE PERMISOS
// El usuario debe tener el permiso 'ver_inventario' para realizar CUALQUIER acción aquí.
if (!isset($_SESSION['permisos']) || !in_array('ver_inventario', $_SESSION['permisos'])) {
    header('Location: ../index.php?page=inventario&error=permiso');
    exit;
}
// --- FIN VERIFICACIÓN DE PERMISOS ---


// 3. ¡NUEVO! VALIDACIÓN DE TOKEN CSRF
// Se valida en todas las peticiones POST que modifican datos.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        // Token inválido o ausente
        header('Location: ../index.php?page=inventario&error=csrf');
        exit;
    }
}
// --- FIN DE VALIDACIÓN CSRF ---


// --- LÓGICA PARA CREAR, ACTUALIZAR Y ELIMINAR (vía POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- VALIDACIONES Y OBTENCIÓN DE DATOS (NUEVO ESQUEMA) ---
    $id_producto = filter_var($_POST['id_producto'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $id_categoria = filter_var($_POST['id_categoria'] ?? null, FILTER_VALIDATE_INT);
    $nombre_producto = trim($_POST['nombre_producto'] ?? '');
    $descripcion = trim($_POST['descripcion_producto'] ?? '');
   
    // Campos numéricos
    $cantidad_stock = filter_var($_POST['cantidad_stock'] ?? 0, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]]);
    $precio_venta = filter_var($_POST['precio_venta_sugerido'] ?? 0, FILTER_VALIDATE_FLOAT, ["options" => ["min_range" => 0.01]]);

    // --- ACCIÓN DE CREAR PRODUCTO ---
    if ($action === 'create') {
        // Validar que todos los campos requeridos para la creación estén presentes
        if (empty($nombre_producto) || empty($id_categoria) || $cantidad_stock === false || $precio_venta === false) {
            header('Location: ../index.php?page=inventario&error=1');
            $pdo = null;
            exit;
        }

        try {
            // Lógica simplificada: Insertar directamente en 'productos'
            $sql = "INSERT INTO productos (id_categoria, nombre_producto, descripcion_producto, precio_venta_sugerido, cantidad_stock)
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_categoria, $nombre_producto, $descripcion, $precio_venta, $cantidad_stock]);
           
            header('Location: ../index.php?page=inventario&success=1');

        } catch (PDOException $e) {
            // Manejar error de duplicado (si 'nombre_producto' es UNIQUE) u otro
            header('Location: ../index.php?page=inventario&error=2');
        }

    }
    // --- ACCIÓN DE ACTUALIZAR INFORMACIÓN DEL PRODUCTO ---
    elseif ($action === 'update' && $id_producto) {
       
        // Validar campos (no se incluye 'cantidad_stock' porque está deshabilitado en el form de edición)
        if (empty($nombre_producto) || empty($id_categoria) || $precio_venta === false) {
            header('Location: ../index.php?page=inventario&error=1');
            $pdo = null;
            exit;
        }

        try {
            // El stock no se actualiza aquí, solo la información del producto
            $sql = "UPDATE productos SET
                        id_categoria = ?,
                        nombre_producto = ?,
                        descripcion_producto = ?,
                        precio_venta_sugerido = ?
                    WHERE id_producto = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_categoria, $nombre_producto, $descripcion, $precio_venta, $id_producto]);
           
            header('Location: ../index.php?page=inventario&success=2');

        } catch (PDOException $e) {
            header('Location: ../index.php?page=inventario&error=2');
        }
   
    // --- ¡CAMBIO! Lógica de borrado movida a POST ---
    } elseif ($action === 'delete' && $id_producto) {
        try {
            // Lógica simplificada: Solo borrar de 'productos',
            // La base de datos (con ON DELETE RESTRICT) impedirá esto si el producto está en 'detalle_de_pedido'
            $stmt = $pdo->prepare("DELETE FROM productos WHERE id_producto = ?");
            $stmt->execute([$id_producto]);

            header('Location: ../index.php?page=inventario&success=3');

        } catch (PDOException $e) {
            // Error si el producto está en un pedido/venta (violación de Foreign Key)
            header('Location: ../index.php?page=inventario&error=3');
        }
    }
   
    $pdo = null;
    exit;
}

// --- ¡CAMBIO! Eliminada la lógica de borrado por GET ---
/*
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete') {
   // ... Esta lógica ahora está arriba, dentro del bloque POST
}
*/

$pdo = null;
header('Location: ../index.php?page=inventario');
exit;
?>
