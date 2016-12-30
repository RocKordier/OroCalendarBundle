UPGRADE FROM 1.10 to 2.0
========================

#SOAP API was removed
- removed all dependencies to the `besimple/soap-bundle` bundle. 
- removed SOAP annotations from the entities. Updated entities:
    - Oro\Bundle\CalendarBundle\Entity\Calendar
    - Oro\Bundle\CalendarBundle\Entity\CalendarProperty
- removed classes:
    - Oro\Bundle\CalendarBundle\Controller\Api\Soap\CalendarConnectionController

#Other changes
- Removed dependency from `Oro\Bundle\SecurityBundle\SecurityFacade` in `Oro\Bundle\CalendarBundle\Model\Email\EmailSendProcessor`.
- Removed constant `Oro\Bundle\CalendarBundle\Entity\CalendarEvent::STATUS_NONE`. Use `Oro\Bundle\CalendarBundle\Entity\Attendee::STATUS_NONE`.
- Removed constant `Oro\Bundle\CalendarBundle\Entity\CalendarEvent::STATUS_TENTATIVE`. Use `Oro\Bundle\CalendarBundle\Entity\Attendee::STATUS_TENTATIVE`.
- Removed constant `Oro\Bundle\CalendarBundle\Entity\CalendarEvent::STATUS_ACCEPTED`. Use `Oro\Bundle\CalendarBundle\Entity\Attendee::STATUS_ACCEPTED`.
- Removed constant `Oro\Bundle\CalendarBundle\Entity\CalendarEvent::STATUS_DECLINED`. Use `Oro\Bundle\CalendarBundle\Entity\Attendee::STATUS_DECLINED`.
- A new property `editableInvitationStatus` was added to the API of calendar events. This property is read-only and means that current API user is able to change invitation status of the event.
- A new property `updateExceptions` was added to the API of calendar events. By default is FALSE. Passing TRUE value will trigger synchronization of exceptions of recurring calendar events. 
- Deprecated property `invitedUsers` was removed from the API.
- Renamed method `Oro\Bundle\CalendarBundle\Manager\CalendarEventManager::changeStatus` to `Oro\Bundle\CalendarBundle\Manager\CalendarEventManager::changeInvitationStatus`. Added a new argument to this method which represent a user instance.
- Removed classes `Oro\Bundle\CalendarBundle\Exception\StatusNotFoundException` and `Oro\Bundle\CalendarBundle\Exception\CalendarEventRelatedAttendeeNotFoundException`. Added class `Oro\Bundle\CalendarBundle\Exception\ChangeInvitationStatusException` instead.
- Removed constant `Oro\Bundle\CalendarBundle\Model\Recurrence::STRING_KEY`. New constant `Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository::RECURRENCE_FIELD_PREFIX` is added instead.
- Changed implementation of method `Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository::getUserEventListQueryBuilder`. Item "relatedAttendeeUserId" added to SELECT, default value added for item "invitationStatus" in the SELECT.
- Removed entity configuration with scope "security" from `Oro\Bundle\CalendarBundle\Entity\Attendee`.
- Added support NULL value for entity field `Oro\Bundle\CalendarBundle\Entity\Attendee::$calendarEvent`.
- Removed deprecated constants of `Oro\Bundle\CalendarBundle\Entity\CalendarEvent`: `NOT_RESPONDED`, `TENTATIVELY_ACCEPTED`, `ACCEPTED`, `DECLINED`.
- Removed deprecation of method `Oro\Bundle\CalendarBundle\Entity\CalendarEvent::getInvitationStatus`.
- Removed method `Oro\Bundle\CalendarBundle\Entity\CalendarEvent::getEventByAttendee`. Added method `Oro\Bundle\CalendarBundle\Entity\CalendarEvent::getChildEventByAttendee` instead.
- Removed method `Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository::getInvitedUsersByParentsQueryBuilder`.
- Changed scope of method `Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository::getEventListQueryBuilder` to `protected`.
- Removed method `Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository::addRecurrencesConditions`, logic of this method moved to method `addTimeIntervalFilter`.
- Refactored form subscribers and event listeners in `Oro\Bundle\CalendarBundle\Form\EventListener`. Changed signature of constructors of these classes.
- Removed class `Oro\Bundle\CalendarBundle\Form\\EventListener\ChildEventsSubscriber`. The logic was refactored moved to classes in `Oro\Bundle\CalendarBundle\Manager\CalendarEvent` namespace.
- Refactored form handlers in `Oro\Bundle\CalendarBundle\Form\Handler` namespace. Added abstract handler `Oro\Bundle\CalendarBundle\Form\Handler\AbstractCalendarEventHandler`. Moved shared code to this abstract class. Changed signature of constructors of form handlers classes of calendar events: `Oro\Bundle\CalendarBundle\Form\Handler\CalendarEventApiHandler`, `Oro\Bundle\CalendarBundle\Form\Handler\CalendarEventHandler`, `Oro\Bundle\CalendarBundle\Form\Handler\SystemCalendarEventHandler`.
- Refactored form types in `Oro\Bundle\CalendarBundle\Form\Type`. Changed signature of constructors of these classes. 
- Removed method `Oro\Bundle\CalendarBundle\Model\Recurrence\StrategyInterface::getValidationErrorMessage`. Added methods to the interface: `getMaxInterval`, `getIntervalMultipleOf`, `getRequiredProperties`. Removed method `Oro\Bundle\CalendarBundle\Model\Recurrence::getValidationErrorMessage`. 
- Validation logic is moved from strategies to `Oro\Bundle\CalendarBundle\Validator\RecurrenceValidator`. Changed validation rules of entities `Oro\Bundle\CalendarBundle\Entity\Recurrence` and `Oro\Bundle\CalendarBundle\Entity\CalendarEvent`.
- Refactored normalizers in `Oro\Bundle\CalendarBundle\Provider`. Changed signature of the constructors. Protected methods were removed `applyAdditionalData`, `applyPermissions`, `addAttendeesToCalendarEvents`.
- Refactored providers in `Oro\Bundle\CalendarBundle\Provider`. Parent class is changed to `Oro\Bundle\CalendarBundle\Provider\AbstractRecurrenceAwareCalendarProvider`. Logic related to recurring events moved to parent class.
- Changed configuration of the grids `calendar-event-grid`. `base-system-calendar-event-grid`, `calendar-event-for-context-grid`.
- Removed class `Oro\Bundle\CalendarBundle\Form\Type\ExceptionFormType`.
- Removed service `oro_calendar.send_processor.email`, added service `oro_calendar.calendar_event.notification_manager` instead.
- Removed class `Oro\Bundle\CalendarBundle\Model\Email\EmailSendProcessor`, the responsibilities for notifications were moved to classes `Oro\Bundle\CalendarBundle\Manager\CalendarEvent\NotificationManager` and `Oro\Bundle\CalendarBundle\Model\Email\EmailNotificationSender`.
- Updated view templates `OroCalendarBundle:CalendarEvent:view.html.twig` and `OroCalendarBundle:CalendarEvent:widget\info.html.twig`. Added property `canChangeInvitationStatus` into the templates which is passed from the respective controller action. 
- Updated view template `OroCalendarBundle:CalendarEvent:update.html.twig`.
- Updated view template `OroCalendarBundle:SystemCalendarEvent:update.html.twig`.
- Updated view template My Calendar widget `OroCalendarBundle:::templates.html.twig`.
- Updated macroses in `OroCalendarBundle::invitations.html.twig`: `calendar_event_invitation_status`, `calendar_event_invitation_action` (removed), `calendar_event_invitation_going_status` (added).
- Removed template `OroCalendarBundle:CalendarEvent:widget\invitationButtons.html.twig`. A new widget to change invitation status added in `OroCalendarBundle:CalendarEvent:widget\invitationControl.html.twig` and JS module `orocalendar/js/app/views/change-status-view`.
- Removed method `Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager::createAttendee`, added new methods instead: `Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager::setRelatedEntity`, `Oro\Bundle\CalendarBundle\Manager\AttendeeManager::createAttendee`.
- Renamed method `Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager::getRelatedDisplayName` to `Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager::getDisplayName`.
- Removed method `Oro\Bundle\CalendarBundle\Autocomplete\AttendeeSearchHandler::setAttendeeRelationManager`. Dependency to `Oro\Bundle\CalendarBundle\Manager\AttendeeRelationManager` is replaced with `Oro\Bundle\CalendarBundle\Manager\AttendeeManager` and method `Oro\Bundle\CalendarBundle\Autocomplete\AttendeeSearchHandler::setAttendeeManager`.
- Updated email templates for calendar events: `calendar_invitation_invite`, `calendar_invitation_update`, `calendar_invitation_delete_parent_event`, `calendar_invitation_uninvite`, `calendar_invitation_accepted`, `calendar_invitation_tentative`, `calendar_invitation_declined`, `calendar_invitation_delete_child_event`.
- Changed signature of method `Oro\Bundle\CalendarBundle\Model\Email\EmailNotification::setEmails`, added `array` type hint.
- Changed signature of method `Oro\Bundle\CalendarBundle\Controller\Api\Rest\CalendarEventController::cgetAction`, added argument for request.
- Removed parameters from DIC: `oro_calendar.oro_calendar_event_attendees_select.form.type.class`, `oro_calendar.oro_calendar_event_attendees_api.form.type.class`, `oro_calendar.system_calendar_event.form.handler.class`, `oro_calendar.attendees_to_view_transformer.class`, `oro_calendar.user_ids_to_users.tranformer.class`, `oro_calendar.calendar_event.form.handler.api.class`.
- Removed deprecated factory properties in definitions of services: `oro_calendar.calendar_event.form`, `oro_calendar.calendar_event.form.template`, `oro_calendar.calendar_event.form.api`, `oro_calendar.calendar_property.form.api`, `oro_calendar.calendar_property.form.soap.api`, `oro_calendar.system_calendar.form`.
- Removed services: `oro_calendar.user_ids_to_users.tranformer`, `oro_calendar.attendees_to_view_transformer`, `oro_calendar.oro_calendar_event_attendees_api.form.type`.
- Property `notifyInvitedUsers` in API was marked as deprecated, use `notifyAttendees` instead.
