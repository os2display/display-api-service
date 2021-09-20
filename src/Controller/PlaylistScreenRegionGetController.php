<?php

namespace App\Controller;

use App\Entity\PlaylistScreenRegion;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class PlaylistScreenRegionGetController extends AbstractController
{
    public function __construct()
    {
        $t2 = 1;
    }


    public function __invoke(string $ulid, string $regionUlid)
    {
        $t = 1;
    }
}
