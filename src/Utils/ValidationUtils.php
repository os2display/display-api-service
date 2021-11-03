<?php

namespace App\Utils;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use RRule\RRule;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ValidationUtils
{
    public function __construct(
        private ValidatorInterface $validator,
        private string $bindDefaultDateFormat
    ) {
    }

    public function validateRRule(string $rrule): RRule
    {
        $errors = $this->validator->validate($rrule, new \App\Validator\RRule());
        if (0 !== count($errors)) {
            throw new InvalidArgumentException('RRule format not valid');
        }

        return new RRule($rrule);
    }

    public function validateDate(string $date): \DateTime
    {
        $errors = $this->validator->validate($date, new Assert\DateTime($this->bindDefaultDateFormat));
        if (0 !== count($errors)) {
            throw new InvalidArgumentException('Date format not valid, valid format is '.$this->bindDefaultDateFormat);
        }

        return new \DateTime($date);
    }

    public function validateUlid(string $ulid): Ulid
    {
        try {
            return Ulid::fromString($ulid);
        } catch (\InvalidArgumentException $e) {
            // (Re)throw the exception as the API platform invalid argument exception.
            throw new InvalidArgumentException($e->getMessage());
        }
    }
}
