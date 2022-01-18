<?php

namespace App\Controller;

use App\Repository\FeedRepository;
use App\Service\FeedService;
use App\Utils\ValidationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class FeedDataGetController extends AbstractController
{
    public function __construct(
        private FeedService $feedService,
        private FeedRepository $feedRepository,
        private ValidationUtils $validationUtils,
    ) {
    }

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $feedUlid = $this->validationUtils->validateUlid($id);
        $feed = $this->feedRepository->find($feedUlid);

        return new JsonResponse($this->feedService->getData($feed), 201);
    }
}
