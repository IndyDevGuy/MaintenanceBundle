Installation
============

## Install the bundle via composer

```shell
composer require indydevguy/maintenance-bundle
```


## Register the bundle

You must register the bundle in your bundles.php file.

    <?php
    // config/bundles.php
    return [
        Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
        ....,
        'IndyDevGuy\MaintenanceBundle\MaintenanceBundle' => ['all' => true],
    ];

-----------------------

Usage
===

you have several options for each driver.

Here is the complete configuration with a `example` of each pair of class / options.

The ttl (time to life) option is optional everywhere, it is used to indicate the duration in `seconds` that the site will be in maintenance mode.

    #config/packages/idg_maintenance.yaml
    idg_maintenance:
        authorized:
            path: /path                               # Optional. Authorized path, accepts regexs
            host: your-domain.com                     # Optional. Authorized domain, accepts regexs
            ips: ['127.0.0.1', '172.123.10.14']       # Optional. Authorized ip addresses
            query: { foo: bar }                       # Optional. Authorized request query parameter (GET/POST)
            cookie: { bar: baz }                      # Optional. Authorized cookie
            route:                                    # Optional. Authorized route name
            attributes:                               # Optional. Authorized route attributes
        driver:
            ttl: 3600                                 # Optional ttl option, can be not set

             # File driver
            class: '\IndyDevGuy\MaintenanceBundle\Drivers\FileDriver'     # class for file driver
            options: {file_path: %kernel.root_dir%/../app/cache/lock}     # file_path is the complete path for create the file (Symfony < 3.0)
            options: {file_path: %kernel.root_dir%/../var/cache/lock}     # file_path is the complete path for create the file (Symfony >= 3.0)

             # Shared memory driver
            class: '\IndyDevGuy\MaintenanceBundle\Drivers\ShmDriver'      # class for shared memory driver

             # MemCache driver
            class: IndyDevGuy\MaintenanceBundle\Drivers\MemCacheDriver        # class for MemCache driver
            options: {key_name: 'maintenance', host: 127.0.0.1, port: 11211}  # need to define a key_name, the host and port

            # Database driver:
            class: 'IndyDevGuy\MaintenanceBundle\Drivers\DatabaseDriver'      # class for database driver
            # Option 1 : for doctrine
            options: {connection: custom}                                      # Optional. You can choice an other connection. Without option it's the doctrine default connection who will be used
            # Option 2 : for dsn, you must have a column ttl type datetime in your table.
            options: {dsn: "mysql:dbname=maintenance;host:localhost", table: maintenance, user: root, password: root}  # the dsn configuration, name of table, user/password

        #Optional. response code and status of the maintenance page
        response:
            code: 503                                                                  # Http response code of Exception page
            status: "Service Temporarily Unavailable"                                  # Exception page title
            exception_message: "Service Temporarily Unavailable"                       # Message when Exception is thrown 


### Bundle Commands

There are two commands to enable/disable maintenance mode:

    idg_maintenance:lock [--set-ttl]

This command will enable maintenance mode according to your configuration. You can pass the maintenance mode time to life in a parameter, ``this does not with file driver``.

    idg_maintenance:unlock

This command will disable maintenance mode.

You can enable/disable maintenance mode without a warning message and interaction with:

    idg_maintenance:lock --no-interaction
    idg_maintenance:unlock --no-interaction

Or (with the optional ttl overwriting)

    idg:maintenance:lock 3600 -n


---------------------

Creating a custom 503  error page
---------------------

In the listener, an exception is thrown when maintenance mode is enabled. This exception is a 'HttpException' (status 503 Service Unavailable). 

In order to create a custom error page
you need to create a new template (notice the template path): 
    
    templates/TwigBundle/views/Exception/error503.html.twig

#### Important Note

    You must be using Twig and Symfony must be in production mode for the error page to show

----------------------

Using with a Load Balancer
---------------------
Some load balancers will monitor the status code
of the http response to stop forwarding traffic
to your nodes.  If you are using a load balancer
you may want to change the status code of the
maintenance page to 200 so your users will still see
something. You may change the response code of the status page from 503 by changing the **response.code** configuration.


Toggling maintenance mode
--------

You can use the ``IndyDevGuy\MaintenanceBundle\Drivers\DriverFactory`` service via Dependency Injection in your controllers or other services to toggle maintenance mode.

For example, controller action that toggles maintenance mode.

    
    namespace Your\Controller\Namespace;

    use IndyDevGuy\MaintenanceBundle\Drivers\DriverFactory;
    use Symfony\Bundle\FrameworkBundle\Controller\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\HttpKernel\Exception\HttpException;
    use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
    use Symfony\Component\Routing\Annotation\Route;

    class YourController extends AbstractController
    {
        /**
         * @Route("/maintenance/{action}", name="toggleMaintenanceMode")
         */
        public function toggleAction(string $action, Request $request, DriverFactory $driverFactory):Response
        {
            $driver = $driverFactory->getDriver();
    
            if ($action === 'lock') {
                $message = $driver->getMessageLock($driver->lock());
            } elseif ($action === 'unlock') {
                $message = $driver->getMessageUnlock($driver->unlock());
            } else {
                throw new NotFoundHttpException();
            }
    
            $this->addFlash('warning', $message);
    
            return new RedirectResponse($this->generateUrl('_demo'));
        }
    }


**Warning**: Make sure you have allowed IP addresses if you run maintenance from the backend, otherwise you will find yourself blocked on page 503.