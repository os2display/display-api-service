<?php

declare(strict_types=1);

namespace App\Tests\Feed;

use App\Model\CalendarEvent;

class CalendarApiFeedTypeData
{
    public array $events = [];
    public array $modifiers = [];

    public function __construct()
    {
        $this->events[] = new CalendarEvent(
            'id1',
            'title1',
            1,
            2,
            'resourse1',
            'Resource 1'
        );
        $this->events[] = new CalendarEvent(
            'id2',
            'title2 (optaget)',
            3,
            4,
            'resourse1',
            'Resource 1'
        );
        $this->events[] = new CalendarEvent(
            'id3',
            'title3 (lISTe)',
            5,
            6,
            'resourse1',
            'Resource 1'
        );
        $this->events[] = new CalendarEvent(
            'id4',
            'title4 (lISTe) (optaGET)',
            7,
            8,
            'resourse1',
            'Resource 1'
        );

        $this->modifiers[] = [
            'type' => 'EXCLUDE_IF_TITLE_NOT_CONTAINS',
            'id' => 'excludeIfNotContainsListe',
            'title' => 'Vis kun begivenheder med (liste) i titlen.',
            'description' => 'Denne mulighed fjerner begivenheder, der IKKE har (liste) i titlen. Den fjerner også (liste) fra titlen.',
            'activateInFeed' => true,
            'trigger' => '\(liste\)',
            'removeTrigger' => true,
            'caseSensitive' => false,
        ];

        $this->modifiers[] = [
            'type' => 'REPLACE_TITLE_IF_CONTAINS',
            'activateInFeed' => false,
            'id' => 'replaceIfContainsOptaget',
            'trigger' => '\(optaget\)',
            'replacement' => 'Optaget',
            'removeTrigger' => true,
            'caseSensitive' => false,
        ];

        $this->modifiers[] = [
            'type' => 'REPLACE_TITLE_IF_CONTAINS',
            'activateInFeed' => true,
            'id' => 'onlyShowAsOptaget',
            'title' => 'Overskriv alle titler med Optaget',
            'description' => 'Denne mulighed viser alle titler som Optaget.',
            'trigger' => '',
            'replacement' => 'Optaget',
            'removeTrigger' => false,
            'caseSensitive' => false,
        ];
    }
}
