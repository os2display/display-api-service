<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\BlameableTrait;
use App\Dto\Trait\IdentifiableTrait;
use App\Dto\Trait\RelationsChecksumTrait;
use App\Dto\Trait\TimestampableTrait;
use Symfony\Component\Serializer\Annotation\Groups;

class Screen
{
    use BlameableTrait;
    use IdentifiableTrait;
    use TimestampableTrait;
    use RelationsChecksumTrait;

    #[Groups(['campaigns/screens:read', 'screen-groups/screens:read'])]
    public string $title = '';

    #[Groups(['campaigns/screens:read', 'screen-groups/screens:read'])]
    public string $description = '';

    #[Groups(['campaigns/screens:read', 'screen-groups/screens:read'])]
    public string $size = '';

    #[Groups(['campaigns/screens:read', 'screen-groups/screens:read'])]
    public string $campaigns = '';

    #[Groups(['campaigns/screens:read', 'screen-groups/screens:read'])]
    public string $layout = '';

    #[Groups(['campaigns/screens:read', 'screen-groups/screens:read'])]
    public string $orientation = '';

    #[Groups(['campaigns/screens:read', 'screen-groups/screens:read'])]
    public string $resolution = '';

    #[Groups(['campaigns/screens:read', 'screen-groups/screens:read'])]
    public string $location = '';

    #[Groups(['campaigns/screens:read', 'screen-groups/screens:read'])]
    public array $regions = [];

    #[Groups(['campaigns/screens:read', 'screen-groups/screens:read'])]
    public string $inScreenGroups = '/v2/screens/{id}/groups';

    #[Groups(['campaigns/screens:read', 'screen-groups/screens:read'])]
    public ?string $screenUser = null;

    #[Groups(['campaigns/screens:read', 'screen-groups/screens:read'])]
    public ?bool $enableColorSchemeChange = null;

    #[Groups(['campaigns/screens:read', 'screen-groups/screens:read'])]
    public ?array $status = null;
}
