<?php

namespace App\DataFixtures\Faker\Provider;

use Faker\Provider\Base;
use Faker\Factory;

class ResourceProvider extends Base
{
    public static function templateResources()
    {
        $faker = Factory::create();

        return [
            'admin' => $faker->url(),
            'schema' => $faker->url(),
            'component' => $faker->url(),
            'assets' => [
            'type' => 'css',
                'url' => $faker->url(),
            ],
            'options' => [
                'fade' => true,
            ],
            'content' => [
                'text' => $faker->sentence(10),
            ],
        ];
    }
}
