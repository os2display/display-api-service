<?php

namespace App\Dto;

use Symfony\Component\Uid\Ulid;

class Template
{
    public Ulid $id;
    public string $title = '';
    public string $description = '';
    public \DateTimeInterface $created;
    public \DateTimeInterface $modified;
    public string $modifiedBy = '';
    public string $createdBy = '';
    public array $resources = [];
}
