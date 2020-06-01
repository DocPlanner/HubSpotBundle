<?php

namespace DocPlanner\Bundle\HubSpotBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class HubSpotExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration;
        $config        = $this->processConfiguration($configuration, $configs);

        $container->setParameter('hubspot', $config);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('hubspot-services.yml');
        $this->validateParameters($config);
    }

    private function validateParameters($config): void
    {
        $apiKey = $config['key'];
        $proxy  = $config['proxy'];

        if (false === isset($proxy['custom_url']) && ('' === $apiKey || null === $apiKey))
        {
            throw new \RuntimeException('You must provide api_key when not using proxy');
        }
    }
}
