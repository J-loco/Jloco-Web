<?php

declare(strict_types=1);

namespace JLoco\Web\Tests\Support;

use JLoco\Web\Config;
use JLoco\Web\Security\PasswordHasher;

/** Builds a Config for tests without touching the environment. */
final class TestConfig
{
    /** @param array<string, mixed> $overrides constructor argument name => value */
    public static function make(array $overrides = []): Config
    {
        $defaults = [
            'appUrl' => 'http://localhost/dofus/',
            'debug' => true,
            'trustCloudflare' => false,
            'siteName' => 'JLoco',
            'adminAccountId' => 1,
            'dbHost' => (string) (getenv('TEST_DB_HOST') ?: '127.0.0.1'),
            'dbPort' => (int) (getenv('TEST_DB_PORT') ?: 3306),
            'dbUser' => (string) (getenv('TEST_DB_USER') ?: 'root'),
            'dbPass' => (string) (getenv('TEST_DB_PASS') ?: ''),
            'loginDbName' => 'jloco_login_test',
            'gameDbName' => 'jloco_game_test',
            'loginServerHost' => '127.0.0.1',
            'loginServerPort' => 1,
            'gameServerHost' => '127.0.0.1',
            'gameServerPort' => 1,
            'gameServerId' => 601,
            'forumUrl' => '',
            'forumRssUrl' => '',
            'downloadUrl' => '',
            'voteUrl' => 'https://vote.example/',
            'votePoints' => 5,
            'dedipassPublicKey' => '',
            'shopServers' => [1 => 'jloco_game_test'],
            'passwordHashScheme' => PasswordHasher::SCHEME_LEGACY,
            'mailerDsn' => '',
            'mailFrom' => '',
        ];
        return new Config(...array_merge($defaults, $overrides));
    }
}
