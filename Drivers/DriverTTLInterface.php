<?php

namespace IndyDevGuy\MaintenanceBundle\Drivers;

interface DriverTTLInterface
{
    /**
     * Set time to life for overwrite basic configuration
     *
     * @param integer $value ttl value
     */
    public function setTtl(int $value);

    /**
     * Return time to life
     *
     * @return integer
     */
    public function getTtl(): int;

    /**
     * Has ttl
     *
     * @return bool
     */
    public function hasTtl(): bool;
}