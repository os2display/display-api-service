<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Profiler\Profiler;

#[AsController]
class TemplateController extends AbstractController
{
    public function __construct(
    ) {}

    public function __invoke(?Profiler $profiler): Response
    {
        $profiler->disable();
        return $this->render('template.html.twig');
    }
}
