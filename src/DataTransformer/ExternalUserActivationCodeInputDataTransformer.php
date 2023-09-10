<?php

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\Dto\ExternalUserActivationCodeInput;
use App\Dto\Template as TemplateDTO;
use App\Dto\ExternalUserOutput;
use App\Entity\ExternalUserActivationCode;
use App\Entity\Template;
use App\Entity\User;
use App\Exceptions\CodeGenerationException;
use App\Service\ExternalUserService;
use Symfony\Component\Security\Core\Security;

class ExternalUserActivationCodeInputDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly ExternalUserService $externalUserService,
    )
    {
    }

    /**
     * {@inheritdoc}
     *
     * @throws CodeGenerationException
     * @var ExternalUserActivationCodeInput $object
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

        return new ExternalUserActivationCode(
            $user->getActiveTenant(),
            $this->externalUserService->generateExternalUserCode(),
            // Expire: 2 days
            (new \DateTime())->add(new \DateInterval('P2D')),
            $object->displayName,
            $roles,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return ExternalUserActivationCode::class === $to && ($context["input"]["class"] ?? null) === ExternalUserActivationCodeInput::class;
    }
}
