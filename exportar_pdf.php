<?php
// exportar_pdf.php

// 1. Cargar Entorno y Seguridad
session_start();
require_once __DIR__ . '/config/db_connection.php';
require_once __DIR__ . '/libs/fpdf.php'; // Asegúrate de que la ruta a FPDF sea correcta

// 2. Seguridad: Verificar Sesión de Usuario
if (!isset($_SESSION['user_id'])) {
    die('Acceso no autorizado.');
}

// 3. Seguridad: Verificar Permisos
if (!isset($_SESSION['permisos']) || !in_array('ver_reportes', $_SESSION['permisos'])) {
    die('No tiene permisos para ver reportes.');
}

// 4. Obtener Parámetros (¡Exactamente igual que en reportes.php!)
$pdo = conectarDB();
$tipo = $_GET['tipo'] ?? null;
// ... (código de parámetros sin cambios) ...
$cliente_id = filter_var($_GET['cliente_id'] ?? null, FILTER_VALIDATE_INT);
$producto_id = filter_var($_GET['producto_id'] ?? null, FILTER_VALIDATE_INT);
$search = trim($_GET['search'] ?? '');
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';

$datos = [];
$titulo = "Reporte General";
$params = [];
$columnas = [];
$anchos = [];

// 5. Re-crear la Lógica de Consulta (¡Exactamente igual que en reportes.php!)
try {
    if ($cliente_id) {
// ... (código de consulta cliente_id sin cambios) ...
        $sql = "SELECT p.id_pedido, DATE_FORMAT(p.fecha_cotizacion, '%d/%m/%Y') as fecha_cotizacion, 
                       ep.nombre_estado, v.total_venta, c.nombre_razon_social
                FROM pedidos p
                JOIN estados_pedido ep ON p.id_estado_pedido = ep.id_estado_pedido
                JOIN clientes c ON p.id_cliente = c.id_cliente
                LEFT JOIN ventas v ON p.id_pedido = v.id_pedido
                WHERE p.id_cliente = ?";
        $params[] = $cliente_id;
        if ($fecha_inicio && $fecha_fin) {
            $sql .= " AND p.fecha_cotizacion BETWEEN ? AND ?";
            $params[] = $fecha_inicio;
            $params[] = $fecha_fin;
        }
        $sql .= " ORDER BY p.fecha_cotizacion DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($datos)) $titulo = "Pedidos de: " . $datos[0]['nombre_razon_social'];
        $columnas = ['ID Pedido', 'Fecha', 'Estado', 'Total Venta (Bs)'];
        $anchos = [30, 40, 60, 50]; // Anchos de columna en mm

    } else if ($producto_id) {
// ... (código de consulta producto_id sin cambios) ...
        $sql = "SELECT c.nombre_razon_social, SUM(ddp.cantidad_pedido) as total_cantidad_pedida,
                       COUNT(DISTINCT p.id_pedido) as numero_de_pedidos,
                       MAX(DATE_FORMAT(p.fecha_cotizacion, '%d/%m/%Y')) as ultima_fecha_pedido,
                       pr.nombre_producto
                FROM detalle_de_pedido ddp
                JOIN pedidos p ON ddp.id_pedido = p.id_pedido
                JOIN clientes c ON p.id_cliente = c.id_cliente
                JOIN productos pr ON ddp.id_producto = pr.id_producto
                WHERE ddp.id_producto = ? AND c.estado = 1";
        $params[] = $producto_id;
        if ($fecha_inicio && $fecha_fin) {
            $sql .= " AND p.fecha_cotizacion BETWEEN ? AND ?";
            $params[] = $fecha_inicio;
            $params[] = $fecha_fin;
        }
        $sql .= " GROUP BY c.id_cliente, c.nombre_razon_social ORDER BY total_cantidad_pedida DESC";
                  
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($datos)) $titulo = "Pedidos del Producto: " . $datos[0]['nombre_producto'];
        $columnas = ['Cliente', 'Cant. Pedida', 'Nro. Pedidos', 'Ultimo Pedido'];
        $anchos = [70, 30, 30, 50];

    } else if ($tipo === 'clientes') {
// ... (código de consulta tipo=clientes sin cambios) ...
        $titulo = "Listado de Clientes";
        $sql = "SELECT id_cliente, nombre_razon_social, nit_ruc FROM clientes WHERE estado = 1";
        if ($search) {
            $sql .= " AND (nombre_razon_social LIKE ? OR nit_ruc LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        $sql .= " ORDER BY nombre_razon_social";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columnas = ['ID Cliente', 'Nombre o Razon Social', 'NIT/RUC'];
        $anchos = [25, 100, 55];

    } else if ($tipo === 'productos') {
// ... (código de consulta tipo=productos sin cambios) ...
        $titulo = "Listado de Productos";
        
        $sql = "SELECT id_producto, nombre_producto, stock FROM productos";

        if ($search) {
            $sql .= " AND (nombre_producto LIKE ?)";
            $params[] = "%$search%";
        }
        $sql .= " ORDER BY nombre_producto";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columnas = ['ID Producto', 'Nombre de Producto', 'Stock'];
        $anchos = [30, 100, 50];
    }

} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}

// 6. Definir la Clase del PDF (para Header y Footer)
class PDF extends FPDF
{
    protected $tituloReporte = '';

    function setTitulo($titulo) {
        $this->tituloReporte = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $titulo);
    }

    // Cabecera de página
    function Header()
    {
        $this->SetFont('Arial','B',14);
        $this->Cell(0, 10, $this->tituloReporte, 0, 1, 'C');
        $this->SetFont('Arial','',10);
        $this->Cell(0, 5, 'Generado el: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
        
        $this->Ln(5);
    }

    // Pie de página
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,utf8_decode('Página ') . $this->PageNo() . '/{nb}',0,0,'C');
    }

    // Tabla de datos
    function GenerarTabla($header, $data, $anchos)
    {
        // Cabecera
        for($i=0; $i<count($header); $i++)
            $this->Cell($anchos[$i], 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $header[$i]), 1, 0, 'C', true);
        $this->Ln();

        // Restauración de colores y fuentes
        $this->SetFillColor(255);
        $this->SetTextColor(0);
        $this->SetFont('');

        // Datos
        $fill = false;
        if (empty($data)) {
            $this->Cell(array_sum($anchos), 10, 'No se encontraron datos.', 'LRB', 1, 'C', $fill);
            return;
        }

        foreach($data as $row)
        {
            $i = 0;
            $row = array_values($row);
            
// ... (código de re-mapeo de $rowData sin cambios) ...
            if ($GLOBALS['cliente_id']) {
                 $rowData = [
                    $row[0], // id_pedido
                    $row[1], // fecha_cotizacion
                    $row[2], // nombre_estado
                    $row[3] ? number_format($row[3], 2) : 'N/A' // total_venta
                ];
            } else if ($GLOBALS['producto_id']) {
                 $rowData = [
                    $row[0], // nombre_razon_social
                    $row[1], // total_cantidad_pedida
                    $row[2], // numero_de_pedidos
                    $row[3]  // ultima_fecha_pedido
                ];
            } else if ($GLOBALS['tipo'] === 'clientes') {
                 $rowData = [
                    $row[0], // id_cliente
                    $row[1], // nombre_razon_social
                    $row[2]  // nit_ruc
                ];
            } else if ($GLOBALS['tipo'] === 'productos') {
                 $rowData = [
                    $row[0], // id_producto
                    $row[1], // nombre_producto
                    $row[2]  // stock (antes cantidad_stock)
                ];
            } else {
                $rowData = []; // Caso de error
            }

            foreach($rowData as $col) {
                $this->Cell($anchos[$i], 6, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $col), 'LR', 0, 'L', $fill);
                $i++;
            }
            $this->Ln();
            $fill = !$fill;
        }
        // Línea de cierre
        $this->Cell(array_sum($anchos), 0, '', 'T');
    }
}

// 7. Generar el PDF
$pdf = new PDF('P', 'mm', 'A4'); // P = Portrait (Vertical)
// ... (código final sin cambios) ...
$pdf->setTitulo($titulo);
$pdf->AliasNbPages(); // Para el número total de páginas
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);
$pdf->GenerarTabla($columnas, $datos, $anchos);
$pdf->Output('I', 'reporte.pdf'); // I = Enviar al navegador

$pdo = null;
?>