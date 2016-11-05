<?php

namespace Oro\Bundle\CalendarBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CalendarBundle\Exception\NotUserCalendarEvent;
use Oro\Bundle\CalendarBundle\Model\ExtendCalendarEvent;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\ReminderBundle\Entity\RemindableInterface;
use Oro\Bundle\ReminderBundle\Model\ReminderData;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository")
 * @ORM\Table(
 *      name="oro_calendar_event",
 *      indexes={
 *          @ORM\Index(name="oro_calendar_event_idx", columns={"calendar_id", "start_at", "end_at"}),
 *          @ORM\Index(name="oro_sys_calendar_event_idx", columns={"system_calendar_id", "start_at", "end_at"}),
 *          @ORM\Index(name="oro_calendar_event_up_idx", columns={"updated_at"}),
 *          @ORM\Index(name="oro_calendar_event_osa_idx", columns={"original_start_at"})
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      routeName="oro_calendar_view_default",
 *      routeView="oro_calendar_event_view",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-time"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="",
 *              "category"="account_management"
 *          },
 *          "grouping"={
 *              "groups"={"activity"}
 *          },
 *          "reminder"={
 *              "reminder_template_name"="calendar_reminder",
 *              "reminder_flash_template_identifier"="calendar_event_template"
 *          },
 *          "note"={
 *              "immutable"=true
 *          },
 *          "activity"={
 *              "route"="oro_calendar_event_activity_view",
 *              "acl"="oro_calendar_view",
 *              "action_button_widget"="oro_add_calendar_event_button",
 *              "action_link_widget"="oro_add_calendar_event_link"
 *          },
 *          "attachment"={
 *              "immutable"=true
 *          },
 *          "grid"={
 *              "default"="calendar-event-grid",
 *              "context"="calendar-event-for-context-grid"
 *          }
 *      }
 * )
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class CalendarEvent extends ExtendCalendarEvent implements RemindableInterface, DatesAwareInterface
{
    use DatesAwareTrait;

    /** @deprecated since 1.10 use constant with STATUS_ prefix */
    const NOT_RESPONDED        = self::STATUS_NONE;
    /** @deprecated since 1.10 use constant with STATUS_ prefix */
    const TENTATIVELY_ACCEPTED = self::STATUS_TENTATIVE;
    /** @deprecated since 1.10 use constant with STATUS_ prefix */
    const ACCEPTED             = self::STATUS_ACCEPTED;
    /** @deprecated since 1.10 use constant with STATUS_ prefix */
    const DECLINED             = self::STATUS_DECLINED;

    const STATUS_NONE      = 'none';
    const STATUS_TENTATIVE = 'tentative';
    const STATUS_ACCEPTED  = 'accepted';
    const STATUS_DECLINED  = 'declined';

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="CalendarEvent", mappedBy="parent", orphanRemoval=true, cascade={"all"})
     */
    protected $childEvents;

    /**
     * @var CalendarEvent
     *
     * @ORM\ManyToOne(targetEntity="CalendarEvent", inversedBy="childEvents", fetch="EAGER")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $parent;

    /**
     * @var Calendar
     *
     * @ORM\ManyToOne(targetEntity="Calendar", inversedBy="events")
     * @ORM\JoinColumn(name="calendar_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $calendar;

    /**
     * @var SystemCalendar
     *
     * @ORM\ManyToOne(targetEntity="SystemCalendar", inversedBy="events")
     * @ORM\JoinColumn(name="system_calendar_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $systemCalendar;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $description;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $start;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $end;

    /**
     * @var bool
     *
     * @ORM\Column(name="all_day", type="boolean")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $allDay;

    /**
     * @var string|null
     *
     * @ORM\Column(name="background_color", type="string", length=7, nullable=true)
     */
    protected $backgroundColor;

    /**
     * @var Collection
     */
    protected $reminders;

    /**
     * Contains list of all attendees of the event. This property is empty for all child events and
     * value of the one from parentEvent is used since all (parent, child) events have the same attendees
     * (so there is no need for some synchronization mechanism in case attendees changes).
     *
     * @var Collection|Attendee[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\CalendarBundle\Entity\Attendee",
     *     mappedBy="calendarEvent",
     *     cascade={"all"},
     *     orphanRemoval=true,
     *     fetch="EAGER"
     * )
     * @ORM\OrderBy({"displayName"="ASC"})
     */
    protected $attendees;

    /**
     * Attendee associated with this event (one attendee from attendees property having calendar owner in user property)
     * It can be null for parent event in case creator of the event is not among attendees.
     *
     * @var Attendee
     *
     * @ORM\ManyToOne(
     *     targetEntity="Oro\Bundle\CalendarBundle\Entity\Attendee",
     *     cascade={"persist", "remove"},
     *     fetch="EAGER"
     * )
     * @ORM\JoinColumn(name="related_attendee_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $relatedAttendee;

    /**
     * Defines recurring event rules. Only original recurring event has this relation not empty.
     *
     * @var Recurrence
     *
     * @ORM\OneToOne(
     *     targetEntity="Oro\Bundle\CalendarBundle\Entity\Recurrence",
     *     inversedBy="calendarEvent",
     *     cascade={"persist"},
     *     orphanRemoval=true
     * )
     * @ORM\JoinColumn(name="recurrence_id", nullable=true, referencedColumnName="id", onDelete="SET NULL")
     */
    protected $recurrence;

    /**
     * Collection of exceptions of recurring event.
     *
     * Exception event is added if one of the events of recurrence have to have different state.
     * For example recurring event starts at 9 AM on weekdays. But on Wednesday user moved this event to 10AM and
     * on Friday user cancelled this event.
     * In that case there will be 3 entities: 1 for recurring event and 2 for exceptions.
     *
     * Only original recurring event might have this collection not empty.
     * Only exception event uses these properties: $recurringEvent, $originalStart and $cancelled.
     * At the same time exception cannot use these properties: $recurrence, $recurringEventExceptions.
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="CalendarEvent", mappedBy="recurringEvent", orphanRemoval=true, cascade={"all"})
     */
    protected $recurringEventExceptions;

    /**
     * This attribute determines whether an event is an exception and what is original recurring event.
     *
     * Only exception event has this relation not empty.
     *
     * @var CalendarEvent
     *
     * @ORM\ManyToOne(targetEntity="CalendarEvent", inversedBy="recurringEventExceptions")
     * @ORM\JoinColumn(name="recurring_event_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $recurringEvent;

    /**
     * For an instance of exception of $recurringEvent, this is the time at which this event would start according to
     * the recurrence data saved in $recurrence property of $recurringEvent.
     *
     * Only exception event has this value not empty.
     *
     * @var \DateTime
     *
     * @ORM\Column(name="original_start_at", type="datetime", nullable=true)
     */
    protected $originalStart;

    /**
     * For an instance of exception of $recurringEvent, this flag determines if this event is cancelled.
     *
     * Only exception event has this value not empty.
     *
     * @var bool
     *
     * @ORM\Column(name="is_cancelled", type="boolean", options={"default"=false})
     */
    protected $cancelled = false;

    /**
     * CalendarEvent constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->reminders   = new ArrayCollection();
        $this->childEvents = new ArrayCollection();
        $this->attendees   = new ArrayCollection();
        $this->recurringEventExceptions  = new ArrayCollection();
    }

    /**
     * Gets an calendar event id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets UID of a calendar this event belongs to
     * The calendar UID is a string includes a calendar alias and id in the following format: {alias}_{id}
     *
     * @return string|null
     */
    public function getCalendarUid()
    {
        if ($this->calendar) {
            return sprintf('%s_%d', Calendar::CALENDAR_ALIAS, $this->calendar->getId());
        } elseif ($this->systemCalendar) {
            $alias = $this->systemCalendar->isPublic()
                ? SystemCalendar::PUBLIC_CALENDAR_ALIAS
                : SystemCalendar::CALENDAR_ALIAS;

            return sprintf('%s_%d', $alias, $this->systemCalendar->getId());
        }

        return null;
    }

    /**
     * Gets owning user's calendar
     *
     * @return Calendar|null
     */
    public function getCalendar()
    {
        return $this->calendar;
    }

    /**
     * Sets owning user's calendar
     *
     * @param Calendar $calendar
     *
     * @return CalendarEvent
     */
    public function setCalendar(Calendar $calendar = null)
    {
        $this->calendar = $calendar;
        // unlink an event from system calendar
        if ($calendar && $this->getSystemCalendar()) {
            $this->setSystemCalendar(null);
        }

        return $this;
    }

    /**
     * Gets owning system calendar
     *
     * @return SystemCalendar|null
     */
    public function getSystemCalendar()
    {
        return $this->systemCalendar;
    }

    /**
     * Sets owning system calendar
     *
     * @param SystemCalendar $systemCalendar
     *
     * @return CalendarEvent
     */
    public function setSystemCalendar(SystemCalendar $systemCalendar = null)
    {
        $this->systemCalendar = $systemCalendar;
        // unlink an event from user's calendar
        if ($systemCalendar && $this->getCalendar()) {
            $this->setCalendar(null);
        }

        return $this;
    }

    /**
     * Gets calendar event title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets calendar event title.
     *
     * @param string $title
     *
     * @return CalendarEvent
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Gets calendar event description.
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets calendar event description.
     *
     * @param  string $description
     *
     * @return CalendarEvent
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Gets date/time an event begins.
     *
     * @return \DateTime
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Sets date/time an event begins.
     *
     * @param \DateTime $start
     *
     * @return CalendarEvent
     */
    public function setStart($start)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Gets date/time an event ends.
     *
     * If an event is all-day the end date is inclusive.
     * This means an event with start Nov 10 and end Nov 12 will span 3 days on the calendar.
     *
     * If an event is NOT all-day the end date is exclusive.
     * This is only a gotcha when your end has time 00:00. It means your event ends on midnight,
     * and it will not span through the next day.
     *
     * @return \DateTime
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Sets date/time an event ends.
     *
     * @param \DateTime $end
     *
     * @return CalendarEvent
     */
    public function setEnd($end)
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Indicates whether an event occurs at a specific time-of-day.
     *
     * @return bool
     */
    public function getAllDay()
    {
        return $this->allDay;
    }

    /**
     * Sets a flag indicates whether an event occurs at a specific time-of-day.
     *
     * @param bool $allDay
     *
     * @return CalendarEvent
     */
    public function setAllDay($allDay)
    {
        $this->allDay = $allDay;

        return $this;
    }

    /**
     * Gets a background color of this events.
     * If this method returns null the background color should be calculated automatically on UI.
     *
     * @return string|null The color in hex format, e.g. #FF0000.
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * Sets a background color of this events.
     *
     * @param string|null $backgroundColor The color in hex format, e.g. #FF0000.
     *                                     Set it to null to allow UI to calculate the background color automatically.
     *
     * @return CalendarEvent
     */
    public function setBackgroundColor($backgroundColor)
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getReminders()
    {
        return $this->reminders;
    }

    /**
     * {@inheritdoc}
     */
    public function setReminders(Collection $reminders)
    {
        $this->reminders = $reminders;
    }

    /**
     * {@inheritdoc}
     *
     * @throws NotUserCalendarEvent
     */
    public function getReminderData()
    {
        if (!$this->getCalendar()) {
            throw new NotUserCalendarEvent($this->id);
        }

        $result = new ReminderData();
        $result->setSubject($this->getTitle());
        $result->setExpireAt($this->getStart());
        $result->setRecipient($this->getCalendar()->getOwner());

        return $result;
    }

    /**
     * Get child calendar events
     *
     * @return Collection|CalendarEvent[]
     */
    public function getChildEvents()
    {
        return $this->childEvents;
    }

    /**
     * Set children calendar events.
     *
     * @param Collection|CalendarEvent[] $calendarEvents
     *
     * @return CalendarEvent
     */
    public function resetChildEvents($calendarEvents)
    {
        $this->childEvents->clear();

        foreach ($calendarEvents as $calendarEvent) {
            $this->addChildEvent($calendarEvent);
        }

        return $this;
    }

    /**
     * Add child calendar event
     *
     * @param CalendarEvent $calendarEvent
     *
     * @return CalendarEvent
     */
    public function addChildEvent(CalendarEvent $calendarEvent)
    {
        if (!$this->childEvents->contains($calendarEvent)) {
            $this->childEvents->add($calendarEvent);
            $calendarEvent->setParent($this);
        }

        return $this;
    }

    /**
     * Remove child calendar event
     *
     * @param CalendarEvent $calendarEvent
     *
     * @return CalendarEvent
     */
    public function removeChildEvent(CalendarEvent $calendarEvent)
    {
        if ($this->childEvents->contains($calendarEvent)) {
            $this->childEvents->removeElement($calendarEvent);
            $calendarEvent->setParent(null);
        }

        return $this;
    }

    /**
     * @param Calendar $calendar
     *
     * @return CalendarEvent|null
     */
    public function getChildEventByCalendar(Calendar $calendar)
    {
        $result = $this->childEvents->filter(
            function (CalendarEvent $item) use ($calendar) {
                $itemCalendar = $item->getCalendar();
                if (!$itemCalendar) {
                    return false;
                }
                return $itemCalendar === $calendar || $itemCalendar->getId() == $calendar->getId();
            }
        );

        return $result->count() ? $result->first() : null;
    }

    /**
     * Set parent calendar event.
     *
     * @param CalendarEvent $parent
     *
     * @return CalendarEvent
     */
    public function setParent(CalendarEvent $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent calendar event.
     *
     * @return CalendarEvent|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Returns invitation status of the event based on related attendee. If there is no related attendee then returns
     * "none" status (@see CalendarEvent::STATUS_NONE).
     *
     * @see CalendarEvent::getRelatedAttendee()
     *
     * @return string Status id (@see CalendarEvent::STATUS_*)
     */
    public function getInvitationStatus()
    {
        $relatedAttendee = $this->getRelatedAttendee();

        if (!$relatedAttendee) {
            return CalendarEvent::STATUS_NONE;
        }

        return $relatedAttendee->getStatusCode();
    }

    /**
     * Get attendees of Calendar Event. If this event is a child event, the attendees collection will be retrieved
     * from the parent instance.
     *
     * @return Collection|Attendee[]
     */
    public function getAttendees()
    {
        $calendarEvent = $this->getParent() ? : $this;

        return $calendarEvent->attendees;
    }

    /**
     * Get attendee of Calendar Event by email.
     *
     * @return Attendee|null
     */
    public function getAttendeeByEmail($email)
    {
        $result = null;

        $attendees = $this->getAttendees();

        foreach ($attendees as $attendee) {
            if ($attendee->isEmailEqual($email)) {
                $result = $attendee;
                break;
            }
        }

        return $result;
    }

    /**
     * Returns all attendees related to child events. This method should not be called using child event.
     *
     * @return Collection|Attendee[]
     * @throws \LogicException If method is called with child event.
     */
    public function getChildAttendees()
    {
        $this->ensureCalendarEventIsNotChild();

        $relatedAttendee = $this->getRelatedAttendee();

        if (!$relatedAttendee) {
            return $this->getAttendees();
        }

        // Filter out related attendee using email
        return $this->getAttendees()->filter(
            function (Attendee $attendee) use ($relatedAttendee) {
                return $attendee !== $relatedAttendee && !$attendee->isEmailEqual($relatedAttendee->getEmail());
            }
        );
    }

    /**
     * Gets recurring event exceptions.
     *
     * @return Collection|CalendarEvent[]
     */
    public function getRecurringEventExceptions()
    {
        return $this->recurringEventExceptions;
    }

    /**
     * Resets recurring event exceptions.
     *
     * @param Collection|CalendarEvent[] $calendarEvents
     *
     * @return CalendarEvent
     */
    public function resetRecurringEventExceptions($calendarEvents)
    {
        $this->recurringEventExceptions->clear();

        foreach ($calendarEvents as $calendarEvent) {
            $this->addRecurringEventException($calendarEvent);
        }

        return $this;
    }

    /**
     * Adds recurring event exception.
     *
     * @param CalendarEvent $calendarEvent
     *
     * @return CalendarEvent
     */
    public function addRecurringEventException(CalendarEvent $calendarEvent)
    {
        if (!$this->recurringEventExceptions->contains($calendarEvent)) {
            $this->recurringEventExceptions->add($calendarEvent);
            $calendarEvent->setRecurringEvent($this);
        }

        return $this;
    }

    /**
     * Removes recurring event exception.
     *
     * @param CalendarEvent $calendarEvent
     *
     * @return CalendarEvent
     */
    public function removeRecurringEventException(CalendarEvent $calendarEvent)
    {
        if ($this->recurringEventExceptions->contains($calendarEvent)) {
            $this->recurringEventExceptions->removeElement($calendarEvent);
            $calendarEvent->setRecurringEvent(null);
        }

        return $this;
    }

    /**
     * Sets parent for calendar event exception.
     *
     * @param CalendarEvent|null $recurringEvent
     *
     * @return CalendarEvent
     */
    public function setRecurringEvent(CalendarEvent $recurringEvent = null)
    {
        $this->recurringEvent = $recurringEvent;

        return $this;
    }

    /**
     * Gets parent for calendar event exception.
     *
     * @return CalendarEvent|null
     */
    public function getRecurringEvent()
    {
        return $this->recurringEvent;
    }

    /**
     * Gets originalStart of calendar event exception or null if calendar event is not an exception.
     *
     * @return \DateTime|null
     */
    public function getOriginalStart()
    {
        return $this->originalStart;
    }

    /**
     * Sets originalStart of calendar event exception.
     *
     * @param \DateTime|null $originalStart
     *
     * @return CalendarEvent
     */
    public function setOriginalStart(\DateTime $originalStart = null)
    {
        $this->originalStart = $originalStart;

        return $this;
    }

    /**
     * Sets cancelled flag.
     *
     * @param bool $cancelled
     *
     * @return CalendarEvent
     */
    public function setCancelled($cancelled = false)
    {
        $this->cancelled = $cancelled;

        return $this;
    }

    /**
     * Gets cancelled flag.
     *
     * @return bool
     */
    public function isCancelled()
    {
        return $this->cancelled;
    }

    /**
     * Get attendee of Calendar Event. This method should not be called using child event.
     *
     * @param Collection|Attendee[] $attendees
     * @return CalendarEvent
     * @throws \LogicException If method is called with child event.
     */
    public function setAttendees(Collection $attendees)
    {
        $this->ensureCalendarEventIsNotChild();

        $this->attendees = $attendees;

        return $this;
    }

    /**
     * Throws an exception of Calendar Event has parent. Can be used to restrict calls for some methods using
     * child Calendar Events.
     *
     * @throws \LogicException
     */
    protected function ensureCalendarEventIsNotChild()
    {
        if ($this->getParent()) {
            throw new \LogicException(
                sprintf(
                    'Update of child Calendar Event (id=%d) is restricted. Use parent Calendar Event instead.',
                    $this->getId()
                )
            );
        }
    }

    /**
     * Add attendee of Calendar Event. This method should not be called using child event.
     *
     * @param Attendee $attendee
     * @return CalendarEvent
     * @throws \LogicException If method is called with child event.
     */
    public function addAttendee(Attendee $attendee)
    {
        $this->ensureCalendarEventIsNotChild();

        if (!$this->getAttendees()->contains($attendee)) {
            $attendee->setCalendarEvent($this);
            $this->getAttendees()->add($attendee);
        }

        return $this;
    }

    /**
     * Remove attendee. Related child event will be also removed. This method should not be called using child event.
     *
     * @param Attendee $attendee
     * @return CalendarEvent
     * @throws \LogicException If method is called with child event.
     */
    public function removeAttendee(Attendee $attendee)
    {
        $this->ensureCalendarEventIsNotChild();

        if ($this->getAttendees()->contains($attendee)) {
            $childEvent = $this->getChildEventByAttendee($attendee);
            if ($childEvent) {
                if ($childEvent->getRecurringEvent()) {
                    // If child event is an exception of recurring event then it should be cancelled
                    // to hide the event in user's calendar
                    $childEvent->setCancelled(true);
                } else {
                    // otherwise it should be removed
                    $this->removeChildEvent($childEvent);
                }
            }
            $this->getAttendees()->removeElement($attendee);
        }

        return $this;
    }

    /**
     * @param Attendee $attendee
     *
     * @return CalendarEvent|null
     */
    protected function getChildEventByAttendee(Attendee $attendee)
    {
        $result = $this->getChildEvents()->filter(
            function (CalendarEvent $chileEvent) use ($attendee) {
                $calendar = $chileEvent->getCalendar();
                $ownerUser = $calendar ? $calendar->getOwner() : null;
                return $ownerUser && $attendee->isUserEqual($ownerUser);
            }
        );

        return $result->count() ? $result->first() : null;
    }

    /**
     * Find attendee related to this event. Related attendee has a user same as an owner of the calendar event.
     *
     * @return Attendee|null
     */
    public function findRelatedAttendee()
    {
        $result = null;

        $calendar = $this->getCalendar();
        if (!$calendar) {
            return $result;
        }

        $ownerUser = $calendar->getOwner();
        if (!$ownerUser) {
            return $result;
        }

        foreach ($this->getAttendees() as $attendee) {
            if ($attendee->isUserEqual($ownerUser)) {
                $result = $attendee;
                break;
            }
        }

        return $result;
    }

    /**
     * @return Attendee
     */
    public function getRelatedAttendee()
    {
        return $this->relatedAttendee;
    }

    /**
     * @param Attendee|null $relatedAttendee
     *
     * @return CalendarEvent
     */
    public function setRelatedAttendee(Attendee $relatedAttendee = null)
    {
        $this->relatedAttendee = $relatedAttendee;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getTitle();
    }

    /**
     * Sets recurrence.
     *
     * @param Recurrence|null $recurrence
     *
     * @return CalendarEvent
     */
    public function setRecurrence(Recurrence $recurrence = null)
    {
        $this->recurrence = $recurrence;

        return $this;
    }

    /**
     * Gets recurrence.
     *
     * @return Recurrence|null
     */
    public function getRecurrence()
    {
        return $this->recurrence;
    }
}
