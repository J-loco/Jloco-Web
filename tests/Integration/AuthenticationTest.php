<?php

declare(strict_types=1);

namespace JLoco\Web\Tests\Integration;

use PDOException;
use JLoco\Web\Migration\Migrator;
use JLoco\Web\Migration\WebUserProvisioner;
use JLoco\Web\Repository\AccountRepository;
use JLoco\Web\Security\PasswordHasher;
use JLoco\Web\Security\Throttle;
use JLoco\Web\Service\AuthService;
use JLoco\Web\Service\PasswordResetService;
use JLoco\Web\Service\VoteService;
use JLoco\Web\Tests\Unit\Security\PasswordHasherTest;

/** Registration, login, password changes, resets, votes, migrations and database grants. */
final class AuthenticationTest extends IntegrationTestCase
{
    private const array REGISTRATION = [
        'username' => 'jean.pierre',
        'email' => 'jp@example.com',
        'password' => 'Secret123',
        'password_confirm' => 'Secret123',
        'question' => 'Ville préférée ?',
        'answer' => 'Astrub',
        'captcha' => 'abcdef',
        'terms' => true,
    ];

    public function testRegistrationValidatesAndCreatesALoginServerCompatibleAccount(): void
    {
        $auth = $this->container()->get(AuthService::class);

        $_SESSION['_captcha'] = 'ABCDEF';
        $errors = $auth->register(['username' => 'jean_pierre', 'password' => 'Secrèt', 'question' => '😀😀', 'captcha' => 'nope', 'terms' => false] + self::REGISTRATION);
        self::assertSame(['username', 'password', 'question', 'terms', 'captcha'], array_keys($errors));
        self::assertArrayNotHasKey('_captcha', $_SESSION, 'captcha consumed even on failure');

        $_SESSION['_captcha'] = 'ABCDEF';
        self::assertSame([], $auth->register(self::REGISTRATION));
        self::assertSame(PasswordHasherTest::LEGACY_VECTOR, $this->scalar($this->login(), 'SELECT pass FROM world_accounts WHERE account = ?', ['jean.pierre']), 'legacy scheme by default');

        $_SESSION['_captcha'] = 'ABCDEF';
        self::assertSame(['username' => 'Ce nom de compte est déjà pris.'], $auth->register(self::REGISTRATION));
    }

    public function testLoginThrottlesAfterRepeatedFailures(): void
    {
        $this->createAccount('alice', PasswordHasher::legacy('Secret123'));
        $auth = $this->container()->get(AuthService::class);

        for ($i = 0; $i < Throttle::MAX_FAILURES; $i++) {
            self::assertSame('Nom de compte ou mot de passe incorrect.', $auth->attempt('alice', 'wrong', false));
        }
        self::assertSame(Throttle::message(), $auth->attempt('alice', 'Secret123', false), 'blocked even with the right password');

        $otherIp = $this->container([], '203.0.113.50')->get(AuthService::class);
        self::assertNull($otherIp->attempt('alice', 'Secret123', false), 'the block is per IP');
        self::assertSame('alice', $otherIp->account()?->name);
    }

    public function testLoginUpgradesLegacyHashesWhenPbkdf2IsEnabled(): void
    {
        $id = $this->createAccount('alice', PasswordHasher::legacy('Secret123'));

        $legacy = $this->container()->get(AuthService::class);
        self::assertNull($legacy->attempt('alice', 'Secret123', false));
        self::assertSame(PasswordHasherTest::LEGACY_VECTOR, $this->scalar($this->login(), 'SELECT pass FROM world_accounts WHERE guid = ?', [$id]));

        $pbkdf2 = $this->container(['passwordHashScheme' => PasswordHasher::SCHEME_PBKDF2])->get(AuthService::class);
        self::assertNull($pbkdf2->attempt('alice', 'Secret123', false));
        $hash = (string) $this->scalar($this->login(), 'SELECT pass FROM world_accounts WHERE guid = ?', [$id]);
        self::assertStringStartsWith('pbkdf2_sha512$210000$', $hash);

        $_SESSION = [];
        self::assertNull($pbkdf2->attempt('alice', 'Secret123', false), 'still logs in with the upgraded hash');
    }

    public function testPasswordChangeWithSecretAnswer(): void
    {
        $id = $this->createAccount('alice', PasswordHasher::legacy('Secret123'));
        $auth = $this->container()->get(AuthService::class);

        self::assertSame('La réponse secrète est incorrecte.', $auth->changePassword($id, 'rouge', 'Secret456', 'Secret456'));
        self::assertSame('Les mots de passe ne sont pas identiques.', $auth->changePassword($id, 'bleu', 'Secret456', 'Secret457'));
        self::assertNull($auth->changePassword($id, 'bleu', 'Secret456', 'Secret456'));
        self::assertSame(PasswordHasher::legacy('Secret456'), $this->scalar($this->login(), 'SELECT pass FROM world_accounts WHERE guid = ?', [$id]));
    }

    public function testPasswordResetByEmailLink(): void
    {
        $id = $this->createAccount('alice', PasswordHasher::legacy('Secret123'));
        $this->createAccount('nomail', '', ['email' => null]);
        $resets = $this->container(['mailerDsn' => 'null://null', 'mailFrom' => 'noreply@example.com'])->get(PasswordResetService::class);

        self::assertTrue($resets->request('alice'));
        self::assertTrue($resets->request('nomail'), 'same answer whether the account can receive mail or not');
        self::assertTrue($resets->request('nobody'));
        self::assertSame(1, (int) $this->scalar($this->login(), 'SELECT COUNT(*) FROM website_password_resets'));

        // The token itself only exists in the email: store a known one to follow the link.
        $this->login()->exec('DELETE FROM website_password_resets');
        $this->login()->prepare('INSERT INTO website_password_resets (selector, token_hash, account, expires_at) VALUES (?, ?, ?, NOW() + INTERVAL 10 MINUTE)')
            ->execute([str_repeat('a', 24), hash('sha256', str_repeat('b', 64)), $id]);

        self::assertNull($resets->accountForLink(str_repeat('a', 24), str_repeat('c', 64)), 'wrong token');
        self::assertSame($id, $resets->accountForLink(str_repeat('a', 24), str_repeat('b', 64)));
        self::assertNotNull($resets->reset(str_repeat('a', 24), str_repeat('b', 64), 'short', 'short'));
        self::assertNull($resets->reset(str_repeat('a', 24), str_repeat('b', 64), 'Secret789', 'Secret789'));
        self::assertSame(PasswordHasher::legacy('Secret789'), $this->scalar($this->login(), 'SELECT pass FROM world_accounts WHERE guid = ?', [$id]));
        self::assertNull($resets->accountForLink(str_repeat('a', 24), str_repeat('b', 64)), 'single use');
    }

    public function testExpiredResetLinksAreRejected(): void
    {
        $id = $this->createAccount('alice');
        // Database clock (UTC), like the application: PHP runs in Europe/Paris.
        $this->login()->prepare('INSERT INTO website_password_resets (selector, token_hash, account, expires_at) VALUES (?, ?, ?, NOW() - INTERVAL 1 MINUTE)')
            ->execute([str_repeat('d', 24), hash('sha256', str_repeat('e', 64)), $id]);

        $resets = $this->container(['mailerDsn' => 'null://null', 'mailFrom' => 'noreply@example.com'])->get(PasswordResetService::class);
        self::assertNull($resets->accountForLink(str_repeat('d', 24), str_repeat('e', 64)));
    }

    public function testVotesAreLimitedPerAccountAndPerIp(): void
    {
        $alice = $this->createAccount('alice');
        $bob = $this->createAccount('bob');
        $accounts = new AccountRepository($this->database);
        $votes = $this->container()->get(VoteService::class);
        $now = time();
        $aliceAccount = $accounts->find($alice);
        $bobAccount = $accounts->find($bob);
        self::assertNotNull($aliceAccount);
        self::assertNotNull($bobAccount);

        self::assertTrue($votes->vote($aliceAccount, $now));
        self::assertFalse($votes->vote($aliceAccount, $now + 60), 'the cooldown is also enforced in SQL');
        self::assertFalse($votes->vote($bobAccount, $now + 60), 'same IP');
        self::assertSame(VoteService::COOLDOWN - 60, $votes->secondsUntilNextVote($bobAccount, $now + 60));

        $otherIp = $this->container([], '203.0.113.99')->get(VoteService::class);
        self::assertTrue($otherIp->vote($bobAccount, $now + 60));
    }

    public function testMigrationsAreRecordedAndIdempotent(): void
    {
        $applied = new Migrator(['login' => $this->login(), 'game' => $this->game()], dirname(__DIR__, 2) . '/migrations', static function (string $message): void {
        })->run();

        self::assertSame(0, $applied);
        self::assertSame(count(glob(dirname(__DIR__, 2) . '/migrations/*.sql') ?: []), (int) $this->scalar($this->login(), 'SELECT COUNT(*) FROM website_migrations'));
    }

    public function testPortalDatabaseUserOnlyGetsTheGrantsItNeeds(): void
    {
        $user = 'jloco_web_test';
        $password = bin2hex(random_bytes(12));
        $admin = $this->login();

        try {
            new WebUserProvisioner($admin)->provision($user, $password, $this->config->loginDbName, [$this->config->gameDbName]);
            $portal = $this->database->open($this->config->loginDbName, $user, $password);

            $portal->query('SELECT COUNT(*) FROM world_players')->fetchColumn();
            $portal->exec("INSERT INTO website_auth_attempts (ip, action, attempted_at) VALUES ('1.2.3.4', 'login', NOW())");
            $this->database->open($this->config->gameDbName, $user, $password)->exec("INSERT INTO gifts (id, objects) VALUES (1, '')");

            foreach (['DELETE FROM world_accounts', 'DROP TABLE website_auth_attempts', 'UPDATE world_players SET kamas = 0', 'CREATE TABLE x (id INT)'] as $forbidden) {
                try {
                    $portal->exec($forbidden);
                    self::fail("The portal user must not be allowed to run: $forbidden");
                } catch (PDOException $e) {
                    self::assertStringContainsString('denied', $e->getMessage());
                }
            }
        } finally {
            $admin->exec("DROP USER IF EXISTS '$user'@'%'");
        }
    }
}
