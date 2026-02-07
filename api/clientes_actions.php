<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_connection.php';

// Helper function for contact update logic
function handleContactUpdate($pdo, $id_cliente, $id_tipo_contacto, $new_dato_contacto) {
    $sql_check_contact = "SELECT id_contacto FROM contactos_cliente WHERE id_cliente = ? AND id_tipo_contacto = ? LIMIT 1";
    $stmt_check_contact = $pdo->prepare($sql_check_contact);
    $stmt_check_contact->execute([$id_cliente, $id_tipo_contacto]);
    $existing_contact = $stmt_check_contact->fetch();
    
    if ($existing_contact) {
        if (!empty($new_dato_contacto)) {
            $stmt_contacto = $pdo->prepare("UPDATE contactos_cliente SET dato_contacto = ? WHERE id_contacto = ?");
            $stmt_contacto->execute([$new_dato_contacto, $existing_contact['id_contacto']]);
        } else {
            $stmt_contacto = $pdo->prepare("DELETE FROM contactos_cliente WHERE id_contacto = ?");
            $stmt_contacto->execute([$existing_contact['id_contacto']]);
        }
    } else if (!empty($new_dato_contacto)) {
        $stmt_contacto = $pdo->prepare("INSERT INTO contactos_cliente (id_cliente, id_tipo_contacto, dato_contacto) VALUES (?, ?, ?)");
        $stmt_contacto->execute([$id_cliente, $id_tipo_contacto, $new_dato_contacto]);
    }
}

$pdo = null;
try {
     $pdo = conectarDB();
} catch (\Exception $e) {
    error_log('Error de conexión en clientes_actions: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado.']);
    exit;
}

if (!isset($_SESSION['permisos']) || !in_array('ver_clientes', $_SESSION['permisos'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No tiene permisos para realizar esta acción.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Error de seguridad (CSRF). Por favor, recargue la página.']);
        exit;
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

$action = $_POST['action'] ?? '';
$nombre = trim($_POST['nombre_razon_social'] ?? '');
$ubicacion = trim($_POST['ubicacion'] ?? '');
$nit_ruc = trim($_POST['nit_ruc'] ?? '') ?: null;
$id_cliente = filter_var($_POST['id_cliente'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);
$telefono_contacto = trim($_POST['telefono_contacto'] ?? '') ?: null;
$email_contacto = trim($_POST['email_contacto'] ?? '') ?: null;
$id_terminos_pago_default = 1;

try {
    if ($action === 'create' || $action === 'update') {
        if (empty($nombre) || empty($ubicacion)) {
            throw new Exception('El Nombre y la Ubicación son obligatorios.');
        }
        if (!empty($telefono_contacto) && !preg_match('/^[0-9]{7,15}$/', $telefono_contacto)) {
            throw new Exception('El teléfono debe contener solo números (entre 7 y 15 dígitos).');
        }
        if (!empty($email_contacto) && !filter_var($email_contacto, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('El formato del email no es válido.');
        }
    }

    if ($action === 'create') {
        $sql_check = "SELECT id_cliente FROM clientes WHERE (nombre_razon_social = ? OR (nit_ruc IS NOT NULL AND nit_ruc = ?)) AND estado = 1";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$nombre, $nit_ruc]);
        if ($stmt_check->fetch()) {
            throw new Exception('Ya existe un cliente con el mismo Nombre o NIT/RUC.');
        }

        $pdo->beginTransaction();
        
        $sql_cliente = "INSERT INTO clientes (nombre_razon_social, nit_ruc, ubicacion, id_terminos_pago) VALUES (?, ?, ?, ?)";
        $stmt_cliente = $pdo->prepare($sql_cliente);
        $stmt_cliente->execute([$nombre, $nit_ruc, $ubicacion, $id_terminos_pago_default]);
        $new_cliente_id = $pdo->lastInsertId();

        if (!empty($telefono_contacto)) {
            $stmt_tipo_tel = $pdo->query("SELECT id_tipo_contacto FROM tipos_contacto WHERE nombre_tipo = 'Telefono'")->fetch(PDO::FETCH_ASSOC);
            if ($stmt_tipo_tel) {
                handleContactUpdate($pdo, $new_cliente_id, $stmt_tipo_tel['id_tipo_contacto'], $telefono_contacto);
            } else {
                 throw new Exception('Tipo de contacto "Teléfono" no encontrado en la base de datos.');
            }
        }

        if (!empty($email_contacto)) {
            $stmt_tipo_email = $pdo->query("SELECT id_tipo_contacto FROM tipos_contacto WHERE nombre_tipo = 'Email'")->fetch(PDO::FETCH_ASSOC);
             if ($stmt_tipo_email) {
                handleContactUpdate($pdo, $new_cliente_id, $stmt_tipo_email['id_tipo_contacto'], $email_contacto);
            } else {
                 throw new Exception('Tipo de contacto "Email" no encontrado en la base de datos.');
            }
        }
        
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Cliente agregado exitosamente.']);

    } elseif ($action === 'update' && $id_cliente) {
        $sql_check = "SELECT id_cliente FROM clientes WHERE (nombre_razon_social = ? OR (nit_ruc IS NOT NULL AND nit_ruc = ?)) AND id_cliente != ? AND estado = 1";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$nombre, $nit_ruc, $id_cliente]);
        if ($stmt_check->fetch()) {
            throw new Exception('Ya existe otro cliente con el mismo Nombre o NIT/RUC.');
        }
        
        $pdo->beginTransaction();
        
        $sql_cliente = "UPDATE clientes SET nombre_razon_social = ?, nit_ruc = ?, ubicacion = ? WHERE id_cliente = ?";
        $stmt_cliente = $pdo->prepare($sql_cliente);
        $stmt_cliente->execute([$nombre, $nit_ruc, $ubicacion, $id_cliente]);
        
        $id_tipo_telefono = $pdo->query("SELECT id_tipo_contacto FROM tipos_contacto WHERE nombre_tipo = 'Telefono'")->fetchColumn();
        if (!$id_tipo_telefono) throw new Exception('Tipo de contacto "Teléfono" no encontrado.');
        handleContactUpdate($pdo, $id_cliente, $id_tipo_telefono, $telefono_contacto);

        $id_tipo_email = $pdo->query("SELECT id_tipo_contacto FROM tipos_contacto WHERE nombre_tipo = 'Email'")->fetchColumn();
        if (!$id_tipo_email) throw new Exception('Tipo de contacto "Email" no encontrado.');
        handleContactUpdate($pdo, $id_cliente, $id_tipo_email, $email_contacto);
        
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
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida o faltan datos.']);
    }

} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log("Error en clientes_actions: " . $e->getMessage());
    $message = (strpos($e->getMessage(), 'SQLSTATE[23000]') !== false) 
        ? 'Error de duplicado. Verifique los datos.' 
        : 'Error en la base de datos.';
    echo json_encode(['status' => 'error', 'message' => $message]);
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$pdo = null;
exit;
?>