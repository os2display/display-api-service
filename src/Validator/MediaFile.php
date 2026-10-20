<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class MediaFile extends Constraint
{
    public string $maxSizeMessage = 'The file is too large ({{ size }} bytes). Allowed maximum size is {{ limit }} bytes.';
    public string $maxPixelsMessage = 'The image is {{ width }}×{{ height }} pixels ({{ megapixels }} megapixels). The maximum is {{ max_megapixels }} megapixels; scale it down before uploading.';
    public string $sizeNotDetectedMessage = 'The size of the image could not be detected.';
    public string $mimeTypesMessage = 'Please upload a valid image format: jpeg, png, webp, gif or svg, or video format: webm or mp4';

    /**
     * @var string[]
     */
    public array $mimeTypes = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/svg+xml',
        'video/webm',
        'video/mp4',
        'image/gif',
    ];

    final public function validatedBy(): string
    {
        return MediaFileValidator::class;
    }
}
