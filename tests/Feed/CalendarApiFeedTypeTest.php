<?php

declare(strict_types=1);

namespace App\Tests\Feed;

use App\Feed\CalendarApiFeedType;
use App\Tests\AbstractBaseApiTestCase;

class CalendarApiFeedTypeTest extends AbstractBaseApiTestCase
{
    public function testApplyModifiersToEvents(): void
    {
        $data = new CalendarApiFeedTypeData();

        $result = CalendarApiFeedType::applyModifiersToEvents($data->events, $data->modifiers, ['excludeIfNotContainsListe']);

        $this->assertEquals(2, count($result));
        $this->assertEquals('title3', $result[0]->title);
        $this->assertEquals('Optaget', $result[1]->title);

        $data = new CalendarApiFeedTypeData();

        $result = CalendarApiFeedType::applyModifiersToEvents($data->events, $data->modifiers, ['onlyShowAsOptaget']);

        $this->assertEquals(4, count($result));
        $this->assertEquals('Optaget', $result[0]->title);
        $this->assertEquals('Optaget', $result[1]->title);
        $this->assertEquals('Optaget', $result[2]->title);
        $this->assertEquals('Optaget', $result[3]->title);

        $data = new CalendarApiFeedTypeData();

        $result = CalendarApiFeedType::applyModifiersToEvents($data->events, $data->modifiers, ['excludeIfNotContainsListe', 'onlyShowAsOptaget']);

        $this->assertEquals(2, count($result));
        $this->assertEquals('Optaget', $result[0]->title);
        $this->assertEquals('Optaget', $result[1]->title);
    }
}
