<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\BlameableTrait;
use App\Dto\Trait\IdentifiableTrait;
use App\Dto\Trait\RelationsChecksumTrait;
use App\Dto\Trait\TimestampableTrait;
use Symfony\Component\Serializer\Annotation\Groups;

class ScreenCampaign
{
    use BlameableTrait;
    use IdentifiableTrait;
    use TimestampableTrait;
    use RelationsChecksumTrait;

    #[Groups(['screen-campaigns:read', 'campaigns/screens:read'])]
    public Playlist $campaign;

    #[Groups(['screen-campaigns:read', 'campaigns/screens:read'])]
    public Screen $screen;
}
