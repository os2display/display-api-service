<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class ScreenLayoutRegions
{
    use EntityIdTrait;

    /**
     * @ORM\Column(type="string", length=255, nullable=false, options={"default" : ""})
     */
    private string $title = '';

    /**
     * @ORM\Column(type="array", nullable=false)
     */
    private array $gridArea = [];

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getGridArea(): array
    {
        return $this->gridArea;
    }

    public function setGridArea(array $gridArea): self
    {
        $this->gridArea = $gridArea;

        return $this;
    }
}
