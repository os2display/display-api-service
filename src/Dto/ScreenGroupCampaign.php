<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\IdentifiableTrait;
use App\Dto\Trait\RelationsChecksumTrait;
use Symfony\Component\Serializer\Annotation\Groups;

class ScreenGroupCampaign
{
    use IdentifiableTrait;
    use RelationsChecksumTrait;

    #[Groups(['screen-groups/campaigns:read', 'campaigns/screen-groups:read'])]
    public Playlist $campaign;

    #[Groups(['screen-groups/campaigns:read', 'campaigns/screen-groups:read'])]
    public ScreenGroup $screenGroup;
}
