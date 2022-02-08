<?php

namespace App\Entity\Tenant;

use App\Entity\EntityIdTrait;
use App\Entity\EntityModificationTrait;
use App\Entity\EntityTenantTrait;
use App\Entity\TenantScopedInterface;
use App\Repository\FeedRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=FeedRepository::class)
 */
class Feed extends AbstractTenantScopedEntityScoped
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
     * @ORM\Column(type="json", nullable=true)
     */
    private array $configuration = [];

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
}
