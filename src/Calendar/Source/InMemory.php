<?php
declare(strict_types=1);

namespace Meraki\Html\Calendar\Source;

use Brick\DateTime\LocalDate;
use Meraki\Html\Calendar\Source;
use Meraki\Html\Calendar\Event;
use Brick\DateTime\ZonedDateTime;
use Brick\DateTime\TimeZone;

final class InMemory implements Source
{
	private array $events = [];
	public bool $selected = true;

	public TimeZone $timeZone;

	public function __construct(public string $id, public string $name, public string $colour, array $events = [])
	{
		$this->timeZone = TimeZone::utc();
		array_map([$this, 'addEvent'], $events);
	}

	public function addEvent(Event $event): void
	{
		$this->events[] = $event;
	}

	public function getAllEventsBetween(ZonedDateTime $from, ZonedDateTime $to): array
	{
		return array_filter($this->events, fn(Event $event) => $event->occursBetween($from, $to));
	}

	public function getAllEventsFor(ZonedDateTime $date): array
	{
		return array_filter($this->events, fn(Event $event) => $event->occursOn($date->getDate()));
	}

	public function getAllEventsOn(LocalDate $date): array
	{
		return array_filter($this->events, fn(Event $event) => $event->occursOn($date));
	}

	public function getIterator(): \Traversable
	{
		return new \ArrayIterator($this->events);
	}

	public function count(): int
	{
		return count($this->events);
	}

	public function equals(Source $other): bool
	{
		return $other instanceof self && $this->id === $other->id;
	}

	public function select(): self
	{
		$this->selected = true;

		return $this;
	}

	public function deselect(): self
	{
		$this->selected = false;

		return $this;
	}
}
