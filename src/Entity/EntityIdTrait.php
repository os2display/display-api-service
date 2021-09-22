<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Component\Uid\Ulid;
use Doctrine\ORM\Mapping as ORM;

trait EntityIdTrait
{
    /**
     * @ORM\Id
     * @ORM\Column(type="ulid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UlidGenerator::class)
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
