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
     * @param $id
     * @param $weight
     * @param $duration
     *
     * @return $this
     */
    public function addSlide($id, $weight, $duration): self
    {
        $this->slides[$id] = [
            'id' => $id,
            'weight' => $weight,
            'duration' => $duration,
        ];

        return $this;
    }

    /**
     * @param $id
     *
     * @return array|null
     */
    public function getSlide($id): ?array
    {
        return array_key_exists($id, $this->slides) ? $this->slides[$id] : null;
    }

    /**
     * @param int $from
     * @param int $to
     *
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

    /**
     * @return array
     */
    public function getPublished(): array
    {
        return $this->published;
    }
}
