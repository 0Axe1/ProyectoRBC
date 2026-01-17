<?php
session_start();
header('Content-Type: application/json');

// --- UTILIZAR LA CONEXIÓN CENTRALIZADA ---
// ¡CORREGIDO! Esta es la ruta correcta
require_once __DIR__ . '/../config/db_connection.php';

$pdo = null;
try {
     $pdo = conectarDB();
} catch (\Exception $e) {
    error_log('Error de conexión en clientes_actions: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

// 1. Verificar si ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // No autorizado
    echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado.']);
    exit;
}

// 2. VERIFICACIÓN DE PERMISOS
if (!isset($_SESSION['permisos']) || !in_array('ver_clientes', $_SESSION['permisos'])) {
    http_response_code(403); // Prohibido
    echo json_encode(['status' => 'error', 'message' => 'No tiene permisos para realizar esta acción.']);
    exit;
}

// 3. VALIDACIÓN DE TOKEN CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Error de seguridad (CSRF). Por favor, recargue la página.']);
        exit;
    }
} else {
    // Solo se aceptan peticiones POST para acciones
    http_response_code(405); // Método no permitido
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

// --- LÓGICA PARA CREAR, ACTUALIZAR Y ELIMINAR (vía POST) ---
$action = $_POST['action'] ?? '';

// OBTENER DATOS
$nombre = trim($_POST['nombre_razon_social'] ?? '');
$ubicacion = trim($_POST['ubicacion'] ?? '');
$nit_ruc = trim($_POST['nit_ruc'] ?? '') ?: null; // Guardar null si está vacío
$id_cliente = filter_var($_POST['id_cliente'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);
$id_tipo_contacto = filter_var($_POST['id_tipo_contacto'] ?? null, FILTER_VALIDATE_INT);
$dato_contacto = trim($_POST['dato_contacto'] ?? '') ?: null; // Guardar null si está vacío
$id_terminos_pago_default = 1;

try {
    // --- ¡NUEVO! VALIDACIÓN DE DATOS DEL LADO DEL SERVIDOR ---
    if ($action === 'create' || $action === 'update') {
        if (empty($nombre) || empty($ubicacion)) {
            throw new Exception('El Nombre y la Ubicación son obligatorios.');
        }

        // Validar el tipo de contacto
        if ($id_tipo_contacto && $dato_contacto) {
            // Obtener el nombre del tipo de contacto desde la DB
            $sql_tipo = "SELECT nombre_tipo FROM tipos_contacto WHERE id_tipo_contacto = ?";
            $stmt_tipo = $pdo->prepare($sql_tipo);
            $stmt_tipo->execute([$id_tipo_contacto]);
            $tipo_nombre_row = $stmt_tipo->fetch(PDO::FETCH_ASSOC);

            if ($tipo_nombre_row) {
                $tipo_nombre = strtolower($tipo_nombre_row['nombre_tipo']);
                
                if (strpos($tipo_nombre, 'email') !== false) {
                    if (!filter_var($dato_contacto, FILTER_VALIDATE_EMAIL)) {
                        throw new Exception('El formato del email no es válido.');
                    }
                } elseif (strpos($tipo_nombre, 'telefono') !== false || strpos($tipo_nombre, 'whatsapp') !== false) {
                    // Validar que sean solo números y tengan una longitud entre 7 y 15
                    if (!preg_match('/^[0-9]{7,15}$/', $dato_contacto)) {
                        throw new Exception('El teléfono debe contener solo números (entre 7 y 15 dígitos).');
                    }
                }
            } else {
                 throw new Exception('El tipo de contacto seleccionado no es válido.');
            }
        }
    }
    // --- Fin de la validación del servidor ---


    if ($action === 'create') {
        $pdo->beginTransaction();
        
        $sql_cliente = "INSERT INTO clientes (nombre_razon_social, nit_ruc, ubicacion, id_terminos_pago) VALUES (?, ?, ?, ?)";
        $stmt_cliente = $pdo->prepare($sql_cliente);
        $stmt_cliente->execute([$nombre, $nit_ruc, $ubicacion, $id_terminos_pago_default]);
        $new_cliente_id = $pdo->lastInsertId();

        if ($dato_contacto && $id_tipo_contacto) {
            $sql_contacto = "INSERT INTO contactos_cliente (id_cliente, id_tipo_contacto, dato_contacto) VALUES (?, ?, ?)";
            $stmt_contacto = $pdo->prepare($sql_contacto);
            $stmt_contacto->execute([$new_cliente_id, $id_tipo_contacto, $dato_contacto]);
        }
        
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Cliente agregado exitosamente.']);

    } elseif ($action === 'update' && $id_cliente) {
        $pdo->beginTransaction();
        
        $sql_cliente = "UPDATE clientes SET nombre_razon_social = ?, nit_ruc = ?, ubicacion = ? WHERE id_cliente = ?";
        $stmt_cliente = $pdo->prepare($sql_cliente);
        $stmt_cliente->execute([$nombre, $nit_ruc, $ubicacion, $id_cliente]);
        
        if ($id_tipo_contacto) {
            $sql_check = "SELECT id_contacto FROM contactos_cliente WHERE id_cliente = ? AND id_tipo_contacto = ? LIMIT 1";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$id_cliente, $id_tipo_contacto]);
            $existing_contact = $stmt_check->fetch();
            
            if ($existing_contact) {
                if (!empty($dato_contacto)) {
                    $stmt_contacto = $pdo->prepare("UPDATE contactos_cliente SET dato_contacto = ? WHERE id_contacto = ?");
                    $stmt_contacto->execute([$dato_contacto, $existing_contact['id_contacto']]);
                } else {
                    $stmt_contacto = $pdo->prepare("DELETE FROM contactos_cliente WHERE id_contacto = ?");
                    $stmt_contacto->execute([$existing_contact['id_contacto']]);
                }
            } else if (!empty($dato_contacto)) {
                $stmt_contacto = $pdo->prepare("INSERT INTO contactos_cliente (id_cliente, id_tipo_contacto, dato_contacto) VALUES (?, ?, ?)");
                $stmt_contacto->execute([$id_cliente, $id_tipo_contacto, $dato_contacto]);
            }
        }
        
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Cliente actualizado exitosamente.']);

    } elseif ($action === 'delete' && $id_cliente) {
        
        $sql = "UPDATE clientes SET estado = 0 WHERE id_cliente = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_cliente]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Cliente eliminado exitosamente.']);
        } else {
            throw new Exception('No se pudo encontrar el cliente a eliminar.');
        }

    } else {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida o faltan datos.']);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500); // Internal Server Error
    error_log("Error en clientes_actions: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$pdo = null;
exit;
?>