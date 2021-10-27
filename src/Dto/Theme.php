<?php

namespace App\Dto;

class Theme
{
    public string $title = '';
    public string $description = '';
    public \DateTimeInterface $created;
    public \DateTimeInterface $modified;
    public string $modifiedBy = '';
    public string $createdBy = '';

    public string $css = '';
}
