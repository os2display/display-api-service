<?php

namespace App\Dto;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Media implements OutputInterface
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
