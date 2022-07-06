<?php

namespace App\Dto;

class Theme
{
    public string $title = '';
    public string $description = '';
    public int $onNumberOfSlides;
    public \DateTimeInterface $created;
    public \DateTimeInterface $modified;
    public string $modifiedBy = '';
    public string $createdBy = '';
    public string $logo = '';

    public string $css = '';
}
