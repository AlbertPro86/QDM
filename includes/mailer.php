<?php
/**
 * CRM QUANTUN Digital — Mailer SMTP ligero
 * Sin dependencias externas. Soporta TLS (STARTTLS puerto 587) y SSL (puerto 465).
 */
class Mailer {

    private string $host;
    private int    $port;
    private string $encryption; // 'tls' | 'ssl' | ''
    private string $user;
    private string $pass;
    private string $fromAddress;
    private string $fromName;

    /** @var resource|false */
    private $socket = false;
    private array $log = [];

    public function __construct(array $config = []) {
        $this->host        = $config['host']         ?? env('MAIL_HOST',         'smtp.gmail.com');
        $this->port        = (int)($config['port']   ?? env('MAIL_PORT',         587));
        $this->encryption  = strtolower($config['encryption'] ?? env('MAIL_ENCRYPTION', 'tls'));
        $this->user        = $config['username']     ?? env('MAIL_USERNAME',     '');
        $this->pass        = $config['password']     ?? env('MAIL_PASSWORD',     '');
        $this->fromAddress = $config['from_address'] ?? env('MAIL_FROM_ADDRESS', $this->user);
        $this->fromName    = $config['from_name']    ?? env('MAIL_FROM_NAME',    'CRM QUANTUN');
    }

    /**
     * Crea un Mailer leyendo config SMTP desde crm_configuraciones (BD).
     * Usa como fallback las variables de entorno.
     */
    public static function fromDb(PDO $pdo): self {
        $get = function(string $k) use ($pdo): string {
            $st = $pdo->prepare("SELECT valor FROM crm_configuraciones WHERE clave = ?");
            $st->execute([$k]);
            $v = $st->fetchColumn();
            return ($v !== false && $v !== '') ? (string)$v : '';
        };
        $cfg = [];
        if ($h = $get('smtp_host'))        $cfg['host']         = $h;
        if ($p = $get('smtp_port'))        $cfg['port']         = $p;
        if ($e = $get('smtp_encryption'))  $cfg['encryption']   = $e;
        if ($u = $get('smtp_username'))    $cfg['username']     = $u;
        if ($pw = $get('smtp_password'))   $cfg['password']     = $pw;
        if ($fa = $get('smtp_from_address')) $cfg['from_address'] = $fa;
        if ($fn = $get('smtp_from_name'))  $cfg['from_name']    = $fn;
        return new self($cfg);
    }

    // ── API pública ────────────────────────────────────────────────────────────

    /**
     * Envía un correo HTML.
     * @param string $to          Destinatario (email o "Nombre <email>")
     * @param string $subject     Asunto
     * @param string $html        Cuerpo HTML
     * @param array  $attachments [['path'=>'...','name'=>'...','mime'=>'...']]
     * @param array  $inlineImages Imágenes embebidas CID [['path'=>'...','cid'=>'...','mime'=>'image/png']]
     *                             Referenciar en HTML como: <img src="cid:NOMBRE_CID">
     * @return array{ok:bool, error:string|null}
     */
    public function send(string $to, string $subject, string $html, array $attachments = [], array $inlineImages = []): array {
        try {
            $this->conectar();
            $this->ehlo();
            if ($this->encryption === 'tls') $this->starttls();
            $this->autenticar();
            $this->enviarMensaje($to, $subject, $html, $attachments, $inlineImages);
            $this->cmd('QUIT');
            @fclose($this->socket);
            return ['ok' => true, 'error' => null];
        } catch (\Throwable $e) {
            @fclose($this->socket);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function getLog(): array { return $this->log; }

    // ── Conexión ───────────────────────────────────────────────────────────────

    private function conectar(): void {
        $prefix = $this->encryption === 'ssl' ? 'ssl://' : '';
        $errno = $errstr = null;
        $this->socket = @stream_socket_client(
            $prefix . $this->host . ':' . $this->port,
            $errno, $errstr, 15
        );
        if (!$this->socket) {
            throw new \RuntimeException("No se pudo conectar al servidor SMTP: $errstr ($errno)");
        }
        stream_set_timeout($this->socket, 15);
        $this->leer(220);
    }

    private function ehlo(): void {
        $this->cmd('EHLO ' . gethostname(), 250);
    }

    private function starttls(): void {
        $this->cmd('STARTTLS', 220);
        if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new \RuntimeException('No se pudo iniciar TLS');
        }
        $this->ehlo(); // re-EHLO tras TLS
    }

    private function autenticar(): void {
        $this->cmd('AUTH LOGIN', 334);
        $this->cmd(base64_encode($this->user), 334);
        $this->cmd(base64_encode($this->pass), 235);
    }

    // ── Construcción y envío del mensaje ──────────────────────────────────────

    private function enviarMensaje(string $to, string $subject, string $html, array $attachments, array $inlineImages = []): void {
        $toEmail = $this->extraerEmail($to);
        $this->cmd('MAIL FROM:<' . $this->fromAddress . '>', 250);
        $this->cmd('RCPT TO:<'  . $toEmail . '>', [250, 251]);
        $this->cmd('DATA', 354);

        $uid    = md5(uniqid());
        $bMix   = 'qcrm_mix_' . $uid;
        $bRel   = 'qcrm_rel_' . $uid;
        $hasAtt = !empty($attachments);
        $hasInl = !empty($inlineImages);

        $headers  = "From: =?UTF-8?B?" . base64_encode($this->fromName) . "?= <{$this->fromAddress}>\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "Message-ID: <" . uniqid() . "@quantun.digital>\r\n";

        // Construye las partes inline (dentro de multipart/related)
        $buildRelatedParts = function() use ($html, $inlineImages, $bRel): string {
            $p  = "--$bRel\r\n";
            $p .= "Content-Type: text/html; charset=UTF-8\r\n";
            $p .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $p .= chunk_split(base64_encode($html)) . "\r\n";
            foreach ($inlineImages as $img) {
                $raw = @file_get_contents($img['path'] ?? '');
                if ($raw === false || $raw === '') continue;
                $p .= "--$bRel\r\n";
                $p .= "Content-Type: " . ($img['mime'] ?? 'image/png') . "\r\n";
                $p .= "Content-Transfer-Encoding: base64\r\n";
                $p .= "Content-ID: <" . $img['cid'] . ">\r\n";
                $p .= "Content-Disposition: inline\r\n\r\n";
                $p .= chunk_split(base64_encode($raw)) . "\r\n";
            }
            $p .= "--$bRel--\r\n";
            return $p;
        };

        if ($hasAtt && $hasInl) {
            // multipart/mixed > multipart/related + adjuntos
            $headers .= "Content-Type: multipart/mixed; boundary=\"$bMix\"\r\n";
            $body  = "--$bMix\r\n";
            $body .= "Content-Type: multipart/related; boundary=\"$bRel\"\r\n\r\n";
            $body .= $buildRelatedParts() . "\r\n";
            foreach ($attachments as $att) {
                $raw = @file_get_contents($att['path']);
                if ($raw === false) continue;
                $mime = $att['mime'] ?? 'application/octet-stream';
                $name = $att['name'] ?? basename($att['path']);
                $body .= "--$bMix\r\n";
                $body .= "Content-Type: $mime; name=\"$name\"\r\n";
                $body .= "Content-Transfer-Encoding: base64\r\n";
                $body .= "Content-Disposition: attachment; filename=\"$name\"\r\n\r\n";
                $body .= chunk_split(base64_encode($raw)) . "\r\n";
            }
            $body .= "--$bMix--\r\n";
        } elseif ($hasInl) {
            // multipart/related (html + imágenes inline)
            $headers .= "Content-Type: multipart/related; boundary=\"$bRel\"\r\n";
            $body = $buildRelatedParts();
        } elseif ($hasAtt) {
            // multipart/mixed (html + adjuntos)
            $headers .= "Content-Type: multipart/mixed; boundary=\"$bMix\"\r\n";
            $body  = "--$bMix\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($html)) . "\r\n";
            foreach ($attachments as $att) {
                $raw = @file_get_contents($att['path']);
                if ($raw === false) continue;
                $mime = $att['mime'] ?? 'application/octet-stream';
                $name = $att['name'] ?? basename($att['path']);
                $body .= "--$bMix\r\n";
                $body .= "Content-Type: $mime; name=\"$name\"\r\n";
                $body .= "Content-Transfer-Encoding: base64\r\n";
                $body .= "Content-Disposition: attachment; filename=\"$name\"\r\n\r\n";
                $body .= chunk_split(base64_encode($raw)) . "\r\n";
            }
            $body .= "--$bMix--\r\n";
        } else {
            // HTML plano
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "Content-Transfer-Encoding: base64\r\n";
            $body = chunk_split(base64_encode($html));
        }

        $this->escribir($headers . "\r\n" . $body . "\r\n.");
        $this->leer(250);
    }

    // ── Bajo nivel ────────────────────────────────────────────────────────────

    private function cmd(string $cmd, int|array|null $expect = null): string {
        $this->escribir($cmd);
        if ($expect !== null) return $this->leer($expect);
        return '';
    }

    private function escribir(string $data): void {
        $this->log[] = '> ' . substr($data, 0, 120);
        fwrite($this->socket, $data . "\r\n");
    }

    private function leer(int|array $expect): string {
        $resp = '';
        while ($line = fgets($this->socket, 512)) {
            $this->log[] = '< ' . rtrim($line);
            $resp .= $line;
            if ($line[3] === ' ') break; // último fragmento de respuesta multi-línea
        }
        $code = (int) substr($resp, 0, 3);
        $expected = is_array($expect) ? $expect : [$expect];
        if (!in_array($code, $expected)) {
            throw new \RuntimeException("SMTP respondió $code, se esperaba " . implode('/', $expected) . ": $resp");
        }
        return $resp;
    }

    private function extraerEmail(string $str): string {
        if (preg_match('/<(.+?)>/', $str, $m)) return trim($m[1]);
        return trim($str);
    }
}
