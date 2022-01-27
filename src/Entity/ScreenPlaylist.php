<?php

namespace App\Entity;

use App\Repository\ScreenPlaylistRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ScreenPlaylistRepository::class)
 */
class ScreenPlaylist
{
    use EntityIdTrait;

    /**
     * @ORM\ManyToOne(targetEntity=Playlist::class, inversedBy="screenPlaylists")
     * @ORM\JoinColumn(nullable=false)
     */
    private Playlist $playlist;

    /**
     * @ORM\ManyToOne(targetEntity=Screen::class, inversedBy="screenPlaylists")
     * @ORM\JoinColumn(nullable=false)
     */
    private Screen $screen;

    public function getPlaylist(): Playlist
    {
        return $this->playlist;
    }

    public function setPlaylist(Playlist $playlist): self
    {
        $this->playlist = $playlist;

        return $this;
    }

    public function getScreen(): Screen
    {
        return $this->screen;
    }

    public function setScreen(Screen $screen): self
    {
        $this->screen = $screen;

        return $this;
    }
}
