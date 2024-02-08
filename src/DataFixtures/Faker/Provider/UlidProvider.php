<?php

declare(strict_types=1);

namespace App\DataFixtures\Faker\Provider;

use Faker\Factory;
use Faker\Generator;
use Faker\Provider\Base;
use Symfony\Component\Uid\Ulid;

/**
 * Ulid provider that uses FakerPHP for the datetime and random parts to enable
 * the generation of predictable "random" Ulid's.
 *
 * This is directly adapted from Symfony's Ulid provider.
 *
 * @see https://github.com/symfony/uid/blob/5.3/Ulid.php
 */
class UlidProvider extends Base
{
    /**
     * Const from Symfony\Component\Uid\BinaryUtil.
     *
     * @see https://github.com/symfony/uid/blob/5.3/Ulid.php
     */
    final public const BASE10 = [
        '' => '0123456789',
        0, 1, 2, 3, 4, 5, 6, 7, 8, 9,
    ];

    private const NIL = '00000000000000000000000000';

    private static string $time = '';
    private static array $rand = [];

    public function __construct(Generator $generator)
    {
        $this->unique = $this->unique();
        parent::__construct($generator);
    }

    public static function ulid(\DateTimeInterface $dateTime = null): Ulid
    {
        $mtime = $dateTime ? $dateTime->getTimestamp().'000' : null;

        $ulid = self::doGenerate($mtime);

        return new Ulid($ulid);
    }

    public static function ulidDate(Ulid $ulid): \DateTimeInterface
    {
        return \DateTime::createFromImmutable($ulid->getDateTime());
    }

    private static function doGenerate(string $mtime = null): string
    {
        $faker = Factory::create();

        if (null === $time = $mtime) {
            /* Original Symfony code for reference
             *
             * $time = microtime(false);
             * $time = substr($time, 11).substr($time, 2, 3);
             */

            // Get unix timestamp and add digits to get micro time
            $time = $faker->unique()->unixTime(new \DateTime('2021-10-10')).$faker->numberBetween(1, 999);
        }

        if ($time !== self::$time) {
            /* Original Symfony code for reference
             *
             * $r = unpack('nr1/nr2/nr3/nr4/nr', random_bytes(10));
             * $r['r1'] |= ($r['r'] <<= 4) & 0xF0000;
             * $r['r2'] |= ($r['r'] <<= 4) & 0xF0000;
             * $r['r3'] |= ($r['r'] <<= 4) & 0xF0000;
             * $r['r4'] |= ($r['r'] <<= 4) & 0xF0000;
             */

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
            /*
             * Inlined functions from Symfony\Component\Uid\BinaryUtil
             *
             * @see https://github.com/symfony/uid/blob/5.3/Ulid.php
             */
            $time = str_pad(bin2hex(self::fromBase($time, self::BASE10)), 12, '0', \STR_PAD_LEFT);
            $time = sprintf('%s%04s%04s',
                base_convert(substr($time, 0, 2), 16, 32),
                base_convert(substr($time, 2, 5), 16, 32),
                base_convert(substr($time, 7, 5), 16, 32)
            );
        }

        return strtr(sprintf('%010s%04s%04s%04s%04s',
            $time,
            base_convert((string) self::$rand[0], 10, 32),
            base_convert((string) self::$rand[1], 10, 32),
            base_convert((string) self::$rand[2], 10, 32),
            base_convert((string) self::$rand[3], 10, 32)
        ), 'abcdefghijklmnopqrstuv', 'ABCDEFGHJKMNPQRSTVWXYZ');
    }

    /**
     * Function from Symfony\Component\Uid\BinaryUtil.
     *
     * @see https://github.com/symfony/uid/blob/5.3/Ulid.php
     *
     * @param string $digits
     * @param array $map
     *
     * @return string
     */
    private static function fromBase(string $digits, array $map): string
    {
        $base = \strlen((string) $map['']);
        $count = \strlen($digits);
        $bytes = [];

        while ($count) {
            $quotient = [];
            $remainder = 0;

            for ($i = 0; $i !== $count; ++$i) {
                $carry = ($bytes ? $digits[$i] : $map[$digits[$i]]) + $remainder * $base;

                if (\PHP_INT_SIZE >= 8) {
                    $digit = $carry >> 16;
                    $remainder = $carry & 0xFFFF;
                } else {
                    $digit = $carry >> 8;
                    $remainder = $carry & 0xFF;
                }

                if ($digit || $quotient) {
                    $quotient[] = $digit;
                }
            }

            $bytes[] = $remainder;
            $count = \count($digits = $quotient);
        }

        return pack(\PHP_INT_SIZE >= 8 ? 'n*' : 'C*', ...array_reverse($bytes));
    }
}
