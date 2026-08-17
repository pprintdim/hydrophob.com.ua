<?php
// SMTP-відправка (Gmail, STARTTLS). Адаптовано з EmailService проекту sitesHub/prom.

class SmtpMailer
{
    private string $host;
    private int $port;
    private string $user;
    private string $pass;
    private string $from;

    public function __construct(string $host, int $port, string $user, string $pass, string $from)
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
        $this->from = $from !== '' ? $from : $user;
    }

    public function send(string $to, string $subject, string $body): void
    {
        $ctx = stream_context_create(['ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
        ]]);

        $sock = stream_socket_client(
            "tcp://{$this->host}:{$this->port}",
            $errno, $errstr, 15,
            STREAM_CLIENT_CONNECT, $ctx
        );
        if (!$sock) {
            throw new RuntimeException("SMTP connect failed: {$errstr} ({$errno})");
        }
        stream_set_timeout($sock, 15);

        $read = function () use ($sock): string {
            $resp = '';
            while ($line = fgets($sock, 1024)) {
                $resp .= $line;
                if (isset($line[3]) && $line[3] === ' ') break;
            }
            return $resp;
        };
        $send = function (string $cmd) use ($sock, $read): string {
            fwrite($sock, $cmd . "\r\n");
            return $read();
        };

        $this->expect($read(), '220', 'greeting');
        $this->expect($send('EHLO ' . (gethostname() ?: 'localhost')), '250', 'EHLO');
        $this->expect($send('STARTTLS'), '220', 'STARTTLS');

        if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
            stream_socket_enable_crypto($sock, false);
            if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('TLS handshake failed');
            }
        }

        $this->expect($send('EHLO ' . (gethostname() ?: 'localhost')), '250', 'EHLO after TLS');
        $this->expect($send('AUTH LOGIN'), '334', 'AUTH LOGIN');
        $this->expect($send(base64_encode($this->user)), '334', 'AUTH user');
        $resp = $send(base64_encode($this->pass));
        if (strpos(trim($resp), '235') !== 0) {
            throw new RuntimeException('SMTP auth failed: ' . trim($resp));
        }

        $fromAddr = $this->parseAddress($this->from);
        $this->expect($send("MAIL FROM:<{$fromAddr}>"), '250', 'MAIL FROM');
        $this->expect($send("RCPT TO:<{$to}>"), '250', 'RCPT TO');
        $this->expect($send('DATA'), '354', 'DATA');

        $date = date('r');
        $subjectEnc = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $bodyB64 = chunk_split(base64_encode($body));

        $message  = "Date: {$date}\r\n";
        $message .= "From: {$this->from}\r\n";
        $message .= "To: {$to}\r\n";
        $message .= "Subject: {$subjectEnc}\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n";
        $message .= "\r\n";
        $message .= $bodyB64;
        $message .= "\r\n.";

        fwrite($sock, $message . "\r\n");
        $this->expect($read(), '250', 'message accepted');
        $send('QUIT');
        fclose($sock);
    }

    private function expect(string $resp, string $code, string $step): void
    {
        if (strpos(ltrim($resp), $code) !== 0) {
            throw new RuntimeException("SMTP {$step} expected {$code}, got: " . trim($resp));
        }
    }

    private function parseAddress(string $from): string
    {
        if (preg_match('/<([^>]+)>/', $from, $m)) {
            return $m[1];
        }
        return trim($from);
    }
}
