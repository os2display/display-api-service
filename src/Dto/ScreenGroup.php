<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\BlameableTrait;
use App\Dto\Trait\IdentifiableTrait;
use App\Dto\Trait\RelationsModifiedTrait;
use App\Dto\Trait\TimestampableTrait;
use Symfony\Component\Serializer\Annotation\Groups;

class ScreenGroup
{
    use BlameableTrait;
    use IdentifiableTrait;
    use TimestampableTrait;
    use RelationsModifiedTrait;

    #[Groups(['screens/screen-groups:read', 'screen-groups/campaigns:read', 'campaigns/screen-groups:read'])]
    public string $title = '';

    #[Groups(['screens/screen-groups:read', 'screen-groups/campaigns:read', 'campaigns/screen-groups:read'])]
    public string $description = '';

    #[Groups(['screens/screen-groups:read', 'screen-groups/campaigns:read', 'campaigns/screen-groups:read'])]
    public string $campaigns = '';

    #[Groups(['screens/screen-groups:read', 'screen-groups/campaigns:read', 'campaigns/screen-groups:read'])]
    public string $screens = '';
}
