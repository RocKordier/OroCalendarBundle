<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\CalendarBundle\Form\EventListener\CalendarSubscriber;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\CalendarEvent;

class CalendarSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var CalendarSubscriber */
    protected $calendarSubscriber;

    /** @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    protected $registry;

    public function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->calendarSubscriber = new CalendarSubscriber($this->securityFacade, $this->registry);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                FormEvents::PRE_SET_DATA  => 'fillCalendar',
            ],
            $this->calendarSubscriber->getSubscribedEvents()
        );
    }

    public function testFillCalendarIfNewEvent()
    {
        $eventData = new CalendarEvent();
        $defaultCalendar = new Calendar();
        $newCalendar = new Calendar();
        $defaultCalendar->setName('def');
        $newCalendar->setName('test');
        $formData = [];
        $this->securityFacade->expects($this->any())
            ->method('getLoggedUserId')
            ->will($this->returnValue(1));
        $this->securityFacade->expects($this->any())
            ->method('getOrganizationId')
            ->will($this->returnValue(1));

        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($formData));

        $repo = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with('OroCalendarBundle:Calendar')
            ->will($this->returnValue($repo));
        $repo->expects($this->any())
            ->method('findDefaultCalendar')
            ->with(1, 1)
            ->will($this->returnValue($defaultCalendar));

        $event = new FormEvent($form, $eventData);
        $this->calendarSubscriber->fillCalendar($event);
        $this->assertNotNull($event->getData()->getCalendar());
    }

    public function testDoNotFillCalendarIfUpdateEvent()
    {
        $eventData = new CalendarEvent();
        $defaultCalendar = new Calendar();
        $newCalendar = new Calendar();
        $defaultCalendar->setName('def');
        $newCalendar->setName('test');
        $eventData->setId(2);
        $eventData->setCalendar($defaultCalendar);
        $formData = [];
        $this->securityFacade->expects($this->any())
            ->method('getLoggedUserId')
            ->will($this->returnValue(1));
        $this->securityFacade->expects($this->any())
            ->method('getOrganizationId')
            ->will($this->returnValue(1));

        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($formData));

        $repo = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry->expects($this->never())
            ->method('getRepository')
            ->with('OroCalendarBundle:Calendar')
            ->will($this->returnValue($repo));
        $repo->expects($this->any())
            ->method('findDefaultCalendar')
            ->with(1, 1)
            ->will($this->returnValue($newCalendar));

        $event = new FormEvent($form, $eventData);
        $this->calendarSubscriber->fillCalendar($event);
        $this->assertEquals($defaultCalendar, $event->getData()->getCalendar());
    }

    public function testDoNotFillCalendarIfFilledCalendar()
    {
        $eventData = new CalendarEvent();
        $defaultCalendar = new Calendar();
        $newCalendar = new Calendar();
        $defaultCalendar->setName('def');
        $newCalendar->setName('test');
        $eventData->setCalendar($defaultCalendar);
        $formData = [];
        $this->securityFacade->expects($this->any())
            ->method('getLoggedUserId')
            ->will($this->returnValue(1));
        $this->securityFacade->expects($this->any())
            ->method('getOrganizationId')
            ->will($this->returnValue(1));

        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($formData));

        $repo = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry->expects($this->never())
            ->method('getRepository')
            ->with('OroCalendarBundle:Calendar')
            ->will($this->returnValue($repo));
        $repo->expects($this->any())
            ->method('findDefaultCalendar')
            ->with(1, 1)
            ->will($this->returnValue($newCalendar));

        $event = new FormEvent($form, $eventData);
        $this->calendarSubscriber->fillCalendar($event);
        $this->assertEquals($defaultCalendar, $event->getData()->getCalendar());
    }
}
