<?php

namespace App\Dto;

class FeedSource
{
    public string $title = '';
    public string $description = '';
    public \DateTimeInterface $created;
    public \DateTimeInterface $modified;
    public string $modifiedBy = '';
    public string $createdBy = '';
    public string $outputType = '';
    public string $feedType = '';
    public array $secrets = [];
    public array $configuration = [];
    public array $feeds = [];
}
