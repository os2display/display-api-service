<?php

declare(strict_types=1);

namespace App\Tests\Api\FlushCount;

use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\Screen;

/**
 * PUT /v2/screens/{campaignId}/campaigns.
 */
class ScreenCampaignsPutFlushCountTest extends AbstractFlushCountTestCase
{
    /**
     * @param int[] $nestingLevels
     *
     * @dataProvider bodySizeProvider
     */
    public function testPutScreenCampaigns(int $screens, int $flushes, array $nestingLevels): void
    {
        $client = $this->getAuthenticatedClient('ROLE_ADMIN');
        $campaignUlid = $this->iriHelperUtils->getUlidFromIRI($this->findIriBy(Playlist::class, ['title' => 'playlist_abc_1']));

        // Start every case from the same single existing relation.
        $this->putScreenCampaigns($client, $campaignUlid, 1);

        $this->resetProbes();
        $this->putScreenCampaigns($client, $campaignUlid, $screens);

        $this->assertProbes($flushes, $nestingLevels);
    }

    /**
     * @return array<string, array{int, int, int[]}>
     */
    public static function bodySizeProvider(): array
    {
        return [
            // One flush per screen (or one for the empty-body branch), all inside the repository's manual transaction.
            '0 screens' => [0, 1, [1]],
            '1 screen' => [1, 1, [1]],
            '5 screens' => [5, 5, [1, 1, 1, 1, 1]],
        ];
    }

    private function putScreenCampaigns(Client $client, string $campaignUlid, int $screens): void
    {
        $body = [];
        for ($i = 1; $i <= $screens; ++$i) {
            $iri = $this->findIriBy(Screen::class, ['title' => 'screen_abc_'.$i]);
            $body[] = (object) ['screen' => $this->iriHelperUtils->getUlidFromIRI($iri)];
        }

        $client->request('PUT', '/v2/screens/'.$campaignUlid.'/campaigns', [
            'json' => $body,
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(201);
    }
}
