<?php

namespace App\Security\Voter;

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

    public function __construct(
        private readonly array $authorizationOverride,
        private readonly RoleHierarchyInterface $roleHierarchy
    ) {
        $this->authorization = array_replace(AuthorizationVoterHelper::AUTHORIZATION_DEFAULTS, $authorizationOverride);
    }

    public function getAuthorization(): array
    {
        return $this->authorization;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // https://symfony.com/doc/current/security/voters.html

        // Supports entries of the form:
        // security: 'is_granted("<ATTRIBUTE>", {type: "<TYPE>", object: object})'

        if (in_array($attribute, [self::EDIT, self::VIEW, self::CREATE, self::DELETE, self::LIST]) &&
            is_array($subject) &&
            !empty($subject['type']) &&
            !empty($subject['object']) &&
            in_array($subject['type'], AuthorizationVoterHelper::TYPES)
        ) {
            return true;
        }

        return false;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        $type = $subject['type'];
        $object = $subject['object'];

        // For all entities check if the user is the creator.
        if ($attribute !== self::LIST) {
            $createdBy = null;

            $isDTO = str_starts_with($object::class, "App\\Dto\\");
            $isEntity = str_starts_with($object::class, "App\\Entity\\");

            if ($isDTO) {
                $createdBy = $object->createdBy;
            } else if ($isEntity) {
                $createdBy = $object->getCreatedBy();
            }

            $userIdentifier = $user->getUserIdentifier();

            // The creator always has permission to use the object.
            if ($userIdentifier === $createdBy) {
                return true;
            }
        }

        $userRoles = $user->getRoles();
        $reachableRoles = $this->roleHierarchy->getReachableRoleNames($userRoles);

        // Check the authorization array for demands for $class and $attribute.
        $requiredRoles = $this->authorization[$type][$attribute];

        $intersect = array_intersect($requiredRoles, $reachableRoles);

        return !empty($intersect);
    }
}
