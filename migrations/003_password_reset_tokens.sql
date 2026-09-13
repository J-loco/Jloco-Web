-- Password reset links sent by email (src/Service/PasswordResetService.php).
-- Only a SHA-256 of the token is stored; one pending link per account, valid 1 hour, single use.
CREATE TABLE IF NOT EXISTS `website_password_resets` (
    `selector`   CHAR(24)    NOT NULL,
    `token_hash` CHAR(64)    NOT NULL,
    `account`    INT(11)     NOT NULL,
    `expires_at` DATETIME    NOT NULL,
    PRIMARY KEY (`selector`),
    KEY `idx_account` (`account`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
