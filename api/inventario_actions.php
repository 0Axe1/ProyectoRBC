<?php
session_start();

require_once __DIR__ . '/../config/db_connection.php';

$pdo = null;
try {
     $pdo = conectarDB();
} catch (\Exception $e) {
    error_log('Error de conexión en inventario_actions: ' . $e->getMessage());
    header('Location: ../index.php?page=inventario&error=db_conn');
    exit;
}

// 1. Verificar si ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die("Acceso no autorizado.");
}

// 2. VERIFICACIÓN DE PERMISOS
if (!isset($_SESSION['permisos']) || !in_array('ver_inventario', $_SESSION['permisos'])) {
    header('Location: ../index.php?page=inventario&error=permiso');
    exit;
}

// 3. VALIDACIÓN DE TOKEN CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header('Location: ../index.php?page=inventario&error=csrf');
        exit;
    }
}

// --- LÓGICA PARA CREAR, ACTUALIZAR Y ELIMINAR (vía POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- DATOS DE 'productos' ---
    $id_producto = filter_var($_POST['id_producto'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $id_categoria = filter_var($_POST['id_categoria'] ?? null, FILTER_VALIDATE_INT);
    $nombre_producto = trim($_POST['nombre_producto'] ?? '');
    $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]]);
    $precio = filter_var($_POST['precio'] ?? null, FILTER_VALIDATE_FLOAT, ["options" => ["min_range" => 0.01]]);

    // --- ¡NUEVO! DATOS DE 'detalle_producto' ---
    // Usamos '?: null' para guardar NULL en la BD si el campo está vacío, en lugar de una cadena vacía.
    $descripcion = trim($_POST['descripcion'] ?? '') ?: null;
    $variedad = trim($_POST['variedad'] ?? '') ?: null;
    $origen = trim($_POST['origen'] ?? '') ?: null;
    $presentacion = trim($_POST['presentacion'] ?? '') ?: null;
    $unidad_medida = trim($_POST['unidad_medida'] ?? '') ?: null;
    $peso_neto = filter_var($_POST['peso_neto'] ?? null, FILTER_VALIDATE_FLOAT, ["options" => ["min_range" => 0]]);
    $calidad = trim($_POST['calidad'] ?? '') ?: null;
    $fecha_cosecha = trim($_POST['fecha_cosecha'] ?? '') ?: null;
    $observaciones = trim($_POST['observaciones'] ?? '') ?: null;
    
    // Si la fecha está vacía, la seteamos como NULL
    if (empty($fecha_cosecha)) {
        $fecha_cosecha = null;
    }
    // Si el peso está vacío, lo seteamos como NULL
    if ($peso_neto === false || $peso_neto === '') {
        $peso_neto = null;
    }


    // --- ACCIÓN DE CREAR PRODUCTO ---
    if ($action === 'create') {
        // Validar campos principales
        if (empty($nombre_producto) || empty($id_categoria) || $stock === null || $precio === null) {
            header('Location: ../index.php?page=inventario&error=1T'); // Error Tipo 1 (Datos)
            $pdo = null;
            exit;
        }

        try {
            $pdo->beginTransaction();

            // 1. Insertar en 'productos'
            $sql_prod = "INSERT INTO productos (id_categoria, nombre_producto, precio, stock)
                         VALUES (?, ?, ?, ?)";
            $stmt_prod = $pdo->prepare($sql_prod);
            $stmt_prod->execute([$id_categoria, $nombre_producto, $precio, $stock]);
            
            // Obtener el ID del producto recién creado
            $new_id_producto = $pdo->lastInsertId();

            // 2. Insertar en 'detalle_producto'
            $sql_det = "INSERT INTO detalle_producto 
                            (id_producto, descripcion, variedad, origen, presentacion, 
                             unidad_medida, peso_neto, calidad, fecha_cosecha, observaciones)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_det = $pdo->prepare($sql_det);
            $stmt_det->execute([
                $new_id_producto, $descripcion, $variedad, $origen, $presentacion,
                $unidad_medida, $peso_neto, $calidad, $fecha_cosecha, $observaciones
            ]);

            $pdo->commit();
            header('Location: ../index.php?page=inventario&success=1');

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Error al crear producto: " . $e->getMessage());
            header('Location: ../index.php?page=inventario&error=2');
        }

    }
    // --- ACCIÓN DE ACTUALIZAR INFORMACIÓN DEL PRODUCTO ---
    elseif ($action === 'update' && $id_producto) {
       
        // Validar campos principales
        if (empty($nombre_producto) || empty($id_categoria) || $precio === null || $stock === null) {
            header('Location: ../index.php?page=inventario&error=1T');
            $pdo = null;
            exit;
        }

        try {
            $pdo->beginTransaction();

            // 1. Actualizar 'productos'
            $sql_prod = "UPDATE productos SET
                            id_categoria = ?,
                            nombre_producto = ?,
                            precio = ?,
                            stock = ?
                         WHERE id_producto = ?";
            $stmt_prod = $pdo->prepare($sql_prod);
            $stmt_prod->execute([$id_categoria, $nombre_producto, $precio, $stock, $id_producto]);
           
            // 2. Actualizar 'detalle_producto' (o crearlo si no existe, usando ON DUPLICATE KEY UPDATE)
            // Esto es más robusto que un simple UPDATE.
            $sql_det = "INSERT INTO detalle_producto 
                            (id_producto, descripcion, variedad, origen, presentacion, 
                             unidad_medida, peso_neto, calidad, fecha_cosecha, observaciones)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                            descripcion = VALUES(descripcion),
                            variedad = VALUES(variedad),
                            origen = VALUES(origen),
                            presentacion = VALUES(presentacion),
                            unidad_medida = VALUES(unidad_medida),
                            peso_neto = VALUES(peso_neto),
                            calidad = VALUES(calidad),
                            fecha_cosecha = VALUES(fecha_cosecha),
                            observaciones = VALUES(observaciones)";
            
            $stmt_det = $pdo->prepare($sql_det);
            $stmt_det->execute([
                $id_producto, $descripcion, $variedad, $origen, $presentacion,
                $unidad_medida, $peso_neto, $calidad, $fecha_cosecha, $observaciones
            ]);

            $pdo->commit();
            header('Location: ../index.php?page=inventario&success=2');

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Error al actualizar producto: " . $e->getMessage());
            header('Location: ../index.php?page=inventario&error=2');
        }
   
    // --- ACCIÓN DE BORRADO ---
    } elseif ($action === 'delete' && $id_producto) {
        try {
            // La BD tiene ON DELETE CASCADE para 'detalle_producto',
            // así que solo necesitamos borrar de 'productos'.
            $stmt = $pdo->prepare("DELETE FROM productos WHERE id_producto = ?");
            $stmt->execute([$id_producto]);

            header('Location: ../index.php?page=inventario&success=3');

        } catch (PDOException $e) {
            // Error si el producto está en un 'detalle_de_pedido' (violación de Foreign Key)
            header('Location: ../index.php?page=inventario&error=3');
        }
    }
   
    $pdo = null;
    exit;
}

$pdo = null;
header('Location: ../index.php?page=inventario');
exit;
?>