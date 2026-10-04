<?php

namespace Base\Gallery\Tests\DependencyInjection;

use Base\Entity\Thread\Taxon;
use Base\Gallery\DependencyInjection\GalleryConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testTheDefaults(): void
    {
        $config = (new Processor())->processConfiguration(new GalleryConfiguration(), []);

        self::assertSame('galerie', $config['path']);
        self::assertSame(24, $config['per_page']);
        self::assertSame(Taxon::class, $config['taxon_class']);
        self::assertTrue($config['sitemap']);
    }

    public function testASitesOwnPathAndTaxonClass(): void
    {
        $config = (new Processor())->processConfiguration(new GalleryConfiguration(), [['path' => 'realisations', 'per_page' => 12, 'taxon_class' => 'App\Entity\Portfolio\Category']]);

        self::assertSame('realisations', $config['path']);
        self::assertSame(12, $config['per_page']);
        self::assertSame('App\Entity\Portfolio\Category', $config['taxon_class']);
    }

    public function testAPathWithSlashesAroundIsRefused(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        (new Processor())->processConfiguration(new GalleryConfiguration(), [['path' => '/galerie/']]);
    }
}
