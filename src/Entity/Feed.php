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
    use EntityTitleDescriptionTrait;
    use EntityModificationTrait;
    use TimestampableEntity;

    /**
     * @ORM\ManyToOne(targetEntity=FeedSource::class, inversedBy="feeds")
     * @ORM\JoinColumn(nullable=false)
     */
    private $FeedSource;

    public function getFeedSource(): ?FeedSource
    {
        return $this->FeedSource;
    }

    public function setFeedSource(?FeedSource $FeedSource): self
    {
        $this->FeedSource = $FeedSource;

        return $this;
    }
}
