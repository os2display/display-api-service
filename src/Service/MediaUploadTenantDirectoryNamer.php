<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Tenant\Media;
use Symfony\Component\String\Slugger\SluggerInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

class MediaUploadTenantDirectoryNamer implements DirectoryNamerInterface
{
    final public const DEFAULT = 'default';
    private const SEPARATOR = '-';

    public function __construct(
        private readonly SluggerInterface $slugger
    ) {}

    public function directoryName($object, PropertyMapping $mapping): string
    {
        if ($object instanceof Media) {
            $key = $object->getTenant()->getTenantKey();

            return \strtolower($this->slugger->slug($key, self::SEPARATOR)->toString());
        }

        return self::DEFAULT;
    }
}
