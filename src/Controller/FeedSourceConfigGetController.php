<?php

namespace App\Controller;

use App\Repository\FeedSourceRepository;
use App\Service\FeedService;
use App\Utils\ValidationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class FeedSourceConfigGetController extends AbstractController
{
    public function __construct(
        private FeedService $feedService,
        private FeedSourceRepository $feedSourceRepository,
        private ValidationUtils $validationUtils,
    ) {
    }

    public function __invoke(Request $request, string $id, string $name): JsonResponse
    {
        $feedUlid = $this->validationUtils->validateUlid($id);
        $feedSource = $this->feedSourceRepository->find($feedUlid);

        if (!$feedSource) {
            return new JsonResponse([], 404);
        }

        return new JsonResponse($this->feedService->getConfigOptions($request, $feedSource, $name), 200);
    }
}
