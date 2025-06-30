<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ApiV1RedirectController extends AbstractController
{
    #[Route('/v1/{endpoint}', name: 'app_api_v1_redirect', requirements: ['endpoint' => '.+'], defaults: ['endpoint' => null])]
    public function index(string $endpoint): RedirectResponse
    {
        return $this->redirect('/v2/'.$endpoint, Response::HTTP_PERMANENTLY_REDIRECT);
    }
}
