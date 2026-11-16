<?php

namespace Base\Gallery\DependencyInjection;

use Base\Bundle\AbstractBaseExtension;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class GalleryExtension extends AbstractBaseExtension
{
    public function getConfiguration(array $config, ContainerBuilder $container): GalleryConfiguration
    {
        return new GalleryConfiguration();
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(\dirname(__DIR__, 2).'/config'));
        $loader->load('services.php');

        $configuration = new GalleryConfiguration();
        $config = (new Processor())->processConfiguration($configuration, $configs);

        // Flat parameters: gallery.path, gallery.per_page, gallery.taxon_class, gallery.sitemap.
        $this->setConfiguration($container, $config, $configuration->getTreeBuilder()->buildTree()->getName());
    }
}
