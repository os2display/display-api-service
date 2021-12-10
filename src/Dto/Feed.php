<?php

namespace App\Dto;

class Feed
{
    public string $title = '';
    public string $description = '';
    public \DateTimeInterface $created;
    public \DateTimeInterface $modified;
    public string $modifiedBy = '';
    public string $createdBy = '';
}
