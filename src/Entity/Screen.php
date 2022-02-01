<?php

namespace App\Entity;

use App\Repository\ScreenRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=ScreenRepository::class)
 */
class Screen
{
    use EntityIdTrait;
    use EntityTitleDescriptionTrait;
    use EntityModificationTrait;
    use TimestampableEntity;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private int $size = 0;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private int $resolutionWidth = 0;

    /**
     * @ORM\OneToMany(targetEntity=ScreenCampaign::class, mappedBy="screen", orphanRemoval=true)
     */
    private Collection $screenCampaigns;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private int $resolutionHeight = 0;

    /**
     * @ORM\ManyToOne(targetEntity=ScreenLayout::class, inversedBy="screens")
     * @ORM\JoinColumn(nullable=false)
     */
    private ScreenLayout $screenLayout;

    /**
     * @ORM\Column(type="string", length=255, nullable=true, options={"default": ""})
     */
    private string $location = '';

    /**
     * @ORM\OneToMany(targetEntity=PlaylistScreenRegion::class, mappedBy="screen", orphanRemoval=true)
     * @ORM\OrderBy({"weight" = "ASC"})
     */
    private Collection $playlistScreenRegions;

    /**
     * @ORM\ManyToMany(targetEntity=ScreenGroup::class, mappedBy="screens")
     */
    private $screenGroups;

    public function __construct()
    {
        $this->playlistScreenRegions = new ArrayCollection();
        $this->screenCampaigns = new ArrayCollection();
        $this->screenGroups = new ArrayCollection();
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getResolutionWidth(): int
    {
        return $this->resolutionWidth;
    }

    public function setResolutionWidth(int $resolutionWidth): self
    {
        $this->resolutionWidth = $resolutionWidth;

        return $this;
    }

    public function getResolutionHeight(): int
    {
        return $this->resolutionHeight;
    }

    public function setResolutionHeight(int $resolutionHeight): self
    {
        $this->resolutionHeight = $resolutionHeight;

        return $this;
    }

    public function getScreenLayout(): ScreenLayout
    {
        return $this->screenLayout;
    }

    public function setScreenLayout(ScreenLayout $screenLayout): self
    {
        $this->screenLayout = $screenLayout;

        return $this;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @return ArrayCollection|PlaylistScreenRegion[]
     */
    public function getPlaylistScreenRegions(): Collection
    {
        return $this->playlistScreenRegions;
    }

    public function addPlaylistScreenRegion(PlaylistScreenRegion $playlistScreenRegion): self
    {
        if (!$this->playlistScreenRegions->contains($playlistScreenRegion)) {
            $this->playlistScreenRegions->add($playlistScreenRegion);
            $playlistScreenRegion->setScreen($this);
        }

        return $this;
    }

    public function removePlaylistScreenRegion(PlaylistScreenRegion $playlistScreenRegion): self
    {
        if ($this->playlistScreenRegions->removeElement($playlistScreenRegion)) {
            // set the owning side to null (unless already changed)
            if ($playlistScreenRegion->getScreen() === $this) {
                $playlistScreenRegion->removeScreen();
            }
        }

        return $this;
    }

    public function removeAllPlaylistScreenRegions(): self
    {
        foreach ($this->playlistScreenRegions as $playlistScreenRegion) {
            // set the owning side to null (unless already changed)
            if ($playlistScreenRegion->getScreen() === $this) {
                $playlistScreenRegion->removeScreen();
            }
        }

        $this->playlistScreenRegions->clear();

        return $this;
    }

    /**
     * @return Collection|ScreenGroup[]
     */
    public function getScreenGroups(): Collection
    {
        return $this->screenGroups;
    }

    public function addScreenGroup(ScreenGroup $screenGroup): self
    {
        if (!$this->screenGroups->contains($screenGroup)) {
            $this->screenGroups->add($screenGroup);
            $screenGroup->addScreen($this);
        }

        return $this;
    }

    public function removeScreenGroup(ScreenGroup $screenGroup): self
    {
        if ($this->screenGroups->removeElement($screenGroup)) {
            $screenGroup->removeScreen($this);
        }

        return $this;
    }

    public function removeAllScreenGroup(): self
    {
        foreach ($this->getScreenGroups() as $screenGroup) {
            // set the owning side to null (unless already changed)
            if ($screenGroup->getScreens()->contains($this)) {
                $screenGroup->getScreens()->removeElement($this);
            }
        }

        $this->screenGroups->clear();

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
            if ($screenCampaign->getScreen() === $this) {
                $screenCampaign->setScreen(null);
            }
        }

        return $this;
    }
}
