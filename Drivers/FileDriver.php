<?php

namespace IndyDevGuy\MaintenanceBundle\Drivers;

use DateTime;
use Exception;
use InvalidArgumentException;

class FileDriver extends AbstractDriver
{
    protected $filePath;

    /**
     * Constructor
     *
     * @param array $options    Options driver
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);

        if ( ! isset($options['file_path'])) {
            throw new InvalidArgumentException('$options[\'file_path\'] cannot be defined if Driver File configuration is used');
        }
        if (null !== $options) {
            $this->filePath = $options['file_path'];
        }

        $this->options = $options;
    }

    /**
     * {@inheritDoc}
     */
    protected function createLock():bool
    {
        return (fopen($this->filePath, 'w+'));
    }

    /**
     * {@inheritDoc}
     */
    protected function createUnlock():bool
    {
        return @unlink($this->filePath);
    }

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function isExists():bool
    {
        if (file_exists($this->filePath)) {
            if (isset($this->options['ttl']) && is_numeric($this->options['ttl'])) {
                $this->isEndTime($this->options['ttl']);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Test if time to life is expired
     *
     * @param integer $timeTtl The ttl value
     *
     * @return boolean
     * @throws Exception
     */
    public function isEndTime(int $timeTtl):bool
    {
        $now = new DateTime('now');
        $accessTime = date("Y-m-d H:i:s.", filemtime($this->filePath));
        $accessTime = new DateTime($accessTime);
        $accessTime->modify(sprintf('+%s seconds', $timeTtl));

        if ($accessTime < $now) {
            return $this->createUnlock();
        } else {
            return true;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getMessageLock(bool $resultTest):string
    {
        $key = $resultTest ? 'idg_maintenance.success_lock_file' : 'idg_maintenance.not_success_lock';

        return $this->translator->trans($key, array(), 'maintenance');
    }

    /**
     * {@inheritDoc}
     */
    public function getMessageUnlock(bool $resultTest):string
    {
        $key = $resultTest ? 'idg_maintenance.success_unlock' : 'idg_maintenance.not_success_unlock';

        return $this->translator->trans($key, array(), 'maintenance');
    }
}