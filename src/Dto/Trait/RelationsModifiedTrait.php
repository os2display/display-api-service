<?php

declare(strict_types=1);

namespace App\Dto\Trait;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Annotation\Groups;

trait RelationsModifiedTrait
{
    #[ApiProperty(schema: ['type' => 'object'])]
    #[Groups([
        'campaigns/screen-groups:read',
        'campaigns/screens:read',
        'playlist-screen-region:read',
        'playlist-slide:read',
        'read',
        'slides/playlists:read',
        'screen-campaigns:read',
        'screen-groups/campaigns:read',
        'screen-groups/screens:read',
        'screens/screen-groups:read',
    ])]
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
