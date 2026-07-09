<?php

declare(strict_types=1);

namespace App\NemDeling\Controller;

use App\NemDeling\Mapper\EventDataMapper;
use App\NemDeling\Model\NemDelingResult;
use App\NemDeling\NemDelingConfig;
use App\NemDeling\NemDelingService;
use App\NemDeling\NemDelingSyncLock;
use App\NemDeling\Xml\NemDelingXmlParser;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class NemDelingEventListsController
{
    public function __construct(
        private readonly NemDelingXmlParser $xmlParser,
        private readonly EventDataMapper $eventDataMapper,
        private readonly NemDelingService $nemDelingService,
        private readonly NemDelingSyncLock $syncLock,
        private readonly NemDelingConfig $config,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/api/v1/nemdeling/event-lists', name: 'nemdeling_event_lists', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $this->logger->debug('NemDeling event-lists webhook received.');

        $this->syncLock->acquireEventLists();

        try {
            $body = $this->xmlParser->parse($request->getContent());
            $mapped = $this->eventDataMapper->map(
                $body,
                $this->config->getEventListTemplateTitle(),
                'eventList'
            );
            $results = $this->nemDelingService->syncEventLists($mapped);

            return $this->createResponse($results);
        } finally {
            $this->syncLock->releaseEventLists();
        }
    }

    /**
     * @param NemDelingResult[] $results
     */
    private function createResponse(array $results): Response
    {
        $payload = array_map(
            static fn (NemDelingResult $result): array => [
                'name' => $result->name,
                'status' => $result->status,
            ],
            $results
        );

        $this->logger->info('NemDeling event lists result: '.json_encode($payload, JSON_THROW_ON_ERROR));

        return new Response('OK: '.json_encode($payload, JSON_THROW_ON_ERROR), Response::HTTP_CREATED);
    }
}
