<?php

namespace App\Tests\Security;

use App\Entity\ScreenUser;
use App\Security\JWSProviderDecorator;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWSProvider\JWSProviderInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class JWSProviderDecoratorTest extends KernelTestCase
{
    public function testCreate(): void
    {
        $JwsProvider = static::getContainer()->get(JWSProviderInterface::class);
        $JWSProviderDecorator = new JWSProviderDecorator($JwsProvider, JWSProviderDecorator::SCREEN_TOKEN_TTL);

        $payload = [
            'roles' => ['ROLE_USER', ScreenUser::ROLE_SCREEN],
            'username' => 'john@example.com',
        ];

        $jws = $JWSProviderDecorator->create($payload, []);

        $token = $jws->getToken();
        $decoded = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $token)[1]))));

        $expected = time() + JWSProviderDecorator::SCREEN_TOKEN_TTL;

        $this->assertGreaterThanOrEqual($expected, $decoded->exp);
    }
}
