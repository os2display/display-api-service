<?php

namespace App\Dto;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Ulid;

class ScreenLayout
{
    public Ulid $id;
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
