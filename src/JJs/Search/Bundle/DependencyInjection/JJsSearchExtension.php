<?php

namespace JJs\Search\Bundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;

/**
 * Search Bundle Extension
 *
 * Configures the service container for the search bundle.
 *
 * @author Josiah <josiah@jjs.id.au>
 */
class JJsSearchExtension extends Extension
{
    /**
     * Loads the specified configuration into the container.
     * 
     * @param array            $configs   Configuration
     * @param ContainerBuilder $container Dependency injection container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('converters.xml');
    }
}
