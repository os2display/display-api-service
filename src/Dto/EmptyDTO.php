<?php

declare(strict_types=1);

namespace App\Dto;

// Used for POST endpoints that expect empty content.
// This is used to avoid having to change content type for a few endpoints in the API.
class EmptyDTO
{
}
