<?php

namespace App\Entity;

use App\Repository\SlideRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=SlideRepository::class)
 */
class Slide
{
    use EntityIdTrait;
    use EntityPublishedTrait;
    use EntityTitleDescriptionTrait;
    use EntityModificationTrait;

    /**
     * @ORM\ManyToOne(targetEntity=Template::class, inversedBy="slides")
     * @ORM\JoinColumn(nullable=false)
     */
    private Template $template;

    // @TODO: template options array to override template settings

    /**
     * @ORM\Column(type="integer", nullable=false, options={"default": -1})
     *
     * @TODO: Talk about default value for not set to ensure type safety. - cableman
     */
    private int $duration = -1;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private array $content = [];

    /**
     * @ORM\ManyToMany(targetEntity=Playlist::class, mappedBy="slides")
     */
    private ArrayCollection $playlists;

    // @TODO: Missing onScreens

    public function __construct()
    {
        $this->playlists = new ArrayCollection();
    }

    /**
     * @return Template
     */
    public function getTemplate(): ?Template
    {
        return $this->template;
    }

    public function setTemplate(?Template $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getContent(): array
    {
        return $this->content;
    }

    public function setContent(array $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return ArrayCollection|Playlist[]
     */
    public function getPlaylists(): ArrayCollection
    {
        return $this->playlists;
    }

    public function addPlaylist(Playlist $playlist): self
    {
        if (!$this->playlists->contains($playlist)) {
            $this->playlists->add($playlist);
            $playlist->addSlide($this);
        }

        return $this;
    }

    public function removePlaylist(Playlist $playlist): self
    {
        if ($this->playlists->removeElement($playlist)) {
            $playlist->removeSlide($this);
        }

        return $this;
    }
}
