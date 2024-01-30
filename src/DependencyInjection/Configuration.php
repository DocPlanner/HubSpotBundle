<?php

namespace DocPlanner\Bundle\HubSpotBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('hub_spot');
        $rootNode = !method_exists($treeBuilder, 'getRootNode') ? $treeBuilder->root('hub_spot') : $treeBuilder->getRootNode();

        $rootNode->children()
            ->scalarNode('key')
            ->end()
            ->arrayNode('proxy')
                ->children()
                    ->scalarNode('custom_url')
                    ->end()
                    ->arrayNode('custom_headers')
                        ->normalizeKeys(false)
                        ->prototype('scalar')
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
