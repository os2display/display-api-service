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
    ) {}

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
    }
}
