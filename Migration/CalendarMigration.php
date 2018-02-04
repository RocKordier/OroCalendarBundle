<?php

namespace Oro\Bundle\CalendarBundle\Migration;

use Doctrine\DBAL\Schema\Schema;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarProperty;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CalendarMigration implements Migration
{
    /** @var RegistryInterface */
    private $registry;

    /** @var EntityManager $entityManager */
    private $entityManager;

    /** @var Calendar[] */
    protected $insertedCalendars = [];

    public function __construct(RegistryInterface $registry) {
        $this->registry = $registry;
        $this->entityManager = $this->registry->getEntityManager();
    }

    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addCalendards();
        $this->addCalendarProperties();
    }

    private function addCalendards()
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->registry->getRepository(User::class);
        /** @var CalendarRepository $calendarRepository */
        $calendarRepository = $this->registry->getRepository(Calendar::class);

        /** @var User $user */
        foreach ($userRepository->findAll() as $user) {
            if($calendarRepository->findDefaultCalendar($user->getId(), $user->getOrganization()->getId())) {
                continue;
            }

            $newCalendar = new Calendar();
            $newCalendar
                ->setOrganization($user->getOrganization())
                ->setOwner($user);
            $this->entityManager->persist($newCalendar);

            $this->insertedCalendars[] = $newCalendar;
        }
        $this->entityManager->flush();
    }

    private function addCalendarProperties()
    {
        foreach ($this->insertedCalendars as $calendar) {
            $calendarProperty = new CalendarProperty();
            $calendarProperty
                ->setTargetCalendar($calendar)
                ->setCalendarAlias(Calendar::CALENDAR_ALIAS)
                ->setCalendar($calendar->getId());
            $this->entityManager->persist($calendarProperty);
        }
        $this->entityManager->flush();
    }
}
