<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Controller\Api\AuthOidcController;
use App\Security\AzureOidcAuthenticator;
use ItkDev\OpenIdConnect\Security\OpenIdConfigurationProvider;
use ItkDev\OpenIdConnectBundle\Security\OpenIdConfigurationProviderManager;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationFailureHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Regression test for the switch from autowired SessionInterface to
 * Request::getSession() in AuthOidcController::getUrls().
 *
 * Locks down that the OAuth2 state/nonce/provider keys end up on the
 * session attached to the request, so an accidental rename of those keys
 * or a regression of the request-session lookup is caught.
 */
class AuthOidcControllerTest extends TestCase
{
    public function testGetUrlsWritesOAuthStateToRequestSession(): void
    {
        $provider = $this->createMock(OpenIdConfigurationProvider::class);
        $provider->method('generateNonce')->willReturn('test-nonce');
        $provider->method('generateState')->willReturn('test-state');
        $provider->method('getEndSessionUrl')->willReturn('https://example.test/end-session');
        $provider->method('getAuthorizationUrl')->willReturn('https://example.test/authorize');

        $manager = $this->createMock(OpenIdConfigurationProviderManager::class);
        $manager->method('getProviderKeys')->willReturn(['internal']);
        $manager->method('getProvider')->with('internal')->willReturn($provider);

        $controller = new AuthOidcController(
            $manager,
            $this->createMock(AzureOidcAuthenticator::class),
            $this->createMock(AuthenticationSuccessHandler::class),
            $this->createMock(AuthenticationFailureHandler::class),
        );
        $controller->setContainer(new Container());

        $request = Request::create('/v2/authentication/oidc/urls?providerKey=internal');
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $response = $controller->getUrls($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string) $response->getContent());
        $this->assertSame('internal', $session->get('oauth2provider'));
        $this->assertSame('test-state', $session->get('oauth2state'));
        $this->assertSame('test-nonce', $session->get('oauth2nonce'));
    }

    public function testGetUrlsFallsBackToFirstProviderKeyWhenNoneRequested(): void
    {
        $provider = $this->createMock(OpenIdConfigurationProvider::class);
        $provider->method('generateNonce')->willReturn('n');
        $provider->method('generateState')->willReturn('s');
        $provider->method('getEndSessionUrl')->willReturn('https://example.test/end');
        $provider->method('getAuthorizationUrl')->willReturn('https://example.test/auth');

        $manager = $this->createMock(OpenIdConfigurationProviderManager::class);
        $manager->method('getProviderKeys')->willReturn(['first', 'second']);
        $manager->expects($this->once())->method('getProvider')->with('first')->willReturn($provider);

        $controller = new AuthOidcController(
            $manager,
            $this->createMock(AzureOidcAuthenticator::class),
            $this->createMock(AuthenticationSuccessHandler::class),
            $this->createMock(AuthenticationFailureHandler::class),
        );
        $controller->setContainer(new Container());

        $request = Request::create('/v2/authentication/oidc/urls');
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $controller->getUrls($request);

        $this->assertSame('first', $session->get('oauth2provider'));
    }
}
