<?php

namespace App\Api\Model;

use ApiPlatform\Core\Annotation\ApiProperty;

class Playlist extends Shared
{
    /**
     * @ApiProperty(identifier=true)
     */
    public string $id = '';

    private array $published = [];
    private array $slides = [];

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
    public function addSlide(string $id, int $weight, int $duration): self
    {
        $this->slides[$id] = [
            'id' => $id,
            'weight' => $weight,
            'duration' => $duration,
        ];

        return $this;
    }

    public function getSlide(string $id): ?array
    {
        return array_key_exists($id, $this->slides) ? $this->slides[$id] : null;
    }

    /**
     * @return $this
     */
    public function addPublished(int $from, int $to): self
    {
        $this->published = [
            'from' => $from,
            'to' => $to,
        ];

        return $this;
    }

    public function getPublished(): array
    {
        return $this->published;
    }
}
