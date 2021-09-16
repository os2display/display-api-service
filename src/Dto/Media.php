<?php

namespace App\Dto;

class MediaOutput
{
    //public $ulid;
    public string $title;
    public string $description;
    public string $license;
    public \DateTimeInterface $created;
    public \DateTimeInterface $modified;
}
