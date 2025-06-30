<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Profiler\Profiler;

#[AsController]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly string $rejseplanenApiKey,
        private readonly bool $touchButtonRegions,
        private readonly bool $showScreenStatus,
        private readonly array $loginMethods,
        private readonly bool $enhancedPreview,
        private readonly ?Profiler $profiler = null,
    ) {}

    public function __invoke(): Response
    {
        $this->profiler?->disable();

        return $this->render('admin/admin.html.twig', [
            'config' => json_encode([
                'rejseplanenApiKey' => $this->rejseplanenApiKey,
                'touchButtonRegions' => $this->touchButtonRegions,
                'showScreenStatus' => $this->showScreenStatus,
                'loginMethods' => $this->loginMethods,
                'enhancedPreview' => $this->enhancedPreview,
            ]),
        ]);
    }
}
