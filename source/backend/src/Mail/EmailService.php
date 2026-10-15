<?php
declare(strict_types=1);

namespace WealthFlow\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use WealthFlow\Config;

/** Reusable SMTP sender. Secrets come only from server configuration. */
final class EmailService
{
    public function __construct(private readonly Config $config) {}

    /**
     * Check transport settings before generating an OTP. This check must not
     * depend on whether the submitted email is registered (anti-enumeration).
     */
    public function assertConfigured(): void
    {
        $transport = strtolower(trim($this->config->get('MAIL_TRANSPORT')));
        $sender = $this->config->get('MAIL_FROM_ADDRESS');
        if (!filter_var($sender, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('MAIL_FROM_ADDRESS_INVALID');
        }
        if ($transport === 'log') {
            if ($this->config->get('APP_ENV') !== 'local') {
                throw new \RuntimeException('MAIL_LOG_NOT_ALLOWED');
            }
            $directory = $this->config->directory . '/storage';
            if (!is_dir($directory) || !is_writable($directory)) {
                throw new \RuntimeException('MAIL_PREVIEW_DIRECTORY_NOT_WRITABLE');
            }
            return;
        }
        if ($transport !== 'smtp') {
            throw new \RuntimeException('MAIL_TRANSPORT_INVALID');
        }
        $host = trim($this->config->get('MAIL_HOST'));
        $port = filter_var($this->config->get('MAIL_PORT'), FILTER_VALIDATE_INT);
        $encryption = strtolower(trim($this->config->get('MAIL_ENCRYPTION')));
        $localHost = in_array($host, ['localhost', '127.0.0.1', '::1'], true);
        if ($host === '' || $port === false || $port < 1 || $port > 65535
            || !in_array($encryption, ['tls', 'ssl', 'none'], true)
            || ($encryption === 'none' && !$localHost)) {
            throw new \RuntimeException('MAIL_SMTP_SETTINGS_INVALID');
        }
        $username = trim($this->config->get('MAIL_USERNAME'));
        // Local mail-capture servers (Mailpit/MailHog) typically have no login.
        if ($username === '' && !$localHost) {
            throw new \RuntimeException('MAIL_SMTP_USERNAME_MISSING');
        }
        if ($username !== '' && trim($this->config->get('MAIL_PASSWORD')) === '') {
            throw new \RuntimeException('MAIL_SMTP_PASSWORD_MISSING');
        }
    }

    public function send(string $recipient, string $name, string $subject, string $html, string $text): void
    {
        $this->assertConfigured();
        $mail = new PHPMailer(true);
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->setFrom($this->config->get('MAIL_FROM_ADDRESS'), $this->config->get('MAIL_FROM_NAME'));
        $mail->addAddress($recipient, $name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html;
        $mail->AltBody = $text;

        if ($this->config->get('MAIL_TRANSPORT') === 'log' && $this->config->get('APP_ENV') === 'local') {
            $mail->preSend();
            $path = $this->config->directory . '/storage/mail-' . bin2hex(random_bytes(8)) . '.eml';
            if (file_put_contents($path, $mail->getSentMIMEMessage(), LOCK_EX) === false) {
                throw new \RuntimeException('Cannot write email preview.');
            }
            chmod($path, 0600);
            return;
        }
        if ($this->config->get('MAIL_TRANSPORT') !== 'smtp') {
            throw new \RuntimeException('Invalid mail transport.');
        }
        $encryption = $this->config->get('MAIL_ENCRYPTION');
        if (!in_array($encryption, ['tls', 'ssl', 'none'], true)
            || ($encryption === 'none' && !in_array($this->config->get('MAIL_HOST'), ['localhost', '127.0.0.1', '::1'], true))) {
            throw new \RuntimeException('SMTP requires TLS outside a local test server.');
        }
        $mail->isSMTP();
        $mail->Host = $this->config->get('MAIL_HOST');
        $mail->Port = (int) $this->config->get('MAIL_PORT');
        $mail->SMTPAuth = $this->config->get('MAIL_USERNAME') !== '';
        $mail->Username = $this->config->get('MAIL_USERNAME');
        $mail->Password = $this->config->get('MAIL_PASSWORD');
        $mail->SMTPSecure = $encryption === 'none' ? '' : $encryption;
        $mail->SMTPAutoTLS = $encryption !== 'none';
        $mail->Timeout = 10;
        $mail->Timelimit = 15;
        $mail->send();
    }

    public function sendWelcome(string $email, string $fullName): void
    {
        $url = rtrim($this->config->get('APP_URL'), '/') . '/index.html?auth=login';
        $name = htmlspecialchars($fullName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $link = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $this->send($email, $fullName, 'Chào mừng bạn đến với WealthFlow!',
            '<h1>Chào mừng đến với WealthFlow</h1><p>Xin chào ' . $name . ',</p>'
            . '<p>Tài khoản của bạn đã được tạo thành công. Bạn có thể đăng nhập ngay.</p>'
            . '<p><a href="' . $link . '">Đăng nhập WealthFlow</a></p>'
            . '<p>Cảm ơn bạn đã đồng hành cùng WealthFlow. Email này không chứa mật khẩu của bạn.</p>',
            "Xin chào {$fullName},\nTài khoản WealthFlow của bạn đã được tạo thành công.\nĐăng nhập: {$url}\n");
    }

    public function sendPasswordResetOtp(string $email, string $fullName, string $otp): void
    {
        $name = htmlspecialchars($fullName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $code = htmlspecialchars($otp, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $this->send($email, $fullName, 'Đặt lại mật khẩu WealthFlow',
            '<h1>Đặt lại mật khẩu</h1><p>Xin chào ' . $name . ',</p>'
            . '<p>Mã OTP để đặt lại mật khẩu của bạn là:</p><p style="font-size:28px;font-weight:bold;letter-spacing:8px">' . $code . '</p>'
            . '<p>Mã có hiệu lực trong 10 phút và tối đa 5 lần nhập.</p>'
            . '<p>Nếu bạn không yêu cầu thao tác này, hãy bỏ qua email. Mật khẩu hiện tại của bạn vẫn an toàn.</p>',
            "Xin chào {$fullName},\nMã OTP để đặt lại mật khẩu WealthFlow của bạn là: {$otp}\nMã có hiệu lực trong 10 phút và tối đa 5 lần nhập.\n\nNếu bạn không yêu cầu thao tác này, hãy bỏ qua email.\n");
    }
}
