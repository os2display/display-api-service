<?php

declare(strict_types=1);

namespace App\Entity\Tenant;

use App\Entity\Interfaces\RelationsChecksumInterface;
use App\Entity\Traits\RelationsChecksumTrait;
use App\Repository\ScreenGroupCampaignRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScreenGroupCampaignRepository::class)]
#[ORM\Index(fields: ['changed'], name: 'changed_idx')]
class ScreenGroupCampaign extends AbstractTenantScopedEntity implements RelationsChecksumInterface
{
    use RelationsChecksumTrait;

    #[ORM\ManyToOne(targetEntity: Playlist::class, inversedBy: 'screenGroupCampaigns')]
    #[ORM\JoinColumn(nullable: false)]
    private Playlist $campaign;

    #[ORM\ManyToOne(targetEntity: ScreenGroup::class, inversedBy: 'screenGroupCampaigns')]
    #[ORM\JoinColumn(nullable: false)]
    private ScreenGroup $screenGroup;

    public function getCampaign(): Playlist
    {
        return $this->campaign;
    }

    public function setCampaign(Playlist $campaign): self
    {
        $this->campaign = $campaign;

        return $this;
    }

    public function getScreenGroup(): ScreenGroup
    {
        return $this->screenGroup;
    }

    public function setScreenGroup(ScreenGroup $screenGroup): self
    {
        $this->screenGroup = $screenGroup;

        return $this;
    }
}
