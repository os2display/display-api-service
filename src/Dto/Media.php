<?php

namespace App\Dto;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Ulid;

class Media
{
    public Ulid $id;
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
