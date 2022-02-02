<?php

namespace IndyDevGuy\MaintenanceBundle\Drivers\Query;

use Exception;
use PDO;
use PDOStatement;
use RuntimeException;

abstract class PdoQuery
{
    /**
     * @var PDO
     */
    protected PDO $db;

    /**
     * @var array
     */
    protected array $options;

    /**
     * Constructor PdoDriver
     *
     * @param array $options Options driver
     */
    public function __construct(array $options = array())
    {
        $this->options = $options;
    }

    /**
     * Execute create query
     *
     * @return void
     */
    abstract function createTableQuery();

    /**
     * Result of delete query
     *
     * @param PDO $db PDO instance
     *
     * @return boolean
     */
    abstract function deleteQuery(PDO $db):bool;

    /**
     * Result of select query
     *
     * @param PDO $db PDO instance
     *
     * @return array
     */
    abstract function selectQuery(PDO $db): array;

    /**
     * @param int $ttl
     * @param int $start
     * @param PDO $db
     * @return bool
     */
    abstract function insertQuery(int $ttl, int $start, PDO $db): bool;

    /**
     * Initialize pdo connection
     *
     * @return PDO
     */
    abstract function initDb(): PDO;

    /**
     * Execute sql
     *
     * @param PDO $db    PDO instance
     * @param string $query Query
     * @param array  $args  Arguments
     *
     * @return boolean
     *
     * @throws RuntimeException
     */
    protected function exec(PDO $db, string $query, array $args = array()): bool
    {
        $stmt = $this->prepareStatement($db, $query);

        foreach ($args as $arg => $val) {
            $stmt->bindValue($arg, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $success = $stmt->execute();

        if (!$success) {
            throw new RuntimeException(sprintf('Error executing query "%s"', $query));
        }

        return $success;
    }

    /**
     * PrepareStatement
     *
     * @param PDO $db    PDO instance
     * @param string $query Query
     *
     * @return PDOStatement
     *
     * @throws RuntimeException
     */
    protected function prepareStatement(PDO $db, string $query): PDOStatement
    {
        try {
            $stmt = $db->prepare($query);
        } catch (Exception $e) {
            $stmt = false;
        }

        if (false === $stmt) {
            throw new RuntimeException('The database cannot successfully prepare the statement');
        }

        return $stmt;
    }

    /**
     * Fetch All
     *
     * @param PDO $db    PDO instance
     * @param string $query Query
     * @param array  $args  Arguments
     *
     * @return array
     */
    protected function fetch(PDO $db, string $query, array $args = array()): array
    {
        $stmt = $this->prepareStatement($db, $query);

        if (false === $stmt) {
            throw new RuntimeException('The database cannot successfully prepare the statement');
        }

        foreach ($args as $arg => $val) {
            $stmt->bindValue($arg, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
