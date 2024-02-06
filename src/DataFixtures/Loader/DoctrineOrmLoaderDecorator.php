<?php

namespace App\DataFixtures\Loader;

use App\EventListener\RelationsModifiedAtListener;
use Doctrine\DBAL\ParameterType;
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
    private const SECONDS_PR_YEAR = 31556926;

    public function __construct(private readonly DoctrineOrmLoader $decorated)
    {
    }

    public function load(Application $application, EntityManagerInterface $manager, array $bundles, string $environment, bool $append, bool $purgeWithTruncate, bool $noBundles = false): array
    {
        $eventManager = $manager->getEventManager();

        // Disable "postFlush" listener while loading features for performance reasons.
        $listeners = $eventManager->getListeners('postFlush');
        foreach ($listeners as $listener) {
            if ($listener instanceof RelationsModifiedAtListener) {
                $eventManager->removeEventListener('postFlush', $listener);
            }
        }

        $result = $this->decorated->load($application, $manager, $bundles, $environment, $append, $purgeWithTruncate, $noBundles);

        // Set random value for 'modifiedAt'. Entity design has no setter for 'modifiedAt' so we can't set it in fixtures.
        $classes = $manager->getMetadataFactory()->getAllMetadata();
        foreach ($classes as $class) {
            $fields = $class->reflFields;
            if (!$class->isMappedSuperclass && array_key_exists('createdAt', $fields) && array_key_exists('modifiedAt', $fields)) {
                $sql = \sprintf('UPDATE %s SET modified_at = LEAST(NOW(), DATE_ADD(created_at, INTERVAL FLOOR( RAND() * :seconds_pr_year) SECOND))', $class->table['name']);
                $stm = $manager->getConnection()->prepare($sql);
                $stm->bindValue('seconds_pr_year', self::SECONDS_PR_YEAR, ParameterType::INTEGER);

                $stm->executeStatement();
            }
        }

        // Apply the SQL statements from the disabled "postFlush" listener
        $this->applyRelationsModified($manager);

        // Above native SQL statements have altered the DB without using the ORM. To ensure the tests
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

        $sqlQueries = RelationsModifiedAtListener::getUpdateRelationsAtQueries(withWhereClause: false);

        $rows = 0;
        foreach ($sqlQueries as $sqlQuery) {
            $stm = $connection->prepare($sqlQuery);
            $rows += $stm->executeStatement();
        }
    }
}
