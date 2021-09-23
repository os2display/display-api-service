<?php

namespace App\Controller;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Repository\SlideRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Uid\Ulid;

#[AsController]
class PlaylistSlideGetController extends AbstractController
{
    private SlideRepository $slideRepository;

    public function __construct(SlideRepository $slideRepository)
    {
        $this->slideRepository = $slideRepository;
    }

    public function __invoke(Request $request, string $id): Paginator
    {
        if (!Ulid::isValid($id)) {
            throw new InvalidArgumentException();
        }

        $page = (int) $request->query->get('page', '1');
        $itemsPerPage = (int) $request->query->get('itemsPerPage', '10');

        $slideUlidObj = Ulid::fromString($id);

        return $this->slideRepository->getPaginator('App\Entity\PLaylist', $slideUlidObj, $page, $itemsPerPage);
    }
}
