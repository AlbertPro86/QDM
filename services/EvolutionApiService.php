<?php
/**
 * CRM QUANTUN Digital - Servicio de Integración Evolution API (WhatsApp)
 */

require_once __DIR__ . '/../config/config.php';

class EvolutionApiService
{
    protected $apiUrl;
    protected $apiKey;
    protected $instance;

    public function __construct()
    {
        $this->apiUrl = env('EVOLUTION_API_URL', '');
        $this->apiKey = env('EVOLUTION_API_KEY', '');
        $this->instance = env('EVOLUTION_INSTANCE', '');
    }

    /**
     * Verificar si la API está configurada
     */
    public function isConfigured()
    {
        return !empty($this->apiUrl) && !empty($this->apiKey) && !empty($this->instance)
            && $this->apiUrl !== 'https://tu-evolution-api.com';
    }

    /**
     * Trigger Lead & Trigger Gasto (Notificaciones de Texto)
     */
    public function sendTextMessage($numero, $mensaje)
    {
        if (!$this->isConfigured()) {
            error_log("Evolution API no configurada. Mensaje no enviado.");
            return ['error' => 'API no configurada'];
        }

        $url = "{$this->apiUrl}/message/sendText/{$this->instance}";
        
        $payload = [
            "number" => $numero,
            "options" => [
                "delay" => 1200,
                "presence" => "composing",
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
        if (!$this->isConfigured()) {
            error_log("Evolution API no configurada. Media no enviado.");
            return ['error' => 'API no configurada'];
        }

        $url = "{$this->apiUrl}/message/sendMedia/{$this->instance}";
        
        if (file_exists($urlArchivoLocal)) {
            $base64Media = base64_encode(file_get_contents($urlArchivoLocal));
            $mediaString = "data:{$mimeType};base64,{$base64Media}";
        } else {
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
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'apikey: ' . $this->apiKey,
                    'Content-Type: application/json'
                ],
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                error_log("Evolution API cURL Error: " . $error);
                return ['error' => $error];
            }

            return json_decode($response, true) ?: ['raw_response' => $response, 'http_code' => $httpCode];
        } catch (Exception $e) {
            error_log("Error Evolution API: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}
