<?php

namespace IndyDevGuy\MaintenanceBundle\Exception;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ServiceUnavailableException extends HttpException
{
    /**
     * Constructor.
     *
     * @param null $message The internal exception message
     * @param Exception|null $previous The previous exception
     * @param integer $code The internal exception code
     */
    public function __construct($message = null, Exception $previous = null, $code = 0)
    {
        parent::__construct(503, $message, $previous, array(), $code);
    }
}