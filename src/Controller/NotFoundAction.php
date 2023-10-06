<?php

namespace App\Controller;

use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsController]
class NotFoundAction
{
    public function __invoke()
    {
        throw new NotFoundHttpException();
    }
}
