<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Enum\UserTypeEnum;
use App\Utils\Roles;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class UserVoter extends Voter
{
    public const CREATE = 'CREATE';
    public const EDIT = 'EDIT';
    public const VIEW = 'VIEW';
    public const DELETE = 'DELETE';

    public function __construct(
        private readonly Security $security
    ) {}

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

        $subjectUserType = $subject->getUserType();

        switch ($subjectUserType) {
            case UserTypeEnum::OIDC_EXTERNAL:
                if ($this->security->isGranted(Roles::ROLE_EXTERNAL_USER_ADMIN)) {
                    return true;
                }
                break;
            case UserTypeEnum::USERNAME_PASSWORD:
            case UserTypeEnum::OIDC_INTERNAL:
                if ($this->security->isGranted(Roles::ROLE_USER_ADMIN)) {
                    return true;
                }
        }

        return false;
    }
}
