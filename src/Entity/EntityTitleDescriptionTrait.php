<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait EntityTitleDescriptionTrait
{
    /**
     * @ORM\Column(type="string", length=255, nullable=false, options={"default": ""})
     */
    private string $title;

    /**
     * @ORM\Column(type="string", length=255, nullable=false, options={"default": ""})
     */
    private string $description;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
