<?php

declare(strict_types=1);

namespace App\Security;

use App\NemDeling\NemDelingConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class NemDelingBasicAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly NemDelingConfig $config,
    ) {}

    public function supports(Request $request): ?bool
    {
        return str_starts_with($request->getPathInfo(), '/api/v1/nemdeling/');
    }

    public function authenticate(Request $request): Passport
    {
        if (!$this->config->isBasicAuthEnabled()) {
            return new SelfValidatingPassport(new UserBadge('nemdeling'));
        }

        $username = $request->headers->get('PHP_AUTH_USER');
        $password = $request->headers->get('PHP_AUTH_PW');

        if (
            !is_string($username)
            || !is_string($password)
            || $username !== $this->config->getBasicAuthUser()
            || $password !== $this->config->getBasicAuthPass()
        ) {
            throw new CustomUserMessageAuthenticationException('Invalid NemDeling credentials.');
        }

        return new SelfValidatingPassport(new UserBadge($username));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $response = new Response('Unauthorized', Response::HTTP_UNAUTHORIZED);
        $response->headers->set('WWW-Authenticate', 'Basic realm="NemDeling"');

        return $response;
    }
}
