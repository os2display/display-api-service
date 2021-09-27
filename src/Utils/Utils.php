<?php

namespace App\Utils;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class Utils
{
    public function __construct(
        private ValidatorInterface $validator,
        private string $bindDefaultDataFormat
    ) {
    }

    public function validateDate(string $date): \DateTime
    {
        // @TODO: Move format string into configuration.
        $errors = $this->validator->validate($date, new Assert\DateTime($this->bindDefaultDataFormat));
        if (0 !== count($errors)) {
            throw new InvalidArgumentException('Date format not valid');
        }

        return new \DateTime($date);
    }
}
