<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\BlameableTrait;
use App\Dto\Trait\IdentifiableTrait;
use App\Dto\Trait\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;

class Media
{
    use BlameableTrait;
    use IdentifiableTrait;
    use TimestampableTrait;

    #[Groups(['theme:read', 'playlist-slide:read', 'slides/playlists:read'])]
    public string $title = '';

    #[Groups(['theme:read', 'playlist-slide:read', 'slides/playlists:read'])]
    public string $description = '';

    #[Groups(['theme:read', 'playlist-slide:read', 'slides/playlists:read'])]
    public string $license = '';

    public Collection $media;

    #[Groups(['theme:read', 'playlist-slide:read', 'slides/playlists:read'])]
    public array $assets = [];

    #[Groups(['theme:read', 'playlist-slide:read', 'slides/playlists:read'])]
    public ?string $thumbnail = null;

    public function __construct()
    {
        $this->media = new ArrayCollection();
    }
}
