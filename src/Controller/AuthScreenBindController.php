<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exceptions\AuthScreenBindException;
use App\Repository\ScreenRepository;
use App\Security\ScreenAuthenticator;
use App\Utils\ValidationUtils;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class AuthScreenBindController extends AbstractController
{
    public function __construct(
        private ScreenAuthenticator $authScreenService,
        private ValidationUtils $validationUtils,
        private ScreenRepository $screenRepository
    ) {}

    public function __invoke(Request $request, string $id): Response
    {
        $screenUlid = $this->validationUtils->validateUlid($id);
        $screen = $this->screenRepository->find($screenUlid);

        if (null === $screen) {
            throw new AuthScreenBindException(sprintf('Could not find screen with id: %s', $id), Response::HTTP_BAD_REQUEST);
        }

        $body = $request->toArray();
        $bindKey = $body['bindKey'];

        if (!isset($bindKey)) {
            throw new AuthScreenBindException('Missing key', Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->authScreenService->bindScreen($screen, $bindKey);
        } catch (\Exception|InvalidArgumentException $exception) {
            return new JsonResponse('Key not accepted', Response::HTTP_BAD_REQUEST);
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
