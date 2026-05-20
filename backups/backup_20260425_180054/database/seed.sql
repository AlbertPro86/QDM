-- =============================================
-- CRM Operativo QUANTUN Digital
-- Datos de Ejemplo (Seed)
-- =============================================

USE crm_quantun;

-- Usuario Administrador (password: admin123)
INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador', 'admin@quantundigital.com', '$2y$10$aJvr58H8tMhHl5XqNzSfl.0FjczCE/PIr.VDlepQHQk25JL8A2T1m', 'admin'),
('Agente Demo', 'agente@quantundigital.com', '$2y$10$aJvr58H8tMhHl5XqNzSfl.0FjczCE/PIr.VDlepQHQk25JL8A2T1m', 'agente');

-- Catálogo de Servicios
INSERT INTO servicios (nombre, descripcion, precio_base, categoria) VALUES
('Desarrollo Web CMS', 'Diseño y desarrollo de sitio web con gestor de contenido WordPress o similar.', 3000.00, 'Desarrollo'),
('Landing Page', 'Página de aterrizaje optimizada para conversiones con diseño premium.', 800.00, 'Desarrollo'),
('Tienda E-Commerce', 'Tienda online completa con WooCommerce, pasarela de pagos y catálogo.', 5000.00, 'Desarrollo'),
('SEO Mensual', 'Optimización para motores de búsqueda: auditoría, keywords, backlinking.', 450.00, 'Marketing'),
('Social Media Management', 'Gestión integral de redes sociales: contenido, programación y analítica.', 600.00, 'Marketing'),
('Branding Digital', 'Identidad visual completa: logo, paleta, tipografía y manual de marca.', 1200.00, 'Diseño'),
('Mantenimiento Web', 'Soporte técnico, actualizaciones y backups mensuales del sitio web.', 200.00, 'Soporte'),
('Hosting VPS', 'Servidor privado virtual administrado con SSL y soporte 24/7.', 120.00, 'Infraestructura');

-- Leads de Ejemplo
INSERT INTO leads (nombre, whatsapp, email, servicio_interes, presupuesto, url_actual, fuente, estado, notas, asignado_a) VALUES
('Carlos Mendoza', '+573001234567', 'carlos@novacorp.co', 'Desarrollo Web CMS', 3500.00, 'https://novacorp.co', 'manual', 'en_negociacion', 'Interesado en rediseño completo. Tiene presupuesto aprobado.', 1),
('María López', '+573009876543', 'maria@boutiquestyle.com', 'Tienda E-Commerce', 5000.00, 'https://boutiquestyle.com', 'wordpress', 'nuevo', 'Llegó por formulario de contacto. Tienda de moda.', NULL),
('Andrés Ruiz', '+573005551234', 'andres@fitgym.co', 'Landing Page', 1000.00, NULL, 'landing', 'contactado', 'Necesita landing para promoción de gimnasio. Responde rápido.', 1),
('Laura Jiménez', '+573002223344', 'laura@ecomarket.co', 'SEO Mensual', 500.00, 'https://ecomarket.co', 'referido', 'ganado', 'Cliente confirmado. Inicia servicio SEO el próximo mes.', 1),
('Pedro Gómez', '+573007778899', 'pedro@techstart.io', 'Branding Digital', 1500.00, NULL, 'manual', 'perdido', 'Decidió ir con otra agencia por temas de precio.', 2),
('Sofia Torres', '+573004445566', 'sofia@cafedelicia.com', 'Social Media Management', 700.00, 'https://cafedelicia.com', 'wordpress', 'nuevo', 'Cafetería artesanal buscando presencia digital.', NULL),
('Diego Vargas', '+573006667788', 'diego@constructora-vr.com', 'Desarrollo Web CMS', 4000.00, 'https://constructora-vr.com', 'manual', 'contactado', 'Constructora grande, necesitan portal corporativo.', 2);

-- Transacciones de Ejemplo
INSERT INTO transacciones (tipo, monto, concepto, descripcion, fecha_vencimiento, estado, lead_id, servicio_id, registrado_por) VALUES
('ingreso', 1500.00, 'Pago 50% Desarrollo Web CMS', 'Anticipo del proyecto de NovaCorp', NULL, 'pagado', 1, 1, 1),
('ingreso', 450.00, 'SEO Mensual - Abril 2026', 'Servicio mensual de SEO para EcoMarket', '2026-04-30', 'pendiente', 4, 4, 1),
('ingreso', 800.00, 'Landing Page FitGym', 'Pago total de landing page promocional', NULL, 'pagado', 3, 2, 1),
('egreso', 120.00, 'Renovación VPS Hostinger', 'Pago mensual del servidor VPS administrado', '2026-04-05', 'pendiente', NULL, 8, 1),
('egreso', 45.00, 'Licencia Elementor Pro', 'Renovación anual del plugin Elementor', '2026-05-15', 'pendiente', NULL, NULL, 1),
('ingreso', 600.00, 'Social Media - Café Delicia', 'Primer mes de gestión de redes sociales', '2026-04-15', 'pendiente', 6, 5, 1),
('egreso', 80.00, 'Dominio quantundigital.com', 'Renovación anual del dominio principal', '2026-06-01', 'pendiente', NULL, NULL, 1),
('ingreso', 1200.00, 'Branding Digital Completo', 'Proyecto de identidad visual completado', NULL, 'pagado', NULL, 6, 1);
