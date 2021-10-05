<?php

namespace App\Controller;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Repository\ScreenGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Uid\Ulid;

#[AsController]
class ScreenGroupsScreensPutController extends AbstractController
{
    public function __construct(
        private ScreenGroupRepository $screenGroupRepository,
        private RequestStack $request
    ) {
    }

    public function __invoke(Request $request, string $id): JsonResponse
    {
        if (!Ulid::isValid($id)) {
            throw new InvalidArgumentException();
        }

        $screenUlid = Ulid::fromString($id);

        $jsonStr = $this->request->getCurrentRequest()->getContent();
        $content = json_decode($jsonStr);
        if (!is_array($content)) {
            throw new InvalidArgumentException();
        }

        // Convert to collection and validate input data.
        $collection = new ArrayCollection($content);
        $this->validate($collection);

        $this->screenGroupRepository->updateRelations($screenUlid, $collection);

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
            if (is_string($element) && Ulid::isValid($element)) {
                return false;
            }

            return true;
        });

        if (0 !== $errors->count()) {
            throw new InvalidArgumentException('One or more ids are not valid');
        }
    }
}
