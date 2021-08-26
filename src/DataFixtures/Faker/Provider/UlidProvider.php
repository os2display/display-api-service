<?php

namespace App\DataFixtures\Faker\Provider;

use Faker\Provider\Base;
use Symfony\Component\Uid\Ulid;

class UlidProvider extends Base
{
    public static function ulid()
    {
        return new Ulid();
    }
}