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

        // Memory use when resizing follows pixel count, not file size, so the
        // allowed image types are also capped by their dimensions. SVG has no
        // fixed pixel size and video is not resized.
        $mimeType = $value->getMimeType();
        if (null !== $mimeType
            && \in_array($mimeType, $constraint->mimeTypes, true)
            && str_starts_with($mimeType, 'image/')
            && 'image/svg+xml' !== $mimeType
        ) {
            // Checked here rather than with Assert\Image, which only offers raw
            // pixel counts for the message.
            $size = @getimagesize($value->getPathname());
            if (false === $size || 0 === $size[0] * $size[1]) {
                $this->context->buildViolation($constraint->sizeNotDetectedMessage)
                    ->setCode(Assert\Image::SIZE_NOT_DETECTED_ERROR)
                    ->addViolation();
            } elseif ($size[0] * $size[1] > $this->mediaMaxUploadPixels) {
                $this->context->buildViolation($constraint->maxPixelsMessage)
                    ->setParameter('{{ width }}', (string) $size[0])
                    ->setParameter('{{ height }}', (string) $size[1])
                    ->setParameter('{{ megapixels }}', self::megapixels($size[0] * $size[1]))
                    ->setParameter('{{ max_megapixels }}', self::megapixels($this->mediaMaxUploadPixels))
                    ->setCode(Assert\Image::TOO_MANY_PIXEL_ERROR)
                    ->addViolation();
            }
        }
    }

    private static function megapixels(int $pixels): string
    {
        return (string) round($pixels / 1000000, 1);
    }
}
