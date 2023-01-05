<?php

namespace App\Service;

use App\Entity\Tenant\Media;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\String\Slugger\SluggerInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

class MediaUploadTenantDirectoryNamer implements DirectoryNamerInterface
{
    public const DEFAULT = 'default';
    private const SEPARATOR = '-';

    public function __construct(
        private Security $security,
        private SluggerInterface $slugger
    ) {}

    public function directoryName($object, PropertyMapping $mapping): string
    {
        if ($object instanceof Media) {
            $key = $object->getTenant()->getTenantKey();

            return \strtolower($this->slugger->slug($key, self::SEPARATOR));
        }

        return self::DEFAULT;
    }
}
