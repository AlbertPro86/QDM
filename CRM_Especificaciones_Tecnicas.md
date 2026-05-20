# 🚀 Especificaciones Técnicas: CRM Operativo QUANTUN Digital

Este documento contiene los cuatro entregables técnicos para el desarrollo e implementación del núcleo operativo del CRM de tu agencia. Está basado en arquitectura **PHP (Laravel) / Node.js** y **MySQL**, utilizando estilos consistentes con el manual de **QUANTUN Digital**.

---

## 1. 🗄️ Esquema SQL (Base de Datos)
Esta es la estructura relacional que modela el ecosistema de leads, servicios y la gestión financiera a nivel transaccional.

```sql
-- 1. Tabla de Leads o Prospectos
CREATE TABLE leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    whatsapp VARCHAR(20) NOT NULL,
    servicio_interes VARCHAR(50) NOT NULL,
    presupuesto DECIMAL(10,2),
    url_actual VARCHAR(255),
    estado ENUM('nuevo', 'contactado', 'en_negociacion', 'ganado', 'perdido') DEFAULT 'nuevo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Tabla de Servicios (Catálogo de la Agencia)
CREATE TABLE servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio_base DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Tabla de Facturas / Comprobantes (Gestor Documental)
CREATE TABLE facturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    archivo_url VARCHAR(255) NOT NULL,
    tipo VARCHAR(20) NOT NULL, -- Ej: 'application/pdf', 'image/jpeg'
    peso_bytes INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Tabla de Transacciones (Núcleo Financiero)
CREATE TABLE transacciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('ingreso', 'egreso') NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    concepto VARCHAR(255) NOT NULL,
    fecha_vencimiento DATE,
    estado ENUM('pendiente', 'pagado', 'vencido') DEFAULT 'pendiente',
    lead_id INT NULL,
    servicio_id INT NULL,
    factura_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE SET NULL,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE SET NULL
);
```

---

## 2. 🤖 Lógica del Controlador (Evolution API / WhatsApp)
Esta clase de servicio (Ejemplo en PHP/Laravel) maneja la comunicación directa con tu instancia de Evolution API dockerizada en el VPS para habilitar triggers de notificaciones, incluyendo el envío del comprobante al cliente.

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionApiService
{
    protected $apiUrl;
    protected $apiKey;
    protected $instance;

    public function __construct()
    {
        // Variables desde tu archivo .env
        $this->apiUrl = env('EVOLUTION_API_URL');
        $this->apiKey = env('EVOLUTION_API_KEY');
        $this->instance = env('EVOLUTION_INSTANCE');
    }

    /**
     * Trigger Lead & Trigger Gasto (Notificaciones de Texto)
     */
    public function sendTextMessage($numero, $mensaje)
    {
        $url = "{$this->apiUrl}/message/sendText/{$this->instance}";
        
        $payload = [
            "number" => $numero,
            "options" => [
                "delay" => 1200,
                "presence" => "composing", // Simula que está escribiendo
                "linkPreview" => true
            ],
            "textMessage" => [
                "text" => $mensaje
            ]
        ];

        return $this->dispararPeticion($url, $payload);
    }

    /**
     * Trigger Pago (Notificación de Documentos Multimedia - Envío de Factura)
     */
    public function sendMediaMessage($numero, $caption, $urlArchivoLocal, $mimeType, $nombreArchivo)
    {
        $url = "{$this->apiUrl}/message/sendMedia/{$this->instance}";
        
        // Convertimos el archivo local a base64 (Evolution API permite base64 string en mediaType)
        if(file_exists($urlArchivoLocal)){
            $base64Media = base64_encode(file_get_contents($urlArchivoLocal));
            $mediaString = "data:{$mimeType};base64,{$base64Media}";
        } else {
            // Manejo si es URL remota en vez de local
            $mediaString = $urlArchivoLocal; 
        }

        $payload = [
            "number" => $numero,
            "options" => [
                "delay" => 1500,
                "presence" => "composing"
            ],
            "mediaMessage" => [
                "mediatype" => str_contains($mimeType, 'pdf') ? 'document' : 'image',
                "caption" => $caption,
                "media" => $mediaString,
                "fileName" => $nombreArchivo
            ]
        ];

        return $this->dispararPeticion($url, $payload);
    }

    private function dispararPeticion($url, $payload)
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($url, $payload);

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Error Evolution API: " . $e->getMessage());
            return false;
        }
    }
}
```

---

## 3. 🌐 Integración WP (Webhook desde WordPress)
Se añade al archivo `functions.php` del tema de WordPress. Intercepta el momento en el que el cliente llena el formulario (CF7 en este ejemplo) y envía el Lead a tu propio servidor (API del Dashboard).

```php
<?php
/**
 * Disparador de Webhook: Contact Form 7 hacia CRM QUANTUN.
 * Colocar en el functions.php del Child Theme de Elementor.
 */
add_action('wpcf7_mail_sent', 'quantun_crm_webhook_lead');

function quantun_crm_webhook_lead($contact_form) {
    $submission = WPCF7_Submission::get_instance();
    if ($submission) {
        $posted_data = $submission->get_posted_data();

        // 1. Mapeo de campos estipulados
        $lead_data = array(
            'nombre'           => isset($posted_data['your-name']) ? $posted_data['your-name'] : 'Lead Sin Nombre',
            'whatsapp'         => isset($posted_data['whatsapp']) ? $posted_data['whatsapp'] : '',
            'servicio_interes' => isset($posted_data['servicio']) ? $posted_data['servicio'] : '',
            'presupuesto'      => isset($posted_data['presupuesto']) ? $posted_data['presupuesto'] : '0.00',
            'url_actual'       => isset($posted_data['url']) ? $posted_data['url'] : 'No URL'
        );

        // 2. Ruta y endpoint en tu VPS
        $crm_webhook_url = 'https://dashboard.quantundigital.com/api/v1/leads/webhook';

        $args = array(
            'body'        => wp_json_encode($lead_data),
            'timeout'     => '15', // Aumentado por seguridad de red
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking'    => true, // true para capturar errores de log, falso si entorpece UX al cliente
            'headers'     => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer TU_API_TOKEN_SECRETO' // Token de seguridad interno
            ),
        );

        // 3. Envío al sistema administrativo
        wp_remote_post($crm_webhook_url, $args);
    }
}
?>
```

---

## 4. 🖥️ Interfaz del Dashboard (Tailwind CSS + Identidad Visual)
Esta es la maqueta inicial del archivo de vista (`dashboard.blade.php` o `index.html`) que integra las variables de color, tipografía de QUANTUN Digital y la estructura solicitada (UI).

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Dashboard | QUANTUN Digital</title>
    
    <!-- CDN de Tailwind (Para prototipado, usar build en producción) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Tipografías de la marca -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Montserrat:wght@400;700;800&display=swap" rel="stylesheet">
    
    <!-- Archivo simulado de Estilos Globales -->
    <!-- <link rel="stylesheet" href="/Estilos/global.css"> -->
    
    <script>
        // Configurando Tailwind para consumir nuestra paleta e identidad directamente
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            primary: '#000000',    /* Negro */
                            secondary: '#c9f31d',  /* Verde Lima */
                            tertiary: '#ffffff',   /* Blanco */
                            surface: '#f8fafc',
                        }
                    },
                    fontFamily: {
                        poppins: ['Poppins', 'sans-serif'],
                        montserrat: ['Montserrat', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Montserrat', sans-serif; background-color: #f3f4f6; }
        h1, h2, h3, h4, .title-font { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-brand-primary">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-brand-primary text-brand-tertiary flex flex-col hidden md:flex shadow-xl z-20">
        <div class="h-20 flex items-center justify-center border-b border-gray-800">
            <!-- Consumiendo el Logo oficial de la marca -->
            <img src="/Assets/logo_quantun_digital_blanco.png" alt="QUANTUN Logo" class="h-8">
        </div>
        <nav class="flex-1 px-4 py-6 space-y-2 font-montserrat text-sm border-r border-gray-800">
            <a href="#" class="flex items-center gap-3 px-4 py-3 bg-brand-secondary text-brand-primary rounded-xl font-bold transition-transform hover:scale-105">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Dashboard
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-800 rounded-xl transition-colors text-gray-300 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                Gestión de Leads
            </a>
            <a href="#" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-800 rounded-xl transition-colors text-gray-300 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Núcleo Financiero
            </a>
        </nav>
    </aside>

    <!-- MAIN APP CONTENEDOR -->
    <main class="flex-1 flex flex-col overflow-y-auto bg-brand-surface relative z-10">
        
        <!-- TOPBAR -->
        <header class="h-20 bg-brand-tertiary shadow-[0_4px_30px_rgba(0,0,0,0.03)] flex items-center justify-between px-8 z-10 sticky top-0">
            <h2 class="text-2xl font-bold title-font tracking-tight text-gray-800">Panel Operativo</h2>
            <div class="flex items-center gap-5">
                <button class="p-2 rounded-full hover:bg-gray-100 relative transition-colors">
                    <span class="absolute top-1 right-1 w-2.5 h-2.5 bg-brand-secondary rounded-full"></span>
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                </button>
                <div class="h-10 w-10 bg-brand-primary rounded-full flex items-center justify-center font-bold text-brand-secondary ring-2 ring-brand-secondary ring-offset-2 shadow-lg">
                    Q
                </div>
            </div>
        </header>

        <!-- CONTENIDO -->
        <div class="p-8">
            <!-- Widgets Financieros -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Tarjeta 1: Leads -->
                <div class="bg-brand-tertiary p-6 rounded-2xl shadow-sm border-l-4 border-brand-secondary hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider font-montserrat">Nuevos Leads (Mes)</h3>
                            <p class="text-3xl font-bold mt-2 font-poppins text-brand-primary">42</p>
                        </div>
                        <div class="p-2 bg-brand-secondary/20 text-brand-primary rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        </div>
                    </div>
                </div>
                <!-- Tarjeta 2: Ingresos -->
                <div class="bg-brand-tertiary p-6 rounded-2xl shadow-sm border-l-4" style="border-color: #10B981;">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider font-montserrat">Ingresos (30 días)</h3>
                    <p class="text-3xl font-bold mt-2 font-poppins text-brand-primary"><span class="text-sm text-gray-400 font-normal mr-1">$</span>8,450.00</p>
                </div>
                <!-- Tarjeta 3: Egresos -->
                <div class="bg-brand-tertiary p-6 rounded-2xl shadow-sm border-l-4" style="border-color: #EF4444;">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider font-montserrat">Egresos / Renovaciones</h3>
                    <p class="text-3xl font-bold mt-2 font-poppins text-brand-primary"><span class="text-sm text-gray-400 font-normal mr-1">$</span>420.00</p>
                </div>
            </div>

            <!-- Tabla Recent Data -->
            <div class="bg-brand-tertiary rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="flex justify-between items-center p-6 border-b border-gray-50">
                    <h3 class="text-lg font-bold title-font">Transacciones Recientes</h3>
                    <button class="text-sm font-bold bg-brand-secondary text-brand-primary px-4 py-2 rounded-lg hover:bg-brand-secondary/80 transition-colors">Nuevo Ingreso</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 font-montserrat">
                        <thead class="bg-gray-50/50 text-gray-400 font-semibold uppercase text-xs">
                            <tr>
                                <th class="px-6 py-4">Concepto</th>
                                <th class="px-6 py-4">Cliente</th>
                                <th class="px-6 py-4">Monto</th>
                                <th class="px-6 py-4">Estado</th>
                                <th class="px-6 py-4">Factura</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <!-- Fila de ejemplo: Ingreso de Servicio Venta Pagina Web -->
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-brand-primary">Pago 50% Desarrollo Web CMS</td>
                                <td class="px-6 py-4">Empresa NovaCorp</td>
                                <td class="px-6 py-4 text-emerald-600 font-semibold">+$ 1,500.00</td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">Pagado</span>
                                </td>
                                <td class="px-6 py-4">
                                    <button onclick="document.getElementById('invoiceModal').classList.remove('hidden')" class="flex items-center gap-2 text-brand-primary hover:text-gray-500 font-semibold text-xs">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        Ver PDF
                                    </button>
                                </td>
                            </tr>
                            <!-- Fila de ejemplo: Egreso de Servidor -->
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-brand-primary">Renovación VPS Hostinger</td>
                                <td class="px-6 py-4">- Proveedor Interno -</td>
                                <td class="px-6 py-4 text-red-600 font-semibold">-$ 120.00</td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-bold">Vence en 24h</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-400 text-xs italic">Sin adjunto</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- MODAL VISOR DE FACTURACIÓN (Gestor Documental) -->
    <div id="invoiceModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50 transition-opacity">
        <div class="bg-brand-tertiary p-6 rounded-2xl shadow-2xl w-11/12 max-w-4xl border border-gray-100">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold title-font">Visualización de Factura #INV-2026-001</h3>
                <button onclick="document.getElementById('invoiceModal').classList.add('hidden')" class="text-gray-400 hover:text-red-500 rounded-full p-1 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <!-- Contenedor del Visor -->
            <div class="aspect-video bg-gray-50 flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 relative overflow-hidden group">
                <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <p class="text-gray-400 font-medium font-montserrat">El visor de archivos de renderizado PDF/JPG iría aquí.</p>
                <!-- IFRAME O IMG REAL SE INYECTA CON JS AQUÍ -->
            </div>
            
            <div class="mt-6 flex justify-end gap-3 font-montserrat">
                <button onclick="document.getElementById('invoiceModal').classList.add('hidden')" class="px-5 py-2.5 border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 font-semibold transition-colors">Cerrar Visor</button>
                <button class="flex items-center gap-2 px-5 py-2.5 bg-brand-primary text-brand-secondary font-bold rounded-lg hover:shadow-lg transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Descargar Archivo
                </button>
            </div>
        </div>
    </div>
</body>
</html>
```
