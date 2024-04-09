<?php

declare(strict_types=1);

namespace App\DataFixtures\Faker\Provider;

use Faker\Generator;
use Faker\Provider\Base;

class LicenseProvider extends Base
{
    public function __construct(Generator $generator)
    {
        $this->unique = $this->unique();
        parent::__construct($generator);
    }

    private const IMAGE_LICENSES = [
        'Attribution License',
        'Attribution-NoDerivs License',
        'Attribution-NonCommercial-NoDerivs License',
        'Creative Commons License',
        'Public Domain',
    ];

    public static function imageLicense(): string
    {
        $rand = array_rand(self::IMAGE_LICENSES, 1);

        return self::IMAGE_LICENSES[$rand];
    }
}
