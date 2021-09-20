<?php

namespace App\Dto;

use Doctrine\Common\Collections\Collection;

class Slide
{
    public string $title = '';
    public string $description = '';
    public \DateTime $created;
    public \DateTime $modified;
    public string $modifiedBy = '';
    public string $createdBy = '';

    public array $template = [
        '@id' => '',
        'options' => [],
    ];

//    public array $onScreens = [];

    public array $onPlaylists = [];
    public ?int $duration = null;
    public array $published = [
        'from' => 0,
        'to' => 0,
    ];
    public array $content = [];
}
