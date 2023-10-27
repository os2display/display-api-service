<?php

namespace App\Dto;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Ulid;

class Playlist
{
    public Ulid $id;
    public string $title = '';
    public string $description = '';
    public array $schedules = [];
    public \DateTimeInterface $created;
    public \DateTimeInterface $modified;
    public string $modifiedBy = '';
    public string $createdBy = '';
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
