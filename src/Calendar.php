<?php
declare(strict_types=1);

namespace Meraki\Html;

use Brick\DateTime\DayOfWeek;
use Brick\DateTime\Duration;
use Brick\DateTime\Instant;
use Brick\DateTime\Interval;
use Brick\DateTime\LocalDate;
use Brick\DateTime\LocalDateRange;
use Brick\DateTime\LocalTime;
use Brick\DateTime\Month;
use Brick\DateTime\Period;
use Brick\DateTime\TimeZone;
use Brick\DateTime\ZonedDateTime;
use Meraki\Html\Attribute;
use Meraki\Html\Element;
use Meraki\Html\Calendar\View;
use Meraki\Html\Calendar\Widget;
use Meraki\Html\Calendar\Source;
use Brick\DateTime\Clock;

final class Calendar extends Element
{
	public const DEFAULT_TIMEZONE = 'UTC';
	public const DEFAULT_FIRST_DAY_OF_WEEK = 1;
	public const DEFAULT_LOCALE = 'en_US';

	/** @var string[] The supported views. */
	public const SUPPORTED_VIEWS = ['agenda', 'day', 'week', 'month'];


	private array $widgets = [];
	public string $view;

	/**
	 * The calendar's timezone.
	 *
	 * All dates and times are in this timezone. All events will be converted to this timezone.
	 */
	public TimeZone $timeZone;
	public DayOfWeek $weekStartsOn;
	private array $listeners = [];
	private array $registeredEvents = [];

	/**
	 * The current date and time, in the calendar's timezone.
	 *
	 * @readonly
	 */
	public ZonedDateTime $now;

	/**
	 * Today's date in the calendar's timezone.
	 *
	 * @readonly
	 */
	public LocalDate $currentDate;

	/**
	 * The date range of the current view that includes today's date in the calendar's timezone.
	 *
	 * @readonly
	 */
	public LocalDateRange $currentDateRange;

	/**
	 * The date that the calendar is currently displaying in the calendar's timezone.
	 *
	 * @readonly
	 */
	public LocalDate $selectedDate;

	/**
	 * The date range of the current view that includes the selected date in the calendar's timezone.
	 *
	 * @readonly
	 */
	public LocalDateRange $selectedDateRange;

	/**
	 * How many days the calendar displays at once.
	 *
	 * @readonly
	 */
	public Period $period;

	/**
	 * Events that have already been dispatched. Used to
	 * dispatch the events that have already been dispatched
	 * before, for when a new listener is added.
	 */
	private array $dispatchedEvents = [];
	public Calendar\Url $url;
	public Calendar\TimeSlots $timeSlots;

	public function __construct(
		public Clock $clock,
		public Source\Set $sources,
		?Attribute\Set $attributes = null,
	) {
		parent::__construct('div', $attributes);

		$this->registerDefaultEvents();

		$this->url = Calendar\Url::fromServer();

		$firstDayOfWeek = $this->attributes->findOrCreate(Attribute\FirstDayOfWeek::class, fn() => new Attribute\FirstDayOfWeek(1));
		$this->weekStartsOn = DayOfWeek::from($firstDayOfWeek->value);

		$this->timeZone = TimeZone::parse($this->attributes->findOrCreate(Attribute\TimeZone::class, fn() => new Attribute\TimeZone('UTC'))->value);
		$this->now = $this->clock->getTime()->atTimeZone($this->timeZone);

		$this->timeSlots = new Calendar\TimeSlots(LocalTime::of(7, 0), LocalTime::of(19, 0), Duration::ofMinutes(15));

		$this->attributes
			->findOrCreate(Attribute\Class_::class, fn() => new Attribute\Class_())
			->add('calendar');

		// Select initial view
		$viewType = $this->attributes->findOrCreate(Attribute\View::class, fn() => new Attribute\View('week'));
		$this->selectView($viewType->value);


		$this->update($this->now->getDate());
		$this->handleQueryParameters();
	}

	/**
	 * Get the date range that includes the given date.
	 *
	 * The date range is based on the selected view's period.
	 *
	 * All dates are in the calendar's timezone.
	 */
	public function getDateRangeThatIncludes(LocalDate $date): LocalDateRange
	{
		$viewPeriod = $this->period;
		$startsFrom = $this->getFirstDayOfPeriodRelativeTo($date, $viewPeriod);
		$endsAt = $date->plusDays($viewPeriod->getDays() - $startsFrom->until($date)->getDays() - 1);
		// $endsAt = $date->plusPeriod($viewPeriod->minusDays($startsFrom->until($date)->getDays()));

		return LocalDateRange::of($startsFrom, $endsAt);
	}

	private function buildTimeColumn(): Element
	{
		$timeSlots = $this->timeSlots;
		$now = $this->now;
		$timeColumn = new Element('div');
		$timeColumn->attributes
			->findOrCreate(Attribute\Class_::class, fn() => new Attribute\Class_())
			->add('time-column');
		$timeColumn->attributes
			->findOrCreate(Attribute\Style::class, fn() => new Attribute\Style())
			->set('display', 'grid')
			->set('grid-template-columns', 'auto')
			->set('grid-template-rows', 'subgrid')
			->set('grid-column', '1 / span 1')
			->set('grid-row', '1 / -1');

		// build time column header cell
		$headerCell = new Element('div');
		$headerCell->attributes
			->findOrCreate(Attribute\Class_::class, fn() => new Attribute\Class_())
			->add('header-cell', 'time-cell');
		$headerCell->attributes
			->findOrCreate(Attribute\Style::class, fn() => new Attribute\Style())
			->set('grid-column', '1 / span 1')
			->set('grid-row', '1 / span 1');
		$headerCell->setContent('&nbsp;');

		$timeColumn->appendContent($headerCell);

		$gridRowStartIndexOffset = 1;

		// build time column body cells
		foreach ($timeSlots as $rowStart => $timeSlot) {
			$rowStart = $gridRowStartIndexOffset + $rowStart + 1;

			$bodyCell = new Element('time');
			$bodyCell->attributes
				->findOrCreate(Attribute\Class_::class, fn() => new Attribute\Class_())
				->add('body-cell', 'time-cell');
			$bodyCell->attributes
				->findOrCreate(Attribute\Style::class, fn() => new Attribute\Style())
				->set('grid-column', '1 / span 1')
				->set('grid-row', $rowStart . ' / span 1');

			$bodyCell->attributes->add(new Attribute('datetime', $timeSlot->__toString()));

			// add data-now attribute if time slot is current
			$nowTime = $now->getTime();

			if ($nowTime->isAfter($timeSlot) && $nowTime->isBefore($timeSlot->plusDuration($timeSlots->duration))) {
				// $bodyCell->attributes->add(new Attribute\Data('now', ''));
			}

			$bodyCell->setContent((string) $timeSlot);

			$timeColumn->appendContent($bodyCell);
		}

		return $timeColumn;
	}

	/**
	 * Get the first day of the period relative to the current date, based on the calendar's timezone.
	 */
	private function getFirstDayOfPeriodRelativeTo(LocalDate $date, Period $period): LocalDate
	{
		$startsFrom = $date;

		// find leading days until the calendar's start of week day
		while ($startsFrom->getDayOfWeek() !== $this->weekStartsOn) {
			$startsFrom = $startsFrom->minusDays(1);
		}

		$periods = [];

		// chunk the days into periods
		while ($startsFrom->isBeforeOrEqualTo($date)) {
			$periods[] = $startsFrom;
			$startsFrom = $startsFrom->plusPeriod($period);
		}

		// last period is the one that includes the current date
		return array_pop($periods);
	}

	private function registerDefaultEvents(): void
	{
		$this->registerEvent('view.registered', ['view']);
		$this->registerEvent('view.selected', ['view']);
		$this->registerEvent('parameter', ['name', 'value']);
		$this->registerEvent('widget', ['widget']);
		$this->registerEvent('datechange', ['date']);
	}

	public function listenOnEvent(string $name, callable $listener): self
	{
		$this->listeners[$name][] = $listener;

		// Dispatch any events that have already been dispatched
		foreach ($this->dispatchedEvents as $event) {
			if ($event->eventName === $name) {
				$listener($event);
			}
		}

		return $this;
	}

	public function dispatchEvent(\stdClass $event): void
	{
		if (!array_key_exists($event->eventName, $this->registeredEvents)) {
			throw new \InvalidArgumentException("Event ".$event->eventName." not registered.");
		}

		foreach ($this->listeners as $eventName => $listeners) {
			if ($eventName === $event->eventName) {
				foreach ($listeners as $listener) {
					$listener($event);
				}

				$this->dispatchedEvents[] = $event;
			}
		}
	}

	/**
	 * Format a date to a human-readable string.
	 *
	 * This is done according to the calendar's locale by default.
	 */
	public function prettifyDate(LocalDate $date, string $format = 'l, j F Y'): string
	{
		return $date->toNativeDateTimeImmutable()->format($format);
	}

	public function registerEvent(string $name, array $requiredParams): self
	{
		$name = strtolower($name);

		if (array_key_exists($name, $this->registeredEvents)) {
			throw new \InvalidArgumentException("Event ".$name." already registered.");
		}

		$this->registeredEvents[$name] = $requiredParams;
		$this->listeners[$name] = [];

		return $this;
	}

	public function createEvent(string $name, array $params): \stdClass
	{
		if (!array_key_exists($name, $this->registeredEvents)) {
			throw new \InvalidArgumentException("Event ".$name." not registered.");
		}

		$event = new \stdClass();
		$event->eventName = $name;

		// params has to have the same keys as requiredParams
		// it cannot have more keys than requiredParams
		$requiredParams = $this->registeredEvents[$name];

		while ($requiredParams) {
			$param = array_shift($requiredParams);

			if (!array_key_exists($param, $params)) {
				throw new \InvalidArgumentException("Missing required parameter ".$param." for event ".$name);
			}

			$event->{$param} = $params[$param];
			unset($params[$param]);
		}

		if ($params) {
			throw new \InvalidArgumentException("Unknown parameters for event: ".implode(', ', array_keys($params)));
		}

		return $event;
	}

	public function selectView(string $type): self
	{
		if (in_array($type, self::SUPPORTED_VIEWS)) {
			$this->view = $type;
			$this->period = match($type) {
				// 'month' => Period::ofDays($this->now->getMonth()->getLength($this->now->)),
				// 'agenda' => Period::ofDays(1),
				'day' => Period::ofDays(1),
				'week' => Period::ofDays(7),
				default => Period::ofDays(7),
			};
			$this->update($this->selectedDate ?? $this->currentDate ?? $this->now->getDate());
			$this->dispatchEvent($this->createEvent('view.selected', ['view' => $type]));

			return $this;
		}

		throw new \InvalidArgumentException('View type "'.$type.'" not supported.');
	}

	public function startDayAt(int $hour): self
	{
		return $this;
	}

	public function endDayAt(int $hour): self
	{
		return $this;
	}

	public function startWeekOn(int $day): self
	{
		return $this;
	}

	public function isInTimeZone(string $timeZone): self
	{
		return $this;
	}

	public function connectTo(Source $source): self
	{
		$this->sources = $this->sources->add($source);

		return $this;
	}

	public function disconnectFrom(Source $source): self
	{
		$this->sources = $this->sources->remove($source);

		return $this;
	}

	public function appendContent(Element|string ...$nodes): self
	{
		$nodesToAppend = [];

		foreach ($nodes as $node) {
			if ($node instanceof Widget) {
				$this->extend($node);
				continue;
			}

			$nodesToAppend[] = $node;
		}

		return parent::appendContent(...$nodesToAppend);
	}

	public function extend(Widget $widget): self
	{
		$shortName = (new \ReflectionClass($widget))->getShortName();
		$kebabCaseName = $this->pascaleCaseToKebabCase($shortName);

		$widget->attributes
			->findOrCreate(Attribute\Class_::class, fn() => new Attribute\Class_())
			->add($kebabCaseName);
		$widget->attributes
			->findOrCreate(Attribute\Style::class, fn() => new Attribute\Style())
			->set('grid-area', $kebabCaseName);

		$this->widgets[] = $widget;
		$this->dispatchEvent($this->createEvent('widget', ['widget' => $widget]));
		$widget->connect($this);

		return $this;
	}

	public function update(LocalDate $date): self
	{
		$this->currentDate = $this->now->getDate();
		$this->currentDateRange = $this->getDateRangeThatIncludes($this->currentDate);

		$this->selectedDate = $date;
		$this->selectedDateRange = $this->getDateRangeThatIncludes($date);

		$this->attributes->set(new Attribute\Date($date->__toString()));
		$this->dispatchEvent($this->createEvent('datechange', ['date' => $date]));

		return $this;
	}

	private function pascaleCaseToKebabCase(string $input): string
	{
		return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $input));
	}

	public function handleQueryParameters(): void
	{
		if ($date = $this->url->getDate()) {
			$this->update($date);
		}

		if ($view = $this->url->getView()) {
			$this->selectView($view);
		}

		$sources = $this->url->getSources() ?? [];

		if (count($sources) > 0) {
			$this->sources->select(...$sources);
		} else {
			$this->sources->selectAll();
		}
	}

	private function buildView(): Element
	{
		$el = match($this->view) {
			'month' => $this->buildMonthView(),
			'agenda' => $this->buildAgendaView(),
			'day' => $this->buildWeekView(),
			'week' => $this->buildWeekView(),
			default => $this->buildWeekView(),
		};

		$el->attributes
			->findOrCreate(Attribute\Class_::class, fn() => new Attribute\Class_())
			->add($this->view . '-view', 'view');

		$el->attributes
			->findOrCreate(Attribute\Style::class, fn() => new Attribute\Style())
			->set('grid-area', 'view');

		// if (in_array($this->view, ['day', 'week'])) {
		// 	$style->set('grid-template-rows', 'auto repeat(' . $this->timeSlots->count() . ', 1fr)');
		// 	$style->set('grid-template-columns', 'auto repeat(' . $this->period->getDays() . ', 1fr)');
		// }

		return $el;
	}

	private function buildAgendaView(): Element
	{
		throw new \RuntimeException('Agenda view not implemented.');
	}

	private function buildMonthView(): Element
	{
		throw new \RuntimeException('Month view not implemented.');
	}

	private function buildWeekView(): Element
	{
		$view = new Element('div');
		$attrs = $view->attributes->findOrCreate(Attribute\Style::class, fn() => new Attribute\Style());

		$attrs->set('display', 'grid')
			->set('grid-template-columns', 'auto repeat(' . $this->period->getDays() . ', 1fr)')
			->set('grid-template-rows', 'auto repeat(' . $this->timeSlots->count() . ', 1fr)');

		$view->appendContent($this->buildTimeColumn());

		foreach ($this->selectedDateRange as $index => $dateToRender) {
			$view->appendContent($this->buildDayColumn($index, $dateToRender));
		}

		return $view;
	}


	private function buildDayColumn(int $dateIndex, LocalDate $dateToRender): Element
	{
		$timeSlots = $this->timeSlots->__toArray();
		$dayColumnStartIndex = 2 + $dateIndex;
		$sources = $this->sources->getSelected();
		$dayColumn = new Element('div');
		$dayColumn->attributes
			->findOrCreate(Attribute\Class_::class, fn() => new Attribute\Class_())
			->add('day-column');
		$dayColumn->attributes
			->findOrCreate(Attribute\Style::class, fn() => new Attribute\Style())
			->set('display', 'grid')
			->set('grid-template-columns', 'repeat(' . count($sources) . ', 1fr)')
			->set('grid-template-rows', 'subgrid')
			->set('grid-column', $dayColumnStartIndex . ' / span 1')
			->set('grid-row', '1 / -1');

		if ($dateToRender->isEqualTo($this->selectedDate)) {
			$dayColumn->attributes->add(new Attribute\Data('selected', ''));
		}

		if ($dateToRender->isEqualTo($this->now->getDate())) {
			$dayColumn->attributes->add(new Attribute\Data('today', ''));
		}

		$dayHeader = new Element('time');
		$dayHeader->attributes
			->findOrCreate(Attribute\Class_::class, fn() => new Attribute\Class_())
			->add('header-cell', 'day');
		$dayHeader->attributes
			->findOrCreate(Attribute\Style::class, fn() => new Attribute\Style())
			->set('grid-column', '1 / -1')
			->set('grid-row', '1 / span 1');
		$dayHeader->attributes->add(new Attribute('datetime', $dateToRender->__toString()));

		$dayHeader->setContent($dateToRender->getDayOfWeek()->toString() . ',<br>' . $this->prettifyDate($dateToRender, 'j M Y'));
		$dayColumn->appendContent($dayHeader);

		$timeSlotStartIndexOffset = 1;

		/** @var Calendar\Source $source */
		foreach ($sources as $sourceIndex => $source) {
			$sourceIndex += 1; // 1-indexed
			$events = $source->getAllEventsOn($dateToRender);

			// stores the time slots that have been used by events
			// used to build empty cells for time slots that have no events
			$timeSlotsUsed = array_fill(0, count($timeSlots), false);

			foreach ($timeSlots as $timeSlotIndex => $timeSlot) {
				$gridRowStart = $timeSlotStartIndexOffset + $timeSlotIndex + 1;
				$eventsForTimeSlot = [];

				/** @var Calendar\Event $event */
				foreach ($events as $eventIndex => $event) {
					if ($event->occursAt($timeSlot)) {
						$eventsForTimeSlot[] = $event;
						unset($events[$eventIndex]);
					}
				}

				if (count($eventsForTimeSlot) > 0) {
					foreach ($eventsForTimeSlot as $event) {
						$gridRowSpan = ceil($event->duration->toMinutes() / $this->timeSlots->duration->toMinutes());

						$eventCell = new Element('button');
						$eventCell->attributes->set(new Attribute('popovertargetaction', 'show'));
						$eventCell->attributes->set(new Attribute('popovertarget', 'source-' . $source->id . '-event-' . $event->id));
						$eventCell->attributes
							->findOrCreate(Attribute\Class_::class, fn() => new Attribute\Class_())
							->add('event');
						$eventCell->attributes
							->findOrCreate(Attribute\Style::class, fn() => new Attribute\Style())
							->set('grid-column', $sourceIndex . ' / span 1')
							->set('grid-row', $gridRowStart . ' / span ' . $gridRowSpan)
							->set('--calendar-source-colour', $source->colour);

						$eventDetails = $this->buildEventPopover($source, $event);

						$eventCell->appendContent($event->name);
						$dayColumn->appendContent($eventCell, $eventDetails);

						// mark time slots as used
						for ($i = 0; $i < $gridRowSpan; $i++) {
							$timeSlotsUsed[$timeSlotIndex + $i] = true;
						}
					}
				}
			}

			// build empty cells for time slots that have no events
			foreach ($timeSlotsUsed as $timeSlotIndex => $isUsed) {
				if (!$isUsed) {
					$emptyCell = new Element('time');
					$emptyCell->attributes
						->findOrCreate(Attribute\Class_::class, fn() => new Attribute\Class_())
						->add('empty-cell');
					$emptyCell->attributes
						->findOrCreate(Attribute\Style::class, fn() => new Attribute\Style())
						->set('grid-column', $sourceIndex . ' / span 1')
						->set('grid-row', $timeSlotStartIndexOffset + $timeSlotIndex + 1 . ' / span 1');
					$emptyCell->attributes->add(new Attribute('datetime', $dateToRender->atTime($timeSlots[$timeSlotIndex])->__toString()));

					$dayColumn->appendContent($emptyCell);
				}
			}
		}

		return $dayColumn;
	}

	private function buildEventPopover(Calendar\Source $source, Calendar\Event $event): Element
	{
		$popover = new Element('dialog');
		$popover->attributes->set(new Attribute('popover', ''));
		$popover->attributes->set(new Attribute\Id('source-' . $source->id . '-event-' . $event->id));
		$popover->attributes->findOrCreate(Attribute\Style::class, fn() => new Attribute\Style())
			->set('--calendar-source-colour', $source->colour);

		$name = new Element('h3');
		$name->attributes->set(new Attribute\Class_('event-name'));
		$name->attributes->set(new Attribute\Id($event->id));
		$name->setContent($event->name);

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

	public function render(?Renderer $renderer = null): string
	{
		foreach ($this->widgets as $widget) {
			$widget->build($this);
			parent::appendContent($widget);
		}

		parent::appendContent($this->buildView());

		return ($renderer ?? new Renderer())->render($this);
	}
}
