<?php

namespace App\Security;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/**
 * class AuthenticationSuccessHandlerDecorator.
 *
 * Decorates the Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler
 * to add additional user and tenant access data to the response
 */
class AuthenticationSuccessHandlerDecorator implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private AuthenticationSuccessHandler $authenticationSuccessHandler
    ) {}

    /** {@inheritDoc} */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        return $this->handleAuthenticationSuccess($token->getUser());
    }

    /** {@inheritDoc} */
    public function handleAuthenticationSuccess(UserInterface $user, $jwt = null): Response
    {
        $response = $this->authenticationSuccessHandler->handleAuthenticationSuccess($user, $jwt);

        if ($user instanceof User) {
            $data = \json_decode($response->getContent(), false, 512, JSON_THROW_ON_ERROR);
            $data->tenants = $user->getUserRoleTenants()->toArray();
            $data->user = $user;
            $response->setData($data);
        }

        return $response;
    }
}
