<?php

declare(strict_types=1);

namespace App\Dto\Trait;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Annotation\Groups;

trait RelationsModifiedTrait
{
    #[ApiProperty(schema: ['type' => 'object'])]
    #[Groups(['playlist-slide:read', 'slides/playlists:read'])]
    private ?array $relationsModified;

    public function getRelationsModified(): ?array
    {
        return 0 === count($this->relationsModified) ? null : $this->relationsModified;
    }

    public function setRelationsModified(array $relationsModified): void
    {
        $this->relationsModified = $relationsModified;
    }
}
