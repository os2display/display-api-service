<?php

namespace App\Api\Model;

class Shared {
    public string $title = '';
    public string $description = '';
    public array $tags = [];
    public int $modified = 0;
    public int $created = 0;
    public string $modifiedBy = '';
    public string $createdBy = '';
}
