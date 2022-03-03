<?php

namespace App\Security;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class AuthenticationSuccessHandlerDecorator implements AuthenticationSuccessHandlerInterface
{
    public function __construct(private AuthenticationSuccessHandler $authenticationSuccessHandler)
    {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        return $this->authenticationSuccessHandler->onAuthenticationSuccess($request, $token);
    }

    /**
     * @throws \JsonException
     */
    public function handleAuthenticationSuccess(UserInterface $user, $jwt = null)
    {
        $response = $this->authenticationSuccessHandler->handleAuthenticationSuccess($user, $jwt);

        if ($user instanceof User) {
            $data = \json_decode($response->getContent(), false, 512, JSON_THROW_ON_ERROR);
            $data->tenants = $user->getTenants()->toArray();
            $response->setData($data);
        }

        return $response;
    }
}
