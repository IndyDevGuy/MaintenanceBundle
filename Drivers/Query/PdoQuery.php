<?php

namespace IndyDevGuy\MaintenanceBundle\Drivers\Query;

use Exception;
use PDO;
use RuntimeException;

abstract class PdoQuery
{
    protected  $db;

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
     * @param $db
     *
     * @return boolean
     */
    abstract function deleteQuery($db):bool;

    /**
     * Result of select query
     *
     * @param $db
     *
     * @return array
     */
    abstract function selectQuery($db): array;

    /**
     * @param int $ttl
     * @param int $start
     * @param $db
     * @return bool
     */
    abstract function insertQuery(int $ttl, int $start, $db): bool;

    /**
     * Initialize pdo connection
     */
    abstract function initDb();

    /**
     * Execute sql
     *
     * @param $db
     * @param string $query Query
     * @param array  $args  Arguments
     *
     * @return boolean
     *
     * @throws RuntimeException
     */
    protected function exec($db, string $query, array $args = array()): bool
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
     * @param $db
     * @param string $query Query
     *
     *
     * @return mixed
     */
    protected function prepareStatement($db, string $query)
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
     * @param $db
     * @param string $query Query
     * @param array  $args  Arguments
     *
     * @return array
     */
    protected function fetch($db, string $query, array $args = array()): array
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
