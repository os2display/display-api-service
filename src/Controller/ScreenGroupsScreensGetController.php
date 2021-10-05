<?php

namespace App\Controller;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use App\Repository\ScreenGroupRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Uid\Ulid;

#[AsController]
class ScreenGroupsScreensGetController extends AbstractController
{
    public function __construct(
        private ScreenGroupRepository $screenGroupRepository
    ) {
    }

    public function __invoke(Request $request, string $id): Paginator
    {
        if (!Ulid::isValid($id)) {
            throw new InvalidArgumentException();
        }

        $page = (int) $request->query->get('page', '1');
        $itemsPerPage = (int) $request->query->get('itemsPerPage', '10');

        $screenUlid = Ulid::fromString($id);

        return $this->screenGroupRepository->getScreenGroups($screenUlid, $page, $itemsPerPage);
    }
}
