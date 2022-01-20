<?php

namespace App\Controller;

use App\Entity\Media;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsController]
class AuthScreenController extends AbstractController
{
    public function __invoke(Request $request): JsonResponse
    {
        $bodyValues = $request->toArray();

        if (!isset($bodyValues['nonce'])) {
            throw new \Exception("No nonce");
        }

        // 0.  Hash nonce.
        // 1.  Hash entry does not exist. Create entry with magic code and return in response, remember cache expire.
        // 2.  Hash entry exists:
        // 2.1 Hash entry contains screen and token. Deliver success.
        // 2.2 Hash entry only contains magic code. Deliver pending.

        return new JsonResponse([
            'test' => 'toast',
        ]);
    }
}
