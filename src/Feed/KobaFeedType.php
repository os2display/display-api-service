<?php

namespace App\Feed;

use App\Entity\Feed;
use App\Entity\FeedSource;
use App\Service\FeedService;

class KobaFeedType implements FeedTypeInterface
{
    public function __construct(private FeedService $feedService)
    {
    }

    public function getData(Feed $feed): ?array
    {
        // @TODO: Get from KOBA.
        return [];
    }

    public function getAdmin(FeedSource $feedSource): ?array
    {
        $endpoint = $this->feedService->getFeedSourceConfigUrl($feedSource, 'resources');

        // @TODO: Translation.
        return [
            [
                'key' => 'koba-resource-selector',
                'input' => 'multiselect-from-endpoint',
                'endpoint' => $endpoint,
                'name' => 'resources',
                'label' => 'Vælg resurser',
                'helpText' => 'Her vælger du hvilke resourcer der skal hentes indgange fra.',
                'formGroupClasses' => 'col-md-6 mb-3',
            ],
        ];
    }

    public function getConfigOptions(FeedSource $feedSource, string $name): ?array
    {
        if ($name === 'resources') {
            // @TODO: Get from KOBA.
            return [
                [
                    'title' => 'Test 0',
                    'value' =>  'test0@example.com',
                ],
                [
                    'title' => 'Test 1',
                    'value' =>  'test1@example.com',
                ],
            ];
        }

        return null;
    }
}
