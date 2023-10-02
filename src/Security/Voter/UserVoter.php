<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Enum\UserTypeEnum;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class UserVoter extends Voter
{
    public const CREATE = 'CREATE';
    public const EDIT = 'EDIT';
    public const VIEW = 'VIEW';
    public const DELETE = 'DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, [self::EDIT, self::VIEW, self::DELETE, self::CREATE])
            && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof User) {
            return false;
        }

        /** @var User $user */
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        $subjectUserType = $subject->getUserType();

        $roles = $user->getRoles();

        switch ($subjectUserType) {
            case UserTypeEnum::OIDC_EXTERNAL:
                if (in_array('ROLE_ADMIN', $roles) || in_array('ROLE_EXTERNAL_USER_ADMIN', $roles)) {
                    return true;
                }
                break;
            case UserTypeEnum::USERNAME_PASSWORD:
            case UserTypeEnum::OIDC_INTERNAL:
            default:
                if (in_array('ROLE_USER_ADMIN', $roles)) {
                    return true;
                }
        }

        return false;
    }
}
