<?php

declare(strict_types=1);

namespace App\Tests\Api\FlushCount;

use App\Entity\Tenant\Screen;
use App\Entity\Tenant\ScreenGroup;

/**
 * PUT /v2/screens/{id}/screen-groups.
 */
class ScreenScreenGroupsPutFlushCountTest extends AbstractFlushCountTestCase
{
    public function testPutScreenScreenGroups(): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');

        $screenUlid = $this->iriHelperUtils->getUlidFromIRI($this->findIriBy(Screen::class, ['title' => 'screen_abc_1']));
        $screenGroupUlid1 = $this->iriHelperUtils->getUlidFromIRI($this->findIriBy(ScreenGroup::class, ['title' => 'screen_group_abc_1']));
        $screenGroupUlid2 = $this->iriHelperUtils->getUlidFromIRI($this->findIriBy(ScreenGroup::class, ['title' => 'screen_group_abc_2']));

        $this->resetProbes();
        $client->request('PUT', '/v2/screens/'.$screenUlid.'/screen-groups', [
            'json' => [$screenGroupUlid1, $screenGroupUlid2],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        // Flush after removeAllScreenGroup(), then the final flush, both inside the repository's manual transaction.
        $this->assertProbes(2, [1, 1]);
    }
}
