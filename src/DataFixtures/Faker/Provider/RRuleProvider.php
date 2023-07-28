<?php

namespace App\DataFixtures\Faker\Provider;

use Faker\Factory;
use Faker\Generator;
use Faker\Provider\Base;
use RRule\RRule;
use RRule\RRuleInterface;

class RRuleProvider extends Base
{
    public function __construct(Generator $generator)
    {
        $this->unique = $this->unique();
        parent::__construct($generator);
    }
    private const FREQ = [
        RRule::WEEKLY,
        RRule::DAILY,
        RRule::HOURLY,
        RRule::MINUTELY,
    ];

    public static function rrule(): ?RRuleInterface
    {
        $faker = Factory::create();
        $freq = array_rand(self::FREQ, 1);

        try {
            $rr = new RRule([
                'freq' => self::FREQ[$freq],
                'interval' => $faker->numberBetween(1, 10),
                'count' => $faker->numberBetween(1, 20),
                'dtstart' => $faker->dateTimeBetween('now', '+10 weeks'),
            ]);
        } catch (\Exception $exception) {
            exit($exception->getMessage());
        }

        return $rr;
    }
}
