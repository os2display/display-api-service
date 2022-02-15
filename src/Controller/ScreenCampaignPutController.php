<?php

namespace App\Controller;

use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Repository\ScreenCampaignRepository;
use App\Utils\ValidationUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ScreenCampaignPutController extends AbstractController
{
    public function __construct(
        private ScreenCampaignRepository $screenCampaignRepository,
        private ValidationUtils $validationUtils
    ) {
    }

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $ulid = $this->validationUtils->validateUlid($id);

        $jsonStr = $request->getContent();
        $content = json_decode($jsonStr);
        if (!is_array($content)) {
            throw new InvalidArgumentException('Content is not an array');
        }

        // Convert to collection and validate input data.
        // Check that the campaigns exist is preformed in the repository class.
        $collection = new ArrayCollection($content);
        $this->validate($collection);

        $this->screenCampaignRepository->updateRelations($ulid, $collection);

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
            if (property_exists($element, 'screen')) {
                return false;
            }

            return true;
        });

        if (0 !== $errors->count()) {
            throw new InvalidArgumentException('Content validation failed');
        }
    }
}
