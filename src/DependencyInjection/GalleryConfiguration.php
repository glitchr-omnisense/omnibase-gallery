<?php

namespace Base\Gallery\DependencyInjection;

use Base\Bundle\AbstractBaseConfiguration;
use Base\Entity\Thread\Taxon;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class GalleryConfiguration extends AbstractBaseConfiguration
{
    private bool $childrenDeclared = false;

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = $this->getTreeBuilder();
        if ($this->childrenDeclared) {
            return $treeBuilder;
        }
        $this->childrenDeclared = true;

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('path')->defaultValue('galerie')->cannotBeEmpty()
                    ->info('The first segment of the gallery\'s addresses: /galerie, /galerie/{slug}. No slash.')
                    ->validate()
                        ->ifTrue(fn ($path) => !\is_string($path) || !preg_match('#^[a-z0-9][a-z0-9\-_/]*[a-z0-9]$#i', $path))
                        ->thenInvalid('gallery.path is one or more URL segments without a leading or trailing slash, %s given.')
                    ->end()
                ->end()
                ->integerNode('per_page')->min(1)->defaultValue(24)
                    ->info('Albums listed per page.')->end()
                ->scalarNode('taxon_class')->defaultValue(Taxon::class)
                    ->info('The taxa that have a page of their own (/galerie/{taxon}): this class and its subclasses. With the default, omnibase\'s Taxon, a taxon has a page once it classifies a published album; with an application\'s own class, every taxon of that class has one.')->end()
                ->booleanNode('sitemap')->defaultTrue()
                    ->info('The albums and the taxa pages in /sitemap.xml.')->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
