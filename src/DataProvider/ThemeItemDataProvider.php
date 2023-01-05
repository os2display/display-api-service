<?php

namespace App\DataProvider;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Tenant\Theme;
use App\Repository\SlideRepository;
use App\Repository\ThemeRepository;
use App\Utils\ValidationUtils;
use Symfony\Component\Security\Core\Security;

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
        $queryNameGenerator = new QueryNameGenerator();
        $user = $this->security->getUser();
        $tenant = $user->getActiveTenant();
        $themeUlid = $this->validationUtils->validateUlid($id);

        // Create a query-builder, as the tenant filter works on query-builders.
        $queryBuilder = $this->themeRepository->getById($themeUlid);

        // Filter the query-builder with tenant extension
        foreach ($this->itemExtensions as $extension) {
            $identifiers = ['id' => $id];
            $extension->applyToItem($queryBuilder, $queryNameGenerator, $resourceClass, $identifiers, $operationName, $context);
            if ($extension instanceof QueryResultItemExtensionInterface && $extension->supportsResult($resourceClass, $operationName, $context)) {
                return $extension->getResult($queryBuilder, $resourceClass, $operationName, $context);
            }
        }

        // Get result. If there is a result this is returned.
        $theme = $queryBuilder->getQuery()->getOneOrNullResult();

        // If there is not a result, shared playlists should be checked.
        if (is_null($theme)) {
            $connectedSlides = $this->slideRepository->getSlidesByTheme($id)->getQuery()->getResult();
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
