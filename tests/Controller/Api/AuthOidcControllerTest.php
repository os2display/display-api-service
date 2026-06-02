<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Controller\Api\AuthOidcController;
use App\Security\AzureOidcAuthenticator;
use ItkDev\OpenIdConnect\Exception\MetadataException;
use ItkDev\OpenIdConnect\Security\OpenIdConfigurationProvider;
use ItkDev\OpenIdConnectBundle\Exception\InvalidProviderException;
use ItkDev\OpenIdConnectBundle\Security\OpenIdConfigurationProviderManager;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationFailureHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    /**
     * getUrls() treats a missing end-session endpoint as optional: when the
     * provider throws an OIDC error from getEndSessionUrl(), the inner catch
     * swallows it and returns endSessionUrl => null with a 200 response.
     *
     * Regression guard for the openid-connect 5.0 exception rework — the inner
     * catch must match on OpenIdConnectExceptionInterface (which
     * MetadataException implements), not the deprecated abstract base.
     */
    public function testGetUrlsReturnsNullEndSessionUrlWhenProviderHasNoEndSessionEndpoint(): void
    {
        $provider = $this->createMock(OpenIdConfigurationProvider::class);
        $provider->method('generateNonce')->willReturn('test-nonce');
        $provider->method('generateState')->willReturn('test-state');
        $provider->method('getEndSessionUrl')->willThrowException(new MetadataException('no end_session_endpoint'));
        $provider->method('getAuthorizationUrl')->willReturn('https://example.test/authorize');

        $manager = $this->createMock(OpenIdConfigurationProviderManager::class);
        $manager->method('getProvider')->with('internal')->willReturn($provider);

        $controller = new AuthOidcController(
            $manager,
            $this->createMock(AzureOidcAuthenticator::class),
            $this->createMock(AuthenticationSuccessHandler::class),
            $this->createMock(AuthenticationFailureHandler::class),
        );
        $controller->setContainer(new Container());

        $request = Request::create('/v2/authentication/oidc/urls?providerKey=internal');
        $request->setSession(new Session(new MockArraySessionStorage()));

        $response = $controller->getUrls($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), (string) $response->getContent());
        $data = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $this->assertNull($data['endSessionUrl']);
        $this->assertSame('https://example.test/authorize', $data['authorizationUrl']);
    }

    /**
     * getUrls() maps an unknown provider key to a 404. The provider manager
     * throws InvalidProviderException, which the controller catches and
     * rethrows as a NotFoundHttpException.
     */
    public function testGetUrlsThrowsNotFoundForUnknownProvider(): void
    {
        $manager = $this->createMock(OpenIdConfigurationProviderManager::class);
        $manager->method('getProvider')->with('bogus')->willThrowException(new InvalidProviderException('unknown provider'));

        $controller = new AuthOidcController(
            $manager,
            $this->createMock(AzureOidcAuthenticator::class),
            $this->createMock(AuthenticationSuccessHandler::class),
            $this->createMock(AuthenticationFailureHandler::class),
        );
        $controller->setContainer(new Container());

        $request = Request::create('/v2/authentication/oidc/urls?providerKey=bogus');
        $request->setSession(new Session(new MockArraySessionStorage()));

        try {
            $controller->getUrls($request);
            $this->fail('Expected NotFoundHttpException was not thrown.');
        } catch (NotFoundHttpException $e) {
            $this->assertSame(Response::HTTP_NOT_FOUND, $e->getStatusCode());
            $this->assertSame('Unknown provider: bogus', $e->getMessage());
        }
    }

    /**
     * getUrls() maps a generic OIDC failure (e.g. malformed discovery document)
     * to a 500. The outer catch must match on OpenIdConnectExceptionInterface;
     * before the openid-connect 5.0 rework this caught the deprecated abstract
     * base and would silently let the exception escape unmapped.
     */
    public function testGetUrlsThrowsHttpExceptionWhenProviderFailsWithOidcError(): void
    {
        $provider = $this->createMock(OpenIdConfigurationProvider::class);
        $provider->method('generateNonce')->willReturn('test-nonce');
        $provider->method('generateState')->willReturn('test-state');
        $provider->method('getEndSessionUrl')->willReturn('https://example.test/end-session');
        $provider->method('getAuthorizationUrl')->willThrowException(new MetadataException('malformed discovery document'));

        $manager = $this->createMock(OpenIdConfigurationProviderManager::class);
        $manager->method('getProvider')->with('internal')->willReturn($provider);

        $controller = new AuthOidcController(
            $manager,
            $this->createMock(AzureOidcAuthenticator::class),
            $this->createMock(AuthenticationSuccessHandler::class),
            $this->createMock(AuthenticationFailureHandler::class),
        );
        $controller->setContainer(new Container());

        $request = Request::create('/v2/authentication/oidc/urls?providerKey=internal');
        $request->setSession(new Session(new MockArraySessionStorage()));

        try {
            $controller->getUrls($request);
            $this->fail('Expected HttpException was not thrown.');
        } catch (HttpException $e) {
            $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getStatusCode());
            $this->assertSame('malformed discovery document', $e->getMessage());
        }
    }
}
