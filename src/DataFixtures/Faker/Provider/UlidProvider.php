<?php

namespace App\DataFixtures\Faker\Provider;

use Faker\Factory;
use Faker\Provider\Base;
use Symfony\Component\Uid\BinaryUtil;
use Symfony\Component\Uid\Ulid;

class UlidProvider extends Base
{
    private const NIL = '00000000000000000000000000';

    private static string $time = '';
    private static array $rand = [];

    public static function ulid(): Ulid
    {
        $ulid = self::doGenerate();

        return new Ulid($ulid);
    }

    public static function ulidDate(Ulid $ulid): \DateTime
    {
        return \DateTime::createFromImmutable($ulid->getDateTime());
    }

    private static function doGenerate(string $mtime = null): string
    {
        $faker = Factory::create();

        if (null === $time = $mtime) {
            // $time = microtime(false);
            // $time = substr($time, 11).substr($time, 2, 3);

            $time = $faker->unixTime(new \DateTime('2021-10-10')).$faker->numberBetween(1, 999);
        }

        if ($time !== self::$time) {
            // $r = unpack('nr1/nr2/nr3/nr4/nr', random_bytes(10));
            // $r['r1'] |= ($r['r'] <<= 4) & 0xF0000;
            // $r['r2'] |= ($r['r'] <<= 4) & 0xF0000;
            // $r['r3'] |= ($r['r'] <<= 4) & 0xF0000;
            // $r['r4'] |= ($r['r'] <<= 4) & 0xF0000;

            $r = [];
            $r['r1'] = $faker->numberBetween(1, 64000);
            $r['r2'] = $faker->numberBetween(1, 64000);
            $r['r3'] = $faker->numberBetween(1, 64000);
            $r['r4'] = $faker->numberBetween(1, 64000);

            self::$rand = array_values($r);
            self::$time = $time;
        } elseif ([0xFFFFF, 0xFFFFF, 0xFFFFF, 0xFFFFF] === self::$rand) {
            if (null === $mtime) {
                usleep(100);
            } else {
                self::$rand = [0, 0, 0, 0];
            }

            return self::doGenerate($mtime);
        } else {
            for ($i = 3; $i >= 0 && 0xFFFFF === self::$rand[$i]; --$i) {
                self::$rand[$i] = 0;
            }

            ++self::$rand[$i];
        }

        if (\PHP_INT_SIZE >= 8) {
            $time = base_convert($time, 10, 32);
        } else {
            $time = str_pad(bin2hex(BinaryUtil::fromBase($time, BinaryUtil::BASE10)), 12, '0', \STR_PAD_LEFT);
            $time = sprintf('%s%04s%04s',
                base_convert(substr($time, 0, 2), 16, 32),
                base_convert(substr($time, 2, 5), 16, 32),
                base_convert(substr($time, 7, 5), 16, 32)
            );
        }

        return strtr(sprintf('%010s%04s%04s%04s%04s',
            $time,
            base_convert(self::$rand[0], 10, 32),
            base_convert(self::$rand[1], 10, 32),
            base_convert(self::$rand[2], 10, 32),
            base_convert(self::$rand[3], 10, 32)
        ), 'abcdefghijklmnopqrstuv', 'ABCDEFGHJKMNPQRSTVWXYZ');
    }
}
