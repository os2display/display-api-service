<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use Symfony\Component\Uid\Ulid;
use Doctrine\ORM\Mapping as ORM;

trait EntityIdTrait
{
    /**
     * @ORM\Id
     * @ORM\Column(type="ulid", unique=true)
     *
     * @ApiProperty(identifier=true)
     */
    private Ulid $id;

    /**
     * Get the Ulid.
     */
    public function getId(): ?Ulid
    {
        return $this->id;
    }

    /**
     * Set the Ulid.
     *
     * @param Ulid $id
     *
     * @return Screen|ScreenLayoutRegions|ScreenGroup|Media|Playlist|ScreenLayout|Slide|Template
     */
    public function setId(Ulid $id): self
    {
        $this->id = $id;

        return $this;
    }
}
