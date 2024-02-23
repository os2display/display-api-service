<?php

declare(strict_types=1);

namespace App\Dto\Trait;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Annotation\Groups;

trait RelationsChecksumTrait
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
    private ?array $relationsChecksum;

    public function getRelationsChecksum(): ?array
    {
        return 0 === count($this->relationsChecksum) ? null : $this->relationsChecksum;
    }

    public function setRelationsChecksum(array $relationsChecksum): void
    {
        $this->relationsChecksum = $relationsChecksum;
    }
}
