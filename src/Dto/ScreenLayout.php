<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\IdentifiableTrait;
use App\Dto\Trait\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class ScreenLayout
{
    use IdentifiableTrait;
    use TimestampableTrait;

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
