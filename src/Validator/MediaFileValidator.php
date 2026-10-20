<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class MediaFileValidator extends ConstraintValidator
{
    // Formats decoded into memory when thumbnails are made. SVG and video are not.
    private const array RASTER_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif'];

    public function __construct(
        private readonly int $mediaMaxUploadSizeMb,
        private readonly int $mediaMaxUploadPixels,
    ) {
        if ($mediaMaxUploadSizeMb <= 0) {
            throw new \InvalidArgumentException(sprintf('MEDIA_MAX_UPLOAD_SIZE_MB must be a positive integer; got %d.', $mediaMaxUploadSizeMb));
        }
        if ($mediaMaxUploadPixels <= 0) {
            throw new \InvalidArgumentException(sprintf('MEDIA_MAX_UPLOAD_PIXELS must be a positive integer; got %d.', $mediaMaxUploadPixels));
        }
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof MediaFile) {
            throw new UnexpectedTypeException($constraint, MediaFile::class);
        }

        // Let other constraints (NotNull, etc.) handle absence.
        if (null === $value) {
            return;
        }

        if (!$value instanceof File) {
            throw new UnexpectedValueException($value, File::class);
        }

        // Delegate to Symfony's File constraint so we inherit its well-tested size
        // and mime sniffing. `Mi` (binary MiB) matches the admin dropzone's
        // `mb * 1024 * 1024` threshold.
        $this->context->getValidator()
            ->inContext($this->context)
            ->validate($value, new Assert\File(
                maxSize: $this->mediaMaxUploadSizeMb.'Mi',
                mimeTypes: $constraint->mimeTypes,
                maxSizeMessage: $constraint->maxSizeMessage,
                mimeTypesMessage: $constraint->mimeTypesMessage,
            ));

        // Memory use when resizing follows pixel count, not file size, so
        // raster images are also capped by their dimensions.
        if (\in_array($value->getMimeType(), self::RASTER_MIME_TYPES, true)) {
            $this->context->getValidator()
                ->inContext($this->context)
                ->validate($value, new Assert\Image(
                    maxPixels: $this->mediaMaxUploadPixels,
                    maxPixelsMessage: $constraint->maxPixelsMessage,
                ));
        }
    }
}
