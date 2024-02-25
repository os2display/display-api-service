<?php

declare(strict_types=1);

namespace App\Entity\Tenant;

use App\Entity\Interfaces\RelationsChecksumInterface;
use App\Entity\Traits\RelationsChecksumTrait;
use App\Repository\FeedRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FeedRepository::class)]
#[ORM\EntityListeners([\App\EventListener\FeedDoctrineEventListener::class])]
#[ORM\Index(fields: ['changed'], name: 'changed_idx')]
class Feed extends AbstractTenantScopedEntity implements RelationsChecksumInterface
{
    use RelationsChecksumTrait;

    #[ORM\ManyToOne(targetEntity: FeedSource::class, inversedBy: 'feeds')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FeedSource $feedSource = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::JSON, nullable: true)]
    private ?array $configuration = [];

    #[ORM\OneToOne(targetEntity: Slide::class, mappedBy: 'feed')]
    private ?Slide $slide = null;

    public function getFeedSource(): ?FeedSource
    {
        return $this->feedSource;
    }

    public function setFeedSource(?FeedSource $feedSource): self
    {
        $this->feedSource = $feedSource;

        return $this;
    }

    public function getConfiguration(): ?array
    {
        return $this->configuration;
    }

    public function setConfiguration(?array $configuration): self
    {
        $this->configuration = $configuration;

        return $this;
    }

    public function getSlide(): ?Slide
    {
        return $this->slide;
    }

    public function setSlide(?Slide $slide): self
    {
        $this->slide = $slide;
        $slide->setFeed($this);

        return $this;
    }
}
