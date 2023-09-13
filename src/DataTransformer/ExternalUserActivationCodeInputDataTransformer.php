<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\ExternalUserActivationCodeInput;
use App\Entity\ExternalUserActivationCode;
use App\Entity\User;
use App\Exceptions\CodeGenerationException;
use App\Service\ExternalUserService;
use Symfony\Component\Security\Core\Security;

class ExternalUserActivationCodeInputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly ExternalUserService $externalUserService,
    ) {}

    /**
     * {@inheritdoc}
     *
     * @throws CodeGenerationException
     *
     * @var ExternalUserActivationCodeInput
     */
    public function transform($object, string $to, array $context = []): ExternalUserActivationCode
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $roles = [];

        // Only allow EXTERNAL_USER roles.
        if (in_array('ROLE_EXTERNAL_USER_ADMIN', $object->roles)) {
            $roles[] = 'ROLE_EXTERNAL_USER_ADMIN';
        } else {
            $roles[] = 'ROLE_EXTERNAL_USER';
        }

        $code = new ExternalUserActivationCode();
        $code->setCode($this->externalUserService->generateExternalUserCode());
        $code->setTenant($user->getActiveTenant());
        // Expire: 2 days
        $code->setCodeExpire((new \DateTime())->add(new \DateInterval('P2D')));
        $code->setUsername($object->displayName);
        $code->setRoles($roles);

        return $code;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return ExternalUserActivationCode::class === $to && ($context['input']['class'] ?? null) === ExternalUserActivationCodeInput::class;
    }
}
