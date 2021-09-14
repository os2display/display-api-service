<?php

namespace App\Entity;

use App\Repository\ScreenLayoutRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=ScreenLayoutRepository::class)
 */
class ScreenLayout
{
    use EntityIdTrait;
    use EntityTitleDescriptionTrait;
    use TimestampableEntity;

    /**
     * @ORM\Column(type="integer")
     */
    private int $gridRows;

    /**
     * @ORM\Column(type="integer")
     */
    private int $gridColumns;

    /**
     * @ORM\Column(type="array")
     */
    private $regions = [];

    /**
     * @ORM\OneToMany(targetEntity=Screen::class, mappedBy="screenLayout")
     */
    private $screens;

    public function __construct()
    {
        $this->screens = new ArrayCollection();
    }

    public function getGridRows(): ?int
    {
        return $this->gridRows;
    }

    public function setGridRows(int $gridRows): self
    {
        $this->gridRows = $gridRows;

        return $this;
    }

    public function getGridColumns(): ?int
    {
        return $this->gridColumns;
    }

    public function setGridColumns(int $gridColumns): self
    {
        $this->gridColumns = $gridColumns;

        return $this;
    }

    public function getRegions(): ?array
    {
        return $this->regions;
    }

    public function setRegions(array $regions): self
    {
        $this->regions = $regions;

        return $this;
    }

    /**
     * @return Collection|Screen[]
     */
    public function getScreens(): Collection
    {
        return $this->screens;
    }

    public function addScreen(Screen $screen): self
    {
        if (!$this->screens->contains($screen)) {
            $this->screens[] = $screen;
            $screen->setScreenLayout($this);
        }

        return $this;
    }

    public function removeScreen(Screen $screen): self
    {
        if ($this->screens->removeElement($screen)) {
            // set the owning side to null (unless already changed)
            if ($screen->getScreenLayout() === $this) {
                $screen->setScreenLayout(null);
            }
        }

        return $this;
    }
}
