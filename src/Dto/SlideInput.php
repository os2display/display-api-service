<?php

namespace App\Dto;

class SlideInput implements InputInterface
{
    use InputTrait;
    use PublishedTrait;

    public array $templateInfo = [
        '@id' => '',
        'options' => [
            'fade' => false,
        ],
    ];

    public ?int $duration = null;

    public array $media = [];

    public array $content = [
        'text' => 'Test text',
    ];
}
