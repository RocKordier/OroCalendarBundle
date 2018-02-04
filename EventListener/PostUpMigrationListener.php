<?php

namespace Oro\Bundle\CalendarBundle\EventListener;

use Oro\Bundle\CalendarBundle\Migration\CalendarMigration;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PostUpMigrationListener
{
    /** @var RegistryInterface */
    private $registry;

    public function __construct(RegistryInterface $registry) {
        $this->registry = $registry;
    }

    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(
            new CalendarMigration($this->registry)
        );
    }
}
