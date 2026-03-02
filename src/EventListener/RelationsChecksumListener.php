<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Interfaces\RelationsChecksumInterface;
use App\Entity\ScreenLayout;
use App\Entity\ScreenLayoutRegions;
use App\Entity\Tenant\Feed;
use App\Entity\Tenant\Media;
use App\Entity\Tenant\Playlist;
use App\Entity\Tenant\PlaylistScreenRegion;
use App\Entity\Tenant\PlaylistSlide;
use App\Entity\Tenant\Schedule;
use App\Entity\Tenant\Screen;
use App\Entity\Tenant\ScreenCampaign;
use App\Entity\Tenant\ScreenGroup;
use App\Entity\Tenant\ScreenGroupCampaign;
use App\Entity\Tenant\Slide;
use App\Service\RelationsChecksumCalculator;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

/**
 * Listener class for updating relationsModified field in the database.
 *
 * The purpose of this class is to propagate checksums for the last update to child nodes in the
 * entity relationship tree from the bottom up the tree updating the "relationsModified" fields.
 *
 * "relationsModified" contains a json object with a checksum for the for any relation the entity has.
 * This must be a checksum of "id", "version" and "relations_modified to ensure the checksum changes.
 * This json object exposed in the API to allow the clients to know when it's necessary to fetch updated
 * to relations.
 *
 * Example (Screen):
 *  "relationsModified": {
 *      "campaigns": "cf9bb7d5fd04743dd21b5e3361db7eed575258e0",
 *      "layout": "4dc925b9043b9d151607328ab2d022610583777f",
 *      "regions": "278df93a0dc5309e0db357177352072d86da0d29",
 *      "inScreenGroups": "bf0d49f6af71ac74da140e32243f3950219bb29c"
 *  }
 *
 * For efficacy this is implemented in raw SQL
 *  - we don't want to load all relations in the doctrine layer to avoid the performance hit and
 *    high memory footprint
 *  - we don't use doctrines (DBAL's) query builder because we depend on SQL functions like GROUP_CONCAT()
 *    and CAST() that are not supported by the query builder.
 */
#[AsDoctrineListener(event: Events::prePersist, priority: 100)]
#[AsDoctrineListener(event: Events::preUpdate)]
#[AsDoctrineListener(event: Events::preRemove)]
#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
class RelationsChecksumListener
{
    public function __construct(
        private readonly RelationsChecksumCalculator $calculator,
    ) {}

    /**
     * PrePersist listener.
     *
     * This will set the initial json object for the relationsModified field.
     * All checksums are set to null because the correct value will be set
     * in the postFlush handler. But we must set proper keys for the "JSON_SET"
     * function to work. If the field is left as null it will be serialized to the
     * database as `[]` preventing JSON_SET from updating the field.
     *
     * @param PrePersistEventArgs $args
     *
     * @return void
     */
    final public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        switch ($entity::class) {
            case Feed::class:
                $modifiedAt = [
                    'feedSource' => null,
                    'slide' => null,
                ];
                $entity->setRelationsChecksum($modifiedAt);
                break;
            case Slide::class:
                $modifiedAt = [
                    'templateInfo' => null,
                    'theme' => null,
                    'media' => null,
                    'feed' => null,
                ];
                $entity->setRelationsChecksum($modifiedAt);
                break;
            case PlaylistSlide::class:
                $modifiedAt = [
                    'slide' => null,
                ];
                $entity->setRelationsChecksum($modifiedAt);
                break;
            case Playlist::class:
                $modifiedAt = [
                    'slides' => null,
                ];
                $entity->setRelationsChecksum($modifiedAt);
                break;
            case ScreenCampaign::class:
                $modifiedAt = [
                    'campaign' => null,
                    'screen' => null,
                ];
                $entity->setRelationsChecksum($modifiedAt);
                break;
            case ScreenGroupCampaign::class:
                $modifiedAt = [
                    'campaign' => null,
                    'screenGroup' => null,
                ];
                $entity->setRelationsChecksum($modifiedAt);
                break;
            case ScreenGroup::class:
                $modifiedAt = [
                    'screenGroupCampaigns' => null,
                    'screens' => null,
                ];
                $entity->setRelationsChecksum($modifiedAt);
                break;
            case PlaylistScreenRegion::class:
                $modifiedAt = [
                    'playlist' => null,
                ];
                $entity->setRelationsChecksum($modifiedAt);
                break;
            case ScreenLayoutRegions::class:
                $modifiedAt = [];
                $entity->setRelationsChecksum($modifiedAt);
                break;
            case ScreenLayout::class:
                $modifiedAt = [
                    'regions' => null,
                ];
                $entity->setRelationsChecksum($modifiedAt);
                break;
            case Screen::class:
                $modifiedAt = [
                    'campaigns' => null,
                    'layout' => null,
                    'regions' => null,
                    'inScreenGroups' => null,
                ];
                $entity->setRelationsChecksum($modifiedAt);
                break;
        }
    }

    /**
     * PreUpdate listener.
     *
     * On update set "changed" to "true" to ensure checksum changes propagate up the tree.
     *
     * @param PreUpdateEventArgs $args
     *
     * @return void
     */
    final public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof RelationsChecksumInterface) {
            $entity->setChanged(true);
        }
    }

    /**
     * PreRemove listener.
     *
     * For "toMany" relations the "preUpdate" listener will not be called for the parent if child relations
     * are deleted by calling remove() on the entity manager. We need to manually set "changed" on the parent
     * to "true" to ensure checksum changes propagate up the tree.
     *
     * @param PreRemoveEventArgs $args
     *
     * @return void
     */
    final public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        switch ($entity::class) {
            case ScreenLayoutRegions::class:
                $entity->getScreenLayout()?->setChanged(true);
                break;
            case ScreenGroup::class:
                foreach ($entity->getScreens() as $screen) {
                    $screen->setChanged(true);
                }
                break;
            case ScreenCampaign::class:
                $entity->getScreen()->setChanged(true);
                break;
            case PlaylistScreenRegion::class:
                $entity->getScreen()?->setChanged(true);
                break;
            case ScreenGroupCampaign::class:
                $entity->getScreenGroup()->setChanged(true);
                break;
            case Schedule::class:
                $entity->getPlaylist()?->setChanged(true);
                break;
            case PlaylistSlide::class:
                $entity->getPlaylist()->setChanged(true);
                break;
            case Media::class:
                foreach ($entity->getSlides() as $slide) {
                    $slide->setChanged(true);
                }
                break;
        }
    }

    /**
     * OnFlush listener.
     *
     * This listener is used to set the "changed" flag on entities that have been modified since the last flush.
     * This is required because Doctrine does not call the "preUpdate" listener for entities that only have
     * collection changes.
     *
     * @param OnFlushEventArgs $args
     *
     * @return void
     */
    final public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        // Catch ManyToMany collection adds/removes
        foreach ($uow->getScheduledCollectionUpdates() as $collection) {
            $owner = $collection->getOwner();
            if ($owner instanceof RelationsChecksumInterface) {
                $owner->setChanged(true);
                $uow->recomputeSingleEntityChangeSet(
                    $em->getClassMetadata($owner::class),
                    $owner
                );
            }
        }

        foreach ($uow->getScheduledCollectionDeletions() as $collection) {
            $owner = $collection->getOwner();
            if ($owner instanceof RelationsChecksumInterface) {
                $owner->setChanged(true);
                $uow->recomputeSingleEntityChangeSet(
                    $em->getClassMetadata($owner::class),
                    $owner
                );
            }
        }
    }

    /**
     * PostFlush listener.
     *
     * Executes update SQL queries to set changed and relations_checksum fields in the database.
     *
     * @param PostFlushEventArgs $args the PostFlushEventArgs object containing information about the event
     *
     * @return void
     *
     * @throws \Doctrine\DBAL\Exception
     */
    final public function postFlush(PostFlushEventArgs $args): void
    {
        $this->calculator->execute(withWhereClause: true);
    }
}
