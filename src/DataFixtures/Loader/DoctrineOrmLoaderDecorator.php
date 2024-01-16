<?php

declare(strict_types=1);

namespace App\DataFixtures\Loader;

use App\EventListener\RelationsModifiedAtListener;
use Doctrine\ORM\EntityManagerInterface;
use Hautelook\AliceBundle\Loader\DoctrineOrmLoader;
use Hautelook\AliceBundle\LoaderInterface as AliceBundleLoaderInterface;
use Hautelook\AliceBundle\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

/**
 * Decorator class for DoctrineOrmLoader.
 *
 * Disable the "postFlush" RelationsModifiedAtListener before loading fixtures to optimize performance. Then run the
 * RelationsModified update queries one time when all fixtures are loaded.
 *
 * @implements AliceBundleLoaderInterface
 * @implements LoggerAwareInterface
 */
#[AsDecorator(decorates: 'hautelook_alice.loader')]
class DoctrineOrmLoaderDecorator implements AliceBundleLoaderInterface, LoggerAwareInterface
{
    public function __construct(
        private readonly DoctrineOrmLoader $decorated
    ) {}

    public function load(Application $application, EntityManagerInterface $manager, array $bundles, string $environment, bool $append, bool $purgeWithTruncate, bool $noBundles = false): array
    {
        $eventManager = $manager->getEventManager();

        $listeners = $eventManager->getListeners('postFlush');
        foreach ($listeners as $listener) {
            if ($listener instanceof RelationsModifiedAtListener) {
                $eventManager->removeEventListener('postFlush', $listener);
            }
        }

        $result = $this->decorated->load($application, $manager, $bundles, $environment, $append, $purgeWithTruncate, $noBundles);

        $this->applyRelationsModified($manager);

        return $result;
    }

    public function withLogger(LoggerInterface $logger)
    {
        $this->decorated->withLogger($logger);
    }

    private function applyRelationsModified(EntityManagerInterface $manager): void
    {
        $connection = $manager->getConnection();

        $sqlQueries = RelationsModifiedAtListener::getUpdateRelationsAtQueries(withWhereClause: false);

        $rows = 0;
        foreach ($sqlQueries as $sqlQuery) {
            $stm = $connection->prepare($sqlQuery);
            $rows += $stm->executeStatement();
        }
    }
}
