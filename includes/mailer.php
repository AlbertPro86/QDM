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

    public function __construct() {
        $this->host        = env('MAIL_HOST',         'smtp.gmail.com');
        $this->port        = (int) env('MAIL_PORT',   587);
        $this->encryption  = strtolower(env('MAIL_ENCRYPTION', 'tls'));
        $this->user        = env('MAIL_USERNAME',     '');
        $this->pass        = env('MAIL_PASSWORD',     '');
        $this->fromAddress = env('MAIL_FROM_ADDRESS', $this->user);
        $this->fromName    = env('MAIL_FROM_NAME',    'CRM QUANTUN');
    }

    // ── API pública ────────────────────────────────────────────────────────────

    /**
     * Envía un correo HTML.
     * @param string      $to      Destinatario (email o "Nombre <email>")
     * @param string      $subject Asunto
     * @param string      $html    Cuerpo HTML
     * @param array       $attachments [['path'=>'...','name'=>'...','mime'=>'...']]
     * @return array{ok:bool, error:string|null}
     */
    public function send(string $to, string $subject, string $html, array $attachments = []): array {
        try {
            $this->conectar();
            $this->ehlo();
            if ($this->encryption === 'tls') $this->starttls();
            $this->autenticar();
            $this->enviarMensaje($to, $subject, $html, $attachments);
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

    private function enviarMensaje(string $to, string $subject, string $html, array $attachments): void {
        $toEmail = $this->extraerEmail($to);
        $this->cmd('MAIL FROM:<' . $this->fromAddress . '>', 250);
        $this->cmd('RCPT TO:<'  . $toEmail . '>', [250, 251]);
        $this->cmd('DATA', 354);

        $boundary = 'qcrm_' . md5(uniqid());
        $hasAtt   = !empty($attachments);

        $headers  = "From: =?UTF-8?B?" . base64_encode($this->fromName) . "?= <{$this->fromAddress}>\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "Message-ID: <" . uniqid() . "@quantun.digital>\r\n";

        if ($hasAtt) {
            $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
            $body     = "--$boundary\r\n";
            $body    .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body    .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body    .= chunk_split(base64_encode($html)) . "\r\n";
            foreach ($attachments as $att) {
                $data  = file_get_contents($att['path']);
                $mime  = $att['mime'] ?? 'application/octet-stream';
                $name  = $att['name'] ?? basename($att['path']);
                $body .= "--$boundary\r\n";
                $body .= "Content-Type: $mime; name=\"$name\"\r\n";
                $body .= "Content-Transfer-Encoding: base64\r\n";
                $body .= "Content-Disposition: attachment; filename=\"$name\"\r\n\r\n";
                $body .= chunk_split(base64_encode($data)) . "\r\n";
            }
            $body .= "--$boundary--\r\n";
        } else {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "Content-Transfer-Encoding: base64\r\n";
            $body     = chunk_split(base64_encode($html));
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
