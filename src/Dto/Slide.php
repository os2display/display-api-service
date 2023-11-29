<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\BlameableTrait;
use App\Dto\Trait\IdentifiableTrait;
use App\Dto\Trait\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;

class Slide
{
    use BlameableTrait;
    use IdentifiableTrait;
    use TimestampableTrait;

    #[Groups(['playlist-slide:read'])]
    public string $title = '';

    #[Groups(['playlist-slide:read'])]
    public string $description = '';

    #[Groups(['playlist-slide:read'])]
    public array $templateInfo = [
        '@id' => '',
        'options' => [],
    ];

    #[Groups(['playlist-slide:read'])]
    public string $theme = '';

    #[Groups(['playlist-slide:read'])]
    public Collection $onPlaylists;

    #[Groups(['playlist-slide:read'])]
    public ?int $duration = null;

    #[Groups(['playlist-slide:read'])]
    public array $published = [
        'from' => 0,
        'to' => 0,
    ];

    public Collection $media;

    public array $content = [];

    public ?array $feed = null;

    public function __construct()
    {
        $this->onPlaylists = new ArrayCollection();
        $this->media = new ArrayCollection();
    }
}
