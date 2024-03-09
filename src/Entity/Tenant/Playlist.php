<?php

declare(strict_types=1);

namespace App\Entity\Tenant;

use App\Entity\Interfaces\MultiTenantInterface;
use App\Entity\Interfaces\RelationsChecksumInterface;
use App\Entity\Traits\EntityPublishedTrait;
use App\Entity\Traits\EntityTitleDescriptionTrait;
use App\Entity\Traits\MultiTenantTrait;
use App\Entity\Traits\RelationsChecksumTrait;
use App\Repository\PlaylistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlaylistRepository::class)]
#[ORM\Index(fields: ['changed'], name: 'changed_idx')]
class Playlist extends AbstractTenantScopedEntity implements MultiTenantInterface, RelationsChecksumInterface
{
    use EntityPublishedTrait;
    use MultiTenantTrait;
    use EntityTitleDescriptionTrait;
    use RelationsChecksumTrait;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    private bool $isCampaign = false;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Tenant\ScreenCampaign>|\App\Entity\Tenant\ScreenCampaign[]
     */
    #[ORM\OneToMany(mappedBy: 'campaign', targetEntity: ScreenCampaign::class, orphanRemoval: true)]
    private Collection $screenCampaigns;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Tenant\ScreenGroupCampaign>|\App\Entity\Tenant\ScreenGroupCampaign[]
     */
    #[ORM\OneToMany(mappedBy: 'campaign', targetEntity: ScreenGroupCampaign::class, orphanRemoval: true)]
    private Collection $screenGroupCampaigns;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Tenant\PlaylistScreenRegion>|\App\Entity\Tenant\PlaylistScreenRegion[]
     */
    #[ORM\OneToMany(mappedBy: 'playlist', targetEntity: PlaylistScreenRegion::class, orphanRemoval: true)]
    private Collection $playlistScreenRegions;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Tenant\PlaylistSlide>|\App\Entity\Tenant\PlaylistSlide[]
     */
    #[ORM\OneToMany(mappedBy: 'playlist', targetEntity: PlaylistSlide::class, orphanRemoval: true)]
    #[ORM\OrderBy(['weight' => \Doctrine\Common\Collections\Order::Ascending->value])]
    private Collection $playlistSlides;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Tenant\Schedule>|\App\Entity\Tenant\Schedule[]
     */
    #[ORM\OneToMany(mappedBy: 'playlist', targetEntity: Schedule::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $schedules;

    public function __construct()
    {
        $this->playlistScreenRegions = new ArrayCollection();
        $this->playlistSlides = new ArrayCollection();
        $this->schedules = new ArrayCollection();
        $this->tenants = new ArrayCollection();
        $this->screenCampaigns = new ArrayCollection();
        $this->screenGroupCampaigns = new ArrayCollection();
    }

    public function getIsCampaign(): bool
    {
        return $this->isCampaign;
    }

    public function setIsCampaign(bool $isCampaign): self
    {
        $this->isCampaign = $isCampaign;

        return $this;
    }

    /**
     * @return Collection
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

    public function removeAllPlaylistScreenRegions(): self
    {
        foreach ($this->playlistScreenRegions as $playlistScreenRegion) {
            // set the owning side to null (unless already changed)
            if ($playlistScreenRegion->getPlaylist() === $this) {
                $playlistScreenRegion->removePlaylist();
            }
        }

        $this->playlistScreenRegions->clear();

        return $this;
    }

    /**
     * @return Collection<PlaylistSlide>
     */
    public function getPlaylistSlides(): Collection
    {
        return $this->playlistSlides;
    }

    public function addPlaylistSlide(PlaylistSlide $playlistSlide): self
    {
        if (!$this->playlistSlides->contains($playlistSlide)) {
            $this->playlistSlides[] = $playlistSlide;
            $playlistSlide->setPlaylist($this);
        }

        return $this;
    }

    public function removePlaylistSlide(PlaylistSlide $playlistSlide): self
    {
        if ($this->playlistSlides->removeElement($playlistSlide)) {
            // set the owning side to null (unless already changed)
            if ($playlistSlide->getPlaylist() === $this) {
                $playlistSlide->setPlaylist(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection
     */
    public function getSchedules(): Collection
    {
        return $this->schedules;
    }

    public function addSchedule(Schedule $schedule): self
    {
        if (!$this->schedules->contains($schedule)) {
            $this->schedules[] = $schedule;
            $schedule->setPlaylist($this);
        }

        return $this;
    }

    public function removeSchedule(Schedule $schedule): self
    {
        if ($this->schedules->removeElement($schedule)) {
            // set the owning side to null (unless already changed)
            if ($schedule->getPlaylist() === $this) {
                $schedule->setPlaylist(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection
     */
    public function getScreenCampaigns(): Collection
    {
        return $this->screenCampaigns;
    }

    public function addScreenCampaign(ScreenCampaign $screenCampaign): self
    {
        if (!$this->screenCampaigns->contains($screenCampaign)) {
            $this->screenCampaigns[] = $screenCampaign;
            $screenCampaign->setCampaign($this);
        }

        return $this;
    }

    public function removeScreenCampaign(ScreenCampaign $screenCampaign): self
    {
        if ($this->screenCampaigns->removeElement($screenCampaign)) {
            // set the owning side to null (unless already changed)
            if ($screenCampaign->getCampaign() === $this) {
                $screenCampaign->setCampaign(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection
     */
    public function getScreenGroupCampaigns(): Collection
    {
        return $this->screenGroupCampaigns;
    }

    public function addScreenGroupCampaign(ScreenGroupCampaign $screenGroupCampaign): self
    {
        if (!$this->screenGroupCampaigns->contains($screenGroupCampaign)) {
            $this->screenGroupCampaigns[] = $screenGroupCampaign;
            $screenGroupCampaign->setCampaign($this);
        }

        return $this;
    }
}
