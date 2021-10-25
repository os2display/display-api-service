<?php

namespace App\Dto;

trait OutputTrait
{
    use InputTrait;

    public \DateTimeInterface $created;
    public \DateTimeInterface $modified;
}