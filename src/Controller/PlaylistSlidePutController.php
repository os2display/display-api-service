<?php

namespace App\Controller;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Repository\PlaylistSlideRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Uid\Ulid;

#[AsController]
class PlaylistSlidePutController extends AbstractController
{
    public function __construct(
        private PlaylistSlideRepository $playlistSlideRepository
    ) {
    }

    public function __invoke(Request $request, string $id): JsonResponse
    {
        if (!Ulid::isValid($id)) {
            throw new InvalidArgumentException();
        }

        $ulid = Ulid::fromString($id);

        $jsonStr = $request->getContent();
        $content = json_decode($jsonStr);
        if (!is_array($content)) {
            throw new InvalidArgumentException();
        }

        // Convert to collection and validate input data. Check that the slides exist is preformed in the repository
        // class.
        $collection = new ArrayCollection($content);
        $this->validate($collection);

        $this->playlistSlideRepository->updateRelations($ulid, $collection);

        return new JsonResponse(null, 201);
    }

    /**
     * Validate the input data.
     *
     * @throws InvalidArgumentException
     */
    private function validate(ArrayCollection $data): void
    {
        $errors = $data->filter(function ($element) {
            if (property_exists($element, 'slide') && property_exists($element, 'weight')) {
                if (is_int($element->weight)) {
                    return false;
                }
            }

            return true;
        });

        if (0 !== $errors->count()) {
            throw new InvalidArgumentException();
        }
    }
}
