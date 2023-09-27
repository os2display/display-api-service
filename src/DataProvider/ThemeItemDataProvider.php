<?php

namespace App\DataProvider;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Tenant\Theme;
use App\Entity\User;
use App\Exceptions\ItemDataProviderException;
use App\Repository\SlideRepository;
use App\Repository\ThemeRepository;
use App\Utils\ValidationUtils;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Ulid;

final class ThemeItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    public function __construct(
        private Security $security,
        private SlideRepository $slideRepository,
        private ThemeRepository $themeRepository,
        private ValidationUtils $validationUtils,
        private iterable $itemExtensions = []
    ) {}

    public function supports(string $resourceClass, string $operationName = null, array $context = []): bool
    {
        return Theme::class === $resourceClass;
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Theme
    {
        $user = $this->security->getUser();
        if (is_null($user)) {
            return null;
        }

        $queryNameGenerator = new QueryNameGenerator();

        /** @var User $user */
        $tenant = $user->getActiveTenant();

        if (!$id instanceof Ulid) {
            throw new ItemDataProviderException('Id should be of a Ulid');
        }

        $themeUlid = $this->validationUtils->validateUlid($id->jsonSerialize());

        // Create a query-builder, as the tenant filter works on query-builders.
        $queryBuilder = $this->themeRepository->getById($themeUlid);

        // Filter the query-builder with tenant extension.
        foreach ($this->itemExtensions as $extension) {
            $identifiers = ['id' => $id];
            $extension->applyToItem($queryBuilder, $queryNameGenerator, $resourceClass, $identifiers, $operationName, $context);
        }

        // Get result. If there is a result this is returned.
        try {
            $theme = $queryBuilder->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            $theme = null;
        }

        // If there is not a result, shared playlists should be checked.
        if (is_null($theme)) {
            $connectedSlides = $this->slideRepository->getSlidesByTheme($themeUlid)->getQuery()->getResult();
            foreach ($connectedSlides as $slide) {
                if (in_array($tenant, $slide->getSlide()->getTenants()->toArray())) {
                    $theme = $this->themeRepository->find($themeUlid);
                    break;
                }
            }
        }

        return $theme;
    }
}
