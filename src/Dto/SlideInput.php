<?php

namespace App\Dto;

class SlideInput
{
    public string $title = '';
    public string $description = '';
    public string $modifiedBy = '';
    public string $createdBy = '';

    public array $templateInfo = [
        '@id' => '',
        'options' => [
            'fade' => false,
        ],
    ];

    public ?int $duration = null;
    public array $published = [
        'from' => 0,
        'to' => 0,
    ];

    public array $media = [];

    public array $content = [
        'text' => 'Test text',
    ];
}
