<?php

namespace App\Security\Voter;

use App\Dto\Screen;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class AuthorizationVoter extends Voter {
    public const EDIT = 'EDIT';
    public const VIEW = 'VIEW';
    public const CREATE = 'CREATE';
    public const DELETE = 'DELETE';
    public const OWN = '_OWN';
    public const SCREEN = 'Screen';

    private array $authorizationDefault = [
        self::SCREEN => [
            self::EDIT => ['ROLE_ADMIN'],
            self::EDIT . self::OWN => ['ROLE_ADMIN'],
            self::VIEW => ['ROLE_ADMIN'],
            self::VIEW . self::OWN => ['ROLE_ADMIN'],
            self::CREATE => ['ROLE_ADMIN'],
            self::CREATE . self::OWN => ['ROLE_ADMIN'],
            self::DELETE => ['ROLE_ADMIN'],
            self::DELETE . self::OWN => ['ROLE_ADMIN'],
        ]
    ];

    private array $authorization;

    public function __construct(private readonly array $authorizationOverride, private readonly RoleHierarchyInterface $roleHierarchy)
    {
        $this->authorization = array_replace($this->authorizationDefault, $authorizationOverride);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // https://symfony.com/doc/current/security/voters.html

        return in_array($attribute, [self::EDIT, self::VIEW, self::CREATE, self::DELETE])
            && $subject instanceof Screen;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        $userRoles = $user->getRoles();

        $class = str_replace("App\\Dto\\", "", $subject::class);

        $createdBy = $subject->createdBy;

        $userIdentifier = $user->getUserIdentifier();

        // Check authorization array for demands for $class, $attribute and $userIdentifier
        // Permissions are different if the user is the creator of the object.
        $actionKey = $attribute . ($userIdentifier === $createdBy ? self::OWN : '');
        $requiredRole = $this->authorization[$class][$actionKey];

        $reachableRoles = $this->roleHierarchy->getReachableRoleNames($userRoles);

        return in_array($requiredRole, $reachableRoles);
    }
}
