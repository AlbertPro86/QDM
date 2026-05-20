# 🧠 Memory.md — CRM QUANTUN Digital
> **Documento de memoria persistente del proyecto.**  
> Este archivo es la fuente de verdad para retomar el desarrollo sin re-leer todo el código.  
> **INSTRUCCIÓN PARA LA IA:** Siempre leer este archivo al inicio de cada conversación nueva. Conservar el hilo, estilos, decisiones de diseño y estado actual. Actualizar este archivo al final de cada sesión de trabajo significativa.

---

## 📌 Datos del Proyecto

| Campo | Valor |
|-------|-------|
| **Nombre** | CRM Operativo QUANTUN Digital |
| **Stack** | PHP Nativo + MySQL + JS Vanilla + CSS Vanilla |
| **Servidor** | XAMPP (local) |
| **Ruta local** | `c:\xampp\htdocs\CRM-QUANTUN-Digital` |
| **URL acceso** | `http://localhost/CRM-QUANTUN-Digital/` |
| **BD** | `crm_quantun` (MySQL) |
| **Login admin** | `admin@quantundigital.com` / `admin123` |
| **Login agente** | `agente@quantundigital.com` / `admin123` |

---

## 🎨 Identidad Visual (NO MODIFICAR sin aprobación)

| Elemento | Valor | Uso |
|----------|-------|-----|
| **Color Primario** | `#000000` (Negro) | Fondos, sidebar, textos principales |
| **Color Secundario** | `#c9f31d` (Verde Lima) | CTAs, botones, acentos, badges activos |
| **Color Terciario** | `#ffffff` (Blanco) | Fondos claros, textos sobre negro |
| **Fuente Títulos** | `Poppins` (Google Fonts) | h1-h4, labels de KPIs, títulos de sección |
| **Fuente Cuerpo** | `Montserrat` (Google Fonts) | Párrafos, tablas, botones, navegación |
| **Tema Dashboard** | Dark theme en KPIs con acentos neón | Tarjetas KPI con fondo oscuro y glow sutil |
| **Bordes** | `border-radius: 16px` en cards, `12px` en botones | Consistente en todo el CRM |
| **Iconos** | SVG inline (estilo Heroicons/outline) | stroke-width: 1.8 en sidebar, 2 en contenido |

### Variables CSS raíz (`css/styles.css`)
```css
:root {
  --color-primary: #000000;
  --color-secondary: #c9f31d;
  --color-tertiary: #ffffff;
  --font-primary: 'Poppins', sans-serif;
  --font-secondary: 'Montserrat', sans-serif;
}
```

### Estilo KPI Cards (Fintech Dark Theme)
- Fondo oscuro con gradientes sutiles
- Valores en color neón según tipo (lima, verde, rojo)
- Iconos con contenedor semitransparente
- Footer con texto muted y datos secundarios
- Hover con `translateY(-4px)` y sombra elevada

---

## 🏗️ Arquitectura del Proyecto

### Estructura de Archivos
```
CRM-QUANTUN-Digital/
├── .env                        # Variables de entorno
├── .htaccess                   # Reglas Apache
├── index.php                   # Login
├── dashboard.php               # Panel principal
├── leads.php                   # Gestión de leads (tabla + kanban)
├── clientes.php                # Área de clientes (3 tabs)
├── cliente_detalle.php         # Ficha detallada de cliente
├── finanzas.php                # Núcleo financiero
├── servicios.php               # Catálogo de servicios
├── perfil.php                  # Configuración y perfil
├── orden_compra.php            # Generador de orden de compra
├── logout.php                  # Cierre de sesión
├── config/
│   ├── config.php              # Carga .env, constantes, sesión
│   └── database.php            # Singleton PDO
├── includes/
│   ├── auth.php                # Login, logout, roles, log actividad
│   ├── functions.php           # Helpers (formatMoney, sanitize, CSRF, flash)
│   ├── header.php              # <head> + topbar + toast container
│   ├── sidebar.php             # Sidebar con navegación
│   └── footer.php              # Cierre layout + toast JS + helpers JS
├── api/
│   ├── leads.php               # CRUD leads
│   ├── clientes.php            # CRUD clientes
│   ├── cliente_servicios.php   # Servicios asignados a clientes
│   ├── cliente_servicios_all.php # Todos los servicios (renovaciones)
│   ├── cliente_notas.php       # Historial/novedades del cliente
│   ├── cliente_archivos.php    # Upload/gestión multimedia
│   ├── transacciones.php       # CRUD financiero
│   ├── facturas.php            # Upload comprobantes
│   ├── servicios.php           # CRUD catálogo
│   └── webhook.php             # Receptor leads WordPress (CF7)
├── js/
│   ├── leads.js                # Lógica de leads (tabla, kanban, drag&drop)
│   ├── clientes.js             # Lógica de clientes (tabs, filtros, CRUD)
│   └── finanzas.js             # Lógica financiera (CRUD, KPIs, visor)
├── css/
│   └── styles.css              # Estilos globales (~33KB)
├── services/
│   └── EvolutionApiService.php # Servicio WhatsApp (Evolution API)
├── database/
│   ├── schema.sql              # Esquema de BD
│   └── seed.sql                # Datos de ejemplo
├── Assets/
│   ├── logo_quantun_digital_blanco.png
│   ├── logo_quantun_digital_negro.png
│   └── logo_curso.png
└── uploads/                    # Directorio de archivos subidos
```

### Patrón de cada página PHP
```
1. require config.php + auth.php + functions.php
2. requireAuth()  ← protección de ruta
3. Consultas SQL para datos de la página
4. include header.php  ← abre <html>, <head>, sidebar, topbar
5. HTML del contenido
6. <script> o <script src="js/archivo.js">
7. include footer.php  ← cierra layout, agrega toast JS
```

### Patrón API REST
```
- GET    → Listar/consultar (query params)
- POST   → Crear (JSON body o FormData)
- PUT    → Actualizar (JSON body)
- DELETE → Eliminar (query param ?id=X)
- Respuesta: jsonResponse(['success'=>true, 'data'=>..., 'message'=>...])
```

---

## 🗄️ Base de Datos

### Tablas en schema.sql
1. `usuarios` — Autenticación (bcrypt, roles: admin/agente)
2. `leads` — Prospectos (5 estados en pipeline)
3. `servicios` — Catálogo de la agencia
4. `facturas` — Comprobantes/archivos
5. `transacciones` — Núcleo financiero (ingreso/egreso)
6. `actividad_log` — Auditoría de acciones

### ⚠️ Tablas creadas pero NO en schema.sql
Estas tablas existen en la BD pero no fueron añadidas al archivo `schema.sql`:
- `clientes` — Clientes convertidos desde leads
- `cliente_servicios` — Servicios activos asignados a clientes (con monto, costo, descuento, frecuencia, vencimiento)
- `cliente_notas` — Historial/novedades por cliente
- `cliente_archivos` — Área multimedia por cliente

---

## ✅ Funcionalidades Implementadas

### Login (`index.php`)
- Formulario con email/password
- Redirección automática si ya autenticado
- Animación de fondo con dots flotantes
- Botón se deshabilita al enviar

### Dashboard (`dashboard.php`)
- 3 KPIs: Nuevos Leads, Ingresos 30d, Egresos 30d
- Tabla de transacciones recientes con visor de facturas (modal)
- Sidebar dereca con últimos 5 leads
- Layout 2 columnas (1fr + 380px) responsive

### Leads (`leads.php` + `js/leads.js`)
- Vista dual: Tabla y Kanban
- Kanban con drag & drop para cambiar estado
- Barra de KPIs por estado (contadores)
- Modal CRUD (crear/editar)
- Filtros por estado + búsqueda
- Botón WhatsApp directo por lead

### Clientes (`clientes.php` + `js/clientes.js`)
- 3 Tabs: Mis Clientes | Renovaciones | Conversión
- Tab Conversión: leads ganados listos para convertir a cliente
- Filtros por servicio, estado y búsqueda
- Modal para crear/editar cliente

### Ficha de Cliente (`cliente_detalle.php`)
- Perfil lateral con datos de contacto
- 3 KPIs: Ingreso Bruto, Egresos, Ganancia Real
- Tabla de servicios activos con:
  - Checkbox para facturación múltiple
  - Monto bruto, descuento, costo, ganancia
  - Estado por vencimiento (colores semáforo)
  - Acciones: Orden de Compra, Registrar Pago, Editar, Eliminar
- Historial/Novedades (timeline con notas)
- Área Multimedia (upload de archivos, preview de imágenes)
- Disparadores WhatsApp: Recordatorio, Novedad, Factura Pagada

### Finanzas (`finanzas.php` + `js/finanzas.js`)
- CRUD transacciones con upload de comprobante
- KPIs dinámicos (ingresos, egresos, pendientes, balance)
- Filtros por tipo, estado, búsqueda
- Visor modal de facturas (PDF/imágenes)

### Servicios (`servicios.php`)
- Catálogo con tarjetas por categoría
- Colores por categoría (Desarrollo=azul, Marketing=verde, etc.)
- CRUD completo (crear, editar, desactivar)

### Orden de Compra (`orden_compra.php`)
- Generador de factura/orden imprimible
- Soporta: servicio individual, múltiples seleccionados, o todos del cliente
- Acciones: Imprimir/PDF, Enviar por WhatsApp, Enviar por Email
- Diseño profesional con logo, datos fiscales, tabla detallada

### Perfil (`perfil.php`)
- Edición de nombre/email
- Cambio de contraseña (verifica actual)
- Log de actividad reciente

---

## 🔐 Seguridad

| Aspecto | Implementado |
|---------|-------------|
| Passwords bcrypt | ✅ |
| Sesiones PHP | ✅ |
| Protección XSS (`sanitize()`) | ✅ |
| Prepared Statements (PDO) | ✅ |
| CSRF tokens | ⚠️ Generados, no validados en todos los forms |
| Rate limiting | ❌ |
| Roles (admin/agente) | ✅ Sidebar condicional |

---

## 🔌 Integraciones

| Sistema | Estado | Notas |
|---------|--------|-------|
| **Evolution API (WhatsApp)** | 🟡 Preparado | Servicio PHP listo. Disparadores actuales redirigen a `wa.me/` |
| **WordPress (CF7 Webhook)** | ✅ Operativo | `api/webhook.php` con token de seguridad |

---

## 📝 Historial de Cambios

### 2026-04-05 — Cotizador rediseñado (Conversación actual)
- Cotizador reconstruido desde cero (`cotizador.php`)
  - Layout: Catálogo fijo izquierda (340px) + Constructor cotización derecha (mayor)
  - Recipient strip: toggle Cliente / Lead / Contacto Nuevo con búsqueda dinámica
  - Catálogo sincronizado con `api/servicios.php` → carga servicios, sub-servicios (anidados en cada servicio) y paquetes
  - **Precios en COP con formato: $ 1.000 (0 decimales, español locale)**
  - Tabla de items con columnas: Descripción editable | Ciclo | Cantidad | Precio unitario | Desc % | Total | Eliminar
  - Descuento por ítem (%) + descuento global
  - **Moneda predeterminada: COP** (selector secundario: USD, EUR, MXN)
  - Estado inicial: Borrador / Enviada
  - Sin emojis — todos los iconos son SVG inline (Heroicons outline)
  - Colores 100% marca: negro (#000), verde lima (#c9f31d), blanco, grises CSS vars
  - Botón principal: fondo negro + texto lima
  - Acciones post-guardado: Ver PDF, WhatsApp, Email
- `api/cotizaciones.php` actualizado:
  - GET ?q soporte parámetro `solo=cliente|lead` para búsqueda filtrada
  - POST acepta campos `moneda` y `estado`
  - Auto-migración de columnas `moneda` en tabla cotizaciones

### 2026-04-02 — Sesión Inicial (Conversación `d554962e`)
- Creación completa del CRM desde las especificaciones técnicas
- Implementación de todo el stack: login, dashboard, leads, finanzas, servicios, perfil
- CSS global (`styles.css`) con tema dark en KPIs
- Seed con datos de ejemplo (2 usuarios, 8 servicios, 7 leads, 8 transacciones)

### 2026-04-02 — Área de Clientes (Conversación `42eb9949`)
- Nuevo módulo: `clientes.php` + `cliente_detalle.php`
- APIs: `api/clientes.php`, `api/cliente_servicios.php`, `api/cliente_notas.php`, `api/cliente_archivos.php`
- Generador de Orden de Compra (`orden_compra.php`)
- Tema Fintech dark para KPIs del dashboard
- Tablas adicionales: `clientes`, `cliente_servicios`, `cliente_notas`, `cliente_archivos`

### 2026-04-02 — Lectura y documentación (Conversación `3d4219bc`)
- Lectura completa del proyecto
- Creación de este archivo `Memory.md`

---

## 🚧 Pendientes / Mejoras Futuras

- [ ] Agregar tablas faltantes a `schema.sql` (clientes, cliente_servicios, cliente_notas, cliente_archivos)
- [ ] Validar CSRF tokens en todos los formularios
- [ ] Conectar Evolution API real (reemplazar wa.me por API directa)
- [ ] Implementar notificaciones en tiempo real
- [ ] Dashboard con gráficos (Chart.js o similar)
- [ ] Exportar reportes (Excel/PDF)
- [ ] Sistema de permisos más granular
- [ ] Modo responsive completo en todas las vistas

---

> **⚠️ REGLA PARA FUTURAS SESIONES:**  
> Al iniciar una nueva conversación sobre este proyecto, leer `Memory.md` primero.  
> Esto evita re-leer los 30+ archivos del proyecto y mantiene la consistencia de estilos, patrones y decisiones de diseño.
