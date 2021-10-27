<?php

namespace App\Dto;

class ThemeInput
{
    public string $title = '';
    public string $description = '';
    public string $modifiedBy = '';
    public string $createdBy = '';

    public string $css = '';
}
