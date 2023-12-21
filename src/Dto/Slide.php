<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\BlameableTrait;
use App\Dto\Trait\IdentifiableTrait;
use App\Dto\Trait\RelationsModifiedTrait;
use App\Dto\Trait\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Slide
{
    use BlameableTrait;
    use IdentifiableTrait;
    use TimestampableTrait;
    use RelationsModifiedTrait;

    public string $title = '';
    public string $description = '';

    public array $templateInfo = [
        '@id' => '',
        'options' => [],
    ];

    public string $theme = '';
    public Collection $onPlaylists;
    public ?int $duration = null;
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
