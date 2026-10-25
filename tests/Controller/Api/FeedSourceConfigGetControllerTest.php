<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Controller\Api\FeedSourceConfigGetController;
use App\Entity\Tenant\FeedSource;
use App\Feed\FeedConfigurationException;
use App\Repository\FeedSourceRepository;
use App\Service\FeedService;
use App\Utils\ValidationUtils;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Validation;

class FeedSourceConfigGetControllerTest extends TestCase
{
    public function testConfigurationFailureReturnsOnlySafeMessage(): void
    {
        $feedService = $this->createMock(FeedService::class);
        $feedService->method('getConfigOptions')->willThrowException(
            new FeedConfigurationException('Safe connection error', 0, new \RuntimeException('Sensitive provider response')),
        );

        $response = $this->createController($feedService)(new Request(), (string) new Ulid(), 'locations');

        $this->assertSame(502, $response->getStatusCode());
        $this->assertSame(['hydra:description' => 'Safe connection error'], json_decode((string) $response->getContent(), true));
    }

    public function testSuccessfulEmptyOptionsRemainAnArray(): void
    {
        $feedService = $this->createMock(FeedService::class);
        $feedService->method('getConfigOptions')->willReturn([]);

        $response = $this->createController($feedService)(new Request(), (string) new Ulid(), 'locations');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('[]', $response->getContent());
    }

    public function testUnknownConfigurationStillReturnsNotFound(): void
    {
        $feedService = $this->createMock(FeedService::class);
        $feedService->method('getConfigOptions')->willReturn(null);

        $response = $this->createController($feedService)(new Request(), (string) new Ulid(), 'unknown');

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('{}', $response->getContent());
    }

    private function createController(FeedService $feedService): FeedSourceConfigGetController
    {
        $repository = $this->createMock(FeedSourceRepository::class);
        $repository->method('find')->willReturn(new FeedSource());

        return new FeedSourceConfigGetController($feedService, $repository, new ValidationUtils(Validation::createValidator(), 'Y-m-d'));
    }
}
