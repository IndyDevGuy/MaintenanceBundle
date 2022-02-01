<?php

namespace IndyDevGuy\MaintenanceBundle\Drivers;

use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractDriver
{
    /**
     * @var array
     */
    protected array $options;

    /**
     * @var TranslatorInterface
     */
    protected TranslatorInterface $translator;

    /**
     * Constructor
     *
     * @param array $options Array of options
     */
    public function __construct(array $options = array())
    {
        $this->options = $options;
    }

    /**
     * Test if object exists
     *
     * @return boolean
     */
    abstract public function isExists(): bool;

    /**
     * Result of creation of lock
     *
     * @return boolean
     */
    abstract protected function createLock(): bool;

    /**
     * Result of create unlock
     *
     * @return boolean
     */
    abstract protected function createUnlock(): bool;

    /**
     * The feedback message
     *
     * @param boolean $resultTest The result of lock
     *
     * @return string
     */
    abstract public function getMessageLock(bool $resultTest): string;

    /**
     * The feedback message
     *
     * @param boolean $resultTest The result of unlock
     *
     * @return string
     */
    abstract public function getMessageUnlock(bool $resultTest): string;

    /**
     * The response of lock
     *
     * @return boolean
     */
    public function lock(): bool
    {
        if (!$this->isExists()) {
            return $this->createLock();
        } else {
            return false;
        }
    }

    /**
     * The response of unlock
     *
     * @return boolean
     */
    public function unlock(): bool
    {
        if ($this->isExists()) {
            return $this->createUnlock();
        } else {
            return false;
        }
    }

    /**
     * the choice of the driver to less pass or not the user
     *
     * @return boolean
     */
    public function decide(): bool
    {
        return ($this->isExists());
    }

    /**
     * Options of driver
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Set translator
     *
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
}