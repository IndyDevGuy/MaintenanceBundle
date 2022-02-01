<?php

namespace IndyDevGuy\MaintenanceBundle\Drivers;

use Datetime;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Exception;
use IndyDevGuy\MaintenanceBundle\Drivers\Query\DefaultQuery;
use IndyDevGuy\MaintenanceBundle\Drivers\Query\DsnQuery;
use IndyDevGuy\MaintenanceBundle\Drivers\Query\PdoQuery;

class DatabaseDriver extends AbstractDriver implements DriverTTLInterface
{
    /**
     * @var Registry|null
     */
    protected ?Registry $doctrine;

    /**
     * @var array
     */
    protected array $options;

    /**
     * @var string
     */
    protected string $db;

    /**
     *
     * @var PdoQuery|DsnQuery|DefaultQuery
     */
    protected $pdoDriver;

    /**
     * Constructor
     *
     * @param Registry|null $doctrine The registry
     */
    public function __construct(Registry $doctrine = null)
    {
        $this->doctrine = $doctrine;
        parent::__construct();
    }

    /**
     * Set options from configuration
     *
     * @param array $options Options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        if (isset($this->options['dsn'])) {
            $this->pdoDriver = new DsnQuery($this->options);
        } else {
            if (isset($this->options['connection'])) {
                $this->pdoDriver = new DefaultQuery($this->doctrine->getManager($this->options['connection']));
            } else {
                $this->pdoDriver = new DefaultQuery($this->doctrine->getManager());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createLock():bool
    {
        $db = $this->pdoDriver->initDb();

        try {
            $ttl = null;
            if (isset($this->options['ttl']) && $this->options['ttl'] !== 0) {
                $now = new Datetime('now');
                $ttl = $this->options['ttl'];
                $ttl = $now->modify(sprintf('+%s seconds', $ttl))->format('Y-m-d H:i:s');
            }
            $status = $this->pdoDriver->insertQuery($ttl, $db);
        } catch (Exception $e) {
            $status = false;
        }

        return $status;
    }

    /**
     * {@inheritdoc}
     */
    protected function createUnlock():bool
    {
        $db = $this->pdoDriver->initDb();

        try {
            $status = $this->pdoDriver->deleteQuery($db);
        } catch (Exception $e) {
            $status = false;
        }

        return $status;
    }

    /**
     * @throws Exception
     */
    public function getTtlDate(): ?Datetime
    {
        $db = $this->pdoDriver->initDb();
        $data = $this->pdoDriver->selectQuery($db);
        if (!$data) {
            return null;
        }
        if (null !== $data[0]['ttl']) {
            return new Datetime($data[0]['ttl']);
        }
        return null;
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function isExists():bool
    {
        $db = $this->pdoDriver->initDb();
        $data = $this->pdoDriver->selectQuery($db);

        if (!$data) {
            return false;
        }

        if (null !== $data[0]['ttl']) {
            $now = new DateTime('now');
            $ttl = new DateTime($data[0]['ttl']);

            if ($ttl < $now) {
                return $this->createUnlock();
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageLock(bool $resultTest):string
    {
        $key = $resultTest ? 'idg_maintenance.success_lock_database' : 'idg_maintenance.not_success_lock';

        return $this->translator->trans($key, array(), 'maintenance');
    }

    /**
     * {@inheritDoc}
     */
    public function getMessageUnlock(bool $resultTest):String
    {
        $key = $resultTest ? 'idg_maintenance.success_unlock' : 'idg_maintenance.not_success_unlock';

        return $this->translator->trans($key, array(), 'maintenance');
    }

    /**
     * {@inheritdoc}
     */
    public function setTtl(int $value)
    {
        $this->options['ttl'] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getTtl():int
    {
        return $this->options['ttl'];
    }

    /**
     * {@inheritdoc}
     */
    public function hasTtl(): bool
    {
        return isset($this->options['ttl']);
    }
}