<?php

declare(strict_types=1);

namespace App\Utils;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use Symfony\Component\Uid\Ulid;

final class IriHelperUtils
{
    /**
     * Extract ULID from IRI string and validate the ULID format.
     *
     * @param string $iri The IRI to extract ULID from
     *
     * @return string The ULID as string
     */
    public function getUlidFromIRI(string $iri): string
    {
        preg_match('@^/v\d/[A-Za-z-]+([A-Za-z-\/]*)/([A-Za-z0-9]{26})$@', $iri, $matches);
        if (3 !== count($matches)) {
            throw new InvalidArgumentException('Unknown resource IRI');
        }
        $ulid = end($matches);

        if (!Ulid::isValid($ulid)) {
            throw new InvalidArgumentException('ULID format not valid');
        }

        return $ulid;
    }
}
