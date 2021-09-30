<?php

namespace App\DataFixtures\Faker\Provider;

use Faker\Provider\Base;

class MediaProvider extends Base
{
    private static array $files = [
        '/fixtures/files/test.jpg',
        '/fixtures/files/test_1.jpg',
        '/fixtures/files/test_2.jpg',
        '/fixtures/files/test_3.jpg',
    ];

    private static function getProjectDir(): string
    {
        return !empty($GLOBALS['app']) ? $GLOBALS['app']->getKernel()->getProjectDir() : getcwd();
    }

    private static function getPublicFile(string $file): string
    {
        return self::getProjectDir().'/public/media/'.basename($file);
    }

    public static function randomImage(): string
    {
        $src = self::getProjectDir().MediaProvider::$files[array_rand(MediaProvider::$files)];
        $dest = self::getPublicFile($src);
        if (!file_exists($dest)) {
            file_put_contents($dest, file_get_contents($src));
        }

        return basename($dest);
    }

    public static function fileSha(string $file): string
    {
        return sha1(self::getPublicFile($file));
    }

    public static function imageSize(string $file): int
    {
        return filesize(self::getPublicFile($file));
    }

    public static function imageWidth(string $file): int
    {
        return getimagesize(self::getPublicFile($file))[0];
    }

    public static function imageHeight(string $file): int
    {
        return getimagesize(self::getPublicFile($file))[1];
    }

    public static function fileMimeType(string $file): string
    {
        return getimagesize(self::getPublicFile($file))['mime'];
    }
}
