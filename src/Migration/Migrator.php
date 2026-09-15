<?php

declare(strict_types=1);

namespace JLoco\Web\Migration;

use PDO;
use RuntimeException;

/**
 * Applies migrations/*.sql in name order, once each, recording them in website_migrations (login DB).
 *
 * A file runs against the login database unless its first line is "-- database: game".
 * Write migrations so they can safely re-run (IF NOT EXISTS / IF EXISTS): databases created
 * before the runner existed may already contain their changes.
 */
final readonly class Migrator
{
    /**
     * @param array{login: PDO, game: PDO} $connections
     * @param \Closure(string): void $log
     */
    public function __construct(
        private array $connections,
        private string $directory,
        private \Closure $log,
    ) {
    }

    /** @return int number of migrations applied */
    public function run(): int
    {
        $login = $this->connections['login'];
        $login->exec('CREATE TABLE IF NOT EXISTS `website_migrations` (
            `name` VARCHAR(191) NOT NULL,
            `applied_at` DATETIME NOT NULL,
            PRIMARY KEY (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $applied = $login->query('SELECT name FROM website_migrations')->fetchAll(PDO::FETCH_COLUMN);
        $files = glob($this->directory . '/*.sql') ?: [];
        sort($files, SORT_STRING);

        $count = 0;
        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $applied, true)) {
                continue;
            }

            $sql = (string) file_get_contents($file);
            $target = preg_match('/\A--\s*database:\s*game\b/i', $sql) ? 'game' : 'login';

            ($this->log)("applying $name ($target)");
            foreach (self::statements($sql) as $statement) {
                $this->connections[$target]->exec($statement);
            }

            $login->prepare('INSERT INTO website_migrations (name, applied_at) VALUES (?, NOW())')->execute([$name]);
            $count++;
        }

        return $count;
    }

    /**
     * Splits a plain SQL file on ";" at end of line, dropping "--" comment lines.
     *
     * @return list<string>
     */
    public static function statements(string $sql): array
    {
        $lines = array_filter(preg_split('/\R/', $sql), fn (string $line): bool => !preg_match('/^\s*--/', $line));
        $statements = array_map(trim(...), preg_split('/;\s*$/m', implode("\n", $lines)));

        $statements = array_values(array_filter($statements, fn (string $s): bool => $s !== ''));
        if ($statements === []) {
            throw new RuntimeException('Empty migration');
        }
        return $statements;
    }
}
