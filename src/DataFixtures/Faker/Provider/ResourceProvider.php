<?php

namespace App\DataFixtures\Faker\Provider;

use Faker\Factory;
use Faker\Generator;
use Faker\Provider\Base;

class ResourceProvider extends Base
{
    public function __construct(Generator $generator)
    {
        $this->unique = $this->unique();
        parent::__construct($generator);
    }

    public static function templateResources(): array
    {
        $faker = Factory::create();

        return [
            'admin' => 'https://raw.githubusercontent.com/os2display/display-templates/main/build/image-text-admin.json',
            'description' => 'A template with different formats of image and text.',
            'title' => 'Image and text',
            'schema' => $faker->url(),
            'component' => 'https://raw.githubusercontent.com/os2display/display-templates/main/build/image-text.js',
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
