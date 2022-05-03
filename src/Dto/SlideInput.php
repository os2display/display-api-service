<?php

namespace App\Dto;

class SlideInput
{
    public string $title = '';
    public string $description = '';

    public array $templateInfo = [
        '@id' => '',
        'options' => [
            'fade' => false,
        ],
    ];

    public string $theme = '';

    public ?int $duration = null;
    public array $published = [
        'from' => 0,
        'to' => 0,
    ];

    public ?array $feed = [];

    public array $media = [];

    public array $content = [
        'text' => 'Test text',
    ];
}
