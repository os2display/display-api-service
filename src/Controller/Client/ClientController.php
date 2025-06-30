<?php

declare(strict_types=1);

namespace App\Controller\Client;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Profiler\Profiler;

#[AsController]
class ClientController extends AbstractController
{
    public function __construct(
        private readonly int $loginCheckTimeout,
        private readonly int $refreshTokenTimeout,
        private readonly int $releaseTimestampIntervalTimeout,
        private readonly int $schedulingInterval,
        private readonly int $pullStrategyInterval,
        private readonly array $colorScheme,
        private readonly bool $debug,
        private readonly array $logging,
        private readonly ?Profiler $profiler = null,
    ) {}

    public function __invoke(): Response
    {
        $this->profiler?->disable();

        return $this->render('client/client.html.twig', [
            'config' => json_encode([
                'loginCheckTimeout' => $this->loginCheckTimeout,
                'refreshTokenTimeout' => $this->refreshTokenTimeout,
                'releaseTimestampIntervalTimeout' => $this->releaseTimestampIntervalTimeout,
                'pullStrategyInterval' => $this->pullStrategyInterval,
                'schedulingInterval' => $this->schedulingInterval,
                'colorScheme' => $this->colorScheme,
                'debug' => $this->debug,
                'logging' => $this->logging,
            ]),
        ]);
    }
}
