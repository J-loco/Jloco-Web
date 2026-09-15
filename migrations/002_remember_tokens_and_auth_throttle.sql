-- Remember-me tokens (include/auth.php) and brute-force throttle for login, password reset, secret answer.
-- JLoco-Web "remember me": one row per issued cookie (selector:validator).
-- Only a SHA-256 of the validator is stored; tokens are single-use and rotated.
CREATE TABLE IF NOT EXISTS `website_remember_tokens` (
    `selector`       CHAR(24)    NOT NULL,
    `validator_hash` CHAR(64)    NOT NULL,
    `account`        INT(11)     NOT NULL,
    `expires_at`     DATETIME    NOT NULL,
    PRIMARY KEY (`selector`),
    KEY `idx_account` (`account`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- JLoco-Web brute-force throttle: failed login / password-reset / secret-answer attempts.
CREATE TABLE IF NOT EXISTS `website_auth_attempts` (
    `id`           BIGINT      NOT NULL AUTO_INCREMENT,
    `ip`           VARCHAR(45) NOT NULL,
    `action`       VARCHAR(32) NOT NULL,
    `attempted_at` DATETIME    NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_ip_action_time` (`ip`, `action`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
