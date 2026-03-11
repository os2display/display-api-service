<?php

namespace App\Security\Voter;

use ApiPlatform\State\Pagination\TraversablePaginator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class AuthorizationVoter extends Voter {
    public const string EDIT = 'EDIT';
    public const string VIEW = 'VIEW';
    public const string LIST = 'LIST';
    public const string CREATE = 'CREATE';
    public const string DELETE = 'DELETE';

    private array $authorization;

    public function __construct(private readonly array $authorizationOverride, private readonly RoleHierarchyInterface $roleHierarchy)
    {
        $authorizationDefaults = AuthorizationVoterHelper::getAuthorizationDefaults();
        $this->authorization = array_replace($authorizationDefaults, $authorizationOverride);
    }

    public function getAuthorization(): array
    {
        return $this->authorization;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // https://symfony.com/doc/current/security/voters.html

        if ($attribute === self::LIST && $subject instanceof TraversablePaginator) {
            return true;
        }

        return in_array($attribute, [self::EDIT, self::VIEW, self::CREATE, self::DELETE]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        if ($attribute === self::VIEW) {
            return true;
        }

        $userRoles = $user->getRoles();

        $dto = str_contains($subject::class, "App\\Dto\\");
        $entity = str_contains($subject::class, "App\\Entity\\Tenant\\");

        $class = '';
        $createdBy = null;

        if ($dto) {
            $class = str_replace("App\\Dto\\", "", $subject::class);
            $createdBy = $subject->createdBy;
        } else if ($entity) {
            $class = str_replace("App\\Entity\\Tenant\\", "", $subject::class);
            $createdBy = $subject->getCreatedBy();
        }

        $userIdentifier = $user->getUserIdentifier();

        // The creator has permission to use the object.
        if ($userIdentifier === $createdBy) {
            return true;
        }

        // Check the authorization array for demands for $class and $attribute.
        $requiredRoles = $this->authorization[$class][$attribute];

        $reachableRoles = $this->roleHierarchy->getReachableRoleNames($userRoles);

        $intersect = array_intersect($requiredRoles, $reachableRoles);

        return !empty($intersect);
    }
}
