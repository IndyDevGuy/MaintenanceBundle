<?php

namespace IndyDevGuy\MaintenanceBundle\Drivers\Query;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;

class DefaultQuery extends PdoQuery
{
    /**
     * @var EntityManager
     */
    protected $em;

    const NAME_TABLE   = 'idg_maintenance';

    /**
     * @param ObjectManager $em Entity Manager
     */
    public function __construct(ObjectManager $em)
    {
        $this->em = $em;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function initDb()
    {
        if (null === $this->db) {
            $db = $this->em->getConnection();
            $this->db = $db;
            $this->createTableQuery();
        }

        return $this->db;
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function createTableQuery()
    {
        $type = $this->em->getConnection()->getDatabasePlatform()->getName() != 'mysql' ? 'timestamp' : 'datetime';

        $this->db->exec(
            sprintf('CREATE TABLE IF NOT EXISTS %s (ttl %s DEFAULT NULL)', self::NAME_TABLE, $type)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function deleteQuery($db)
    {
        return $this->exec($db, sprintf('DELETE FROM %s', self::NAME_TABLE));
    }

    /**
     * {@inheritdoc}
     */
    public function selectQuery($db)
    {
        return $this->fetch($db, sprintf('SELECT ttl FROM %s', self::NAME_TABLE));
    }

    /**
     * {@inheritdoc}
     */
    public function insertQuery($ttl, $db)
    {
        return $this->exec(
            $db, sprintf('INSERT INTO %s (ttl) VALUES (:ttl)',
            self::NAME_TABLE),
            array(':ttl' => $ttl)
        );
    }
}