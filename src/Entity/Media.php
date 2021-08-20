<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\MediaRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=MediaRepository::class)
 */
class Media
{
    use EntityIdTrait;
    use EntityTitleDescTrait;
    use TimestampableEntity;

    /** TODO Blameable when we have a User entity */

    /** TODO Image file handling and upload */
}
