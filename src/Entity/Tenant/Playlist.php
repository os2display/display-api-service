<?php

namespace App\Entity\Tenant;

use App\Entity\Traits\MultiTenantTrait;
use App\Entity\Interfaces\MultiTenantInterface;
use App\Entity\Traits\EntityPublishedTrait;
use App\Entity\Traits\EntityTitleDescriptionTrait;
use App\Repository\PlaylistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PlaylistRepository::class)
 */
class Playlist extends AbstractTenantScopedEntity implements MultiTenantInterface
{
    use EntityPublishedTrait;
    use MultiTenantTrait;
    use EntityTitleDescriptionTrait;

    /**
     * @ORM\OneToMany(targetEntity=ScreenCampaign::class, mappedBy="campaign", orphanRemoval=true)
     */
    private Collection $screenCampaigns;

    /**
     * @ORM\OneToMany(targetEntity=ScreenGroupCampaign::class, mappedBy="campaign", orphanRemoval=true)
     */
    private Collection $screenGroupCampaigns;

    /**
     * @ORM\OneToMany(targetEntity=PlaylistScreenRegion::class, mappedBy="playlist", orphanRemoval=true)
     */
    private Collection $playlistScreenRegions;

    /**
     * @ORM\OneToMany(targetEntity=PlaylistSlide::class, mappedBy="playlist", orphanRemoval=true)
     * @ORM\OrderBy({"weight" = "ASC"})
     */
    private Collection $playlistSlides;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isCampaign = false;

    /**
     * @ORM\OneToMany(targetEntity=Schedule::class, mappedBy="playlist", orphanRemoval=true, cascade={"persist"})
     */
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
     * @return Collection
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
     * @return Collection|Schedule[]
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

    public function removeScreenGroupCampaign(ScreenGroupCampaign $screenGroupCampaign): self
    {
        if ($this->screenGroupCampaigns->removeElement($screenGroupCampaign)) {
            // set the owning side to null (unless already changed)
            if ($screenGroupCampaign->getCampaign() === $this) {
                $screenGroupCampaign->setCampaign(null);
            }
        }

        return $this;
    }
}
