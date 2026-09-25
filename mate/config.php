<?php

declare(strict_types=1);

// User's service configuration file
// This file is loaded into the Symfony DI container

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->parameters()
        // No host PHP: agents run Mate inside the phpfpm container.
        ->set('mate.invocation', 'docker compose exec -T phpfpm vendor/bin/mate')
        ->set('mate.php_version', '8.4')
        // Override default parameters here
        // ->set('mate.cache_dir', sys_get_temp_dir().'/mate')
        // ->set('mate.env_file', ['.env']) // This will load mate/.env and mate/.env.local
    ;

    $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()

        // Register your custom services here
    ;
};
