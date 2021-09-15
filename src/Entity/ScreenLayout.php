<?php

namespace App\Entity;

use App\Repository\ScreenLayoutRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use JetBrains\PhpStorm\Pure;

/**
 * @ORM\Entity(repositoryClass=ScreenLayoutRepository::class)
 */
class ScreenLayout
{
    use EntityIdTrait;
    use EntityTitleDescriptionTrait;
    use EntityModificationTrait;
    use TimestampableEntity;

    /**
     * @ORM\Column(type="integer", nullable=false, options={"default": 0})
     */
    private int $gridRows = 0;

    /**
     * @ORM\Column(type="integer", nullable=false, options={"default": 0})
     */
    private int $gridColumns = 0;

    /**
     * @ORM\OneToMany(targetEntity=ScreenLayoutRegions::class, mappedBy="screenLayoutRegions")
     */
    private ArrayCollection $regions;

    /**
     * @ORM\OneToMany(targetEntity=Screen::class, mappedBy="screenLayout")
     */
    private ArrayCollection $screens;

    public function __construct()
    {
        $this->screens = new ArrayCollection();
        $this->regions = new ArrayCollection();
    }

    public function getGridRows(): int
    {
        return $this->gridRows;
    }

    public function setGridRows(int $gridRows): self
    {
        $this->gridRows = $gridRows;

        return $this;
    }

    public function getGridColumns(): int
    {
        return $this->gridColumns;
    }

    public function setGridColumns(int $gridColumns): self
    {
        $this->gridColumns = $gridColumns;

        return $this;
    }

    /**
     * @return ArrayCollection|ScreenLayoutRegions[]
     */
    public function getRegions(): ArrayCollection
    {
        return $this->regions;
    }

    public function setRegions(ArrayCollection $region): self
    {
        if (!$this->regions->contains($region)) {
            $this->regions->add($region);
        }

        return $this;
    }

    /**
     * @return ArrayCollection|Screen[]
     */
    public function getScreens(): ArrayCollection
    {
        return $this->screens;
    }

    public function addScreen(Screen $screen): self
    {
        if (!$this->screens->contains($screen)) {
            $this->screens->add($screen);
            $screen->setScreenLayout($this);
        }

        return $this;
    }

    public function removeScreen(Screen $screen): self
    {
        if ($this->screens->removeElement($screen)) {
            // Set the owning side to null (unless already changed)
            if ($screen->getScreenLayout() === $this) {
                $screen->setScreenLayout(null);
            }
        }

        return $this;
    }
}
