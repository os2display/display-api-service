<?php

declare(strict_types=1);

namespace App\Entity\Interfaces;

interface UserInterface extends \Symfony\Component\Security\Core\User\UserInterface
{
    public function getBlamableIdentifier(): string;
}
