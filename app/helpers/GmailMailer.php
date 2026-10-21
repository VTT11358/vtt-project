<?php

class GmailMailer
{
    private $socket;

    public function sendVerificationEmail(
        array $config,
        string $recipient,
        string $recipientName,
        string $verificationUrl
    ): void {
        $this->sendActionEmail(
            $config,
            $recipient,
            $recipientName,
            "Xác nhận email đăng ký Laptop Store",
            "Xác nhận địa chỉ email",
            "Vui lòng xác nhận email để hoàn tất đăng ký tài khoản Laptop Store.",
            "Xác nhận email",
            $verificationUrl,
            "Liên kết có hiệu lực trong 24 giờ. Nếu bạn không tạo tài khoản này, có thể bỏ qua email."
        );
    }

    public function sendEmailChangeVerificationEmail(
        array $config,
        string $recipient,
        string $recipientName,
        string $verificationUrl
    ): void {
        $this->sendActionEmail(
            $config,
            $recipient,
            $recipientName,
            "Xác nhận email mới Laptop Store",
            "Xác nhận địa chỉ email mới",
            "Bạn đã yêu cầu thay đổi email tài khoản Laptop Store. Xác nhận địa chỉ mới để hoàn tất thay đổi.",
            "Xác nhận email mới",
            $verificationUrl,
            "Liên kết có hiệu lực trong 24 giờ và chỉ sử dụng được một lần. Email tài khoản chỉ được đổi sau khi xác nhận."
        );
    }

    public function sendPasswordResetEmail(
        array $config,
        string $recipient,
        string $recipientName,
        string $resetUrl
    ): void {
        $this->sendActionEmail(
            $config,
            $recipient,
            $recipientName,
            "Đặt lại mật khẩu Laptop Store",
            "Đặt lại mật khẩu",
            "Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản Laptop Store của bạn.",
            "Đặt mật khẩu mới",
            $resetUrl,
            "Liên kết chỉ có hiệu lực trong 1 giờ và chỉ sử dụng được một lần. Nếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này."
        );
    }

    private function sendActionEmail(
        array $config,
        string $recipient,
        string $recipientName,
        string $subject,
        string $heading,
        string $intro,
        string $buttonLabel,
        string $actionUrl,
        string $expiryNote
    ): void {
        $this->validateConfig($config);

        $context = stream_context_create([
            "ssl" => [
                "verify_peer" => true,
                "verify_peer_name" => true,
            ],
        ]);
        $this->socket = stream_socket_client(
            "tcp://" . $config["smtp_host"] . ":" . (int) $config["smtp_port"],
            $errorCode,
            $errorMessage,
            20,
            STREAM_CLIENT_CONNECT,
            $context
        );
        if (!$this->socket) {
            throw new RuntimeException(
                "Không thể kết nối máy chủ Gmail SMTP: " . $errorMessage,
                (int) $errorCode
            );
        }
        stream_set_timeout($this->socket, 20);

        try {
            $this->expect([220]);
            $this->command("EHLO localhost", [250]);
            $this->command("STARTTLS", [220]);
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException("Không thể thiết lập kết nối TLS với Gmail SMTP.");
            }
            $this->command("EHLO localhost", [250]);
            $this->command("AUTH LOGIN", [334]);
            $this->command(base64_encode($config["smtp_username"]), [334]);
            $password = preg_replace('/\s+/', '', $config["smtp_password"]);
            $this->command(base64_encode($password), [235]);
            $this->command("MAIL FROM:<" . $config["mail_from_email"] . ">", [250]);
            $this->command("RCPT TO:<" . $recipient . ">", [250, 251]);
            $this->command("DATA", [354]);
            $message = $this->buildMessage(
                $recipient,
                $recipientName,
                $subject,
                $heading,
                $intro,
                $buttonLabel,
                $actionUrl,
                $expiryNote,
                $config
            );
            $message = preg_replace('/^\./m', '..', $message);
            $this->write($message . "\r\n.\r\n");
            $this->expect([250]);
            $this->command("QUIT", [221]);
        } finally {
            fclose($this->socket);
        }
    }

    private function validateConfig(array $config): void
    {
        foreach ([
            "smtp_host",
            "smtp_port",
            "smtp_username",
            "smtp_password",
            "mail_from_email",
            "mail_from_name",
        ] as $key) {
            if (!isset($config[$key]) || trim((string) $config[$key]) === "") {
                throw new RuntimeException("Thiếu cấu hình Gmail SMTP: " . $key);
            }
        }

        if (!filter_var($config["smtp_username"], FILTER_VALIDATE_EMAIL)
            || !filter_var($config["mail_from_email"], FILTER_VALIDATE_EMAIL)
        ) {
            throw new RuntimeException("Địa chỉ email gửi Gmail SMTP không hợp lệ.");
        }
    }

    private function buildMessage(
        string $recipient,
        string $recipientName,
        string $subject,
        string $heading,
        string $intro,
        string $buttonLabel,
        string $actionUrl,
        string $expiryNote,
        array $config
    ): string {
        $boundary = "alt_" . bin2hex(random_bytes(12));
        $subject = $this->encodeHeader($subject);
        $fromName = $this->encodeHeader($config["mail_from_name"]);
        $safeName = htmlspecialchars($recipientName, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
        $safeHeading = htmlspecialchars($heading, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
        $safeIntro = htmlspecialchars($intro, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
        $safeButtonLabel = htmlspecialchars($buttonLabel, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
        $safeUrl = htmlspecialchars($actionUrl, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
        $safeExpiryNote = htmlspecialchars($expiryNote, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
        $html = "<!doctype html><html lang=\"vi\"><body style=\"margin:0;background:#f6f7f9;font-family:Arial,sans-serif;color:#192536\">"
            . "<div style=\"max-width:560px;margin:32px auto;padding:32px;background:#fff;border:1px solid #e8ebef;border-radius:14px\">"
            . "<h1 style=\"font-size:23px\">" . $safeHeading . "</h1>"
            . "<p>Xin chào " . $safeName . ",</p>"
            . "<p>" . $safeIntro . "</p>"
            . "<p style=\"margin:28px 0\"><a href=\"" . $safeUrl
            . "\" style=\"padding:13px 20px;border-radius:8px;background:#e63b4b;color:#fff;text-decoration:none;font-weight:bold\">"
            . $safeButtonLabel . "</a></p>"
            . "<p>Nếu nút không hoạt động, hãy mở liên kết sau:</p><p style=\"word-break:break-all\">" . $safeUrl . "</p>"
            . "<p>" . $safeExpiryNote . "</p>"
            . "<p style=\"color:#788496\">Laptop Store</p></div></body></html>";
        $plain = "Xin chào " . $recipientName . ",\r\n\r\n"
            . $intro . "\r\n\r\n"
            . $buttonLabel . ": " . $actionUrl . "\r\n\r\n" . $expiryNote;
        $plain = preg_replace("/(?<!\r)\n/", "\r\n", $plain);

        $message = "From: " . $fromName . " <" . $config["mail_from_email"] . ">\r\n"
            . "To: " . $this->encodeHeader($recipientName) . " <" . $recipient . ">\r\n"
            . "Subject: " . $subject . "\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: multipart/alternative; boundary=\"" . $boundary . "\"\r\n\r\n"
            . "--" . $boundary . "\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($plain))
            . "--" . $boundary . "\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html))
            . "--" . $boundary . "--";

        return preg_replace("/(?<!\r)\n/", "\r\n", $message);
    }

    private function encodeHeader(string $value): string
    {
        return "=?UTF-8?B?" . base64_encode($value) . "?=";
    }

    private function command(string $command, array $expectedCodes): string
    {
        $this->write($command . "\r\n");
        return $this->expect($expectedCodes);
    }

    private function write(string $data): void
    {
        $offset = 0;
        $length = strlen($data);
        while ($offset < $length) {
            $written = fwrite($this->socket, substr($data, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException("Không thể gửi dữ liệu tới Gmail SMTP.");
            }
            $offset += $written;
        }
    }

    private function expect(array $expectedCodes): string
    {
        $response = "";
        do {
            $line = fgets($this->socket, 515);
            if ($line === false) {
                throw new RuntimeException("Gmail SMTP không phản hồi.");
            }
            $response .= $line;
        } while (strlen($line) >= 4 && $line[3] === "-");

        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException(
                "Gmail SMTP từ chối yêu cầu (" . $code . "): " . trim($response)
            );
        }

        return $response;
    }
}
