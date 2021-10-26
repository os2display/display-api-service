<?php

namespace App\Entity;

use App\Repository\ScreenGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=ScreenGroupRepository::class)
 */
class ScreenGroup implements EntitySharedInterface
{
    use EntityIdTrait;
    use EntityTitleDescriptionTrait;
    use EntityModificationTrait;
    use TimestampableEntity;

    /**
     * @ORM\ManyToMany(targetEntity=Screen::class, inversedBy="screenGroups")
     */
    private $screens;

    public function __construct()
    {
        $this->screens = new ArrayCollection();
    }

    /**
     * @return Collection
     */
    public function getScreens(): Collection
    {
        return $this->screens;
    }

    public function addScreen(Screen $screen): self
    {
        if (!$this->screens->contains($screen)) {
            $this->screens->add($screen);
        }

        return $this;
    }

    public function removeScreen(Screen $screen): self
    {
        $this->screens->removeElement($screen);

        return $this;
    }
}
