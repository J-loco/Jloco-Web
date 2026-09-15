<?php

declare(strict_types=1);

namespace JLoco\Web\Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;
use JLoco\Web\Config;
use JLoco\Web\Container;
use JLoco\Web\Database;
use JLoco\Web\Http\Request;
use JLoco\Web\Migration\Migrator;
use JLoco\Web\Tests\Support\TestConfig;
use JLoco\Web\View\TwigFactory;
use Twig\Environment;

/**
 * Base class for tests against a real MariaDB (TEST_DB_HOST, TEST_DB_USER, TEST_DB_PASS).
 *
 * The first test of a run (re)creates jloco_login_test / jloco_game_test from
 * tests/Integration/schema/*.sql and applies migrations/*.sql; every test starts with empty tables.
 */
abstract class IntegrationTestCase extends TestCase
{
    private static bool $schemaReady = false;

    protected Config $config;
    protected Database $database;

    protected function setUp(): void
    {
        if (getenv('TEST_DB_HOST') === false) {
            self::markTestSkipped('Integration tests need TEST_DB_HOST (docker compose run --rm jloco_web_tools test).');
        }
        $this->config = TestConfig::make();
        if (!self::$schemaReady) {
            self::createSchema($this->config);
            self::$schemaReady = true;
        }
        $this->database = new Database($this->config);
        $this->truncateAll();
        $_SESSION = [];
    }

    /**
     * A container wired like the application, for the given request and config overrides.
     *
     * @param array<string, mixed> $configOverrides
     */
    protected function container(array $configOverrides = [], string $ip = '198.51.100.7'): Container
    {
        $container = new Container();
        $config = $configOverrides === [] ? $this->config : TestConfig::make($configOverrides);
        $container->set(Config::class, $config);
        $container->set(Database::class, $configOverrides === [] ? $this->database : new Database($config));
        $container->set(Request::class, new Request('POST', '/', [], [], [], ['REMOTE_ADDR' => $ip]));
        $container->factory(Environment::class, static fn (Container $c): Environment => TwigFactory::create($c));
        return $container;
    }

    protected function login(): PDO
    {
        return $this->database->login();
    }

    protected function game(): PDO
    {
        return $this->database->game();
    }

    /** @param array<string, scalar|null> $row */
    protected function insert(PDO $pdo, string $table, array $row): int
    {
        $columns = implode(', ', array_map(static fn (string $c): string => "`$c`", array_keys($row)));
        $placeholders = implode(', ', array_fill(0, count($row), '?'));
        $pdo->prepare("INSERT INTO `$table` ($columns) VALUES ($placeholders)")->execute(array_values($row));
        return (int) $pdo->lastInsertId();
    }

    /** @param array<string, scalar|null> $extra */
    protected function createAccount(string $name = 'alice', string $passwordHash = '', array $extra = []): int
    {
        return $this->insert($this->login(), 'world_accounts', $extra + [
            'account' => $name,
            'pass' => $passwordHash,
            'email' => $name . '@example.com',
            'question' => 'Couleur ?',
            'reponse' => 'bleu',
            'pseudo' => ucfirst($name),
            'points' => 0,
        ]);
    }

    /** @param list<scalar|null> $params */
    protected function scalar(PDO $pdo, string $sql, array $params = []): mixed
    {
        $query = $pdo->prepare($sql);
        $query->execute($params);
        return $query->fetchColumn();
    }

    private function truncateAll(): void
    {
        foreach ([$this->login(), $this->game()] as $pdo) {
            foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
                if ($table !== 'website_migrations') {
                    $pdo->exec("TRUNCATE TABLE `$table`");
                }
            }
        }
    }

    private static function createSchema(Config $config): void
    {
        $server = new PDO(
            sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $config->dbHost, $config->dbPort),
            $config->dbUser,
            $config->dbPass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
        foreach (['login' => $config->loginDbName, 'game' => $config->gameDbName] as $kind => $name) {
            $server->exec("DROP DATABASE IF EXISTS `$name`");
            $server->exec("CREATE DATABASE `$name`");
            $server->exec("USE `$name`");
            foreach (Migrator::statements((string) file_get_contents(__DIR__ . "/schema/$kind.sql")) as $statement) {
                $server->exec($statement);
            }
        }

        $database = new Database($config);
        new Migrator(['login' => $database->login(), 'game' => $database->game()], dirname(__DIR__, 2) . '/migrations', static function (string $message): void {
        })->run();
    }
}
