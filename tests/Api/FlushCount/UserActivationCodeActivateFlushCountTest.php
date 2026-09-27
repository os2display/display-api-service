<?php

declare(strict_types=1);

namespace App\Tests\Api\FlushCount;

use App\Utils\Roles;

/**
 * POST /v2/user-activation-codes/activate.
 */
class UserActivationCodeActivateFlushCountTest extends AbstractFlushCountTestCase
{
    public function testActivate(): void
    {
        $adminClient = $this->getAuthenticatedClient(Roles::ROLE_EXTERNAL_USER_ADMIN);
        $response = $adminClient->request('POST', '/v2/user-activation-codes', [
            'body' => json_encode(['displayName' => 'Flush Count', 'roles' => [Roles::ROLE_EXTERNAL_USER_ADMIN]]),
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseIsSuccessful();
        $code = $response->toArray()['code'];

        $externalClient = $this->getAuthenticatedClientForExternalUser(true);

        $this->resetProbes();
        $externalClient->request('POST', '/v2/user-activation-codes/activate', [
            'json' => ['activationCode' => $code],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(204);

        // The activation code removal flushes on its own before UserService's final flush.
        $this->assertProbes(2, [0, 0]);
    }
}
