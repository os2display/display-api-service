<?php

namespace App\Utils;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ValidationUtils
{
    public function __construct(
        private ValidatorInterface $validator,
        private string $bindDefaultDateFormat
    ) {
    }

    public function validateDate(string $date): \DateTime
    {
        $errors = $this->validator->validate($date, new Assert\DateTime($this->bindDefaultDateFormat));
        if (0 !== count($errors)) {
            throw new InvalidArgumentException('Date format not valid');
        }

        return new \DateTime($date);
    }
}
