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

        $dto = str_contains($subject::class, "App\\Dto\\");
        $entity = str_contains($subject::class, "App\\Entity\\Tenant\\");

        return in_array($attribute, [self::EDIT, self::VIEW, self::CREATE, self::DELETE])
            && ($dto || $entity);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
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

        // Check authorization array for demands for $class, $attribute and $userIdentifier
        // Permissions are different if the user is the creator of the object.
        $actionKey = $attribute . ($userIdentifier === $createdBy ? self::OWN : '');
        $requiredRoles = $this->authorization[$class][$actionKey];

        $reachableRoles = $this->roleHierarchy->getReachableRoleNames($userRoles);

        $intersect = array_intersect($requiredRoles, $reachableRoles);

        return !empty($intersect);
    }
}
