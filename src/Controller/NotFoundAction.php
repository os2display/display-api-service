<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsController]
class NotFoundAction
{
    public function __invoke(): never
    {
        throw new NotFoundHttpException();
    }
}
