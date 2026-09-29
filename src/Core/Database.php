<?php

declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;
use PDO;
use PDOException;
use Throwable;

final class Database
{
    private array $connections = [];

    public function __construct(private readonly array $config = [])
    {
    }

    public function connection(?string $name = null): PDO
    {
        $name ??= (string) ($this->config['default'] ?? 'mysql');

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        $connection = $this->config['connections'][$name] ?? null;

        if (!is_array($connection)) {
            throw new InvalidArgumentException("La conexiÃƒÂ³n de base de datos [{$name}] no estÃƒÂ¡ definida en config/database.php");
        }

        return $this->connections[$name] = $this->createPdo($connection);
    }

    public function select(string $sql, array $params = [], ?string $name = null): array
    {
        $statement = $this->connection($name)->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function selectOne(string $sql, array $params = [], ?string $name = null): ?array
    {
        $statement = $this->connection($name)->prepare($sql);
        $statement->execute($params);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function scalar(string $sql, array $params = [], ?string $name = null): mixed
    {
        $statement = $this->connection($name)->prepare($sql);
        $statement->execute($params);

        $value = $statement->fetchColumn();

        return $value === false ? null : $value;
    }

    public function statement(string $sql, array $params = [], ?string $name = null): bool
    {
        return $this->connection($name)->prepare($sql)->execute($params);
    }

    public function affectedRows(string $sql, array $params = [], ?string $name = null): int
    {
        $statement = $this->connection($name)->prepare($sql);
        $statement->execute($params);

        return $statement->rowCount();
    }

    public function insert(string $sql, array $params = [], ?string $name = null): int
    {
        $this->statement($sql, $params, $name);

        return (int) $this->connection($name)->lastInsertId();
    }

    public function transaction(callable $callback, ?string $name = null): mixed
    {
        $pdo = $this->connection($name);
        $ownsTransaction = !$pdo->inTransaction();

        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $result = $callback($pdo, $this);
        } catch (Throwable $exception) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }

        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->commit();
        }

        return $result;
    }

    public function ping(?string $name = null): bool
    {
        try {
            return (bool) $this->scalar('SELECT 1', [], $name);
        } catch (PDOException) {
            return false;
        }
    }

    public function serverVersion(?string $name = null): string
    {
        return (string) $this->scalar('SELECT VERSION()', [], $name);
    }

    public function databaseName(?string $name = null): string
    {
        return (string) $this->scalar('SELECT DATABASE()', [], $name);
    }

    public function name(?string $name = null): string
    {
        $name ??= (string) ($this->config['default'] ?? 'mysql');

        return (string) ($this->config['connections'][$name]['database'] ?? '');
    }

    public function host(?string $name = null): string
    {
        $name ??= (string) ($this->config['default'] ?? 'mysql');

        return (string) ($this->config['connections'][$name]['host'] ?? '');
    }

    public function port(?string $name = null): int
    {
        $name ??= (string) ($this->config['default'] ?? 'mysql');

        return (int) ($this->config['connections'][$name]['port'] ?? 0);
    }

    public function charset(?string $name = null): string
    {
        $name ??= (string) ($this->config['default'] ?? 'mysql');

        return (string) ($this->config['connections'][$name]['charset'] ?? '');
    }

    public function prefix(?string $name = null): string
    {
        $name ??= (string) ($this->config['default'] ?? 'mysql');

        return (string) ($this->config['connections'][$name]['prefix'] ?? '');
    }

    public function disconnect(?string $name = null): void
    {
        $name ??= (string) ($this->config['default'] ?? 'mysql');

        unset($this->connections[$name]);
    }

    private function createPdo(array $config): PDO
    {
        $driver = (string) ($config['driver'] ?? 'mysql');

        if ($driver !== 'mysql') {
            throw new InvalidArgumentException("El driver [{$driver}] no estÃƒÂ¡ soportado por esta base.");
        }

        $charset = (string) ($config['charset'] ?? 'utf8mb4');
        $collation = (string) ($config['collation'] ?? 'utf8mb4_unicode_ci');

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'] ?? '127.0.0.1',
            (int) ($config['port'] ?? 3306),
            $config['database'] ?? '',
            $charset
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ];

        if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES {$charset} COLLATE {$collation}";
        }

        $options = array_replace($options, $config['options'] ?? []);

        return new PDO(
            $dsn,
            (string) ($config['username'] ?? 'root'),
            (string) ($config['password'] ?? ''),
            $options
        );
    }
}
