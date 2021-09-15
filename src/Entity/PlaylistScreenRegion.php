<?php

namespace App\Entity;

use App\Repository\PlaylistScreenRegionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PlaylistScreenRegionRepository::class)
 */
class PlaylistScreenRegion
{
    use EntityIdTrait;

    /**
     * @ORM\ManyToOne(targetEntity=Playlist::class, inversedBy="playlistScreenRegions")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Playlist $playlist;

    /**
     * @ORM\ManyToOne(targetEntity=Screen::class, inversedBy="playlistScreenRegions")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Screen $screen;

    /**
     * @ORM\ManyToOne(targetEntity=ScreenLayoutRegions::class, inversedBy="playlistScreenRegions")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?ScreenLayoutRegions $region;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlaylist(): ?Playlist
    {
        return $this->playlist;
    }

    public function setPlaylist(Playlist $playlist): self
    {
        $this->playlist = $playlist;

        return $this;
    }

    public function removePlaylist(): self
    {
        $this->playlist = null;

        return $this;
    }

    public function getScreen(): ?Screen
    {
        return $this->screen;
    }

    public function setScreen(Screen $screen): self
    {
        $this->screen = $screen;

        return $this;
    }

    public function removeScreen(): self
    {
        $this->screen = null;

        return $this;
    }

    public function getRegion(): ?ScreenLayoutRegions
    {
        return $this->region;
    }

    public function setRegion(ScreenLayoutRegions $region): self
    {
        $this->region = $region;

        return $this;
    }

    public function removeRegion(): self
    {
        $this->region = null;

        return $this;
    }
}
