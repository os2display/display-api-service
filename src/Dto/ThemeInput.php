<?php

declare(strict_types=1);

namespace App\Dto;

use App\Validator\ThemeCss;

class ThemeInput
{
    public string $title = '';
    public string $description = '';
    public string $logo = '';

    #[ThemeCss]
    public string $css = '';
}
