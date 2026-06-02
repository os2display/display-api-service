<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Security\AzureOidcAuthenticator;
use ItkDev\OpenIdConnect\Exception\OpenIdConnectExceptionInterface;
use ItkDev\OpenIdConnectBundle\Exception\InvalidProviderException;
use ItkDev\OpenIdConnectBundle\Security\OpenIdConfigurationProviderManager;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationFailureHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

#[AsController]
class AuthOidcController extends AbstractController
{
    public function __construct(
        private readonly OpenIdConfigurationProviderManager $configurationProviderManager,
        private readonly AzureOidcAuthenticator $oidcAuthenticator,
        private readonly AuthenticationSuccessHandler $successHandler,
        private readonly AuthenticationFailureHandler $failureHandler,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/v2/authentication/oidc/token', name: 'authentication_oidc_token', methods: ['GET'])]
    public function getToken(Request $request): Response
    {
        if ($request->query->has('state') && $request->query->has('code')) {
            try {
                $passport = $this->oidcAuthenticator->authenticate($request);

                return $this->successHandler->handleAuthenticationSuccess($passport->getUser());
            } catch (CustomUserMessageAuthenticationException|InvalidProviderException $e) {
                $this->logger->warning('OIDC token authentication failed', ['exception' => $e]);

                $e = new AuthenticationException($e->getMessage());

                return $this->failureHandler->onAuthenticationFailure($request, $e);
            }
        } else {
            $e = new AuthenticationException('Bad request');

            return $this->failureHandler->onAuthenticationFailure($request, $e);
        }
    }

    #[Route('/v2/authentication/oidc/urls', name: 'authentication_oidc_urls', methods: ['GET'])]
    public function getUrls(Request $request): Response
    {
        $providerKey = $request->query->get('providerKey');

        if (!$providerKey) {
            $keys = $this->configurationProviderManager->getProviderKeys();
            $providerKey = array_shift($keys);
        }

        try {
            $provider = $this->configurationProviderManager->getProvider($providerKey);

            $nonce = $provider->generateNonce();
            $state = $provider->generateState();

            // Save to session
            $session = $request->getSession();
            $session->set('oauth2provider', $providerKey);
            $session->set('oauth2state', $state);
            $session->set('oauth2nonce', $nonce);

            // We allow end session endpoint to not be set.
            try {
                $endSessionUrl = $provider->getEndSessionUrl();
            } catch (OpenIdConnectExceptionInterface) { // @phpstan-ignore logging.silentCatch (the end-session endpoint is optional; its absence is an expected configuration, not a failure)
                $endSessionUrl = null;
            }

            $data = [
                'authorizationUrl' => $provider->getAuthorizationUrl([
                    'state' => $state,
                    'nonce' => $nonce,
                    'response_type' => 'code',
                    'scope' => 'openid email profile',
                ]),
                'endSessionUrl' => $endSessionUrl,
            ];

            return new JsonResponse($data);
        } catch (InvalidProviderException) {
            throw $this->createNotFoundException('Unknown provider: '.$providerKey);
        } catch (OpenIdConnectExceptionInterface $e) {
            throw new HttpException(500, $e->getMessage());
        }
    }
}
