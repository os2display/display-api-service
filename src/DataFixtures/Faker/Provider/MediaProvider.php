<?php

namespace App\DataFixtures\Faker\Provider;

use App\Service\MediaUploadTenantDirectoryNamer;
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

    private static function getPublicTenantPath(string $tenantKey): string
    {
        return self::getProjectDir().'/public/media/'.$tenantKey.'/';
    }

    private static function getPublicFile(string $file, string $tenantKey): string
    {
        return self::getPublicTenantPath($tenantKey).basename($file);
    }

    public static function randomImage(string $tenantKey): string
    {
        $src = self::getProjectDir().MediaProvider::$files[array_rand(MediaProvider::$files)];
        $dest = self::saveFile($src, $tenantKey);

        // This is ugly and a hack. We need to copy the file to 'default' location as well
        // because the BaseDatabaseTrait::populateDatabase has no security context and no active
        // tenant and will fail if it can't find the file.
        $defaultDest = self::saveFile($src, MediaUploadTenantDirectoryNamer::DEFAULT);

        return basename($dest);
    }

    public static function fileSha(string $file, string $tenantKey): string
    {
        return sha1(self::getPublicFile($file, $tenantKey));
    }

    public static function imageSize(string $file, string $tenantKey): int
    {
        return filesize(self::getPublicFile($file, $tenantKey));
    }

    public static function imageWidth(string $file, string $tenantKey): int
    {
        return getimagesize(self::getPublicFile($file, $tenantKey))[0];
    }

    public static function imageHeight(string $file, string $tenantKey): int
    {
        return getimagesize(self::getPublicFile($file, $tenantKey))[1];
    }

    public static function fileMimeType(string $file, $tenantKey): string
    {
        return getimagesize(self::getPublicFile($file, $tenantKey))['mime'];
    }

    private static function saveFile(string $src, string $tenantKey): string
    {
        $path = self::getPublicTenantPath($tenantKey);
        $dest = self::getPublicFile($src, $tenantKey);
        if (!file_exists($path)) {
            mkdir($path);
        }
        if (!file_exists($dest)) {
            file_put_contents($dest, file_get_contents($src));
        }

        return $dest;
    }
}
