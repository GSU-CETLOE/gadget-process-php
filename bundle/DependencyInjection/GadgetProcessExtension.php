<?php

declare(strict_types=1);

namespace Gadget\Process\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class GadgetProcessExtension extends Extension
{
    /**
     * Registers every class under src/ as an autowired service.
     *
     * @param mixed[] $configs
     * @param ContainerBuilder $container
     * @return void
     */
    public function load(
        array $configs,
        ContainerBuilder $container
    ): void {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(dirname(__DIR__) . '/Resources/config')
        );

        $loader->load('services.yaml');
    }
}
