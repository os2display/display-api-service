<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class ApiV1RedirectController extends AbstractController
{
    #[Route('/v1/{endpoint}', name: 'app_api_v1_redirect', requirements: ['endpoint' => '.+'], defaults: ['endpoint' => null], methods: ['GET'])]
    public function index(string $endpoint): RedirectResponse
    {
        return $this->redirect('/v2/'.$endpoint, \Symfony\Component\HttpFoundation\Response::HTTP_MOVED_PERMANENTLY);
    }
}
