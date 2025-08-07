<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class AdminConfigController extends AbstractController
{
    public function __construct(
        private readonly string $rejseplanenApiKey,
        private readonly bool $touchButtonRegions,
        private readonly bool $showScreenStatus,
        private readonly array $loginMethods,
        private readonly bool $enhancedPreview,
    ) {}

    public function __invoke(): Response
    {
        return new JsonResponse([
            'rejseplanenApiKey' => $this->rejseplanenApiKey,
            'touchButtonRegions' => $this->touchButtonRegions,
            'showScreenStatus' => $this->showScreenStatus,
            'loginMethods' => $this->loginMethods,
            'enhancedPreview' => $this->enhancedPreview,
        ]);
    }
}
