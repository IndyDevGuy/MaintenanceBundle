<?php

namespace IndyDevGuy\MaintenanceBundle\Drivers;

use ErrorException;
use Symfony\Contracts\Translation\TranslatorInterface;

class DriverFactory
{
    /**
     * @var array
     */
    protected array $driverOptions;

    /**
     * @var DatabaseDriver
     */
    protected DatabaseDriver $dbDriver;

    /**
     * @var TranslatorInterface
     */
    protected TranslatorInterface $translator;

    const DATABASE_DRIVER = 'IndyDevGuy\MaintenanceBundle\Drivers\DatabaseDriver';

    /**
     * Constructor driver factory
     *
     * @param DatabaseDriver      $dbDriver The databaseDriver Service
     * @param TranslatorInterface $translator The translator service
     * @param array               $driverOptions Options driver
     * @throws ErrorException
     */
    public function __construct(DatabaseDriver $dbDriver, TranslatorInterface $translator, array $driverOptions)
    {
        $this->driverOptions = $driverOptions;

        if ( ! isset($this->driverOptions['class'])) {
            throw new ErrorException('You need to define a driver class');
        }

        $this->dbDriver = $dbDriver;
        $this->translator = $translator;
    }

    /**
     * Return the driver
     *
     * @return mixed
     * @throws ErrorException
     */
    public function getDriver()
    {
        $class = $this->driverOptions['class'];

        if (!class_exists($class)) {
            throw new ErrorException("Class '".$class."' not found in ".get_class($this));
        }

        if ($class === self::DATABASE_DRIVER) {
            $driver = $this->dbDriver;
            $driver->setOptions($this->driverOptions['options']);
        } else {
            $driver = new $class($this->driverOptions['options']);
        }

        $driver->setTranslator($this->translator);

        return $driver;
    }
}