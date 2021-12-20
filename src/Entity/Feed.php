<?php

namespace App\Entity;

use App\Repository\FeedRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=FeedRepository::class)
 */
class Feed
{
    use EntityIdTrait;
    use EntityModificationTrait;
    use TimestampableEntity;

    /**
     * @ORM\ManyToOne(targetEntity=FeedSource::class, inversedBy="feeds")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?FeedSource $feedSource;

    /**
     * @ORM\OneToOne(targetEntity=Slide::class, mappedBy="feed")
     */
    private ?Slide $slide;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $configuration = [];

    public function getFeedSource(): ?FeedSource
    {
        return $this->feedSource;
    }

    public function setFeedSource(?FeedSource $feedSource): self
    {
        $this->feedSource = $feedSource;

        return $this;
    }

    public function getSlide(): ?Slide
    {
        return $this->slide;
    }

    public function setSlide(?Slide $slide): self
    {
        // unset the owning side of the relation if necessary
        if (null === $slide && null !== $this->slide) {
            $this->slide->setFeed(null);
        }

        // set the owning side of the relation if necessary
        if (null !== $slide && $slide->getFeed() !== $this) {
            $slide->setFeed($this);
        }

        $this->slide = $slide;

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
}
