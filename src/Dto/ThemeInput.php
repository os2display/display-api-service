<?php

declare(strict_types=1);

namespace App\Dto;

class ThemeInput
{
    public string $title = '';
    public string $description = '';
    public string $logo = '';

    public string $css = '';
}
