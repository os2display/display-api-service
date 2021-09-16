<?php

namespace App\Dto;

use App\Entity\ScreenLayoutRegions;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class ScreenLayout
{
    public string $title = '';
    public array $grid = [
        'rows' => 1,
        'columns' => 1,
    ];
    public Collection $regions;

    public function __construct()
    {
        $this->regions = new ArrayCollection();
    }
}
