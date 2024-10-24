<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Dto\User as UserOutput;
use App\Entity\User;
use App\Enum\UserTypeEnum;
use App\Utils\Roles;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter extends Voter
{
    final public const string CREATE = 'CREATE';
    final public const string EDIT = 'EDIT';
    final public const string VIEW = 'VIEW';
    final public const string DELETE = 'DELETE';

    public function __construct(
        private readonly Security $security,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, [self::EDIT, self::VIEW, self::DELETE, self::CREATE])
            && ($subject instanceof User || $subject instanceof UserOutput);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof User && !$subject instanceof UserOutput) {
            return false;
        }

        if ($subject instanceof User) {
            $subjectUserType = $subject->getUserType();
        } else {
            $subjectUserType = $subject->userType;
        }

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
