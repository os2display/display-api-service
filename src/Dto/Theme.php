<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\BlameableTrait;
use App\Dto\Trait\IdentifiableTrait;
use App\Dto\Trait\TimestampableTrait;
use Symfony\Component\Serializer\Annotation\Groups;

class Theme
{
    use BlameableTrait;
    use IdentifiableTrait;
    use TimestampableTrait;

    #[Groups(['theme:read', 'playlist-slide:read', 'slides/playlists:read'])]
    public string $title = '';

    #[Groups(['theme:read', 'playlist-slide:read', 'slides/playlists:read'])]
    public string $description = '';

    #[Groups(['theme:read', 'playlist-slide:read', 'slides/playlists:read'])]
    public ?Media $logo;

    #[Groups(['theme:read', 'playlist-slide:read', 'slides/playlists:read'])]
    public string $cssStyles = '';
}
