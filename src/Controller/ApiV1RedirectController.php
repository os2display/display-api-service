<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ApiV1RedirectController extends AbstractController
{
    #[Route('/v1/{endpoint}', name: 'app_api_v1_redirect_get', requirements: ['endpoint' => '.+'], defaults: ['endpoint' => null], methods: ['GET'])]
    public function redirectGets(string $endpoint): RedirectResponse
    {
        return $this->redirect('/v2/'.$endpoint, Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/v1/{endpoint}', name: 'app_api_v1_redirect_post', requirements: ['endpoint' => '.+'], defaults: ['endpoint' => null], methods: ['POST'])]
    public function redirectPosts(string $endpoint): RedirectResponse
    {
        return $this->redirect('/v2/'.$endpoint, Response::HTTP_TEMPORARY_REDIRECT);
    }
}
