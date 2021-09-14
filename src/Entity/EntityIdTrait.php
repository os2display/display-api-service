<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use Symfony\Component\Uid\Ulid;

trait EntityIdTrait
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     *
     * @ApiProperty(identifier=false)
     */
    private int $id;

    /**
     * @ORM\Column(type="ulid", unique=true)
     *
     * @ApiProperty(identifier=true)
     */
    private Ulid $ulid;

    /**
     * Get the id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the Ulid.
     */
    public function getUlid(): ?Ulid
    {
        return $this->ulid;
    }

    /**
     * Set the Ulid.
     *
     * @param Ulid $ulid
     *
     * @return Screen|EntityIdTrait|Media|Playlist|ScreenLayout|Slide|Template
     */
    public function setUlid(Ulid $ulid): self
    {
        $this->ulid = $ulid;

        return $this;
    }
}
