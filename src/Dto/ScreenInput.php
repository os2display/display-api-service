<?php

namespace App\Dto;

class ScreenInput implements InputInterface
{
    use InputTrait;

    public string $size = '';
    public string $layout = '';
    public string $location = '';
    public array $dimensions = [
        'width' => 0,
        'height' => 0,
    ];
}
