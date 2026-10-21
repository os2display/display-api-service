<?php

declare(strict_types=1);

namespace App\Dto;

use App\Validator\ThemeCss;

class ThemeInput
{
    public string $title = '';
    public string $description = '';
    public string $logo = '';

    // Null when left out, which keeps the theme's css; "" clears it.
    #[ThemeCss]
    public ?string $css = null;
}
