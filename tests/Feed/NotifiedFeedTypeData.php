<?php

declare(strict_types=1);

namespace App\Tests\Feed;

class NotifiedFeedTypeData
{
    public static function getConfigData(): array
    {
        return [
            [
                'id' => 12345,
                'name' => 'Test1',
                'accountId' => 12345,
                'categoryId' => 12345,
                'categoryName' => 'Test2',
                'queries' => [
                    '("#tag")',
                ],
                'selectedMediaTypes' => [
                    'instagram',
                ],
            ],
            [
                'id' => 12346,
                'name' => 'Test3',
                'accountId' => 12346,
                'categoryId' => 12346,
                'categoryName' => 'Test4',
                'queries' => [
                    '("#tag")',
                ],
                'selectedMediaTypes' => [
                    'instagram',
                ],
            ],
        ];
    }

    public static function getData(): array
    {
        return [
            [
                'externalId' => '1_1111111111111111111',
                'title' => null,
                'description' => "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.\n\nUt enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. #tag",
                'sourceName' => 'test',
                'sourceUrl' => 'https://example.com',
                'author' => null,
                'mediaUrl' => '/media/thumbnail_other.png',
                'url' => 'https://example.com',
                'searchProfileId' => 123456,
                'queryString' => '("#tag")',
                'languageCode' => 'da',
                'mediaType' => 'instagram',
                'published' => '2024-04-23T08:00:34',
                'sentiment' => 'positive',
                'commonMetadata' => [
                    'reach' => 0,
                    'engagement' => 9,
                    'authorImageUrl' => null,
                ],
                'articleMetadata' => null,
                'blogMetadata' => null,
                'facebookMetadata' => null,
                'forumMetadata' => null,
                'instagramMetadata' => [
                    'likeCount' => 9,
                    'commentCount' => 0,
                    'geo' => '0|0',
                ],
                'twitterMetadata' => null,
                'youtubeMetadata' => null,
                'rssMetadata' => null,
                'printMetadata' => null,
                'tikTokMetadata' => null,
                'linkedInMetadata' => null,
                'commonLocationData' => [
                    'country' => 'Denmark',
                    'countryCode' => 'DK',
                    'latitude' => 55.67576,
                    'longitude' => 12.56902,
                ],
            ],
        ];
    }
}
