<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Trait\BlameableTrait;
use App\Dto\Trait\IdentifiableTrait;
use App\Dto\Trait\RelationsModifiedTrait;
use App\Dto\Trait\TimestampableTrait;

class Theme
{
    use BlameableTrait;
    use IdentifiableTrait;
    use TimestampableTrait;
    use RelationsModifiedTrait;

    public string $title = '';
    public string $description = '';
    public ?\App\Entity\Tenant\Media $logo;

    public string $cssStyles = '';
}
