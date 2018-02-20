<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\AttendeeRepository;
use Oro\Bundle\CalendarBundle\Validator\Constraints\EventAttendees;
use Oro\Bundle\CalendarBundle\Validator\Constraints\EventAttendeesValidator;
use Oro\Bundle\CalendarBundle\Validator\Constraints\UnchangeableUid;
use Oro\Bundle\CalendarBundle\Validator\Constraints\UnchangeableUidValidator;
use Oro\Component\Testing\Validator\AbstractConstraintValidatorTest;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class EventAttendeesValidatorTest extends AbstractConstraintValidatorTest
{
    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    private $registry;

    /** @var ObjectRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $repository;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    private $manager;

    protected function setUp()
    {
        $this->mockDoctrine();
        parent::setUp();
    }

    /**
     * Means: do not validate for new events (as they have no attendees)
     */
    public function testNoViolationIfEventDoesNotHaveId()
    {
        $constraint = new EventAttendees();
        $calendarEvent = new CalendarEvent();
        $this->validator->validate($calendarEvent, $constraint);
        $this->assertNoViolation();
    }

    public function testNoViolationIfEventIsOrganizer()
    {
        $constraint = new EventAttendees();
        $calendarEvent = new CalendarEvent();
        $calendarEvent->setIsOrganizer(true);
        $this->validator->validate($calendarEvent, $constraint);
        $this->assertNoViolation();
    }

    public function testNoViolationIfAttendeeListIsEqual()
    {
        $attendee1 = new Attendee();
        $attendee2 = new Attendee();
        $constraint = new EventAttendees();
        $calendarEvent = $this->getCalendarEventEntity(1);
        $calendarEvent->addAttendee($attendee1)->addAttendee($attendee2);
        $this->repository->expects($this->once())
            ->method('getAttendeesForCalendarEvent')
            ->willReturn([$attendee1, $attendee2]);
        $this->validator->validate($calendarEvent, $constraint);
        $this->assertNoViolation();
    }

    public function testNoViolationIfAttendeeListIsEqualDespiteAttendeesOrder()
    {
        $attendee1 = $this->getCalendarEventAttendeeEntity(1, "test1@oroinc.com");
        $attendee2 = $this->getCalendarEventAttendeeEntity(2, "test2@oroinc.com");
        $constraint = new EventAttendees();
        $calendarEvent = $this->getCalendarEventEntity(1);
        $calendarEvent->addAttendee($attendee1)->addAttendee($attendee2);
        $this->repository->expects($this->once())
            ->method('getAttendeesForCalendarEvent')
            ->willReturn([$attendee2, $attendee1]);
        $this->validator->validate($calendarEvent, $constraint);
        $this->assertNoViolation();
    }

    public function testNoViolationWhenTryingToChangeAttendeesOnOrganizerEvent()
    {
        $attendee1 = $this->getCalendarEventAttendeeEntity(1, "test1@oroinc.com");
        $attendee2 = $this->getCalendarEventAttendeeEntity(2, "test2@oroinc.com");
        $constraint = new EventAttendees();
        $calendarEvent = $this->getCalendarEventEntity(1);
        $calendarEvent->setIsOrganizer(true);
        $calendarEvent->addAttendee($attendee1);
        $this->repository->expects($this->once())
            ->method('getAttendeesForCalendarEvent')
            ->willReturn([$attendee2, $attendee1]);
        $this->validator->validate($calendarEvent, $constraint);
        $this->assertNoViolation();
    }

    public function testViolationWhenTryingToChangeAttendeesOnNonOrganizerEvent()
    {
        $attendee1 = $this->getCalendarEventAttendeeEntity(1, "test1@oroinc.com");
        $attendee2 = $this->getCalendarEventAttendeeEntity(2, "test2@oroinc.com");
        $constraint = new EventAttendees();
        $calendarEvent = $this->getCalendarEventEntity(1);
        $calendarEvent->setIsOrganizer(false);
        $calendarEvent->addAttendee($attendee1);
        $this->repository->expects($this->once())
            ->method('getAttendeesForCalendarEvent')
            ->willReturn([$attendee2, $attendee1]);
        $this->validator->validate($calendarEvent, $constraint);
        $this->buildViolation($constraint->message)
            ->atPath('property.path.attendees')
            ->assertRaised();
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidator()
    {
        return new EventAttendeesValidator($this->registry);
    }

    private function mockDoctrine()
    {
        $this->manager = $this->createMock(ObjectManager::class);
        $this->repository = $this->createMock(AttendeeRepository::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->manager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->manager);
    }

    /**
     * @param int $id
     * @return CalendarEvent
     */
    private function getCalendarEventEntity(int $id): CalendarEvent
    {
        $calendarEvent = new CalendarEvent();
        $reflectionClass = new \ReflectionClass(CalendarEvent::class);
        $reflectionProperty = $reflectionClass->getProperty('id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($calendarEvent, $id);

        return $calendarEvent;
    }

    /**
     * @param int $id
     * @param string $email
     *
     * @return Attendee
     */
    private function getCalendarEventAttendeeEntity(int $id, string $email): Attendee
    {
        $attendee = new Attendee();
        $reflectionClass = new \ReflectionClass(Attendee::class);
        $reflectionProperty = $reflectionClass->getProperty('id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($attendee, $id);
        $attendee->setEmail($email);

        return $attendee;
    }
}
