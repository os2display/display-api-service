<?php

namespace App\Api\Model;

use ApiPlatform\Core\Annotation\ApiProperty;

/**
 * Class Screen
 */
class Screen extends Shared
{
    /**
     * @ApiProperty(identifier=true)
     */
    public string $id = '';
    private array $regions = [];

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param string $name
     * @param array $playlists
     *
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

    /**
     * @return array
     */
    public function getRegions(): array
    {
        return $this->regions;
    }

}
