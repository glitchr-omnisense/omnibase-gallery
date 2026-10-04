<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

/*
 * Everything in src/ is a plain autowired service, the way an application's
 * own src/ is. The back office's CRUD controllers are loaded only when
 * omnibase/admin is installed.
 */
return function (ContainerConfigurator $configurator) {
    $src = dirname(__DIR__).'/src';

    $services = $configurator->services();
    $services->defaults()
        ->autowire(true)
        ->autoconfigure(true)
        ->public(false);

    $services->load('Base\\Gallery\\', $src.'/')
        ->exclude([
            $src.'/DependencyInjection/',
            $src.'/Entity/',
            $src.'/Controller/Admin/',
            $src.'/GalleryBundle.php',
        ]);

    $services->load('Base\\Gallery\\Controller\\Client\\', $src.'/Controller/Client/')
        ->tag('controller.service_arguments');

    if (class_exists('Base\\Admin\\Controller\\AbstractCrudController')) {
        $services->load('Base\\Gallery\\Controller\\Admin\\', $src.'/Controller/Admin/')
            ->tag('controller.service_arguments');
    }
};
