<?php

namespace App\Dto;

use Doctrine\Common\Collections\Collection;

class Theme
{
    public string $title = '';
    public string $description = '';
    public Collection $onSlides;
    public \DateTimeInterface $created;
    public \DateTimeInterface $modified;
    public string $modifiedBy = '';
    public string $createdBy = '';

    public string $css = '';
}
