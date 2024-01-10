<?php

declare(strict_types=1);

namespace App\Dto;

class ScreenInput
{
    public string $title = '';
    public string $description = '';
    public string $size = '';

    public string $layout = '';
    public string $location = '';
    public string $resolution = '';
    public string $orientation = '';

    public ?bool $enableColorSchemeChange = null;
}
