<?php

namespace App\Dto;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Media
{
    use OutputTrait;

    public string $license = '';
    public Collection $media;
    public array $assets = [];

    public function __construct()
    {
        $this->media = new ArrayCollection();
    }
}
