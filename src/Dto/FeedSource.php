<?php

namespace App\Dto;

use Symfony\Component\Uid\Ulid;

class FeedSource
{
    public Ulid $id;
    public string $title = '';
    public string $description = '';
    public \DateTimeInterface $created;
    public \DateTimeInterface $modified;
    public string $modifiedBy = '';
    public string $createdBy = '';
    public string $outputType = '';
    public string $feedType = '';
    public array $secrets = [];
    public array $feeds = [];
    public array $admin = [];
    public string $supportedFeedOutputType = '';
}
