<?php

declare(strict_types=1);

namespace App\Dto\Trait;

use ApiPlatform\Metadata\ApiProperty;

trait RelationsChecksumTrait
{
    #[ApiProperty(schema: ['type' => 'object'])]
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
