<?php

declare(strict_types=1);

namespace App\Entity\Tenant;

use App\Entity\Traits\RelationsModifiedAtTrait;
use App\Repository\PlaylistSlideRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlaylistSlideRepository::class)]
#[ORM\Index(fields: ["relationsModifiedAt"], name: "relations_modified_at_idx")]
#[ORM\Index(fields: ["modifiedAt"], name: "modified_at_idx")]
class PlaylistSlide extends AbstractTenantScopedEntity
{
    use RelationsModifiedAtTrait;

    #[ORM\ManyToOne(targetEntity: Playlist::class, inversedBy: 'playlistSlides')]
    #[ORM\JoinColumn(nullable: false)]
    private Playlist $playlist;

    #[ORM\ManyToOne(targetEntity: Slide::class, inversedBy: 'playlistSlides')]
    #[ORM\JoinColumn(nullable: false)]
    private Slide $slide;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER, options: ['default' => 0])]
    private int $weight = 0;

    public function getPlaylist(): Playlist
    {
        return $this->playlist;
    }

    public function setPlaylist(Playlist $playlist): self
    {
        $this->playlist = $playlist;

        return $this;
    }

    public function getSlide(): Slide
    {
        return $this->slide;
    }

    public function setSlide(Slide $slide): self
    {
        $this->slide = $slide;

        return $this;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): self
    {
        $this->weight = $weight;

        return $this;
    }
}
