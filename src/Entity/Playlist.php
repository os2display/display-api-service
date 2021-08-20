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
    use EntityTitleDescTrait;
    use TimestampableEntity;

    /**
     * @ORM\ManyToMany(targetEntity=Slide::class, inversedBy="playlists")
     */
    private Collection $slides;

    /**
     * @ORM\ManyToMany(targetEntity=Screen::class, mappedBy="playlists")
     */
    private $screens;

    public function __construct()
    {
        $this->slides = new ArrayCollection();
        $this->screens = new ArrayCollection();
    }

    /**
     * @return Collection|Slide[]
     */
    public function getSlides(): Collection
    {
        return $this->slides;
    }

    public function addSlide(Slide $slide): self
    {
        if (!$this->slides->contains($slide)) {
            $this->slides[] = $slide;
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
            $this->screens[] = $screen;
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
