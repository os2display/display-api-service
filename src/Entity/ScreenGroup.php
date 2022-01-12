<?php

namespace App\Entity;

use App\Repository\ScreenGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=ScreenGroupRepository::class)
 */
class ScreenGroup
{
    use EntityIdTrait;
    use EntityTitleDescriptionTrait;
    use EntityModificationTrait;
    use TimestampableEntity;

    /**
     * @ORM\ManyToMany(targetEntity=Screen::class, inversedBy="screenGroups")
     */
    private $screens;

        /**
     * @ORM\ManyToMany(targetEntity=Campaign::class, inversedBy="screenGroups")
     */
    private $campaigns;

    public function __construct()
    {
        $this->screens = new ArrayCollection();
        $this->campaigns = new ArrayCollection();
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
    public function getCampaigns(): Collection
    {
        return $this->campaigns;
    }

    public function addCampaign(Campaign $campaign): self
    {
        if (!$this->campaigns->contains($campaign)) {
            $this->campaigns->add($campaign);
        }

        return $this;
    }

    public function removeCampaign(Campaign $campaign): self
    {
        $this->campaigns->removeElement($campaign);

        return $this;
    }
}
