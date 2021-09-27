<?php

namespace App\DataFixtures\Faker\Provider;

use Faker\Provider\Base;

class LicenseProvider extends Base
{
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
