<?php

namespace App\Feed;

use App\Entity\Feed;
use App\Entity\FeedSource;

class KobaFeedType implements FeedTypeInterface
{
    public function __construct()
    {
    }

    public function getData(FeedSource $feedSource, Feed $feed): ?array
    {
        return [];
    }

    public function getAdmin(): ?array
    {
        // @TODO: Translation.
        return [
            [
                'key' => 'koba-resource-selector',
                'input' => 'multiselect',
                'name' => 'selectResources',
                'label' => 'Vælg resurser',
                'helpText' => 'Her vælger du hvilke resourcer der skal hentes indgange fra.',
                'formGroupClasses' => 'col-md-6 mb-3',
            ],
        ];
    }
}
