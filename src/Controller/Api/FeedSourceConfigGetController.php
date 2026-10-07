<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\FeedSourceRepository;
use App\Service\FeedService;
use App\Utils\ValidationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class FeedSourceConfigGetController extends AbstractController
{
    public function __construct(
        private readonly FeedService $feedService,
        private readonly FeedSourceRepository $feedSourceRepository,
        private readonly ValidationUtils $validationUtils,
    ) {}

    public function __invoke(Request $request, string $id, string $name): JsonResponse
    {
        $feedSourceUlid = $this->validationUtils->validateUlid($id);
        $feedSource = $this->feedSourceRepository->find($feedSourceUlid);

        if (!$feedSource) {
            return new JsonResponse([], Response::HTTP_NOT_FOUND);
        }

        $config = $this->feedService->getConfigOptions($request, $feedSource, $name);
        if (is_null($config)) {
            return new JsonResponse($config, Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($config, Response::HTTP_OK);
    }
}
