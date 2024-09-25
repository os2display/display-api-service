<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\ScreenInput;
use App\Entity\Tenant\Screen;
use App\Repository\ScreenLayoutRepository;
use App\Repository\ScreenLayoutRegionsRepository;
use App\Repository\PlaylistRepository;
use App\Utils\IriHelperUtils;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Tenant\PlaylistScreenRegion;
use App\Entity\Tenant\Playlist;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Exceptions\AuthScreenBindException;

class ScreenProcessor extends AbstractProcessor
{
    public function __construct(
        private readonly IriHelperUtils $iriHelperUtils,
        private readonly ScreenLayoutRepository $layoutRepository,
        private readonly ScreenLayoutRegionsRepository $screenLayoutRegionsRepository,
        private readonly PlaylistRepository $playlistRepository,
        EntityManagerInterface $entityManager,
        ProcessorInterface $persistProcessor,
        ProcessorInterface $removeProcessor,
        ScreenProvider $provider
    ) {
        parent::__construct($entityManager, $persistProcessor, $removeProcessor, $provider);
    }

    protected function fromInput(mixed $object, Operation $operation, array $uriVariables, array $context): Screen
    {
        // FIXME Do we really have to do (something like) this to load an existing object into the entity manager?
        $screen = $this->loadPrevious(new Screen(), $context);

        assert($object instanceof ScreenInput);
        empty($object->title) ?: $screen->setTitle($object->title);
        empty($object->description) ?: $screen->setDescription($object->description);
        empty($object->createdBy) ?: $screen->setCreatedBy($object->createdBy);
        empty($object->modifiedBy) ?: $screen->setModifiedBy($object->modifiedBy);
        empty($object->size) ?: $screen->setSize((int) $object->size);
        empty($object->location) ?: $screen->setLocation($object->location);
        empty($object->orientation) ?: $screen->setOrientation($object->orientation);
        empty($object->resolution) ?: $screen->setResolution($object->resolution);

        if (isset($object->enableColorSchemeChange)) {
            $screen->setEnableColorSchemeChange($object->enableColorSchemeChange);
        }

        if (isset($object->regionsAndPlaylists)) {
            foreach ($object->regionsAndPlaylists as $playlistAndRegion) {
                $playlistAndRegionToSave = new PlaylistScreenRegion();

                $region =  $this->screenLayoutRegionsRepository->findOneBy(['id' => $playlistAndRegion['regionId']]);
                if (is_null($region)) {
                    throw new InvalidArgumentException('Unknown region resource');
                }

                $playlist =  $this->playlistRepository->findOneBy(['id' => $playlistAndRegion['playlist']]);
                if (is_null($playlist)) {
                    throw new InvalidArgumentException('Unknown playlist resource');
                }

                $playlistAndRegionToSave->setPlaylist($playlist);
                $playlistAndRegionToSave->setRegion($region);
                $screen->addPlaylistScreenRegion($playlistAndRegionToSave);
            }
        }

        if (!empty($object->layout)) {
            // Validate that layout IRI exists.
            $ulid = $this->iriHelperUtils->getUlidFromIRI($object->layout);

            // Try loading layout entity.
            $layout = $this->layoutRepository->findOneBy(['id' => $ulid]);
            if (is_null($layout)) {
                throw new InvalidArgumentException('Unknown layout resource');
            }

            $screen->setScreenLayout($layout);
        }

        return $screen;
    }
}
