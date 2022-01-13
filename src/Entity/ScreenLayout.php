<?php

namespace App\Entity;

use App\Repository\ScreenLayoutRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=ScreenLayoutRepository::class)
 */
class ScreenLayout
{
    use EntityIdTrait;
    use EntityTitleDescriptionTrait;
    use EntityModificationTrait;
    use TimestampableEntity;

    /**
     * @ORM\Column(type="integer", nullable=false, options={"default": 0})
     */
    private int $gridRows = 0;

    /**
     * @ORM\Column(type="integer", nullable=false, options={"default": 0})
     */
    private int $gridColumns = 0;

    /**
     * @ORM\OneToMany(targetEntity=Screen::class, mappedBy="screenLayout")
     */
    private Collection $screens;

    /**
     * @ORM\OneToMany(targetEntity=Campaign::class, mappedBy="screenLayout")
     */
    private Collection $campaigns;

    /**
     * @ORM\OneToMany(targetEntity=ScreenLayoutRegions::class, mappedBy="screenLayout")
     */
    private Collection $regions;

    public function __construct()
    {
        $this->screens = new ArrayCollection();
        $this->campaigns = new ArrayCollection();
        $this->regions = new ArrayCollection();
    }

    public function getGridRows(): int
    {
        return $this->gridRows;
    }

    public function setGridRows(int $gridRows): self
    {
        $this->gridRows = $gridRows;

        return $this;
    }

    public function getGridColumns(): int
    {
        return $this->gridColumns;
    }

    public function setGridColumns(int $gridColumns): self
    {
        $this->gridColumns = $gridColumns;

        return $this;
    }

    /**
     * @return Collection|Campaigns[]
     */
    public function getCampaigns(): ArrayCollection
    {
        return $this->campaigns;
    }

    public function addCampaign(Campaign $campaign): self
    {
        if (!$this->campaigns->contains($campaign)) {
            $this->campaigns->add($campaign);
            $campaign->setCampaignLayout($this);
        }

        return $this;
    }

    public function removeCampaign(Campaign $campaign): self
    {
        if ($this->campaigns->removeElement($campaign)) {
            // Set the owning side to null (unless already changed)
            if ($campaign->getCampaignLayout() === $this) {
                $campaign->setCampaignLayout(null);
            }
        }

        return $this;
    }

    public function removeAllCampaigns(): self
    {
        foreach ($this->campaigns as $campaign) {
            // Set the owning side to null (unless already changed)
            if ($campaign->getCampaignLayout() === $this) {
                $campaign->setCampaignLayout(null);
            }
        }

        $this->campaigns->clear();

        return $this;
    }

    /**
     * @return Collection|Screen[]
     */
    public function getScreens(): ArrayCollection
    {
        return $this->screens;
    }

    public function addScreen(Screen $screen): self
    {
        if (!$this->screens->contains($screen)) {
            $this->screens->add($screen);
            $screen->setScreenLayout($this);
        }

        return $this;
    }

    public function removeScreen(Screen $screen): self
    {
        if ($this->screens->removeElement($screen)) {
            // Set the owning side to null (unless already changed)
            if ($screen->getScreenLayout() === $this) {
                $screen->setScreenLayout(null);
            }
        }

        return $this;
    }

    public function removeAllScreen(): self
    {
        foreach ($this->screens as $screen) {
            // Set the owning side to null (unless already changed)
            if ($screen->getScreenLayout() === $this) {
                $screen->setScreenLayout(null);
            }
        }

        $this->screens->clear();

        return $this;
    }

    /**
     * @return Collection|ScreenLayoutRegions[]
     */
    public function getRegions(): Collection
    {
        return $this->regions;
    }

    public function addRegion(ScreenLayoutRegions $region): self
    {
        if (!$this->regions->contains($region)) {
            $this->regions[] = $region;
            $region->setScreenLayout($this);
        }

        return $this;
    }

    public function removeRegion(ScreenLayoutRegions $region): self
    {
        if ($this->regions->removeElement($region)) {
            // set the owning side to null (unless already changed)
            if ($region->getScreenLayout() === $this) {
                $region->setScreenLayout(null);
            }
        }

        return $this;
    }

    public function removeAllRegion(): self
    {
        foreach ($this->regions as $region) {
            // set the owning side to null (unless already changed)
            if ($region->getScreenLayout() === $this) {
                $region->setScreenLayout(null);
            }
        }

        $this->regions->clear();

        return $this;
    }
}
