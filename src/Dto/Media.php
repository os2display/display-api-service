<?php

namespace App\Dto;

class Media
{
    public string $title = '';
    public string $description = '';
    public string $license = '';
    public \DateTimeInterface $created;
    public \DateTimeInterface $modified;
    public string $modifiedBy = '';
    public string $createdBy = '';
    public array $assets = [];
}
