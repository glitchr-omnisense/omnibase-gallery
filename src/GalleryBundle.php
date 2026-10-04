<?php

namespace Base\Gallery;

use Base\Bundle\AbstractBaseBundle;
use Base\Traits\SingletonTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Albums of photographs on top of omnibase's Thread. An Album IS a Thread
 * (JOINED subclass): one album is one creation, its Photos are ordered.
 * Nothing else: no follow-up of the work (that is omnibase/forge), no trade.
 */
class GalleryBundle extends AbstractBaseBundle
{
    use SingletonTrait;

    public function __construct()
    {
        parent::__construct();
    }

    /** Modern layout: the class lives in src/, the bundle root is the package root. */
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // App\-wins, as omnibase does for its own entities: an application may
        // declare App\Entity\Gallery\Album extending ours and take over. An
        // application that only adds fields declares a subclass anywhere
        // under its own src/Entity (see docs/extending.md).
        $this->setMapping($this->getPath().'/src/Entity', 'Base\Gallery\Entity', 'App\Entity\Gallery');
        $this->setMapping($this->getPath().'/src/Repository', 'Base\Gallery\Repository', 'App\Repository\Gallery');
    }
}
