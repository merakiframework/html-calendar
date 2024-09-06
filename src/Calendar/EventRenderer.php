<?php
declare(strict_types=1);

namespace Meraki\Html\Calendar;

use Meraki\Html\Calendar\Event;
use Meraki\Html\Calendar\Source;
use Meraki\Html\Calendar;
use Meraki\Html\Element;
use Meraki\Html\Attribute;

/**
 * The EventRenderer is responsible for rendering events into the calendar view.
 */
class EventRenderer
{
	public function renderEventCellForAgendaView(Event $event, Source $source, Calendar $calendar): Element
	{
		throw new \RuntimeException('Not implemented yet.');
	}

	public function renderEventPopupForAgendaView(Event $event, Source $source, Calendar $calendar): Element
	{
		throw new \RuntimeException('Not implemented yet.');
	}

	/**
	 * Create the cell that will be used to display the event in the day view.
	 *
	 * Do not add positioning styles to the cell or add popover functionality:
	 * this will be handled by the calendar. Aria roles will also be handled
	 * by the calendar.
	 */
	public function renderEventCellForDayView(Event $event, Source $source, Calendar $calendar): Element
	{
		$eventCell = new Element('button');

		$eventCell->attributes->add(new Attribute\Class_('event'));
		$eventCell->attributes->add(new Attribute\Style(['--calendar-source-colour' => $source->colour]));

		$eventCell->appendContent($event->title);

		return $eventCell;
	}

	/**
	 * Create the popup that will be used to display detailed event information
	 * in the day view.
	 *
	 * Do not add positioning styles to the cell or add popover functionality:
	 * this will be handled by the calendar. Aria roles will also be handled
	 * by the calendar.
	 */
	public function renderEventPopupForDayView(Event $event, Source $source, Calendar $calendar): Element
	{
		$popover = new Element('dialog');
		$popover->attributes->findOrCreate(Attribute\Style::class, fn() => new Attribute\Style())
			->set('--calendar-source-colour', $source->colour);

		$name = new Element('h3');
		$name->attributes->set(new Attribute\Class_('event-title'));
		$name->setContent($event->title);

		$startsAt = $event->when;
		$endsAt = $startsAt->plusDuration($event->duration);
		$tz = $startsAt->getTimeZone();

		$from = new Element('time');
		$from->attributes->set(new Attribute('datetime', $startsAt->__toString()));
		$from->setContent($startsAt->toNativeDateTimeImmutable()->format('g:i A'));

		$to = new Element('time');
		$to->attributes->set(new Attribute('datetime', $endsAt->__toString()));
		$to->setContent($endsAt->toNativeDateTimeImmutable()->format('g:i A'));

		$when = new Element('p');
		$when->attributes->set(new Attribute\Class_('event-when'));
		$when->setContent(
			$from,
			' - ',
			$to,
			' (' . $tz->getId() . ')'
		);


		$popover->appendContent($name, $when);

		return $popover;
	}

	public function renderEventCellForWeekView(Event $event, Source $source, Calendar $calendar): Element
	{
		return $this->renderEventCellForDayView($event, $source, $calendar);
	}

	public function renderEventPopupForWeekView(Event $event, Source $source, Calendar $calendar): Element
	{
		return $this->renderEventPopupForDayView($event, $source, $calendar);
	}

	public function renderEventCellForMonthView(Event $event, Source $source, Calendar $calendar): Element
	{
		throw new \RuntimeException('Not implemented yet.');
	}

	public function renderEventPopupForMonthView(Event $event, Source $source, Calendar $calendar): Element
	{
		throw new \RuntimeException('Not implemented yet.');
	}
}
