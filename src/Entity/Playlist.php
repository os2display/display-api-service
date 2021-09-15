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
    use TimestampableEntity;

    /**
     * @ORM\ManyToMany(targetEntity=Slide::class, inversedBy="playlists")
     */
    private ArrayCollection $slides;

    /**
     * @ORM\ManyToMany(targetEntity=Screen::class, mappedBy="playlists")
     */
    private ArrayCollection $screens;

    /**
     * @ORM\OneToMany(targetEntity=PlaylistScreenRegion::class, mappedBy="playlist", orphanRemoval=true)
     */
    private $playlistScreenRegions;

    public function __construct()
    {
        $this->slides = new ArrayCollection();
        $this->screens = new ArrayCollection();
        $this->playlistScreenRegions = new ArrayCollection();
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

    /**
     * @return Collection|PlaylistScreenRegion[]
     */
    public function getPlaylistScreenRegions(): Collection
    {
        return $this->playlistScreenRegions;
    }

    public function addPlaylistScreenRegion(PlaylistScreenRegion $playlistScreenRegion): self
    {
        if (!$this->playlistScreenRegions->contains($playlistScreenRegion)) {
            $this->playlistScreenRegions->add($playlistScreenRegion);
            $playlistScreenRegion->setPlaylist($this);
        }

        return $this;
    }

    public function removePlaylistScreenRegion(PlaylistScreenRegion $playlistScreenRegion): self
    {
        if ($this->playlistScreenRegions->removeElement($playlistScreenRegion)) {
            // set the owning side to null (unless already changed)
            if ($playlistScreenRegion->getPlaylist() === $this) {
                $playlistScreenRegion->removePlaylist();
            }
        }

        return $this;
    }
}
