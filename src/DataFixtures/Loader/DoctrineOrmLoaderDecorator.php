<?php

declare(strict_types=1);

namespace App\DataFixtures\Loader;

use App\EventListener\RelationsChecksumListener;
use App\EventListener\TimestampableListener;
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

        // Disable "RelationsModifiedAtListener" while loading fixtures for performance reasons.
        $postFlushListeners = $eventManager->getListeners('postFlush');
        foreach ($postFlushListeners as $listener) {
            if ($listener instanceof RelationsChecksumListener) {
                $relationsModifiedAtListener = $listener;
                $eventManager->removeEventListener('postFlush', $relationsModifiedAtListener);
                break;
            }
        }

        // Disable "TimestampableListener" while loading fixtures to allow created and modified timestamps to be set
        // as defined in fixtures
        $prePersistListeners = $eventManager->getListeners('prePersist');
        foreach ($prePersistListeners as $listener) {
            if ($listener instanceof TimestampableListener) {
                $timestampableListener = $listener;
                $eventManager->removeEventListener('prePersist', $timestampableListener);
                break;
            }
        }

        // Load fixtures
        $result = $this->decorated->load($application, $manager, $bundles, $environment, $append, $purgeWithTruncate, $noBundles);

        // Apply the SQL statements from the disabled "postFlush" listener
        $this->applyRelationsModified($manager);

        // Re-enable listeners
        $eventManager->addEventListener('postFlush', $relationsModifiedAtListener);
        $eventManager->addEventListener('prePersist', $timestampableListener);

        // Above native SQL statements (applyRelationsModified()) have altered the DB without using the ORM. To ensure the tests
        // reload data from the DB and not stale data the ORM has in memory we need to clear the EntityManager.
        $manager->clear();

        return $result;
    }

    public function withLogger(LoggerInterface $logger): static
    {
        $this->decorated->withLogger($logger);
    }

    private function applyRelationsModified(EntityManagerInterface $manager): void
    {
        $connection = $manager->getConnection();

        $sqlQueries = RelationsChecksumListener::getUpdateRelationsAtQueries(withWhereClause: false);

        $rows = 0;
        foreach ($sqlQueries as $sqlQuery) {
            $stm = $connection->prepare($sqlQuery);
            $rows += $stm->executeStatement();
        }
    }
}
