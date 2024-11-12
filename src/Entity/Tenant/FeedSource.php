<?php

declare(strict_types=1);

namespace App\Entity\Tenant;

use App\Entity\Interfaces\RelationsChecksumInterface;
use App\Entity\Traits\EntityTitleDescriptionTrait;
use App\Entity\Traits\RelationsChecksumTrait;
use App\Repository\FeedSourceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FeedSourceRepository::class)]
#[ORM\EntityListeners([\App\EventListener\FeedSourceDoctrineEventListener::class])]
#[ORM\Index(fields: ['changed'], name: 'changed_idx')]
class FeedSource extends AbstractTenantScopedEntity implements RelationsChecksumInterface
{
    use EntityTitleDescriptionTrait;
    use RelationsChecksumTrait;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    private string $feedType = '';

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::JSON, nullable: true)]
    private ?array $secrets = [];

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Tenant\Feed>
     */
    #[ORM\OneToMany(targetEntity: Feed::class, mappedBy: 'feedSource', orphanRemoval: true)]
    private Collection $feeds;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    private string $supportedFeedOutputType = '';

    public function __construct()
    {
        $this->feeds = new ArrayCollection();
    }

    public function getFeedType(): ?string
    {
        return $this->feedType;
    }

    public function setFeedType(string $feedType): self
    {
        $this->feedType = $feedType;

        return $this;
    }

    public function getSecrets(): ?array
    {
        return $this->secrets;
    }

    public function setSecrets(?array $secrets): self
    {
        $this->secrets = $secrets;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getFeeds(): Collection
    {
        return $this->feeds;
    }

    public function addFeed(Feed $feed): self
    {
        if (!$this->feeds->contains($feed)) {
            $this->feeds[] = $feed;
            $feed->setFeedSource($this);
        }

        return $this;
    }

    public function removeFeed(Feed $feed): self
    {
        if ($this->feeds->removeElement($feed)) {
            // set the owning side to null (unless already changed)
            if ($feed->getFeedSource() === $this) {
                $feed->setFeedSource(null);
            }
        }

        return $this;
    }

    public function getSupportedFeedOutputType(): ?string
    {
        return $this->supportedFeedOutputType;
    }

    public function setSupportedFeedOutputType(string $supportedFeedOutputType): self
    {
        $this->supportedFeedOutputType = $supportedFeedOutputType;

        return $this;
    }

    /**
     * @throws \JsonException
     */
    public function getSchema(): mixed
    {
        $jsonSchema = <<<'JSON'
        {
          "$schema": "https://json-schema.org/draft/2020-12/schema",
          "$id": "https://os2display.dk/config-schema.json",
          "title": "Config file schema",
          "description": "Schema for defining config files for templates",
          "type": "object",
          "properties": {
            "title": {
              "description": "The title of the feed source",
              "type": "string",
              "minLength": 1
            },
            "description": {
              "description": "A description of the feed source",
              "type": "string",
              "minLength": 1
            },
            "feedType": {
              "description": "The type of the feed source",
              "type": "string",
              "minLength": 1
            },
            "secrets": {
                "type": "array",
                "items": {
                    "type": "string"
                  }
            }
          },
          "required": ["title", "description", "feedType", "secrets"]
        }
        JSON;

        return json_decode($jsonSchema, true, 512, JSON_THROW_ON_ERROR);
    }
}
