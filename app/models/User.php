
<?php

class User
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function findById($id)
    {
        $sql = "SELECT * FROM users WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            return null;
        }

        $id = (int) $id;
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $user ?: null;
    }

    public function getProfileById(int $id): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT id, name, email, phone, address, avatar, role, status, created_at
             FROM users
             WHERE id = ?
             LIMIT 1"
        );
        if (!$stmt) {
            throw new RuntimeException("Không thể chuẩn bị truy vấn hồ sơ.");
        }
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể tải hồ sơ tài khoản.");
        }
        $profile = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $profile ?: null;
    }

    public function findByEmail($email)
    {
        $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        $stmt->close();

        return $user ?: null;
    }

    public function create($name, $email, $passwordHash, $phone, $address, bool $emailVerified = true)
    {
        $sql = "INSERT INTO users
                    (name, email, password, phone, address, email_verified)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $verified = $emailVerified ? 1 : 0;
        $stmt->bind_param(
            "sssssi",
            $name,
            $email,
            $passwordHash,
            $phone,
            $address,
            $verified
        );

        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function saveEmailVerificationToken(
        int $userId,
        string $tokenHash
    ): void {
        $stmt = $this->conn->prepare(
            "INSERT INTO email_verification_tokens (user_id, token_hash, expires_at)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 DAY))
             ON DUPLICATE KEY UPDATE token_hash = VALUES(token_hash),
                                     expires_at = DATE_ADD(NOW(), INTERVAL 1 DAY),
                                     created_at = CURRENT_TIMESTAMP"
        );
        if (!$stmt) {
            throw new RuntimeException("Không thể chuẩn bị token xác nhận email.");
        }
        $stmt->bind_param("is", $userId, $tokenHash);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể lưu token xác nhận email.");
        }
        $stmt->close();
    }

    public function hasRecentEmailVerificationToken(int $userId): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT 1
             FROM email_verification_tokens
             WHERE user_id = ?
               AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND)
             LIMIT 1"
        );
        if (!$stmt) {
            throw new RuntimeException("Không thể kiểm tra thời gian gửi email xác nhận.");
        }
        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể kiểm tra thời gian gửi email xác nhận.");
        }
        $recent = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $recent;
    }

    public function savePasswordResetToken(int $userId, string $tokenHash): void
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))
             ON DUPLICATE KEY UPDATE token_hash = VALUES(token_hash),
                                     expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR),
                                     created_at = CURRENT_TIMESTAMP"
        );
        if (!$stmt) {
            throw new RuntimeException("Không thể chuẩn bị liên kết đổi mật khẩu.");
        }
        $stmt->bind_param("is", $userId, $tokenHash);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể lưu liên kết đổi mật khẩu.");
        }
        $stmt->close();
    }

    public function hasRecentPasswordResetToken(int $userId): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT 1
             FROM password_reset_tokens
             WHERE user_id = ?
               AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND)
             LIMIT 1"
        );
        if (!$stmt) {
            throw new RuntimeException("Không thể kiểm tra thời gian gửi email đổi mật khẩu.");
        }
        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể kiểm tra thời gian gửi email đổi mật khẩu.");
        }
        $recent = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $recent;
    }

    public function deletePasswordResetToken(int $userId): void
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM password_reset_tokens WHERE user_id = ?"
        );
        if (!$stmt) {
            throw new RuntimeException("Không thể xóa liên kết đặt lại mật khẩu.");
        }
        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể xóa liên kết đặt lại mật khẩu.");
        }
        $stmt->close();
    }

    public function isPasswordResetTokenValid(string $tokenHash): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT 1
             FROM password_reset_tokens
             WHERE token_hash = ? AND expires_at > NOW()
             LIMIT 1"
        );
        if (!$stmt) {
            throw new RuntimeException("Không thể kiểm tra liên kết đổi mật khẩu.");
        }
        $stmt->bind_param("s", $tokenHash);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể kiểm tra liên kết đổi mật khẩu.");
        }
        $valid = $stmt->get_result()->num_rows === 1;
        $stmt->close();
        return $valid;
    }

    public function updatePasswordWithResetToken(string $tokenHash, string $passwordHash): ?int
    {
        $this->conn->begin_transaction();
        try {
            $find = $this->conn->prepare(
                "SELECT user_id
                 FROM password_reset_tokens
                 WHERE token_hash = ? AND expires_at > NOW()
                 FOR UPDATE"
            );
            if (!$find) {
                throw new RuntimeException("Không thể kiểm tra liên kết đổi mật khẩu.");
            }
            $find->bind_param("s", $tokenHash);
            if (!$find->execute()) {
                $find->close();
                throw new RuntimeException("Không thể kiểm tra liên kết đổi mật khẩu.");
            }
            $token = $find->get_result()->fetch_assoc();
            $find->close();
            if (!$token) {
                $this->conn->rollback();
                return null;
            }

            $userId = (int) $token["user_id"];
            $update = $this->conn->prepare(
                "UPDATE users SET password = ? WHERE id = ?"
            );
            if (!$update) {
                throw new RuntimeException("Không thể chuẩn bị cập nhật mật khẩu.");
            }
            $update->bind_param("si", $passwordHash, $userId);
            if (!$update->execute()) {
                $update->close();
                throw new RuntimeException("Không thể cập nhật mật khẩu.");
            }
            $update->close();

            $delete = $this->conn->prepare(
                "DELETE FROM password_reset_tokens WHERE user_id = ?"
            );
            if (!$delete) {
                throw new RuntimeException("Không thể vô hiệu hóa liên kết đổi mật khẩu.");
            }
            $delete->bind_param("i", $userId);
            if (!$delete->execute()) {
                $delete->close();
                throw new RuntimeException("Không thể vô hiệu hóa liên kết đổi mật khẩu.");
            }
            $delete->close();
            $this->conn->commit();
            return $userId;
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function verifyEmailToken(string $tokenHash): bool
    {
        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare(
                "SELECT user_id
                 FROM email_verification_tokens
                 WHERE token_hash = ? AND expires_at > NOW()
                 FOR UPDATE"
            );
            if (!$stmt) {
                throw new RuntimeException("Không thể kiểm tra token xác nhận email.");
            }
            $stmt->bind_param("s", $tokenHash);
            if (!$stmt->execute()) {
                $stmt->close();
                throw new RuntimeException("Không thể kiểm tra token xác nhận email.");
            }
            $token = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$token) {
                $this->conn->rollback();
                return false;
            }

            $userId = (int) $token["user_id"];
            $update = $this->conn->prepare(
                "UPDATE users SET email_verified = 1 WHERE id = ?"
            );
            if (!$update) {
                throw new RuntimeException("Không thể xác nhận địa chỉ email.");
            }
            $update->bind_param("i", $userId);
            if (!$update->execute()) {
                $update->close();
                throw new RuntimeException("Không thể xác nhận địa chỉ email.");
            }
            $update->close();

            $delete = $this->conn->prepare(
                "DELETE FROM email_verification_tokens WHERE user_id = ?"
            );
            if (!$delete) {
                throw new RuntimeException("Không thể xóa token xác nhận email.");
            }
            $delete->bind_param("i", $userId);
            if (!$delete->execute()) {
                $delete->close();
                throw new RuntimeException("Không thể xóa token xác nhận email.");
            }
            $delete->close();
            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function emailIsInUseByAnotherUser(string $email, int $userId): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT 1
             FROM users
             WHERE email = ? AND id <> ?
             LIMIT 1"
        );
        if (!$stmt) {
            throw new RuntimeException("Không thể kiểm tra địa chỉ email.");
        }
        $stmt->bind_param("si", $email, $userId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể kiểm tra địa chỉ email.");
        }
        $inUse = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $inUse;
    }

    public function updateProfileDetails(
        int $userId,
        string $name,
        string $phone,
        string $address
    ): void {
        $stmt = $this->conn->prepare(
            "UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?"
        );
        if (!$stmt) {
            throw new RuntimeException("Không thể chuẩn bị cập nhật hồ sơ.");
        }
        $stmt->bind_param("sssi", $name, $phone, $address, $userId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể cập nhật thông tin hồ sơ.");
        }
        $stmt->close();
    }

    public function saveEmailChangeToken(
        int $userId,
        string $email,
        string $tokenHash
    ): void {
        $stmt = $this->conn->prepare(
            "INSERT INTO email_change_tokens (user_id, new_email, token_hash, expires_at)
             VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 DAY))
             ON DUPLICATE KEY UPDATE new_email = VALUES(new_email),
                                     token_hash = VALUES(token_hash),
                                     expires_at = DATE_ADD(NOW(), INTERVAL 1 DAY),
                                     created_at = CURRENT_TIMESTAMP"
        );
        if (!$stmt) {
            throw new RuntimeException("Không thể chuẩn bị liên kết xác nhận email mới.");
        }
        $stmt->bind_param("iss", $userId, $email, $tokenHash);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể lưu liên kết xác nhận email mới.");
        }
        $stmt->close();
    }

    public function hasRecentEmailChangeToken(int $userId): bool
    {
        $stmt = $this->conn->prepare(
            "SELECT 1
             FROM email_change_tokens
             WHERE user_id = ?
               AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND)
             LIMIT 1"
        );
        if (!$stmt) {
            throw new RuntimeException("Không thể kiểm tra thời gian gửi email xác nhận.");
        }
        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể kiểm tra thời gian gửi email xác nhận.");
        }
        $recent = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $recent;
    }

    public function getPendingEmailChange(int $userId): ?string
    {
        $stmt = $this->conn->prepare(
            "SELECT new_email
             FROM email_change_tokens
             WHERE user_id = ? AND expires_at > NOW()
             LIMIT 1"
        );
        if (!$stmt) {
            throw new RuntimeException("Không thể tải trạng thái xác nhận email.");
        }
        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể tải trạng thái xác nhận email.");
        }
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ? (string) $result["new_email"] : null;
    }

    public function deleteEmailChangeToken(int $userId): void
    {
        $stmt = $this->conn->prepare(
            "DELETE FROM email_change_tokens WHERE user_id = ?"
        );
        if (!$stmt) {
            throw new RuntimeException("Không thể xóa liên kết xác nhận email.");
        }
        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể xóa liên kết xác nhận email.");
        }
        $stmt->close();
    }

    public function verifyEmailChangeToken(string $tokenHash): ?array
    {
        $this->conn->begin_transaction();
        try {
            $find = $this->conn->prepare(
                "SELECT user_id, new_email
                 FROM email_change_tokens
                 WHERE token_hash = ? AND expires_at > NOW()
                 FOR UPDATE"
            );
            if (!$find) {
                throw new RuntimeException("Không thể kiểm tra liên kết xác nhận email.");
            }
            $find->bind_param("s", $tokenHash);
            if (!$find->execute()) {
                $find->close();
                throw new RuntimeException("Không thể kiểm tra liên kết xác nhận email.");
            }
            $token = $find->get_result()->fetch_assoc();
            $find->close();
            if (!$token) {
                $this->conn->rollback();
                return null;
            }

            $userId = (int) $token["user_id"];
            $email = (string) $token["new_email"];
            $collision = $this->conn->prepare(
                "SELECT 1 FROM users WHERE email = ? AND id <> ? LIMIT 1 FOR UPDATE"
            );
            if (!$collision) {
                throw new RuntimeException("Không thể xác nhận địa chỉ email mới.");
            }
            $collision->bind_param("si", $email, $userId);
            if (!$collision->execute()) {
                $collision->close();
                throw new RuntimeException("Không thể xác nhận địa chỉ email mới.");
            }
            $emailInUse = $collision->get_result()->num_rows > 0;
            $collision->close();
            if ($emailInUse) {
                $delete = $this->conn->prepare(
                    "DELETE FROM email_change_tokens WHERE user_id = ?"
                );
                if (!$delete) {
                    throw new RuntimeException("Không thể xóa liên kết xác nhận email.");
                }
                $delete->bind_param("i", $userId);
                if (!$delete->execute()) {
                    $delete->close();
                    throw new RuntimeException("Không thể xóa liên kết xác nhận email.");
                }
                $delete->close();
                $this->conn->commit();
                return null;
            }

            $update = $this->conn->prepare(
                "UPDATE users SET email = ?, email_verified = 1 WHERE id = ?"
            );
            if (!$update) {
                throw new RuntimeException("Không thể cập nhật địa chỉ email.");
            }
            $update->bind_param("si", $email, $userId);
            if (!$update->execute()) {
                $update->close();
                throw new RuntimeException("Không thể cập nhật địa chỉ email.");
            }
            $update->close();

            $delete = $this->conn->prepare(
                "DELETE FROM email_change_tokens WHERE user_id = ?"
            );
            if (!$delete) {
                throw new RuntimeException("Không thể xóa liên kết xác nhận email.");
            }
            $delete->bind_param("i", $userId);
            if (!$delete->execute()) {
                $delete->close();
                throw new RuntimeException("Không thể xóa liên kết xác nhận email.");
            }
            $delete->close();
            $this->conn->commit();

            return ["id" => $userId, "email" => $email];
        } catch (Throwable $e) {
            $this->conn->rollback();
            if ($e instanceof mysqli_sql_exception && (int) $e->getCode() === 1062) {
                return null;
            }
            throw $e;
        }
    }

    public function markEmailVerified(int $userId): void
    {
        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare(
                "UPDATE users SET email_verified = 1 WHERE id = ?"
            );
            if (!$stmt) {
                throw new RuntimeException("Không thể xác nhận địa chỉ email.");
            }
            $stmt->bind_param("i", $userId);
            if (!$stmt->execute()) {
                $stmt->close();
                throw new RuntimeException("Không thể xác nhận địa chỉ email.");
            }
            $stmt->close();

            $delete = $this->conn->prepare(
                "DELETE FROM email_verification_tokens WHERE user_id = ?"
            );
            if (!$delete) {
                throw new RuntimeException("Không thể xóa token xác nhận email.");
            }
            $delete->bind_param("i", $userId);
            if (!$delete->execute()) {
                $delete->close();
                throw new RuntimeException("Không thể xóa token xác nhận email.");
            }
            $delete->close();
            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function updateGoogleAvatar(int $userId, string $avatarUrl): void
    {
        $stmt = $this->conn->prepare(
            "UPDATE users SET avatar = ? WHERE id = ?"
        );
        if (!$stmt) {
            throw new RuntimeException("Không thể lưu ảnh đại diện Google.");
        }
        $stmt->bind_param("si", $avatarUrl, $userId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException("Không thể lưu ảnh đại diện Google.");
        }
        $stmt->close();
    }
}
