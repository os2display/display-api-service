<?php

namespace App\Entity\Tenant;

use App\Entity\EntityIdTrait;
use App\Entity\Traits\EntityTitleDescriptionTrait;
use App\Repository\ScreenGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ScreenGroupRepository::class)
 */
class ScreenGroup extends AbstractTenantScopedEntity
{
    use EntityTitleDescriptionTrait;

    /**
     * @ORM\OneToMany(targetEntity=ScreenGroupCampaign::class, mappedBy="screenGroup", orphanRemoval=true)
     */
    private Collection $screenGroupCampaigns;

    /**
     * @ORM\ManyToMany(targetEntity=Screen::class, inversedBy="screenGroups")
     */
    private $screens;

    public function __construct()
    {
        $this->screens = new ArrayCollection();
        $this->screenGroupCampaigns = new ArrayCollection();
    }

    /**
     * @return Collection
     */
    public function getScreens(): Collection
    {
        return $this->screens;
    }

    public function addScreen(Screen $screen): self
    {
        if (!$this->screens->contains($screen)) {
            $this->screens->add($screen);
        }

        return $this;
    }

    public function removeScreen(Screen $screen): self
    {
        $this->screens->removeElement($screen);

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
            if ($screenGroupCampaign->getScreen() === $this) {
                $screenGroupCampaign->setScreen(null);
            }
        }

        return $this;
    }
}
