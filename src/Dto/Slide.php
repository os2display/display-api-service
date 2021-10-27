<?php

namespace App\Dto;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Slide
{
    public string $title = '';
    public string $description = '';
    public \DateTime $created;
    public \DateTime $modified;
    public string $modifiedBy = '';
    public string $createdBy = '';

    public array $templateInfo = [
        '@id' => '',
        'options' => [],
    ];

    public Collection $onPlaylists;
    public ?int $duration = null;
    public array $published = [
        'from' => 0,
        'to' => 0,
    ];

    public Collection $media;
    public array $content = [];

    public function __construct()
    {
        $this->onPlaylists = new ArrayCollection();
        $this->media = new ArrayCollection();
    }
}
