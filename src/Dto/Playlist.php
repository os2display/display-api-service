<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\BlameableTrait;
use App\Dto\Trait\IdentifiableTrait;
use App\Dto\Trait\RelationsModifiedTrait;
use App\Dto\Trait\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Playlist
{
    use BlameableTrait;
    use IdentifiableTrait;
    use TimestampableTrait;
    use RelationsModifiedTrait;

    public string $title = '';
    public string $description = '';
    public array $schedules = [];
    public string $slides = '';
    public Collection $campaignScreens;
    public Collection $campaignScreenGroups;
    public Collection $tenants;
    public bool $isCampaign;
    public array $published = [
        'from' => '',
        'to' => '',
    ];

    public function __construct()
    {
        $this->campaignScreens = new ArrayCollection();
        $this->campaignScreenGroups = new ArrayCollection();
    }
}
