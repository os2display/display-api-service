<?php

namespace App\Entity;

use App\Repository\CampaignRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=CampaignRepository::class)
 */
class Campaign
{
    use EntityIdTrait;
    use EntityPublishedTrait;
    use EntityTitleDescriptionTrait;
    use EntityModificationTrait;
    use TimestampableEntity;

    /**
     * @ORM\ManyToOne(targetEntity=ScreenLayout::class, inversedBy="campaigns")
     * @ORM\JoinColumn(nullable=false)
     */
    private ScreenLayout $screenLayout;

    /**
     * @ORM\ManyToMany(targetEntity=ScreenGroup::class, mappedBy="campaigns")
     */
    private $screenGroups;

        /**
     * @ORM\ManyToMany(targetEntity=Playlist::class, inversedBy="campaigns")
     */
    private Collection $playlists;

    public function __construct()
    {
        $this->screenGroups = new ArrayCollection();
        $this->playlists = new ArrayCollection();
    }

    public function getCampaignLayout(): ScreenLayout
    {
        return $this->screenLayout;
    }

    public function setCampaignLayout(ScreenLayout $screenLayout): self
    {
        $this->screenLayout = $screenLayout;

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
           $screenGroup->addCampaign($this);
       }

       return $this;
   }

   public function removeScreenGroup(ScreenGroup $screenGroup): self
   {
       if ($this->screenGroups->removeElement($screenGroup)) {
           $screenGroup->removeCampaign($this);
       }

       return $this;
   }

   public function removeAllScreenGroup(): self
   {
       foreach ($this->getScreenGroups() as $screenGroup) {
           // set the owning side to null (unless already changed)
           if ($screenGroup->getCampaigns()->contains($this)) {
               $screenGroup->getCampaigns()->removeElement($this);
           }
       }

       $this->screenGroups->clear();

       return $this;
   }


    /**
     * @return ArrayCollection|Playlist[]
     */
    public function getPlaylists(): Collection
    {
        return $this->playlists;
    }

    public function addPlaylist(Playlist $playlist): self
    {
        if (!$this->playlists->contains($playlist)) {
            $this->playlists->add($playlist);
        }

        return $this;
    }

    public function removePlaylist(Playlist $playlist): self
    {
        $this->playlists->removeElement($playlist);

        return $this;
    }

    public function removeAllPlaylists(): self
    {
        $this->playlists->clear();

        return $this;
    }

}
