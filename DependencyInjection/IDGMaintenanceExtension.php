<?php

namespace IndyDevGuy\MaintenanceBundle\DependencyInjection;

use Exception;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;

class IDGMaintenanceExtension extends Extension
{
    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');
        $loader->load('database.yaml');

        if (isset($config['driver']['ttl'])) {
            $config['driver']['options']['ttl'] = $config['driver']['ttl'];
        }

        $container->setParameter('idg_maintenance.driver', $config['driver']);

        $container->setParameter('idg_maintenance.authorized.path', $config['authorized']['path']);
        $container->setParameter('idg_maintenance.authorized.host', $config['authorized']['host']);
        $container->setParameter('idg_maintenance.authorized.ips', $config['authorized']['ips']);
        $container->setParameter('idg_maintenance.authorized.query', $config['authorized']['query']);
        $container->setParameter('idg_maintenance.authorized.cookie', $config['authorized']['cookie']);
        $container->setParameter('idg_maintenance.authorized.route', $config['authorized']['route']);
        $container->setParameter('idg_maintenance.authorized.attributes', $config['authorized']['attributes']);
        $container->setParameter('idg_maintenance.response.http_code', $config['response']['code']);
        $container->setParameter('idg_maintenance.response.http_status', $config['response']['status']);
        $container->setParameter('idg_maintenance.response.exception_message', $config['response']['exception_message']);

        if (isset($config['driver']['options']['dsn'])) {
            $this->registerDsnconfiguration($config['driver']['options']);
        }
    }

    /**
     * Load dsn configuration
     *
     * @param array $options A configuration array
     *
     * @throws InvalidArgumentException
     */
    protected function registerDsnConfiguration(array $options)
    {
        if ( ! isset($options['table'])) {
            throw new InvalidArgumentException('You need to define table for dsn use');
        }

        if ( ! isset($options['user'])) {
            throw new InvalidArgumentException('You need to define user for dsn use');
        }

        if ( ! isset($options['password'])) {
            throw new InvalidArgumentException('You need to define password for dsn use');
        }
    }
}