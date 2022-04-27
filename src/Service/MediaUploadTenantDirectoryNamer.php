<?php

namespace App\Service;

use App\Entity\Interfaces\TenantScopedUserInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\String\Slugger\SluggerInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

class MediaUploadTenantDirectoryNamer implements DirectoryNamerInterface
{
    private const SEPARATOR = '-';
    private const DEFAULT = 'default';

    public function __construct(private Security $security, private SluggerInterface $slugger)
    {
    }

    public function directoryName($object, PropertyMapping $mapping): string
    {
        $user = $this->security->getUser();

        if ($user instanceof TenantScopedUserInterface) {
            $key = $user->getActiveTenant()->getTenantKey();
        } else {
            $key = self::DEFAULT;
        }

        return \strtolower($this->slugger->slug($key, self::SEPARATOR));
    }
}
