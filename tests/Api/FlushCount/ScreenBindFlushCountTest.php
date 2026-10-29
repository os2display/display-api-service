<?php

declare(strict_types=1);

namespace App\Tests\Api\FlushCount;

use App\Entity\Tenant\Screen;

/**
 * POST /v2/screens/{id}/bind, which binds the key from POST /v2/authentication/screen (ScreenAuthenticator::bindScreen()).
 */
class ScreenBindFlushCountTest extends AbstractFlushCountTestCase
{
    public function testBindScreen(): void
    {
        // Create clients first; creating a client boots the kernel and resets the bind key session.
        $screenClient = self::createClient();
        $adminClient = $this->getAuthenticatedClient('ROLE_ADMIN');
        $screenClient->disableReboot();
        $adminClient->disableReboot();

        $response = $screenClient->request('POST', '/v2/authentication/screen', [
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $bindKey = json_decode($response->getContent(), null, 512, JSON_THROW_ON_ERROR)->bindKey;

        $screenIri = $this->findIriBy(Screen::class, ['title' => 'screen_abc_1']);

        $this->resetProbes();
        $adminClient->request('POST', $screenIri.'/bind', [
            'json' => ['bindKey' => $bindKey],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(204);

        // ScreenUser flush, then the refresh token manager's own flush; neither touches the checksum tree, so the calculator is skipped.
        $this->assertProbes(2, []);
    }
}
