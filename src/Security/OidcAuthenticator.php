<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use ItkDev\OpenIdConnect\Exception\ItkOpenIdConnectException;
use ItkDev\OpenIdConnectBundle\Exception\InvalidProviderException;
use ItkDev\OpenIdConnectBundle\Security\OpenIdConfigurationProviderManager;
use ItkDev\OpenIdConnectBundle\Security\OpenIdLoginAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class OidcAuthenticator extends OpenIdLoginAuthenticator
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager, SessionInterface $session, OpenIdConfigurationProviderManager $providerManager)
    {
        parent::__construct($providerManager, $session);

        $this->entityManager = $entityManager;
    }

    /**
     * @throws InvalidProviderException
     */
    public function authenticate(Request $request): Passport
    {
        try {
            // Validate claims
            $claims = $this->validateClaims($request);

            // Extract properties from claims
            // $name = $claims['name'];
            $email = $claims['email'];
            $roles = $claims['groups'] ?? [];

            // Check if user exists already - if not create a user
            $user = $this->entityManager->getRepository(User::class)
                ->findOneBy(['email' => $email]);
            if (null === $user) {
                // Create the new user and persist it
                $user = new User();

                $this->entityManager->persist($user);
            }
            // Update/set user properties
            // @TODO Set from claims
            $user->setFullName($email);
            $user->setEmail($email);

            $user->setRoles($roles);

            $this->entityManager->flush();

            return new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), [$this, 'getUser']));
        } catch (ItkOpenIdConnectException $exception) {
            throw new CustomUserMessageAuthenticationException($exception->getMessage());
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new Response('Auth success', 200);
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new Response('Auth header required', 401);
    }

    public function getUser(string $identifier): User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['email' => $identifier]);
    }

    private function getTenants(array $roles)
}
