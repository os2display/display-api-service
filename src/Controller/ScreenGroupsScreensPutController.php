<?php

declare(strict_types=1);

namespace App\Controller;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use App\Repository\ScreenGroupRepository;
use App\Utils\ValidationUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Uid\Ulid;

#[AsController]
class ScreenGroupsScreensPutController extends AbstractController
{
    public function __construct(
        private readonly ScreenGroupRepository $screenGroupRepository,
        private readonly ValidationUtils $validationUtils
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $screenUlid = $this->validationUtils->validateUlid($id);

        $jsonStr = $request->getContent();
        $content = json_decode($jsonStr, null, 512, JSON_THROW_ON_ERROR);
        if (!is_array($content)) {
            throw new InvalidArgumentException('Content is not an array');
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
     * @TODO: Use validation service to preform validation against json schema.
     *
     * @throws InvalidArgumentException
     */
    private function validate(ArrayCollection $data): void
    {
        $errors = $data->filter(function (mixed $element) {
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
