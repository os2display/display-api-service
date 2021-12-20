<?php

namespace App\Dto;

class Feed
{
    public \DateTimeInterface $created;
    public \DateTimeInterface $modified;
    public string $modifiedBy = '';
    public string $createdBy = '';
    public ?array $configuration = [];
    public ?string $slide = null;
    public ?string $feedSource = null;
    public ?string $feedUrl = null;
}
