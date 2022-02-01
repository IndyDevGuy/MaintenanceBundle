<?php

namespace IndyDevGuy\MaintenanceBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('idg_maintenance');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('authorized')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('path')
            ->defaultNull()
            ->end()
            ->scalarNode('host')
            ->defaultNull()
            ->end()
            ->variableNode('ips')
            ->defaultNull()
            ->end()
            ->variableNode('query')
            ->defaultValue(array())
            ->end()
            ->variableNode('cookie')
            ->defaultValue(array())
            ->end()
            ->scalarNode('route')
            ->defaultNull()
            ->end()
            ->variableNode('attributes')
            ->defaultValue(array())
            ->end()
            ->end()
            ->end()
            ->arrayNode('driver')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('class')
            ->defaultNull()
            ->end()
            ->integerNode('ttl')
            ->defaultNull()
            ->end()
            ->variableNode('options')
            ->defaultValue(array())
            ->end()
            ->end()
            ->end()
            ->arrayNode('response')
            ->addDefaultsIfNotSet()
            ->children()
            ->integerNode('code')
            ->defaultValue( 503 )
            ->end()
            ->scalarNode('status')
            ->defaultValue('Service Temporarily Unavailable')
            ->end()
            ->scalarNode('exception_message')
            ->defaultValue('Service Temporarily Unavailable')
            ->end()
            ->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}