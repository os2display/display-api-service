<?php

declare(strict_types=1);

namespace App\Entity\Tenant;

use App\Entity\Interfaces\RelationsChecksumInterface;
use App\Entity\Traits\EntityTitleDescriptionTrait;
use App\Entity\Traits\RelationsChecksumTrait;
use App\Repository\ScreenGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScreenGroupRepository::class)]
#[ORM\Index(fields: ['changed'], name: 'changed_idx')]
class ScreenGroup extends AbstractTenantScopedEntity implements RelationsChecksumInterface
{
    use EntityTitleDescriptionTrait;
    use RelationsChecksumTrait;

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Tenant\ScreenGroupCampaign>|\App\Entity\Tenant\ScreenGroupCampaign[]
     */
    #[ORM\OneToMany(mappedBy: 'screenGroup', targetEntity: ScreenGroupCampaign::class, orphanRemoval: true)]
    private Collection $screenGroupCampaigns;

    #[ORM\ManyToMany(targetEntity: Screen::class, inversedBy: 'screenGroups')]
    private Collection $screens;

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
            $screenGroupCampaign->setScreenGroup($this);
        }

        return $this;
    }
}
