<?php

declare(strict_types=1);

namespace App\Entity\Tenant;

use App\Repository\ScheduleRepository;
use Doctrine\ORM\Mapping as ORM;
use RRule\RRule;

#[ORM\Entity(repositoryClass: ScheduleRepository::class)]
class Schedule extends AbstractTenantScopedEntity
{
    #[ORM\Column(type: 'rrule')]
    private RRule $rrule;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    private int $duration = 0;

    #[ORM\ManyToOne(targetEntity: Playlist::class, inversedBy: 'schedules')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Playlist $playlist = null;

    public function getRrule(): RRule
    {
        return $this->rrule;
    }

    public function setRrule(RRule $rrule): self
    {
        $this->rrule = $rrule;

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

    public function getPlaylist(): ?Playlist
    {
        return $this->playlist;
    }

    public function setPlaylist(?Playlist $playlist): self
    {
        $this->playlist = $playlist;

        return $this;
    }
}
