<?php

namespace App\Entity;

use App\Repository\ScheduleRepository;
use Doctrine\ORM\Mapping as ORM;
use RRule\RRule;

/**
 * @ORM\Entity(repositoryClass=ScheduleRepository::class)
 */
class Schedule
{
    use EntityIdTrait;

    /**
     * @ORM\Column(type="rrule")
     */
    private ?RRule $rrule;

    /**
     * @ORM\Column(type="integer")
     */
    private ?int $duration;

    /**
     * @ORM\ManyToOne(targetEntity=Playlist::class, inversedBy="schedules")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Playlist $playlist;

    public function getRrule(): ?RRule
    {
        return $this->rrule;
    }

    public function setRrule(RRule $rrule): self
    {
        $this->rrule = $rrule;

        return $this;
    }

    public function getDuration(): ?int
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
