<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Tenant\Feed;
use App\Service\FeedService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class FeedGetDataController extends AbstractController
{
    public function __construct(
        private readonly FeedService $feedService
    ) {}

    public function __invoke(Feed $feed): ?array
    {
        return $this->feedService->getData($feed) ?: [];
    }
}
