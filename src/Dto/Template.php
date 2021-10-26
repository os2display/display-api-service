<?php

namespace App\Dto;

class Template implements OutputInterface
{
    use OutputTrait;

    public array $resources = [];
}
