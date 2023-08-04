<?php

namespace App\Dto;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Media
{
    public string $title = '';
    public string $description = '';
    public string $license = '';
    public \DateTimeInterface $created;
    public \DateTimeInterface $modified;
    public string $modifiedBy = '';
    public string $createdBy = '';
    public Collection $media;
    public array $assets = [];
    public ?string $thumbnail = null;

    public function __construct()
    {
        $this->media = new ArrayCollection();
    }
}
