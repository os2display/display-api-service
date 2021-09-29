<?php

namespace App\Controller;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Entity\Slide;
use App\Repository\PlaylistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Uid\Ulid;

#[AsController]
class SlidePlaylistGetController extends AbstractController
{
    public function __construct(
        private PlaylistRepository $playlistRepository
    ) {
    }

    public function __invoke(Request $request, string $id): Paginator
    {
        if (!Ulid::isValid($id)) {
            throw new InvalidArgumentException();
        }

        $page = (int) $request->query->get('page', '1');
        $itemsPerPage = (int) $request->query->get('itemsPerPage', '10');

        $playListUlidObj = Ulid::fromString($id);

        return $this->playlistRepository->getPaginator(Slide::class, $playListUlidObj, $page, $itemsPerPage);
    }
}
