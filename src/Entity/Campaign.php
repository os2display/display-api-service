<?php

namespace App\Entity;

use App\Repository\CampaignRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=CampaignRepository::class)
 */
class Campaign
{
    use EntityIdTrait;
    use EntityTitleDescriptionTrait;
    use EntityModificationTrait;
    use TimestampableEntity;


    public function __construct()
    {
    }

}
