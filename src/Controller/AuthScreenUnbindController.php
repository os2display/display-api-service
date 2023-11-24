<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exceptions\AuthScreenUnbindException;
use App\Repository\ScreenRepository;
use App\Security\ScreenAuthenticator;
use App\Utils\ValidationUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class AuthScreenUnbindController extends AbstractController
{
    public function __construct(
        private readonly ScreenAuthenticator $authScreenService,
        private readonly ValidationUtils $validationUtils,
        private readonly ScreenRepository $screenRepository
    ) {}

    /**
     * @throws AuthScreenUnbindException
     * @throws \Exception
     */
    public function __invoke(string $id): Response
    {
        $screenUlid = $this->validationUtils->validateUlid($id);
        $screen = $this->screenRepository->find($screenUlid);

        if (null === $screen) {
            throw new AuthScreenUnbindException(sprintf('Could not find screen with id: %s', $id), Response::HTTP_BAD_REQUEST);
        }

        $this->authScreenService->unbindScreen($screen);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
