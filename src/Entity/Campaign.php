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
    use EntityTitleDescriptionTrait;
    use EntityModificationTrait;
    use TimestampableEntity;

    /**
     * @ORM\ManyToOne(targetEntity=ScreenLayout::class, inversedBy="campaigns")
     * @ORM\JoinColumn(nullable=false)
     */
    private ScreenLayout $screenLayout;

    public function __construct()
    {
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

}
