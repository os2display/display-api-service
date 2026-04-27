<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Profiler\Profiler;

#[AsController]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly ?Profiler $profiler = null,
    ) {}

    public function __invoke(): Response
    {
        $this->profiler?->disable();

        return $this->render('admin/admin.html.twig');
    }
}
