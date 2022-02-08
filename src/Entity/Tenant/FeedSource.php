<?php

namespace App\Entity\Tenant;

use App\Entity\EntityIdTrait;
use App\Entity\EntityModificationTrait;
use App\Entity\EntityTitleDescriptionTrait;
use App\Repository\FeedSourceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=FeedSourceRepository::class)
 */
class FeedSource extends AbstractTenantScopedEntityScoped
{
    use EntityIdTrait;
    use EntityTitleDescriptionTrait;
    use EntityModificationTrait;
    use TimestampableEntity;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $feedType;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private array $secrets = [];

    /**
     * @ORM\OneToMany(targetEntity=Feed::class, mappedBy="feedSource", orphanRemoval=true)
     */
    private Collection $feeds;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private array $configuration = [];

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
     * @return Collection|Feed[]
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
