<?php

declare(strict_types=1);

namespace App\Controller;

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use App\Repository\ScreenCampaignRepository;
use App\Utils\ValidationUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ScreenCampaignPutController extends AbstractTenantAwareController
{
    public function __construct(
        private readonly ScreenCampaignRepository $screenCampaignRepository,
        private readonly ValidationUtils $validationUtils,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $ulid = $this->validationUtils->validateUlid($id);

        $jsonStr = $request->getContent();
        $content = json_decode($jsonStr, null, 512, JSON_THROW_ON_ERROR);
        if (!is_array($content)) {
            throw new InvalidArgumentException('Content is not an array');
        }

        // Convert to collection and validate input data.
        // Check that the campaigns exist is preformed in the repository class.
        $collection = new ArrayCollection($content);
        $this->validate($collection);

        $this->screenCampaignRepository->updateRelations($ulid, $collection, $this->getActiveTenant());

        return new JsonResponse(null, \Symfony\Component\HttpFoundation\Response::HTTP_CREATED);
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
