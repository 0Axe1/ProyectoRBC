-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 10-11-2025 a las 02:58:36
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `db_rbc_3nf`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias_producto`
--

CREATE TABLE `categorias_producto` (
  `id_categoria` int(11) NOT NULL,
  `nombre_categoria` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias_producto`
--

INSERT INTO `categorias_producto` (`id_categoria`, `nombre_categoria`, `descripcion`) VALUES
(1, 'Cereales', 'Granos secos como maíz, trigo, etc.'),
(2, 'Legumbres', 'Como garbanzos, lentejas, etc.'),
(3, 'Procesados', 'Productos que han pasado por algún proceso industrial.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `nombre_razon_social` varchar(255) NOT NULL,
  `nit_ruc` varchar(50) DEFAULT NULL,
  `ubicacion` text NOT NULL COMMENT 'Datos de zona, direccion',
  `id_terminos_pago` int(11) NOT NULL COMMENT 'Condiciones de pago o credito',
  `linea_credito` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id_cliente`, `nombre_razon_social`, `nit_ruc`, `ubicacion`, `id_terminos_pago`, `linea_credito`, `estado`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(3, 'reychel', '123123', 'mi casa', 1, 0.00, 1, '2025-10-26 22:32:16', '2025-10-26 22:32:16'),
(4, 'p11', 'P1231231', 'mi casa1', 1, 0.00, 0, '2025-10-30 10:12:37', '2025-11-02 19:02:53'),
(6, 'P2', 'P222321123', 'casas', 1, 0.00, 0, '2025-10-30 10:14:57', '2025-10-30 10:17:23'),
(7, 'P22', 'P21112', 'casa2', 1, 0.00, 0, '2025-10-30 14:37:48', '2025-10-30 14:38:16'),
(8, 'P31', 'P311121', 'casa231', 1, 0.00, 0, '2025-10-30 16:26:23', '2025-10-30 16:26:46'),
(9, 'P41', 'P4111211', 'casa41', 1, 0.00, 0, '2025-10-30 16:32:23', '2025-10-30 16:32:41'),
(10, 'P51', 'P51112111', 'casa51', 1, 0.00, 0, '2025-10-30 17:23:57', '2025-10-30 17:24:15'),
(11, 'cliente 21', '1231231231', 'calle Juan Jose Perez1', 1, 0.00, 1, '2025-11-02 19:02:27', '2025-11-02 19:02:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contactos_cliente`
--

CREATE TABLE `contactos_cliente` (
  `id_contacto` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_tipo_contacto` int(11) NOT NULL,
  `dato_contacto` varchar(255) NOT NULL COMMENT 'El numero de WhatsApp, email, etc.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `contactos_cliente`
--

INSERT INTO `contactos_cliente` (`id_contacto`, `id_cliente`, `id_tipo_contacto`, `dato_contacto`) VALUES
(1, 3, 1, '123123'),
(2, 4, 3, 'P11@mail.com'),
(3, 6, 1, '123'),
(4, 7, 1, '12312311232'),
(5, 8, 1, '1231231131'),
(6, 9, 1, '123123141'),
(7, 10, 1, '123123451'),
(8, 11, 1, '123234541');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_de_pedido`
--

CREATE TABLE `detalle_de_pedido` (
  `id_detalle_pedido` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad_pedido` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL COMMENT 'Precio por unidad',
  `precio_total_cotizado` decimal(10,2) NOT NULL COMMENT 'Precio total cotizado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `detalle_de_pedido`
--

INSERT INTO `detalle_de_pedido` (`id_detalle_pedido`, `id_pedido`, `id_producto`, `cantidad_pedido`, `precio_unitario`, `precio_total_cotizado`) VALUES
(1, 1, 1, 500, 123.00, 61500.00),
(2, 2, 1, 99, 132.00, 13068.00),
(3, 3, 1, 99, 123.00, 12177.00),
(4, 4, 2, 123, 123.00, 15129.00),
(5, 4, 1, 123, 123.00, 15129.00),
(6, 5, 1, 123, 123.00, 15129.00),
(7, 6, 3, 123, 123.00, 15129.00),
(8, 6, 1, 123, 123.00, 15129.00),
(9, 7, 2, 45, 123.00, 5535.00),
(10, 8, 3, 50, 200.00, 10000.00),
(11, 8, 1, 190, 120.00, 22800.00),
(12, 9, 2, 10, 120.00, 1200.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_producto`
--

CREATE TABLE `detalle_producto` (
  `id_detalle` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `variedad` varchar(100) DEFAULT NULL,
  `origen` varchar(150) DEFAULT NULL,
  `presentacion` varchar(100) DEFAULT NULL,
  `unidad_medida` varchar(20) DEFAULT NULL,
  `peso_neto` decimal(10,2) DEFAULT NULL,
  `calidad` varchar(50) DEFAULT NULL,
  `fecha_cosecha` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `detalle_producto`
--

INSERT INTO `detalle_producto` (`id_detalle`, `id_producto`, `descripcion`, `variedad`, `origen`, `presentacion`, `unidad_medida`, `peso_neto`, `calidad`, `fecha_cosecha`, `observaciones`) VALUES
(1, 1, 'Maíz para pipoca (pipoquín) de alta expansión.', 'Maíz Pipoca (Butterfly)', 'Bolivia - Valles Cruceños', 'Saco 50 kg', 'kg', 50.00, 'Grado A', '2025-03-10', 'Certificación orgánica pendiente'),
(2, 2, 'Soya amarilla para procesamiento industrial.', 'Soya Amarilla', 'Argentina', 'Granel', 'kg', 1000.00, 'Exportación', '2025-04-15', 'Lote #S1A-45. Libre de OGM.'),
(3, 3, 'Frijol negro de primera calidad.', 'Frijol Negro', 'Bolivia - Yungas', 'Bolsa 1 kg', 'kg', 1.00, 'Grado A', '2025-05-20', NULL),
(4, 4, 'Garbanzo calibre 8, ideal para envasado.', 'Garbanzo Calibre 8', 'Perú', 'Saco 25 kg', 'kg', 25.00, 'Industrial', '2025-02-01', 'Control de calidad aprobado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_venta`
--

CREATE TABLE `detalle_venta` (
  `id_detalle_venta` int(11) NOT NULL,
  `id_venta` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario_venta` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `detalle_venta`
--

INSERT INTO `detalle_venta` (`id_detalle_venta`, `id_venta`, `id_producto`, `cantidad`, `precio_unitario_venta`, `subtotal`) VALUES
(1, 1, 1, 500, 123.00, 61500.00),
(2, 2, 1, 99, 132.00, 13068.00),
(3, 3, 2, 123, 123.00, 15129.00),
(4, 3, 1, 123, 123.00, 15129.00),
(5, 4, 3, 123, 123.00, 15129.00),
(6, 4, 1, 123, 123.00, 15129.00),
(7, 5, 3, 50, 200.00, 10000.00),
(8, 5, 1, 190, 120.00, 22800.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estados_pago`
--

CREATE TABLE `estados_pago` (
  `id_estado_pago` int(11) NOT NULL,
  `nombre_estado` varchar(50) NOT NULL COMMENT 'Ej: Pendiente de Pago, Pagado, Anulado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estados_pago`
--

INSERT INTO `estados_pago` (`id_estado_pago`, `nombre_estado`) VALUES
(3, 'Anulado'),
(2, 'Pagado'),
(1, 'Pendiente de Pago');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estados_pedido`
--

CREATE TABLE `estados_pedido` (
  `id_estado_pedido` int(11) NOT NULL,
  `nombre_estado` varchar(50) NOT NULL COMMENT 'Ej: Cotizado, Confirmado, En Preparacion, Entregado, Cancelado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estados_pedido`
--

INSERT INTO `estados_pedido` (`id_estado_pedido`, `nombre_estado`) VALUES
(3, 'Cancelado'),
(4, 'En Preparacion'),
(2, 'Entregado'),
(1, 'Pendiente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metodos_pago`
--

CREATE TABLE `metodos_pago` (
  `id_metodo_pago` int(11) NOT NULL,
  `nombre_metodo` varchar(50) NOT NULL COMMENT 'Ej: Efectivo, Transferencia, QR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `metodos_pago`
--

INSERT INTO `metodos_pago` (`id_metodo_pago`, `nombre_metodo`) VALUES
(1, 'Efectivo'),
(3, 'QR'),
(2, 'Transferencia');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id_pedido` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_estado_pedido` int(11) NOT NULL COMMENT 'Para seguimiento en tiempo real ',
  `fecha_cotizacion` date NOT NULL COMMENT 'Fecha de la cotizacion',
  `direccion_entrega` text DEFAULT NULL COMMENT 'Direccion de entrega',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '--- MEJORA -- Se anadio ON UPDATE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id_pedido`, `id_cliente`, `id_estado_pedido`, `fecha_cotizacion`, `direccion_entrega`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 3, 2, '2025-10-27', 'pipo', '2025-10-26 23:00:04', '2025-10-26 23:00:10'),
(2, 4, 2, '2025-10-30', 'pipo', '2025-10-30 10:16:48', '2025-10-30 10:16:53'),
(3, 4, 3, '2025-10-30', 'qwe', '2025-10-30 10:17:47', '2025-10-30 10:17:51'),
(4, 4, 2, '2025-10-30', 'pipo', '2025-10-30 14:39:18', '2025-10-30 14:39:25'),
(5, 3, 3, '2025-10-30', '123', '2025-10-30 14:39:43', '2025-10-30 14:39:46'),
(6, 4, 2, '2025-10-30', 'pipo', '2025-10-30 17:24:53', '2025-10-30 17:25:12'),
(7, 4, 3, '2025-10-30', 'pipo', '2025-10-30 17:25:29', '2025-10-30 17:25:31'),
(8, 11, 2, '2025-11-02', 'calle Juan jose perez', '2025-11-02 19:04:01', '2025-11-02 19:04:06'),
(9, 11, 3, '2025-11-02', 'calle Juan jose perez', '2025-11-02 19:04:21', '2025-11-02 19:04:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos`
--

CREATE TABLE `permisos` (
  `id_permiso` int(11) NOT NULL,
  `nombre_permiso` varchar(100) NOT NULL COMMENT 'Ej: crear_usuario, ver_reportes_ventas',
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `permisos`
--

INSERT INTO `permisos` (`id_permiso`, `nombre_permiso`, `descripcion`) VALUES
(1, 'ver_dashboard', 'Acceso al Dashboard'),
(2, 'ver_clientes', 'Acceso al módulo de Clientes'),
(3, 'ver_pedidos', 'Acceso al módulo de Pedidos'),
(4, 'ver_inventario', 'Acceso al módulo de Inventario'),
(5, 'ver_reportes', 'Acceso al módulo de Reportes'),
(6, 'ver_usuarios', 'Acceso al módulo de Gestión de Usuarios');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id_producto` int(11) NOT NULL,
  `nombre_producto` varchar(150) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `activo` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Si el producto está disponible (1) o no (0)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id_producto`, `nombre_producto`, `id_categoria`, `precio`, `stock`, `fecha_creacion`, `fecha_actualizacion`, `activo`) VALUES
(1, 'MAIZ PIPOCA', 1, 123.00, 965, '2025-10-26 22:59:38', '2025-11-02 19:04:01', 1),
(2, 'soya', 2, 120.00, 77, '2025-10-30 10:18:34', '2025-11-02 19:04:24', 1),
(3, 'frijol1', 2, 121.00, 27, '2025-10-30 14:40:23', '2025-11-02 19:04:01', 1),
(4, 'garbanzo', 1, 120.00, 200, '2025-11-02 19:05:06', '2025-11-02 19:05:06', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre_rol` varchar(50) NOT NULL COMMENT 'Ej: Administrador, Empleado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id_rol`, `nombre_rol`) VALUES
(1, 'Administrador'),
(2, 'Empleado'),
(3, 'Vendedor');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles_permisos`
--

CREATE TABLE `roles_permisos` (
  `id_rol` int(11) NOT NULL,
  `id_permiso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles_permisos`
--

INSERT INTO `roles_permisos` (`id_rol`, `id_permiso`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(2, 1),
(2, 2),
(2, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `terminos_pago`
--

CREATE TABLE `terminos_pago` (
  `id_terminos_pago` int(11) NOT NULL,
  `nombre_terminos` varchar(50) NOT NULL COMMENT 'Ej: Contado, Credito 15 dias',
  `dias_credito` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `terminos_pago`
--

INSERT INTO `terminos_pago` (`id_terminos_pago`, `nombre_terminos`, `dias_credito`) VALUES
(1, 'Contado', 0),
(2, 'Credito 15 dias', 15),
(3, 'Credito 30 dias', 30);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipos_contacto`
--

CREATE TABLE `tipos_contacto` (
  `id_tipo_contacto` int(11) NOT NULL,
  `nombre_tipo` varchar(50) NOT NULL COMMENT 'Ej: Telefono, WhatsApp, Email'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tipos_contacto`
--

INSERT INTO `tipos_contacto` (`id_tipo_contacto`, `nombre_tipo`) VALUES
(3, 'Email'),
(1, 'Telefono'),
(2, 'WhatsApp');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `nombre_usuario` varchar(100) NOT NULL,
  `contrasena` varchar(255) NOT NULL COMMENT 'ALMACENAR SIEMPRE COMO HASH (ej: bcrypt)',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `id_rol`, `nombre_usuario`, `contrasena`, `fecha_creacion`) VALUES
(8, 2, 'axel', '$2y$10$1uHvTEc.m0iF6Sdo900XXuRmGz5Tw1yHuPmWNyN16v6TjuWNJkwM.', '2025-10-26 23:15:54'),
(10, 1, 'admin', '$2y$10$6OXh37sQ6NqURkCyFw8BJu27phVgjRo.bYje6DSD3L2tLmhmIuFjq', '2025-10-26 23:54:49'),
(11, 1, 'superadmin', '$2y$10$Q48tRRYiUhmHcu9zuqK7Lerre1Bnj7zQWXkuf.dYsOro9fY42XmGi', '2025-10-27 00:09:23'),
(12, 1, 'a123', '$2y$10$LKffWuW4rJayci7COYoef.VEp5ZsxAqk2xGPk4Koeu5DwAUeS8tOe', '2025-10-30 14:59:45'),
(14, 3, 'A1', '$2y$10$5GrkWcS0QRLWkoorKhKeteURHNwd/l/p.DtTABSPRKB.g825yO2Mu', '2025-10-30 18:53:26'),
(15, 3, 'A2', '$2y$10$goe1hstNh8HxiKzXa5cuZOeLC5VxMY7uHmWlZHcRrNd9un174CV52', '2025-10-30 18:54:25'),
(16, 2, 'user', '$2y$10$Vwov3KK6JKVKHe9xy.wKledTful.wbrP9L9GcLm/EcKuQvXMWa66K', '2025-11-02 18:59:49'),
(17, 2, 'usuario2', '$2y$10$ONmMnFKxzPIsDrxSnmNieeyEa28ypZc4X8ng.aThpqL2HDczZXpqe', '2025-11-02 19:06:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

CREATE TABLE `ventas` (
  `id_venta` int(11) NOT NULL,
  `id_pedido` int(11) DEFAULT NULL COMMENT 'FK a pedidos, UNIQUE para asegurar 1 Venta por Pedido',
  `id_metodo_pago` int(11) DEFAULT NULL,
  `id_estado_pago` int(11) NOT NULL,
  `fecha_venta` datetime NOT NULL DEFAULT current_timestamp(),
  `total_venta` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ventas`
--

INSERT INTO `ventas` (`id_venta`, `id_pedido`, `id_metodo_pago`, `id_estado_pago`, `fecha_venta`, `total_venta`) VALUES
(1, 1, 1, 2, '2025-10-26 23:00:10', 61500.00),
(2, 2, 1, 2, '2025-10-30 10:16:53', 13068.00),
(3, 4, 1, 2, '2025-10-30 14:39:25', 30258.00),
(4, 6, 1, 2, '2025-10-30 17:25:12', 30258.00),
(5, 8, 1, 2, '2025-11-02 19:04:06', 32800.00);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias_producto`
--
ALTER TABLE `categorias_producto`
  ADD PRIMARY KEY (`id_categoria`),
  ADD UNIQUE KEY `nombre_categoria` (`nombre_categoria`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `nit_ruc` (`nit_ruc`),
  ADD KEY `fk_clientes_terminos_pago` (`id_terminos_pago`),
  ADD KEY `idx_nombre_razon_social` (`nombre_razon_social`);

--
-- Indices de la tabla `contactos_cliente`
--
ALTER TABLE `contactos_cliente`
  ADD PRIMARY KEY (`id_contacto`),
  ADD KEY `fk_contactos_cliente` (`id_cliente`),
  ADD KEY `fk_contactos_tipo` (`id_tipo_contacto`);

--
-- Indices de la tabla `detalle_de_pedido`
--
ALTER TABLE `detalle_de_pedido`
  ADD PRIMARY KEY (`id_detalle_pedido`),
  ADD KEY `fk_detallepedido_pedido` (`id_pedido`),
  ADD KEY `fk_detallepedido_producto` (`id_producto`);

--
-- Indices de la tabla `detalle_producto`
--
ALTER TABLE `detalle_producto`
  ADD PRIMARY KEY (`id_detalle`),
  ADD UNIQUE KEY `id_producto` (`id_producto`) COMMENT 'Asegura un solo detalle por producto';

--
-- Indices de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD PRIMARY KEY (`id_detalle_venta`),
  ADD KEY `fk_detalleventa_venta` (`id_venta`),
  ADD KEY `fk_detalleventa_producto` (`id_producto`);

--
-- Indices de la tabla `estados_pago`
--
ALTER TABLE `estados_pago`
  ADD PRIMARY KEY (`id_estado_pago`),
  ADD UNIQUE KEY `nombre_estado` (`nombre_estado`);

--
-- Indices de la tabla `estados_pedido`
--
ALTER TABLE `estados_pedido`
  ADD PRIMARY KEY (`id_estado_pedido`),
  ADD UNIQUE KEY `nombre_estado` (`nombre_estado`);

--
-- Indices de la tabla `metodos_pago`
--
ALTER TABLE `metodos_pago`
  ADD PRIMARY KEY (`id_metodo_pago`),
  ADD UNIQUE KEY `nombre_metodo` (`nombre_metodo`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id_pedido`),
  ADD KEY `fk_pedidos_cliente` (`id_cliente`),
  ADD KEY `fk_pedidos_estado` (`id_estado_pedido`);

--
-- Indices de la tabla `permisos`
--
ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id_permiso`),
  ADD UNIQUE KEY `nombre_permiso` (`nombre_permiso`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id_producto`),
  ADD KEY `fk_productos_categoria` (`id_categoria`),
  ADD KEY `idx_nombre_producto` (`nombre_producto`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `nombre_rol` (`nombre_rol`);

--
-- Indices de la tabla `roles_permisos`
--
ALTER TABLE `roles_permisos`
  ADD PRIMARY KEY (`id_rol`,`id_permiso`),
  ADD KEY `fk_rolespermisos_permiso` (`id_permiso`);

--
-- Indices de la tabla `terminos_pago`
--
ALTER TABLE `terminos_pago`
  ADD PRIMARY KEY (`id_terminos_pago`),
  ADD UNIQUE KEY `nombre_terminos` (`nombre_terminos`);

--
-- Indices de la tabla `tipos_contacto`
--
ALTER TABLE `tipos_contacto`
  ADD PRIMARY KEY (`id_tipo_contacto`),
  ADD UNIQUE KEY `nombre_tipo` (`nombre_tipo`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  ADD KEY `fk_usuarios_rol` (`id_rol`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id_venta`),
  ADD UNIQUE KEY `id_pedido` (`id_pedido`),
  ADD KEY `fk_ventas_metodo_pago` (`id_metodo_pago`),
  ADD KEY `fk_ventas_estado_pago` (`id_estado_pago`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias_producto`
--
ALTER TABLE `categorias_producto`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `contactos_cliente`
--
ALTER TABLE `contactos_cliente`
  MODIFY `id_contacto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `detalle_de_pedido`
--
ALTER TABLE `detalle_de_pedido`
  MODIFY `id_detalle_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `detalle_producto`
--
ALTER TABLE `detalle_producto`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `id_detalle_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `estados_pago`
--
ALTER TABLE `estados_pago`
  MODIFY `id_estado_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `estados_pedido`
--
ALTER TABLE `estados_pedido`
  MODIFY `id_estado_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `metodos_pago`
--
ALTER TABLE `metodos_pago`
  MODIFY `id_metodo_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `permisos`
--
ALTER TABLE `permisos`
  MODIFY `id_permiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `terminos_pago`
--
ALTER TABLE `terminos_pago`
  MODIFY `id_terminos_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tipos_contacto`
--
ALTER TABLE `tipos_contacto`
  MODIFY `id_tipo_contacto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `id_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `fk_clientes_terminos_pago` FOREIGN KEY (`id_terminos_pago`) REFERENCES `terminos_pago` (`id_terminos_pago`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `contactos_cliente`
--
ALTER TABLE `contactos_cliente`
  ADD CONSTRAINT `fk_contactos_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_contactos_tipo` FOREIGN KEY (`id_tipo_contacto`) REFERENCES `tipos_contacto` (`id_tipo_contacto`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_de_pedido`
--
ALTER TABLE `detalle_de_pedido`
  ADD CONSTRAINT `fk_detallepedido_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detallepedido_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_producto`
--
ALTER TABLE `detalle_producto`
  ADD CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD CONSTRAINT `fk_detalleventa_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalleventa_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedidos_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedidos_estado` FOREIGN KEY (`id_estado_pedido`) REFERENCES `estados_pedido` (`id_estado_pedido`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_productos_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias_producto` (`id_categoria`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `roles_permisos`
--
ALTER TABLE `roles_permisos`
  ADD CONSTRAINT `fk_rolespermisos_permiso` FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id_permiso`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rolespermisos_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `fk_ventas_estado_pago` FOREIGN KEY (`id_estado_pago`) REFERENCES `estados_pago` (`id_estado_pago`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ventas_metodo_pago` FOREIGN KEY (`id_metodo_pago`) REFERENCES `metodos_pago` (`id_metodo_pago`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ventas_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
