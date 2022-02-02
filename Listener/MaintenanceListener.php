<?php

namespace IndyDevGuy\MaintenanceBundle\Listener;

use ErrorException;
use IndyDevGuy\MaintenanceBundle\Drivers\DriverFactory;
use IndyDevGuy\MaintenanceBundle\Exception\ServiceUnavailableException;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MaintenanceListener
{
    /**
     * @var AuthorizationCheckerInterface $authorizationChecker
     */
    protected AuthorizationCheckerInterface $authorizationChecker;

    /**
     * Service driver factory
     *
     * @var DriverFactory
     */
    protected DriverFactory $driverFactory;

    /**
     * Authorized data
     *
     * @var array|null
     */
    protected ?array $authorizedIps;

    /**
     * @var null|String
     */
    protected ?string $path;

    /**
     * @var null|String
     */
    protected ?string $host;

    /**
     * @var array|null
     */
    protected ?array $ips;

    /**
     * @var array|null
     */
    protected ?array $roles;

    /**
     * @var array|null
     */
    protected ?array $query;

    /**
     * @var array|null
     */
    protected ?array $cookie;

    /**
     * @var null|String
     */
    protected ?string $route;

    /**
     * @var array|null
     */
    protected ?array $attributes;

    /**
     * @var int|null
     */
    protected ?int $http_code;

    /**
     * @var null|string
     */
    protected ?string $http_status;

    /**
     * @var null|string
     */
    protected ?string $http_exception_message;

    /**
     * @var bool
     */
    protected bool $handleResponse = false;

    /**
     * @var bool
     */
    protected bool $debug;

    /**
     * Constructor Listener
     *
     * Accepts a driver factory, and several arguments to be compared against the
     * incoming request.
     * When the maintenance mode is enabled, the request will be allowed to bypass
     * it if at least one of the provided arguments is not empty and matches the
     *  incoming request.
     *
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param DriverFactory $driverFactory The driver factory
     * @param string|null $path A regex for the path
     * @param string|null $host A regex for the host
     * @param array|null $roles
     * @param array|null $ips The list of IP addresses
     * @param array $query Query arguments
     * @param array $cookie Cookies
     * @param string|null $route Route name
     * @param array $attributes Attributes
     * @param int|null $http_code http status code for response
     * @param string|null $http_status http status message for response
     * @param string|null $http_exception_message http response page exception message
     * @param bool $debug
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        DriverFactory $driverFactory,
        string $path = null,
        string $host = null,
        array $roles = null,
        array $ips = null,
        array $query = array(),
        array $cookie = array(),
        string $route = null,
        array $attributes = array(),
        int $http_code = null,
        string $http_status = null,
        string $http_exception_message = null,
        bool $debug = false
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->driverFactory = $driverFactory;
        $this->path = $path;
        $this->host = $host;
        $this->roles = $roles;
        $this->ips = $ips;
        $this->query = $query;
        $this->cookie = $cookie;
        $this->route = $route;
        $this->attributes = $attributes;
        $this->http_code = $http_code;
        $this->http_status = $http_status;
        $this->http_exception_message = $http_exception_message;
        $this->debug = $debug;
    }

    /**
     * @param RequestEvent $event RequestEvent
     *
     * @return void
     *
     * @throws ServiceUnavailableException|ErrorException
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if(!$event->isMasterRequest()){
            return;
        }

        $request = $event->getRequest();

        if (is_array($this->query)) {
            foreach ($this->query as $key => $pattern) {
                if (!empty($pattern) && preg_match('{'.$pattern.'}', $request->get($key))) {
                    return;
                }
            }
        }

        if (is_array($this->cookie)) {
            foreach ($this->cookie as $key => $pattern) {
                if (!empty($pattern) && preg_match('{'.$pattern.'}', $request->cookies->get($key))) {
                    return;
                }
            }
        }

        if (is_array($this->attributes)) {
            foreach ($this->attributes as $key => $pattern) {
                if (!empty($pattern) && preg_match('{'.$pattern.'}', $request->attributes->get($key))) {
                    return;
                }
            }
        }

        if (!empty($this->path) && preg_match('{'.$this->path.'}', rawurldecode($request->getPathInfo()))) {
            return;
        }

        if (!empty($this->host) && preg_match('{'.$this->host.'}i', $request->getHost())) {
            return;
        }

        if (count((array) $this->roles) !== 0 && $this->checkRoles($this->roles)) {
            return;
        }

        if (count((array) $this->ips) !== 0 && $this->checkIps($request->getClientIp(), $this->ips)) {
            return;
        }

        $route = $request->get('_route');
        if (null !== $this->route && preg_match('{'.$this->route.'}', $route)  || (true === $this->debug && '_' === $route[0])) {
            return;
        }

        // Get driver class defined in your configuration
        $driver = $this->driverFactory->getDriver();

        if ($driver->decide() && HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            $this->handleResponse = true;
            throw new ServiceUnavailableException($this->http_exception_message);
        }
    }

    /**
     * Rewrites the http code of the response
     *
     * @param ResponseEvent $event ResponseEvent
     * @return void
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        if ($this->handleResponse && $this->http_code !== null) {
            $response = $event->getResponse();
            $response->setStatusCode($this->http_code, $this->http_status);
        }
    }

    protected function checkRoles(array $roles):bool
    {
        foreach ($roles as $role) {
            if ($this->authorizationChecker->isGranted($role)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if the requested ip is valid.
     *
     * @param string $requestedIp
     * @param string|array $ips
     * @return boolean
     */
    protected function checkIps(string $requestedIp, $ips): bool
    {
        $ips = (array) $ips;

        $valid = false;
        $i = 0;

        while ($i<count($ips) && !$valid) {
            $valid = IpUtils::checkIp($requestedIp, $ips[$i]);
            $i++;
        }

        return $valid;
    }
}