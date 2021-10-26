<?php

namespace App\Dto;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Slide implements OutputInterface, PublishedInterface
{
    use OutputTrait;
    use PublishedTrait;

    public array $templateInfo = [
        '@id' => '',
        'options' => [],
    ];

    public array $onPlaylists = [];
    public ?int $duration = null;

    public Collection $media;
    public array $content = [];

    public function __construct()
    {
        $this->media = new ArrayCollection();
    }
}
