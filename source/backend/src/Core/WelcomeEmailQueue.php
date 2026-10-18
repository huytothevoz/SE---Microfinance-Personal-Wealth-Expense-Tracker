<?php

declare(strict_types=1);

namespace WealthFlow\Core;

use PDO;
use Throwable;

final class WelcomeEmailQueue
{
    public function __construct(private readonly PDO $db, private readonly EmailService $mailer) {}

    /** Durable retry: SMTP failure must not undo an already created account. */
    public function deliver(int $userId): bool
    {
        $this->db->beginTransaction();
        try {
            $query = $this->db->prepare('SELECT e.id, e.status, u.email, u.full_name FROM registration_emails e JOIN users u ON u.id = e.user_id WHERE e.user_id = ? FOR UPDATE');
            $query->execute([$userId]);
            $row = $query->fetch();
            if (!$row || $row['status'] === 'SENT') {
                $this->db->commit();
                return true;
            }
            $sent = false;
            try {
                $this->mailer->sendWelcome($row['email'], $row['full_name']);
                $sent = true;
            } catch (Throwable $error) {
                // Never log SMTP credentials, message body, password or tokens.
                error_log('WealthFlow welcome email deferred; user=' . $userId . '; type=' . get_class($error));
            }
            $update = $this->db->prepare("UPDATE registration_emails SET attempts = attempts + 1, status = ?, sent_at = ? WHERE id = ?");
            $update->execute([$sent ? 'SENT' : 'PENDING', $sent ? gmdate('Y-m-d H:i:s') : null, $row['id']]);
            $this->db->commit();
            return $sent;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('WealthFlow welcome queue deferred; type=' . get_class($error));
            return false;
        }
    }
}
