<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\BlameableTrait;
use App\Dto\Trait\IdentifiableTrait;
use App\Dto\Trait\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;

class Playlist
{
    use BlameableTrait;
    use IdentifiableTrait;
    use TimestampableTrait;

    #[Groups(['playlist-screen-region:read', 'screen-campaigns:read', 'campaigns/screens:read', 'slides/playlists:read', 'screen-groups/campaigns:read', 'campaigns/screen-groups:read'])]
    public string $title = '';

    #[Groups(['playlist-screen-region:read', 'screen-campaigns:read', 'campaigns/screens:read', 'slides/playlists:read', 'screen-groups/campaigns:read', 'campaigns/screen-groups:read'])]
    public string $description = '';

    #[Groups(['playlist-screen-region:read', 'screen-campaigns:read', 'campaigns/screens:read', 'slides/playlists:read', 'screen-groups/campaigns:read', 'campaigns/screen-groups:read'])]
    public array $schedules = [];

    #[Groups(['playlist-screen-region:read', 'screen-campaigns:read', 'campaigns/screens:read', 'slides/playlists:read', 'screen-groups/campaigns:read', 'campaigns/screen-groups:read'])]
    public string $slides = '';

    #[Groups(['playlist-screen-region:read', 'screen-campaigns:read', 'campaigns/screens:read', 'slides/playlists:read', 'screen-groups/campaigns:read', 'campaigns/screen-groups:read'])]
    public Collection $campaignScreens;

    #[Groups(['playlist-screen-region:read', 'screen-campaigns:read', 'campaigns/screens:read', 'slides/playlists:read', 'screen-groups/campaigns:read', 'campaigns/screen-groups:read'])]
    public Collection $campaignScreenGroups;

    #[Groups(['playlist-screen-region:read', 'screen-campaigns:read', 'campaigns/screens:read', 'slides/playlists:read', 'screen-groups/campaigns:read', 'campaigns/screen-groups:read'])]
    public Collection $tenants;

    #[Groups(['playlist-screen-region:read', 'screen-campaigns:read', 'campaigns/screens:read', 'slides/playlists:read', 'screen-groups/campaigns:read', 'campaigns/screen-groups:read'])]
    public bool $isCampaign;

    #[Groups(['playlist-screen-region:read', 'screen-campaigns:read', 'campaigns/screens:read', 'slides/playlists:read', 'screen-groups/campaigns:read', 'campaigns/screen-groups:read'])]
    public array $published = [
        'from' => '',
        'to' => '',
    ];

    public function __construct()
    {
        $this->campaignScreens = new ArrayCollection();
        $this->campaignScreenGroups = new ArrayCollection();
    }
}
