<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use App\Entity\Tenant\Media;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\PlaylistSlide;
use App\Entity\Tenant\Screen;
use App\Entity\Tenant\ScreenCampaign;
use App\Entity\Tenant\ScreenGroup;
use App\Entity\Tenant\ScreenGroupCampaign;
use App\Entity\Tenant\ScreenLayout;
use App\Entity\Tenant\ScreenLayoutRegions;
use App\Entity\Tenant\Slide;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Component\Uid\Ulid;

/**
 * @internal
 */
trait EntityIdTrait
{
    /**
     * @ORM\Id
     * @ORM\Column(type="ulid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UlidGenerator::class)
     * @ApiProperty(identifier=true)
     */
    private Ulid $id;

    /**
     * Get the Ulid.
     */
    public function getId(): ?Ulid
    {
        return $this->id;
    }

    /**
     * Set the Ulid.
     *
     * @return Screen|ScreenLayoutRegions|ScreenGroup|Media|Playlist|ScreenLayout|Slide|Template|PlaylistSlide|ScreenCampaign|ScreenGroupCampaign
     */
    public function setId(Ulid $id): self
    {
        $this->id = $id;

        return $this;
    }
}
