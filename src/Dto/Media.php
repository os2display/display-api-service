<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\BlameableTrait;
use App\Dto\Trait\IdentifiableTrait;
use App\Dto\Trait\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Media
{
    use BlameableTrait;
    use IdentifiableTrait;
    use TimestampableTrait;

    public string $title = '';
    public string $description = '';
    public string $license = '';
    public Collection $media;
    public array $assets = [];
    public ?string $thumbnail = null;

    public function __construct()
    {
        $this->media = new ArrayCollection();
    }
}
