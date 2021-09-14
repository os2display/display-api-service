<?php

namespace App\Entity;

use App\Repository\PlaylistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=PlaylistRepository::class)
 */
class Playlist
{
    use EntityIdTrait;
    use EntityPublishedTrait;
    use EntityTitleDescriptionTrait;
    use EntityModificationTrait;

    /**
     * @ORM\ManyToMany(targetEntity=Slide::class, inversedBy="playlists")
     */
    private ArrayCollection $slides;

    /**
     * @ORM\ManyToMany(targetEntity=Screen::class, mappedBy="playlists")
     */
    private ArrayCollection $screens;

    public function __construct()
    {
        $this->slides = new ArrayCollection();
        $this->screens = new ArrayCollection();
    }

    /**
     * @return ArrayCollection|Slide[]
     */
    public function getSlides(): ArrayCollection
    {
        return $this->slides;
    }

    public function addSlide(Slide $slide): self
    {
        if (!$this->slides->contains($slide)) {
            $this->slides->add($slide);
        }

        return $this;
    }

    public function removeSlide(Slide $slide): self
    {
        $this->slides->removeElement($slide);

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
            $this->screens->add($screen);
            $screen->addPlaylist($this);
        }

        return $this;
    }

    public function removeScreen(Screen $screen): self
    {
        if ($this->screens->removeElement($screen)) {
            $screen->removePlaylist($this);
        }

        return $this;
    }
}
