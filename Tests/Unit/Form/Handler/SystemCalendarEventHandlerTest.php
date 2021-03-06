<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\Handler\SystemCalendarEventHandler;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class SystemCalendarEventHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var RequestStack */
    protected $requestStack;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $objectManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $tokenAccessor;

    /** @var SystemCalendarEventHandler */
    protected $handler;

    /** @var CalendarEvent */
    protected $entity;

    /** @var Organization */
    protected $organization;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $activityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|CalendarEventManager */
    protected $calendarEventManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $notificationManager;

    protected function setUp()
    {
        $this->form = $this->createMock('Symfony\Component\Form\Form');
        $this->request = new Request();
        $this->requestStack = new RequestStack();
        $this->requestStack->push($this->request);

        $this->objectManager = $this->createMock('Doctrine\Common\Persistence\ObjectManager');

        $doctrine = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');

        $doctrine->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($this->objectManager));

        $this->organization = new Organization();
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->tokenAccessor->expects($this->any())
            ->method('getOrganization')
            ->willReturn($this->organization);

        $this->activityManager     = $this->getMockBuilder(ActivityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->calendarEventManager = $this
            ->getMockBuilder(CalendarEventManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->notificationManager = $this
            ->getMockBuilder(NotificationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entity  = new CalendarEvent();
        $this->handler = new SystemCalendarEventHandler(
            $this->requestStack,
            $doctrine,
            $this->tokenAccessor,
            $this->activityManager,
            $this->calendarEventManager,
            $this->notificationManager
        );
        $this->handler->setForm($this->form);
    }

    public function testProcessWithContexts()
    {
        $context = new User();
        ReflectionUtil::setId($context, 123);

        $owner = new User();
        ReflectionUtil::setId($owner, 321);

        $this->request->setMethod('POST');
        $defaultCalendar = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Calendar')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entity->setCalendar($defaultCalendar);

        $this->form->expects($this->any())
            ->method('get')
            ->will($this->returnValue($this->form));

        $this->form->expects($this->once())
            ->method('has')
            ->with('contexts')
            ->will($this->returnValue(true));

        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $defaultCalendar->expects($this->once())
            ->method('getOwner')
            ->will($this->returnValue($owner));

        $this->form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue([$context]));

        $this->activityManager->expects($this->once())
            ->method('setActivityTargets')
            ->with(
                $this->identicalTo($this->entity),
                $this->identicalTo([$context, $owner])
            );

        $this->activityManager->expects($this->never())
            ->method('removeActivityTarget');
        $this->handler->process($this->entity);

        $this->assertSame($defaultCalendar, $this->entity->getCalendar());
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessInvalidData($method)
    {
        $this->request->setMethod($method);

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->identicalTo($this->entity));
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->identicalTo($this->request));
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(false));
        $this->calendarEventManager->expects($this->never())
            ->method($this->anything());
        $this->objectManager->expects($this->never())
            ->method($this->anything());

        $this->assertFalse(
            $this->handler->process($this->entity)
        );
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessValidData($method)
    {
        $this->request->setMethod($method);

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->identicalTo($this->entity));
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->identicalTo($this->request));
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));
        $this->calendarEventManager->expects($this->once())
            ->method('onEventUpdate')
            ->with($this->entity, clone $this->entity, $this->organization, true);
        $this->objectManager->expects($this->once())
            ->method('persist');
        $this->objectManager->expects($this->once())
            ->method('flush');

        $this->assertTrue(
            $this->handler->process($this->entity)
        );
    }

    public function supportedMethods()
    {
        return [
            ['POST'],
            ['PUT']
        ];
    }
}
