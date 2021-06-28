<?php

namespace App\Api\Model;

use ApiPlatform\Core\Annotation\ApiProperty;

/**
 * Class Screen.
 */
class Screen extends Shared
{
    /**
     * @ApiProperty(identifier=true)
     */
    public string $id = '';
    private array $regions = [];

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return $this
     */
    public function addRegion(string $name, array $playlists): self
    {
        $this->regions[] = [
            'name' => $name,
            'playlists' => $playlists,
        ];

        return $this;
    }

    public function getRegions(): array
    {
        return $this->regions;
    }
}
