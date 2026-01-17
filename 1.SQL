CREATE DATABASE RBC_BD;

USE RBC_BD;

CREATE TABLE `categorias_producto` (
  `id_categoria` int(11) NOT NULL,
  `nombre_categoria` varchar(100) NOT NULL COMMENT 'Ej: Cereal, Grano, etc.',
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `clientes` (
  `id_cliente` int(11) NOT NULL,
  `nombre_razon_social` varchar(255) NOT NULL,
  `nit_ruc` varchar(50) DEFAULT NULL,
  `ubicacion` varchar(255) DEFAULT NULL,
  `id_terminos_pago` int(11) NOT NULL,
  `linea_credito` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `contactos_cliente` (
  `id_contacto` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_tipo_contacto` int(11) NOT NULL,
  `dato_contacto` varchar(255) NOT NULL COMMENT 'El numero de WhatsApp, email, etc. [cite: 111]'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `detalle_de_pedido` (
  `id_detalle_pedido` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad_pedido` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL COMMENT 'Precio por unidad [cite: 124]',
  `precio_total_cotizado` decimal(10,2) NOT NULL COMMENT 'Precio total cotizado [cite: 124]'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `detalle_venta` (
  `id_detalle_venta` int(11) NOT NULL,
  `id_venta` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario_venta` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `estados_pago` (
  `id_estado_pago` int(11) NOT NULL,
  `nombre_estado` varchar(50) NOT NULL COMMENT 'Ej: Pendiente de Pago, Pagado, Anulado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `estados_pedido` (
  `id_estado_pedido` int(11) NOT NULL,
  `nombre_estado` varchar(50) NOT NULL COMMENT 'Ej: Cotizado, Confirmado, En Preparacion, Entregado, Cancelado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `metodos_pago` (
  `id_metodo_pago` int(11) NOT NULL,
  `nombre_metodo` varchar(50) NOT NULL COMMENT 'Ej: Efectivo, Transferencia, QR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pedidos` (
  `id_pedido` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_estado_pedido` int(11) NOT NULL COMMENT 'Para seguimiento en tiempo real ',
  `fecha_cotizacion` date NOT NULL COMMENT 'Fecha de la cotizacion [cite: 124]',
  `direccion_entrega` text DEFAULT NULL COMMENT 'Direccion de entrega [cite: 118]',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `permisos` (
  `id_permiso` int(11) NOT NULL,
  `nombre_permiso` varchar(100) NOT NULL COMMENT 'Ej: crear_usuario, ver_reportes_ventas',
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `productos` (
  `id_producto` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `nombre_producto` varchar(150) NOT NULL COMMENT 'Ej: Maiz Pipoca, Garbanzo, Soya [cite: 43]',
  `descripcion_producto` text DEFAULT NULL,
  `precio_venta_sugerido` decimal(10,2) NOT NULL DEFAULT 0.00,
  `peso_kg` decimal(10,2) DEFAULT NULL COMMENT 'Peso del producto ',
  `cantidad_stock` int(11) NOT NULL DEFAULT 0 COMMENT 'Stock en el almacen unico [cite: 135, 158]',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `nombre_rol` varchar(50) NOT NULL COMMENT 'Ej: Administrador, Empleado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles_permisos` (
  `id_rol` int(11) NOT NULL,
  `id_permiso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `terminos_pago` (
  `id_terminos_pago` int(11) NOT NULL,
  `nombre_terminos` varchar(50) NOT NULL COMMENT 'Ej: Contado, Credito 15 dias',
  `dias_credito` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tipos_contacto` (
  `id_tipo_contacto` int(11) NOT NULL,
  `nombre_tipo` varchar(50) NOT NULL COMMENT 'Ej: Telefono, WhatsApp, Email'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `nombre_usuario` varchar(100) NOT NULL,
  `contrasena` varchar(255) NOT NULL COMMENT 'ALMACENAR SIEMPRE COMO HASH (ej: bcrypt)',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ventas` (
  `id_venta` int(11) NOT NULL,
  `id_pedido` int(11) DEFAULT NULL COMMENT 'FK a pedidos, UNIQUE para asegurar 1 Venta por Pedido',
  `id_metodo_pago` int(11) DEFAULT NULL,
  `id_estado_pago` int(11) NOT NULL,
  `fecha_venta` datetime NOT NULL DEFAULT current_timestamp(),
  `total_venta` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

4. Relaciones de las Tablas de la Base de Datos.  

ALTER TABLE `categorias_producto`
  ADD PRIMARY KEY (`id_categoria`),
  ADD UNIQUE KEY `nombre_categoria` (`nombre_categoria`);

ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `nit_ruc` (`nit_ruc`),
  ADD KEY `fk_clientes_terminos_pago` (`id_terminos_pago`);

ALTER TABLE `contactos_cliente`
  ADD PRIMARY KEY (`id_contacto`),
  ADD KEY `fk_contactos_cliente` (`id_cliente`),
  ADD KEY `fk_contactos_tipo` (`id_tipo_contacto`);

ALTER TABLE `detalle_de_pedido`
  ADD PRIMARY KEY (`id_detalle_pedido`),
  ADD KEY `fk_detallepedido_pedido` (`id_pedido`),
  ADD KEY `fk_detallepedido_producto` (`id_producto`);

ALTER TABLE `detalle_venta`
  ADD PRIMARY KEY (`id_detalle_venta`),
  ADD KEY `fk_detalleventa_venta` (`id_venta`),
  ADD KEY `fk_detalleventa_producto` (`id_producto`);

ALTER TABLE `estados_pago`
  ADD PRIMARY KEY (`id_estado_pago`),
  ADD UNIQUE KEY `nombre_estado` (`nombre_estado`);

ALTER TABLE `estados_pedido`
  ADD PRIMARY KEY (`id_estado_pedido`),
  ADD UNIQUE KEY `nombre_estado` (`nombre_estado`);

ALTER TABLE `metodos_pago`
  ADD PRIMARY KEY (`id_metodo_pago`),
  ADD UNIQUE KEY `nombre_metodo` (`nombre_metodo`);

ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id_pedido`),
  ADD KEY `fk_pedidos_cliente` (`id_cliente`),
  ADD KEY `fk_pedidos_estado` (`id_estado_pedido`);

ALTER TABLE `permisos`
  ADD PRIMARY KEY (`id_permiso`),
  ADD UNIQUE KEY `nombre_permiso` (`nombre_permiso`);

ALTER TABLE `productos`
  ADD PRIMARY KEY (`id_producto`),
  ADD UNIQUE KEY `nombre_producto` (`nombre_producto`),
  ADD KEY `fk_productos_categoria` (`id_categoria`);

ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `nombre_rol` (`nombre_rol`);

ALTER TABLE `roles_permisos`
  ADD PRIMARY KEY (`id_rol`,`id_permiso`),
  ADD KEY `fk_rolespermisos_permiso` (`id_permiso`);

ALTER TABLE `terminos_pago`
  ADD PRIMARY KEY (`id_terminos_pago`),
  ADD UNIQUE KEY `nombre_terminos` (`nombre_terminos`);

ALTER TABLE `tipos_contacto`
  ADD PRIMARY KEY (`id_tipo_contacto`),
  ADD UNIQUE KEY `nombre_tipo` (`nombre_tipo`);

ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  ADD KEY `fk_usuarios_rol` (`id_rol`);

ALTER TABLE `ventas`
  ADD PRIMARY KEY (`id_venta`),
  ADD UNIQUE KEY `id_pedido` (`id_pedido`),
  ADD KEY `fk_ventas_metodo_pago` (`id_metodo_pago`),
  ADD KEY `fk_ventas_estado_pago` (`id_estado_pago`);

ALTER TABLE `categorias_producto`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `clientes`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `contactos_cliente`
  MODIFY `id_contacto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `detalle_de_pedido`
  MODIFY `id_detalle_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `detalle_venta`
  MODIFY `id_detalle_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `estados_pago`
  MODIFY `id_estado_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `estados_pedido`
  MODIFY `id_estado_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `metodos_pago`
  MODIFY `id_metodo_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `pedidos`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `permisos`
  MODIFY `id_permiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

ALTER TABLE `productos`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `terminos_pago`
  MODIFY `id_terminos_pago` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `tipos_contacto`
  MODIFY `id_tipo_contacto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

ALTER TABLE `ventas`
  MODIFY `id_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `clientes`
  ADD CONSTRAINT `fk_clientes_terminos_pago` FOREIGN KEY (`id_terminos_pago`) REFERENCES `terminos_pago` (`id_terminos_pago`) ON UPDATE CASCADE;

ALTER TABLE `contactos_cliente`
  ADD CONSTRAINT `fk_contactos_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_contactos_tipo` FOREIGN KEY (`id_tipo_contacto`) REFERENCES `tipos_contacto` (`id_tipo_contacto`) ON UPDATE CASCADE;

ALTER TABLE `detalle_de_pedido`
  ADD CONSTRAINT `fk_detallepedido_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detallepedido_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON UPDATE CASCADE;

ALTER TABLE `detalle_venta`
  ADD CONSTRAINT `fk_detalleventa_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalleventa_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedidos_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedidos_estado` FOREIGN KEY (`id_estado_pedido`) REFERENCES `estados_pedido` (`id_estado_pedido`) ON UPDATE CASCADE;

ALTER TABLE `productos`
  ADD CONSTRAINT `fk_productos_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categorias_producto` (`id_categoria`) ON UPDATE CASCADE;

ALTER TABLE `roles_permisos`
  ADD CONSTRAINT `fk_rolespermisos_permiso` FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id_permiso`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rolespermisos_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON UPDATE CASCADE;

ALTER TABLE `ventas`
  ADD CONSTRAINT `fk_ventas_estado_pago` FOREIGN KEY (`id_estado_pago`) REFERENCES `estados_pago` (`id_estado_pago`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ventas_metodo_pago` FOREIGN KEY (`id_metodo_pago`) REFERENCES `metodos_pago` (`id_metodo_pago`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ventas_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`) ON DELETE SET NULL ON UPDATE CASCADE;
