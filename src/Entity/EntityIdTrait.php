<?php

namespace App\Entity;

use Symfony\Component\Uid\Ulid;

trait EntityIdTrait
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="ulid", unique=true)
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
     * @return $this
     */
    public function setUlid(Ulid $ulid): self
    {
        $this->ulid = $ulid;

        return $this;
    }
}
