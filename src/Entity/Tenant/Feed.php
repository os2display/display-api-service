<?php

namespace App\Entity\Tenant;

use App\Repository\FeedRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=FeedRepository::class)
 * @ORM\EntityListeners({"App\EventListener\FeedDoctrineEventListener"})
 */
class Feed extends AbstractTenantScopedEntity
{
    /**
     * @ORM\ManyToOne(targetEntity=FeedSource::class, inversedBy="feeds")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?FeedSource $feedSource;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private array $configuration = [];

    /**
     * @ORM\OneToOne(targetEntity=Slide::class, mappedBy="feed"))
     */
    private ?Slide $slide;

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

        return $this;
    }
}
