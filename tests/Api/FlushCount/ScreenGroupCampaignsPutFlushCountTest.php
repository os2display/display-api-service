<?php

declare(strict_types=1);

namespace App\Tests\Api\FlushCount;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\ScreenGroup;

/**
 * PUT /v2/screen-groups/{campaignId}/campaigns.
 */
class ScreenGroupCampaignsPutFlushCountTest extends AbstractFlushCountTestCase
{
    /**
     * @param int[] $nestingLevels
     *
     * @dataProvider bodySizeProvider
     */
    public function testPutScreenGroupCampaigns(int $screenGroups, int $flushes, array $nestingLevels): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');
        $campaignUlid = $this->iriHelperUtils->getUlidFromIRI($this->findIriBy(Playlist::class, ['title' => 'playlist_abc_1']));

        // Start every case from the same single existing relation.
        $this->putScreenGroupCampaigns($client, $campaignUlid, 1);

        $this->resetProbes();
        $this->putScreenGroupCampaigns($client, $campaignUlid, $screenGroups);

        $this->assertProbes($flushes, $nestingLevels);
    }

    /**
     * @return array<string, array{int, int, int[]}>
     */
    public static function bodySizeProvider(): array
    {
        return [
            // One flush per screen group (or one for the empty-body branch), all inside the repository's manual transaction.
            '0 screen groups' => [0, 1, [1]],
            '1 screen group' => [1, 1, [1]],
            '5 screen groups' => [5, 5, [1, 1, 1, 1, 1]],
        ];
    }

    private function putScreenGroupCampaigns(Client $client, string $campaignUlid, int $screenGroups): void
    {
        $body = [];
        for ($i = 1; $i <= $screenGroups; ++$i) {
            $iri = $this->findIriBy(ScreenGroup::class, ['title' => 'screen_group_abc_'.$i]);
            $body[] = (object) ['screengroup' => $this->iriHelperUtils->getUlidFromIRI($iri)];
        }

        $client->request('PUT', '/v2/screen-groups/'.$campaignUlid.'/campaigns', [
            'json' => $body,
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(201);
    }
}
