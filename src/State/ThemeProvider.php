<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Theme as ThemeDTO;
use App\Entity\Tenant\Theme;
use App\Entity\User;
use App\Exceptions\ItemDataProviderException;
use App\Repository\SlideRepository;
use App\Repository\ThemeRepository;
use App\Utils\ValidationUtils;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Ulid;

/**
 * A Theme state provider.
 *
 * @see https://api-platform.com/docs/v2.7/core/state-providers/
 *
 * @template T of Theme
 */
final class ThemeProvider extends AbstractProvider
{
    public function __construct(
        private readonly Security $security,
        private readonly SlideRepository $slideRepository,
        private readonly ThemeRepository $themeRepository,
        private readonly MediaProvider $mediaProvider,
        private readonly ValidationUtils $validationUtils,
        private readonly iterable $itemExtensions,
        ProviderInterface $collectionProvider
    ) {
        parent::__construct($collectionProvider, $this->themeRepository);
    }

    public function toOutput(object $object): ThemeDTO
    {
        assert($object instanceof Theme);

        $output = new ThemeDTO();
        $output->id = $object->getId();
        $output->title = $object->getTitle();
        $output->description = $object->getDescription();
        $output->created = $object->getCreatedAt();
        $output->modified = $object->getModifiedAt();
        $output->createdBy = $object->getCreatedBy();
        $output->modifiedBy = $object->getModifiedBy();

        $output->logo = $this->mediaProvider->toOutput($object->getLogo());
        $output->cssStyles = $object->getCssStyles();

        return $output;
    }

    protected function provideItem(Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        $user = $this->security->getUser();
        if (is_null($user)) {
            return null;
        }

        $queryNameGenerator = new QueryNameGenerator();
        $resourceClass = Theme::class;

        /** @var User $user */
        $tenant = $user->getActiveTenant();

        $id = $uriVariables['id'] ?? null;
        if (!$id instanceof Ulid) {
            throw new ItemDataProviderException('Id should be of a Ulid');
        }

        $themeUlid = $this->validationUtils->validateUlid($id->jsonSerialize());

        // Create a query-builder, as the tenant filter works on query-builders.
        $queryBuilder = $this->themeRepository->getById($themeUlid);

        // Filter the query-builder with tenant extension.
        foreach ($this->itemExtensions as $extension) {
            if ($extension instanceof QueryItemExtensionInterface) {
                $identifiers = ['id' => $id];
                $extension->applyToItem($queryBuilder, $queryNameGenerator, $resourceClass, $identifiers, $operation, $context);
            }
        }

        // Get result. If there is a result this is returned.
        try {
            $theme = $queryBuilder->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException) {
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
