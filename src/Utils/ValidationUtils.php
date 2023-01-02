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
        private string $defaultDateFormat
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
        $errors = $this->validator->validate($date, new Assert\DateTime($this->defaultDateFormat));
        if (0 !== count($errors)) {
            $epoc = \DateTime::createFromFormat('Y-m-d\TH:i:s.v\Z', '1970-01-01T01:02:03.000Z');
            $example = $epoc->format($this->defaultDateFormat);
            throw new InvalidArgumentException(sprintf('%s is not a valid date format, valid format is simplified extended ISO format, e.g %s', $date, $example));
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
