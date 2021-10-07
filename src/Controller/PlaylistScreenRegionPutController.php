<?php

namespace App\Controller;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Repository\PlaylistScreenRegionRepository;
use App\Utils\ValidationUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class PlaylistScreenRegionPutController extends AbstractController
{
    public function __construct(
        private PlaylistScreenRegionRepository $playlistScreenRegionRepository,
        private RequestStack $request,
        private ValidationUtils $validationUtils
    ) {
    }

    public function __invoke(Request $request, string $id, string $regionId): JsonResponse
    {
        $screenUlid = $this->validationUtils->validateUlid($id);
        $regionUlid = $this->validationUtils->validateUlid($regionId);

        $jsonStr = $this->request->getCurrentRequest()->getContent();
        $content = json_decode($jsonStr);
        if (!is_array($content)) {
            throw new InvalidArgumentException('Content is not an array');
        }

        // Convert to collection and validate input data. Check that the slides exist is preformed in the repository
        // class.
        $collection = new ArrayCollection($content);
        $this->validate($collection);

        $this->playlistScreenRegionRepository->updateRelations($screenUlid, $regionUlid, $collection);

        return new JsonResponse(null, 201);
    }

    /**
     * Validate the input data.
     *
     * @TODO: Use validation service to preform validation against json schema.
     *
     * @throws InvalidArgumentException
     */
    private function validate(ArrayCollection $data): void
    {
        $errors = $data->filter(function ($element) {
            if (property_exists($element, 'playlist') && property_exists($element, 'weight')) {
                if (is_int($element->weight)) {
                    return false;
                }
            }

            return true;
        });

        if (0 !== $errors->count()) {
            throw new InvalidArgumentException('Content validation failed');
        }
    }
}
