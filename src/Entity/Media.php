<?php

namespace App\Entity;

use App\Repository\MediaRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=MediaRepository::class)
 */
class Media
{
    use EntityIdTrait;
    use EntityTitleDescriptionTrait;
    use TimestampableEntity;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $license;

    /* TODO Blameable when we have a User entity */

    /* TODO Image file handling and upload */

    public function getLicense(): ?string
    {
        return $this->license;
    }

    public function setLicense(?string $license): self
    {
        $this->license = $license;

        return $this;
    }
}
